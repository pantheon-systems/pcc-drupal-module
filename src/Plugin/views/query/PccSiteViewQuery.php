<?php

namespace Drupal\pcx_connect\Plugin\views\query;

use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use PccPhpSdk\api\Query\Enums\PublishingLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * An array mapping table aliases and field names to field aliases.
   *
   * @var array
   */
  protected $fieldAliases = [];

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
   * A simple array of order by clauses.
   */
  public $orderby = [];

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
   * Ensures a table exists in the query.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable(): string {
    return '';
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
    if ($table == $this->view->storage->get('base_table') && $field == $this->view->storage->get('base_field') && empty($alias)) {
      $alias = $this->view->storage->get('base_field');
    }

    if (!$alias && $table) {
      $alias = $table . '_' . $field;
    }

    // Make sure an alias is assigned.
    $alias = $alias ? $alias : $field;

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

    // Keep track of all aliases used.
    $this->fieldAliases[$table][$field] = $alias;
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
   * @param $table
   *   The table this field is part of. If a formula, enter NULL.
   *   If you want to orderby random use "rand" as table and nothing else.
   * @param $field
   *   The field or formula to sort on. If already a field, enter NULL
   *   and put in the alias.
   * @param $order
   *   Either ASC or DESC.
   * @param $alias
   *   The alias to add the field as. In SQL, all fields in the order by
   *   must also be in the SELECT portion. If an $alias isn't specified
   *   one will be generated for from the $field; however, if the
   *   $field is a formula, this alias will likely fail.
   * @param $params
   *   Any params that should be passed through to the addField.
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    // Only ensure the table if it's not the special random key.
    // @todo: Maybe it would make sense to just add an addOrderByRand or something similar.
    if ($table && $table != 'rand') {
      $this->ensureTable($table);
    }

    // Only fill out this aliasing if there is a table;
    // otherwise we assume it is a formula.
    if (!$alias && $table) {
      $as = $table . '_' . $field;
    }
    else {
      $as = $alias;
    }

    if ($field) {
      $as = $this->addField($table, $field, $as, $params);
    }

    $this->orderby[] = [
      'field' => $as,
      'direction' => strtoupper($order),
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
        $field_alias = $field['alias'];
        if ($index > 0) {
          $query_fields .= "\n  $field_alias";
        }
        else {
          $query_fields .= "$field_alias";
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

    $items_per_page = 20;
    $total_articles = 20;
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
