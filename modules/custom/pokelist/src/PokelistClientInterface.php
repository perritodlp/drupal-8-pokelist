<?php
namespace Drupal\pokelist;

/**
 * Utilizes Drupal's httpClient to connect to PokéApi
 * Info: https://pokeapi.co/
 * API Docs: https://pokeapi.co/docs/
 */ 
interface PokelistClientInterface {
    /** 
     *   get, post, patch, delete, etc. See Guzzle documentation.
     * @param string $method
     *   The PokeAPI endpoint (ex. pokemon/ditto/)
     * @param string $endpoint
     *   Query string parameters the endpoint allows (ex. ['per_page' => 50]
     * @param array $query
     *   Utilized for some endpoints
     * @param array $body (converted to JSON)
     *   \GuzzleHttp\Psr7\Response body
     *
     * @return object
     */ 
    public function connect($method, $endpoint, $query, $body);
}