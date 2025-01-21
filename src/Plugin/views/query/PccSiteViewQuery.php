<?php

namespace Drupal\pcx_connect\Plugin\views\query;

use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\Type;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a Views query class for PCC sites.
 *
 * @ViewsQuery(
 *   id = "pcc_site_view_query",
 *   title = @Translation("PCC site"),
 *   help = @Translation("PCC site query")
 * )
 */
class PccSiteViewQuery extends QueryPluginBase {

  const DEFAULTITEMSPERPAGE = 20;

  /**
   * An array of sections of the WHERE query.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::where()
   */
  protected array $where = [];

  /**
   * An array of fields.
   */
  public array $fields = [];

  protected PccArticlesApiInterface $pccContentApi;

  protected array $contextualFilters = [];

  /**
   * The site token.
   *
   * @var string
   */
  protected string $siteToken = '';

  /**
   * The site key.
   *
   * @var string
   */
  protected $siteKey = '';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PccArticlesApiInterface $pccContentApi, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pccContentApi = $pccContentApi;
    $this->requestStack = $request_stack;
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
      $container->get('request_stack')
    );
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
    parent::init($view, $display, $options);
    $base_table = $view->storage->get('base_table');
    if (!PccSite::load($base_table)) {
      throw new \LogicException($this->t('View %view is not based on PCC Site API but tries to use its query plugin.', ['%view' => $view->storage->label()]));
    }
  }

  /**
   * Adds a field to the table.
   *
   * @param string|null $table
   *  If this the PCC site ID and $field is the string id then $alias is
   *  overridden to be the string id. Otherwise, this is currently unused.
   * @param string $field
   *   The combined property path of the property that should be retrieved.
   * @param string $alias
   *   The field alias. (Optional.)
   * @param array $params
   *   Additional parameters. (Currently unused.)
   *
   * @return string
   *   The alias this field can be referred to as.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   */
  public function addField($table, $field, $alias = '', $params = []): string {
    if ($table == $this->view->storage->get('base_table') && $field == $this->view->storage->get('base_field') && empty($alias)) {
      $alias = $this->view->storage->get('base_field');
    }

    if (!$alias && $table) {
      $alias = $table . '_' . $field;
    }

    // Make sure an alias is assigned.
    $alias = $alias ?: $field;

    // PostgreSQL truncates aliases to 63 characters:
    // https://www.drupal.org/node/571548.
    // We limit the length of the original alias up to 60 characters
    // to get a unique alias later if its have duplicates.
    $alias = substr($alias, 0, 60);

    // Create a field info array.
    $field_info = [
      'field' => $field,
      'table' => $table,
      'alias' => $alias,
    ] + $params;

    // Test to see if the field is actually the same or not. Due to
    // differing parameters changing the aggregation function, we need
    // to do some automatic alias collision detection:
    $base = $alias;
    $counter = 0;

    while (!empty($this->fields[$alias]) && $this->fields[$alias] != $field_info) {
      $field_info['alias'] = $alias = $base . '_' . ++$counter;
    }

    if (empty($this->fields[$alias])) {
      $this->fields[$alias] = $field_info;
    }

    // Hardcoded field.
    $this->fields['previewActiveUntil'] = [
      'field' => 'previewActiveUntil',
      'table' => '',
      'alias' => 'previewActiveUntil',
    ];

    return $alias;
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
   *
   * The caller is responsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table already exists in the query.
   *
   * The $field, $value and $operator arguments can also be passed in with a
   * single DatabaseCondition object, like this:
   * @code
   * $this->query->addWhere(
   *   $this->options['group'],
   *   ($this->query->getConnection()->condition('OR'))
   *     ->condition($field, $value, 'NOT IN')
   *     ->condition($field, $value, 'IS NULL')
   * );
   * @endcode
   *
   * @param int $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param string $field
   *   The name of the field to check.
   * @param mixed $value
   *   The value to test the field against. In most cases, this is a scalar.
   *   For more complex options, it is an array.
   *   The meaning of each element in the array is
   *   dependent on the $operator.
   * @param mixed $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL): void {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    $filter_field = str_replace('.', '', $field);
    if (empty($group)) {
      $group = 0;
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
   * Add an ORDER BY clause to the query.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addOrderBy()
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    // @TODO Implement this?
  }

  /**
   * {@inheritdoc}
   *
   * Generates a GRAPHQL query.
   */
  public function query($get_count = FALSE) {
    $type = new Type($this->contextualFilters ? 'article' : 'articles');
    $type->addArgument(new Argument('contentType', 'TREE_PANTHEON_V2'));

    if ($this->where) {
      foreach ($this->where as $group) {
        foreach ($group['conditions'] as $condition) {
          $field = str_replace('.', '', $condition['field']);
          $value = $condition['value'];
          $type->addArgument(new Argument($field, $value));
        }
      }
    }
    $type->addSubTypes(array_column($this->fields, 'alias'));
    return (string) $type;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view): void {
    $base_table = $view->storage->get('base_table');

    $pcc_site = PccSite::load($base_table);
    if ($pcc_site) {
      try {
        $this->siteKey = $pcc_site->get('site_key');
        $this->siteToken = $pcc_site->get('site_token');
        if (isset($this->contextualFilters['slug'])) {
          $content_slug = $this->contextualFilters['slug'];
          $this->getArticleBySlugOrIdFromPccContentApi($view, $content_slug, 'slug');
        }
        elseif (isset($this->contextualFilters['id'])) {
          $content_id = $this->contextualFilters['id'];
          $this->getArticleBySlugOrIdFromPccContentApi($view, $content_id, 'id');
        }
        else {
          $this->getArticlesFromPccContentApi($view);
        }

        array_walk($view->result, function (ResultRow $row, $index) {
          $row->index = $index;
        });
      }
      catch (\Exception $e) {
        $view->result = [];
        \Drupal::logger('pcx_connect')->error('Failed to load views output: <pre>' . print_r($e->getMessage(), TRUE) . '</pre>');
        $this->execute($view);
      }
    }
  }

  /**
   * Get Articles from Pcc Content API Service.
   */
  protected function getArticlesFromPccContentApi(ViewExecutable &$view): void {
    $request = $this->requestStack->getCurrentRequest();
    // Convert to milliseconds.
    $default_cursor = (time() * 1000);

    $items_per_page = self::DEFAULTITEMSPERPAGE;
    $current_page = 0;
    if ($view->pager->getCurrentPage()) {
      $current_page = $view->pager->getCurrentPage();
    }

    if (!empty($view->pager->options['items_per_page']) && $view->pager->options['items_per_page'] > 0) {
      $items_per_page = $view->pager->options['items_per_page'];
    }

    $views_filters = [];
    if ($view->filter) {
      foreach ($view->filter as $key => $filter) {
        $views_filters[$key] = $filter->value;
      }
    }

    if ($request->query->get('cursor')) {
      $default_cursor = $request->query->get('cursor');
    }

    $pager = [
      'current_page' => $current_page,
      'items_per_page' => $items_per_page,
      'filters' => $views_filters,
      'cursor' => $default_cursor,
    ];

    $field_keys = array_keys($this->fields);

    $articles = $this->pccContentApi->getAllArticles($this->siteKey, $this->siteToken, $field_keys, $pager);

    $index = 0;
    if ($articles) {
      foreach ($articles['articles'] as $article) {
        // Render articles based on pager.
        $view->result[] = $this->toRow($article, $index++);
      }

      $view->pager->options['cursor'] = $articles['cursor'];

      if (count($view->result)) {
        // Setup the result row objects.
        $total_articles = $articles['total'];
        $view->pager->total_items = $total_articles;
        array_walk($view->result, function (ResultRow $row, $index) {
          $row->index = $index;
        });

        $view->pager->updatePageInfo();
        $view->total_rows = $total_articles;
      }
    }
  }

  /**
   * Get Article from Pcc Content API Service.
   */
  protected function getArticleBySlugOrIdFromPccContentApi(ViewExecutable &$view, string $slug_or_id, string $type): void {
    $field_keys = array_keys($this->fields);
    $index = 0;
    $publishingLevel = $this->getPublishingLevel();
    if ($type == 'slug') {
      $article_data = $this->pccContentApi->getArticle(
        $slug_or_id,
        $this->siteKey,
        $this->siteToken,
        'slug',
        $field_keys,
        $publishingLevel
      );
    }
    else {
      $article_data = $this->pccContentApi->getArticle(
        $slug_or_id,
        $this->siteKey,
        $this->siteToken,
        'id',
        $field_keys,
        $publishingLevel
      );
    }
    $article = (array) $article_data;
    $view->result[] = $this->toRow($article, $index++);
  }

  /**
   * Get view row data.
   *
   * @param array $article
   *   The array articles.
   * @param int $index
   *   The row index.
   *
   * @return \Drupal\views\ResultRow
   *   The views row.
   */
  protected function toRow(array $article, int $index): ResultRow {
    $row = [];
    foreach ($article as $field => $value) {
      if ($value) {
        $row[$field] = $value;
        if ($field === 'publishedDate') {
          $row[$field] = intdiv($value, 1000);
        }
        if ($field === 'updatedAt') {
          $row[$field] = intdiv($value, 1000);
        }
      }
    }
    $row['index'] = $index;
    return new ResultRow($row);
  }

  /**
   * Get the publishing level.
   *
   * @return \PccPhpSdk\api\Query\Enums\PublishingLevel
   *   The publishing level.
   */
  protected function getPublishingLevel(): PublishingLevel {
    $publishingLevel = PublishingLevel::PRODUCTION;
    if (isset($this->contextualFilters['publishingLevel'])) {
      $publishingLevel = match ($this->contextualFilters['publishingLevel']) {
        'realtime', 'REALTIME' => PublishingLevel::REALTIME,
        default => PublishingLevel::PRODUCTION,
      };
    }
    return $publishingLevel;
  }

}
