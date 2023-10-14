<?php

/**
* @file
* Contains \Drupal\pushnotification\Form\NotificationConfigForm.
*/

namespace Drupal\pushnotification\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotificationConfigForm extends ConfigFormBase {
    /**
    * {@inheritdoc}.
    */
    public function getFormId() {
        return 'pushnotification_form';
    }
    
    /**
    * {@inheritdoc}.
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('pushnotification.settings');

        // Push-Notification Configuration
        $form['push_notification'] = array(
            '#type' => 'details',
            '#title' => t('Push Notification Configuration'),
            '#description' => t('Push Notification config information'),
            '#open' => FALSE,
        );
        $form['push_notification']['server_key'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('API Server Key'),
            '#default_value' => $config->get('pushnotification.server_key'),
            '#description' => $this->t('Please enter Firebase API Server Key'),
        );
        $form['push_notification']['sender_id'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Sender ID'),
            '#default_value' => $config->get('pushnotification.sender_id'),
            '#description' => $this->t('Please enter Sender ID'),
        );
        $form['push_notification']['fcm_url'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('FCM URL'),
            '#default_value' => $config->get('pushnotification.fcm_url'),
            '#description' => $this->t('Please enter FCM URL'),
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
        $config = $this->config('pushnotification.settings');
        $config->set('pushnotification.server_key', $form_state->getValue('server_key'));
        $config->set('pushnotification.sender_id', $form_state->getValue('sender_id'));
        $config->set('pushnotification.fcm_url', $form_state->getValue('fcm_url'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }
    
    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames() {
        return [
            'pushnotification.settings',
        ];
    }
}