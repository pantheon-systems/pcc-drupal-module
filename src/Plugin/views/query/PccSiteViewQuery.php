<?php

namespace Drupal\pcx_connect\Plugin\views\query;

use Drupal\pcx_connect\Entity\PccSite;
use Drupal\pcx_connect\Pcc\Service\PccArticlesApiInterface;
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
   */

  public $where = [];

  /**
   * PCC Content API service
   *
   * @var PccArticlesApiInterface
   */
  protected PccArticlesApiInterface $pccContentApi;

  /**
   * Constructs a PccSiteViewQuery object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The database-specific date handler.
   * @param PccArticlesApiInterface $pccContentApi
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
    return $field;
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
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
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
   */
  public function execute(ViewExecutable $view): void {
    $base_table = $view->storage->get('base_table');
    $pcc_site = PccSite::load($base_table);
    if ($pcc_site) {
      $articles = $this->getArticlesFromPccContentApi($pcc_site->get('site_token'), $pcc_site->get('site_key'));
      $index = 0;
      foreach ($articles as $article) {
        $view->result[] = $this->toRow($article, $index++);
      }
    }
  }

  /**
   * Get Articles from Pcc Content API Service.
   *
   * @param string $token
   *   The site token.
   * @param string $site_key
   *   The site key.
   *
   * @return array
   *   The API response.
   */
  protected function getArticlesFromPccContentApi(string $token, string $site_key): array {
    return $this->pccContentApi->getAllArticles($site_key, $token);
  }

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
