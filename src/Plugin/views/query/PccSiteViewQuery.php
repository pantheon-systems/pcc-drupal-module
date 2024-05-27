<?php

namespace Drupal\pcx_connect\Plugin\views\query;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Views query class for Config Entities.
 *
 * @ViewsQuery(
 *   id = "pcc_site_view_query",
 *   title = @Translation("Configuration Entity"),
 *   help = @Translation("Configuration Entity Query")
 * )
 */
class PccSiteViewQuery extends QueryPluginBase {
  /**
   * An array of sections of the WHERE query.
   *
   * Each section is in itself an array of pieces and a flag as to whether or
   * not it should be AND or OR.
   *
   * @var array
   */
  public $where = [];

  /**
   * Number of results to display.
   *
   * @var int
   */
  protected $limit;

  /**
   * The index this view accesses.
   *
   * @var \Drupal\pcx_connect\Entity\PccSite
   */
  protected $index;

  /**
   * The graphql query that will be executed.
   *
   * @var string
   */
  protected string $query;

  /**
   * Array of all encountered errors.
   *
   * Each of these is fatal, meaning that a non-empty $errors property will
   * result in an empty result being returned.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Whether to abort the search instead of executing it.
   *
   * @var bool
   */
  protected $abort = FALSE;

  /**
   * The IDs of fields whose values should be retrieved by the backend.
   *
   * @var string[]
   */
  protected $retrievedFieldValues = [];

  /**
   * An array of fields.
   *
   * @var array
   */
  public $fields = [];

  /**
   * PCC Content API service.
   *
   * @var \Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface
   */
  protected PccArticlesApiInterface $pccContentApi;

  /**
   * Contextual filters.
   *
   * @var array
   */
  protected $contextualFilters = [];

  /**
   * The site token.
   *
   * @var string
   */
  protected $siteToken = '';

  /**
   * The site key.
   *
   * @var string
   */
  protected $siteKey = '';

  /**
   * Constructs a PccSiteViewQuery object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The database-specific date handler.
   * @param \Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface $pccContentApi
   *   The PCC Content API Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PccArticlesApiInterface $pccContentApi) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pccContentApi = $pccContentApi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pcx_connect.pcc_articles_api'),
    );
  }

  /**
   * Ensures a table exists in the query.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable(): string {
    return '';
  }

  /**
   * Let modules modify the query just prior to finalizing it.
   */
  public function alter(ViewExecutable $view) {
    \Drupal::moduleHandler()->invokeAll('views_query_alter', [$view, $this]);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    try {
      parent::init($view, $display, $options);
      $base_table = $view->storage->get('base_table');
      $this->index = PccSite::load($base_table);
      if (!$this->index) {
        $this->abort(new FormattableMarkup('View %view is not based on PCC Site API but tries to use its query plugin.', ['%view' => $view->storage->label()]));
      }
      $this->query = $this->query();
    }
    catch (\Exception $e) {
      $this->abort($e->getMessage());
    }
  }

  /**
   * Aborts this PCC Site query.
   *
   * Used by handlers to flag a fatal error which shouldn't be displayed but
   * still lead to the view returning empty and the search not being executed.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $msg
   *   Optionally, a translated, unescaped error message to display.
   */
  public function abort($msg = NULL) {
    if ($msg) {
      $this->errors[] = $msg;
    }
    $this->abort = TRUE;
    if (isset($this->query)) {
      $this->abort($msg);
    }
  }

  /**
   * Adds a field to the table.
   *
   * @param string|null $table
   *   Ignored.
   * @param string $field
   *   The combined property path of the property that should be retrieved.
   * @param string $alias
   *   (optional) Ignored.
   * @param array $params
   *   (optional) Ignored.
   *
   * @return string
   *   The name that this field can be referred to as (always $field).
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   */
  public function addField($table, $field, $alias = '', $params = []): string {
    $this->fields[$field] = $field;
    return $field;
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();
    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * Adds a simple WHERE clause to the query.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
      $filter_field = str_replace('.', '', $field);
      $this->contextualFilters[$filter_field] = $value;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Generates a GRAPHQL query.
   */
  public function query($get_count = FALSE) {
    $query_condition = "( \n  contentType: TREE_PANTHEON_V2";
    if ($this->where) {
      foreach ($this->where as $group) {
        foreach ($group['conditions'] as $condition) {
          $field = str_replace('.', '', $condition['field']);
          $value = $condition['value'];
          $query_condition .= "\n  $field: $value\n";
        }
      }
    }
    $query_condition .= ")";
    $query_fields = '';
    if ($this->fields) {
      $index = 0;
      foreach ($this->fields as $field) {
        if ($index > 0) {
          $query_fields .= "\n  $field";
        }
        else {
          $query_fields .= "$field";
        }
        $index++;
      }
    }
    $entity = 'articles';
    if (!empty($this->contextualFilters)) {
      $entity = 'article';
    }
    $query = <<<GRAPHQL
    $entity $query_condition {
      $query_fields
    }
    GRAPHQL;
    return $query;
  }

  /**
   * Executes the query and fills associated view object with according values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->current_page.
   */
  public function execute(ViewExecutable $view): void {
    $base_table = $view->storage->get('base_table');
    $pcc_site = PccSite::load($base_table);
    $api_response = [];
    if ($pcc_site) {
      try {
        $this->siteKey = $pcc_site->get('site_key');
        $this->siteToken = $pcc_site->get('site_token');
        if (isset($this->contextualFilters['slug'])) {
          $content_slug = $this->contextualFilters['slug'];
          $api_response = $this->getArticleBySlugOrId($content_slug);
        }
        elseif (isset($this->contextualFilters['id'])) {
          $content_id = $this->contextualFilters['id'];
          $api_response = $this->getArticleBySlugOrId($content_id, 'id');
        }
        else {
          $api_response = $this->getArticlesFromPccContentApi();
        }

        if (!empty($api_response['data'])) {
          // Render multiple articles.
          if (isset($api_response['data']['articles'])) {
            $api_data = $api_response['data']['articles'];
            $this->renderMultipleArticles($view, $api_data);
          }

          // Render a single article.
          if (isset($api_response['data']['article'])) {
            $api_data = $api_response['data']['article'];
            $this->renderSingleArticle($view, $api_data);
          }

          if (isset($api_response['errors'])) {
            \Drupal::logger('pcx_connect')->error('Failed to load views output: <pre>' . print_r($api_response['errors'], TRUE) . '</pre>');
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('pcx_connect')->error('Failed to load views output: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
        $this->execute($view);
      }
    }
  }

  /**
   * Render multiple articles.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable view.
   * @param array $api_data
   *   The API data.
   */
  protected function renderMultipleArticles(ViewExecutable &$view, array $api_data): void {
    $index = 0;
    foreach ($api_data as $data) {
      // Check if views fields exists.
      $views_fields = array_intersect_key($data, $this->fields);
      if ($views_fields) {
        foreach ($views_fields as $field_name => $data) {
          $row[$field_name] = $data;
          if (in_array($field_name, ['publishedDate', 'updatedAt'])) {
            // Convert milliseconds to seconds.
            $row[$field_name] = (int) ((int) $data / 1000);
          }
        }
      }
      $row['index'] = $index++;
      $view->result[] = new ResultRow($row);
    }
  }

  /**
   * Render a single article.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable view.
   * @param array $api_data
   *   The API data.
   */
  protected function renderSingleArticle(ViewExecutable &$view, array $api_data): void {
    $views_fields = array_intersect_key($api_data, $this->fields);
    if ($views_fields) {
      $row = [];
      foreach ($views_fields as $field_name => $data) {
        if (in_array($field_name, array_keys($this->fields))) {
          $row[$field_name] = $data;
          if (in_array($field_name, ['publishedDate', 'updatedAt'])) {
            // Convert milliseconds to seconds.
            $row[$field_name] = (int) ((int) $data / 1000);
          }
        }
      }
      $row['index'] = 0;
      $view->result[] = new ResultRow($row);
    }
  }

  /**
   * Get Articles from Pcc Content API Service.
   *
   * @return array
   *   The API response.
   */
  protected function getArticlesFromPccContentApi(): array {
    $site_key = $this->siteKey;
    $token = $this->siteToken;
    return $this->pccContentApi->getAllArticles($site_key, $token);
  }

  /**
   * Get Article from Pcc Content API Service.
   *
   * @param string $slug_or_id
   *   The site id or slug.
   * @param string $type
   *   The filter type.
   *
   * @return array
   *   The API response.
   */
  protected function getArticleBySlugOrId(string $slug_or_id, string $type = 'slug') {
    $site_key = $this->siteKey;
    $token = $this->siteToken;
    return $this->pccContentApi->getArticle($slug_or_id, $site_key, $token, $type);
  }

  /**
   * Convert data into rows.
   *
   * @param array $article
   *   The articles array.
   * @param int $index
   *   The row index.
   *
   * @return \Drupal\views\ResultRow
   *   The row result.
   */
  protected function toRow(array $article, int $index): ResultRow {
    $row = [];
    $row['id'] = $article['id'];
    $row['title'] = $article['title'];
    $row['content'] = $article['content'];
    $row['snippet'] = $article['snippet'];
    $row['publishedDate'] = intdiv($article['publishedDate'], 1000);
    $row['updatedAt'] = intdiv($article['updatedAt'], 1000);
    $row['index'] = $index;
    return new ResultRow($row);
  }

}
