<?php
namespace Drupal\pokelist\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides structure for implementing Pokelist module configuration form.
 */
class ConfigForm extends ConfigFormBase {

    /**
     * Return form Id.
     *
     * @return string
     *   Return string.
     */
    public function getFormId() {
        return 'pokelist_settings_form';
    }

    /**
     * Pokelist module configuration form structure.
     *
     * @return array
     *   Return array. Form structure
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->getConfig();

        $form['base_uri'] = [
            '#type' => 'textfield',
            '#title' => 'PokeApi Base URL',
            '#default_value' => $config->get('base_uri'),
            '#description' => 'Include trailing slash.',
        ];

        $form['num_per_page'] = [
            '#type' => 'textfield',
            '#title' => 'Items per page',
            '#default_value' => $config->get('num_per_page'),
            '#description' => 'Include here the number of pokemons per page.',
        ];

        $form['num_favorites'] = [
            '#type' => 'textfield',
            '#title' => 'Max number of favorite pokémons',
            '#default_value' => $config->get('num_favorites'),
            '#description' => 'Include here the max number of favorite pokémons.',
        ];            

        return parent::buildForm($form, $form_state);
    }
}