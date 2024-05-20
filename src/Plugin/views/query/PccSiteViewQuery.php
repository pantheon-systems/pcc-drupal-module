<?php

namespace Drupal\pcx_connect\Plugin\views\query;

use Drupal\pcx_connect\Entity\PccSite;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use GuzzleHttp\ClientInterface;
use Http\Client\Exception\RequestException;
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
  const GRAPHQL_ENDPOINT = 'https://gql.prod.pcc.pantheon.io/sites';

  /**
   * Constructs a PccSiteViewQuery object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The database-specific date handler.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, public ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
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
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view): void {
    $base_table = $view->storage->get('base_table');
    $pcc_site = PccSite::load($base_table);
    if ($pcc_site) {
      try {
        $api_response = $this->performApiRequest($pcc_site->get('site_token'), $pcc_site->get('site_key'));
        if (!empty($api_response['data'])) {
          $index = 0;
          foreach ($api_response['data']['articles'] as $data) {
            $row['id'] = $pcc_site->id();
            $row['title'] = $data['title'];
            $row['content'] = $data['content'];
            $row['snippet'] = $data['snippet'];
            $row['publishedAt'] = ((int) $data['publishedDate'] / 1000);
            $row['updatedAt'] = ((int) $data['updatedAt'] / 1000);
            $row['index'] = $index++;
            $view->result[] = new ResultRow($row);
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
   * Perform the graphql query.
   *
   * @param string $token
   *   The site token.
   * @param string $site_key
   *   The site key.
   *
   * @return array
   *   The API response.
   */
  protected function performApiRequest(string $token, string $site_key): array {
    $data = [];
    $graphql_endpoint = self::GRAPHQL_ENDPOINT;
    try {
      // Execute post request to salesforce".
      $url = "$graphql_endpoint/$site_key/query";
      $query = <<<'GRAPHQL'
      {
          articles (pageSize: 10, contentType: TREE_PANTHEON_V2) {
              title
              content
              snippet
              publishedDate
              updatedAt
          }
      }
      GRAPHQL;

      $headers = [
        'PCC-TOKEN' => $token,
        'Content-Type' => 'application/json',
      ];

      $response = $this->httpClient->post($url, [
        'headers' => $headers,
        'json' => [
          'query' => $query,
        ],
      ]);
      $body = $response->getBody();
      $data = json_decode($body, TRUE);
    }
    catch (RequestException $e) {
      \Drupal::logger('pcx_connect')->error('Failed to fetch data from GraphQL endpoint: <pre>' . print_r($e, TRUE) . '</pre>');
    }
    return $data;
  }

}
