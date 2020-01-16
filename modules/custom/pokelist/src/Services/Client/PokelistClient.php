<?php
namespace Drupal\pokelist\Services\Client;

use Drupal\Core\Config\ConfigFactory;
use Drupal\pokelist\PokelistClientInterface;
use \GuzzleHttp\ClientInterface;
use \GuzzleHttp\Exception\RequestException;

class PokelistClient implements PokelistClientInterface {
    /**
     * An http client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * A configuration instance.
     *
     * @var \Drupal\Core\Config\ConfigInterface;
     */
    protected $config;

    /**
     * Planning Center Base URI.
     *
     * @var string
     */
    protected $base_uri;

    /**
     * Constructor.
     */
    public function __construct(ClientInterface $http_client, ConfigFactory $config_factory) {
        $this->httpClient = $http_client;
        $config = $config_factory->get('pokelist.settings');
        $this->base_uri = $config->get('base_uri');
    }

    /**
     * { @inheritdoc }
     */
    public function connect($method, $endpoint, $query, $body) {
        try {
            $response = $this->httpClient->{$method}(
                $this->base_uri . $endpoint,
                $this->buildOptions($query, $body)
            );
        }
        catch (RequestException $exception) {
            \Drupal::messenger()->addError('Failed to complete request: '. $exception->getMessage(), 'error');

            \Drupal::logger('pokelist')->error('Failed to complete request "%error"', ['%error' => $exception->getMessage()]);

            return FALSE;
        }

        return $response->getBody()->getContents();
    }

    /**
     * Build options for the client.
     */
    private function buildOptions($query, $body) {
        $options = [];

        if ($body) {
            $options['body'] = $body;
        }
        if ($query) {
            $options['query'] = $query;
        }

        return $options;
    }
}