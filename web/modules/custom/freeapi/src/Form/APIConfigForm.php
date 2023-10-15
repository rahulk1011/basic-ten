<?php

/**
* @file
* Contains \Drupal\freeapi\Form\APIConfigForm.
*/

namespace Drupal\freeapi\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class APIConfigForm extends ConfigFormBase {
    /**
    * {@inheritdoc}.
    */
    public function getFormId() {
        return 'apiconfig_form';
    }
    
    /**
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('apiconfig.settings');

        // Push-Notification Configuration
        $form['apiconfig'] = array(
            '#type' => 'details',
            '#title' => t('Free API Configuration'),
            '#description' => t('Free API config information'),
            '#open' => FALSE,
        );
        $form['apiconfig']['api_host'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('API Host'),
            '#default_value' => $config->get('apiconfig.api_host'),
            '#description' => $this->t('Please enter API Host'),
        );
        $form['apiconfig']['api_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('API Key'),
            '#default_value' => $config->get('apiconfig.api_key'),
            '#description' => $this->t('Please enter API Key'),
        );

        // Weather Form
        $form['weather'] = array(
            '#type' => 'details',
            '#title' => t('Check Weather'),
            '#description' => t('Check Weather Link'),
            '#open' => FALSE,
        );
        $form['weather']['free_weather'] = array(
            '#type' => 'link',
            '#title' => $this->t('<strong>Check Weather</strong>'),
            '#url' => Url::fromRoute('freeapi.WeatherForm'),
            '#attributes' => ['target' => '_blank'],
        );
        return $form;
    }
    
    /**
    * {@inheritdoc}
    */
    public function validateForm(array &$form, FormStateInterface $form_state) {  
    }
    
    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('apiconfig.settings');
        $config->set('apiconfig.api_host', $form_state->getValue('api_host'));
        $config->set('apiconfig.api_key', $form_state->getValue('api_key'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }
    
    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames() {
        return [
            'apiconfig.settings',
        ];
    }
}