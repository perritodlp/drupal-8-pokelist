<?php
/**
 * @file
 * @author Fernando Rengifo
 * Contains \Drupal\pokelist\Controller\PokelistController.
 */
namespace Drupal\pokelist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pokelist\Services\Client\PokelistClient;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
//use Drupal\Core\Ajax\AlertCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Provides route responses for the Pokemon's module.
 */
class PokelistController extends ControllerBase {

    /**
     * Drupal\pokelist\Services\Client\PokelistClient definition.
     *
     * @var \Drupal\pokelist\Services\Client\PokelistClient
     */
    protected $pokeApiClient;

    /**
     * {@inheritdoc}
     */
    public function __construct(PokelistClient $pokelist_api_client) {
        $this->pokelistApiClient = $pokelist_api_client;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('pokelist.client')
        );
    }

    /**
     * Gets the Pokémon list content from the Poke Api.
     *
     * @return json
     *   Return json.
     */
    public function content($limit, $offset, $endpoint) {
        // This would get $limit pokemons from PokeApi on page load.
        $query = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        $request = $this->pokelistApiClient->connect('get', $endpoint, $query, []);
        $results = json_decode($request, true);

        return $results;
    } 

    /**
     * Returns a render-able array for a Pokémon list page.
     */
    public function pokemonList() {

        $page = pager_find_page();
        $num_per_page = \Drupal::config('pokelist.settings')->get('num_per_page');
        $offset = $num_per_page * $page;        

        $getResults = $this->content($num_per_page, $offset, "pokemon/");
        $totalRows = $getResults["count"];
        $results = $getResults["results"];
        $info_pokemon = array();

        if( !empty($results) ) {
            foreach ($results as $key => $value) {
                $url = $value["url"];
                $url_array = explode("/", $url); 
                $last = end($url_array);
                $id = prev($url_array);

                $pokemon = $this->content(1, 0, "pokemon/".$id);

                if( !empty($pokemon) ) {
                    $info_pokemon[$key]["name"]  = $pokemon["name"];
                    $info_pokemon[$key]["image"] = $pokemon["sprites"]["front_default"];
                    $info_pokemon[$key]["id"] = $pokemon["id"];
                }
            }

            pager_default_initialize($totalRows, $num_per_page);
        }

        // Create a render array with the Pokemon list.
        $render = [];

        $render[] = [
            '#theme' => 'pokelist_theme_hook',
            '#info_pokemon' => $info_pokemon,
            '#totalRows' => $totalRows,
            '#type' => 'remote',
            '#pager' => [
                '#type' => 'pager',
            ]            
        ];

        return $render;          
    }  

    /**
     * Code to execute when we need to mark a Pokémon as favorite
     */
    public function markAsFavoriteCallback() {
        $poke_id = \Drupal::request()->request->get("poke_id");
        $pokemon_name = \Drupal::request()->request->get("poke_name");
        $num_max_favorites = \Drupal::config('pokelist.settings')->get('num_favorites');
        $uid = \Drupal::currentUser()->id();
        $response_message = '';
        $error = false;

        // Si viene el identificador del pokémon
        if ( $poke_id ) {

            $result = \Drupal::database()->select('pokd8_pokemon_favorites', 'p')
                                        ->fields('p', array('pfid', 'pokemon_name', 'pokemon_id','uid'))
                                        ->condition('p.pokemon_id', $poke_id, '=')
                                        ->condition('p.uid', $uid, '=')
                                        ->execute()->fetchAllAssoc('pfid');

            $num_pokemon = count((array)$result);

            // Si no existe el pokémon en la lista de favoritos
            if( !$num_pokemon ) {

                $result_favorites = \Drupal::database()->select('pokd8_pokemon_favorites', 'p')
                                                        ->fields('p', array('pfid'))
                                                        ->condition('p.uid', $uid, '=')
                                                        ->execute()->fetchAllAssoc('pfid');

                $num_favorites = count((array)$result_favorites);

                // Si el número de favoritos es menor al máximo de favoritos
                if( $num_favorites < $num_max_favorites ) {

                    $result = \Drupal::database()->insert('pokd8_pokemon_favorites')
                                                            ->fields([
                                                                'pokemon_id' => $poke_id,
                                                                'uid' => $uid,
                                                                'pokemon_name' => $pokemon_name,
                                                                'created_at' => date("Y-m-d H:i:s", time()),
                                                            ])
                                                            ->execute();

                    $response_message = 'Pokémon '.$pokemon_name.' has been inserted';                                                            

                } else {
                    $response_message = 'Pokémon '.$pokemon_name.' hasn\'t been inserted because the max number of favorites has been reached';
                    $error = true;
                }

            } else {
                $response_message = 'Pokémon '.$pokemon_name.' hasn\'t been inserted because it already exists in the favorites list.';
                $error = true;
            }

        } else {
            $response_message = 'Pokémon id hasn\'t been sended';
            $error = true;
        }

        $data[] = [
            'response' => $response_message,
            'error' => $error
        ];

        return new JsonResponse($data, 200, ['Content-Type'=> 'application/json']);
    }
}
?>