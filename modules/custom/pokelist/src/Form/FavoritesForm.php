<?php

namespace Drupal\pokelist\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pokelist\Services\Client\PokelistClient;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Implementing a ajax form.
 */
class FavoritesForm extends FormBase {

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
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'unmark-favorites-table';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Add the core AJAX library.
        $form['#attached']['library'][] = 'core/drupal.ajax';

        $form['#prefix'] = '<div class="result-message"></div><div id="favorites_ajax_form">';
        $form['#suffix'] = '</div>';

        /* $form['message'] = [
            '#type' => 'markup',
            '#markup' => '<div class="result-message"></div>',
        ]; */

        //drupal_set_message(print_r($options , 1));

        $form['favorites_table'] = array(
            '#type' => 'tableselect',
            '#header' => array (t('Id'), t('Name'), t('Image')),
            '#options' => $this->pokemonFavorites(),
            '#empty' => t('There are no Pokémon yet. <a href="@add-url">Add an Pokémon.</a>', array(
                '@add-url' => Url::fromRoute('pokelist.list'),
            )),
        );  

        $form['actions'] = [
            '#type' => 'submit',
            '#name' => 'submit',
            '#value' => $this->t('Submit'),
            '#ajax' => [
                'wrapper' => 'favorites_ajax_form',
                'callback' => '::unMarkAsFavoriteCallback',
                'effect' => 'fade',
                'event' => 'click',
                'progress' => [
                    'type' => 'throbber',
                ],                
            ],
        ];

        return $form;
    }

    /**
     * Submitting the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        $uid = \Drupal::currentUser()->id();
        $favorites_table = $form_state->getValue('favorites_table');
        $poke_id = array_filter($favorites_table);
        $num_deleted = 0;

        // Check if there is favorites pokémon to delete. 
        if ( $poke_id ) {
            $result = \Drupal::database()->select('pokd8_pokemon_favorites', 'p')
                                        ->fields('p', array('pfid', 'pokemon_name', 'pokemon_id','uid'))
                                        ->condition('p.pokemon_id', $poke_id, 'IN')
                                        ->condition('p.uid', $uid, '=')
                                        ->execute()->fetchAllAssoc('pfid');

            if( $result ) {
                $num_deleted = \Drupal::database()->delete('pokd8_pokemon_favorites')
                                                ->condition('pokemon_id', $poke_id, 'IN')
                                                ->condition('uid', $uid, '=')
                                                ->execute();

                $message = $num_deleted.' Pokémon deleted.';
            } else {
                $message = 'Pokémon not Found.';
            }

        } else {
            $message = 'Pokémon id wasn\'t sended.';
        }        

        if ($num_deleted) {
            $submit_message = $message." Favorites Pokémon deleted successfully";
        }
        else {
            $submit_message = $message." Favorites Pokémon were not deleted successfully";
        }

        \Drupal::messenger()->addMessage($submit_message, 'status', TRUE);

        $form_state->setRebuild(TRUE);        
    }

    /**
     * Returns a render-able array for a Pokémon favorites page.
     */
    public function pokemonFavorites() {
        global $base_root, $base_path;

        $num_favorites = \Drupal::config('pokelist.settings')->get('num_favorites');
        $uid = \Drupal::currentUser()->id();

        // you can write your own query to fetch the data I have given my example.
        $result = \Drupal::database()->select('pokd8_pokemon_favorites', 'p')
                                     ->fields('p', array('pfid', 'pokemon_name', 'pokemon_id','uid'))
                                     ->condition('p.uid', $uid, '=')
                                     ->range(0, $num_favorites)
                                     ->orderBy('p.created_at', 'DESC')
                                     ->execute()->fetchAllAssoc('pfid');

        $options = array();
        $no_available_image = $pokemon_image = $base_root . $base_path . drupal_get_path('module', 'pokelist') . '/images/' . 'no-image-available.jpg';

        foreach ($result as $key => $content) {
            $pokemon = $this->content(1, 0, "pokemon/".$content->pokemon_id);

            if( !empty($pokemon) ) {
                $image = $pokemon["sprites"]["front_default"];
                $pokemon_image = ( !empty($image) ) ? new FormattableMarkup('<img src="@pokemon_image" />',['@pokemon_image' => $image]) : $no_available_image; 
            } else {
                $pokemon_image = $no_available_image;
            }

            $options[$content->pokemon_id] = array(
                $content->pokemon_id,
                $content->pokemon_name, 
                $pokemon_image
            );
        }        

        return $options;
    }  

    /**
     * Returns an ajax response to unmark as favorite action.
     */
    public function unMarkAsFavoriteCallback(array $form, FormStateInterface $form_state) {

        // Instantiate an AjaxResponse Object to return.
        $ajax_response = new AjaxResponse();

        $messages = ['#type' => 'status_messages'];

        $ajax_response->addCommand(new HtmlCommand('.result-message', $messages));        

        // You also need to replace the form:
        $ajax_response->addCommand(new ReplaceCommand(NULL, $form));              
        
        // Return the AjaxResponse Object.
        return $ajax_response;
    }      
}