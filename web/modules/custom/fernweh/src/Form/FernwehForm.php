<?php

/**
 * @file
 * Contains \Drupal\fernweh\Form\FernwehForm.
*/
namespace Drupal\fernweh\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FernwehForm extends ConfigFormBase {
    /**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'fernweh_form';
	}

    /**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('fernweh.settings');

        $form['user_pin'] = array(
            '#type' => 'details',
            '#title' => 'User PIN Information',
            '#description' => 'This is User PIN Information',
            '#open' => FALSE,
        );
        $form['user_pin']['user_pin_value'] = array(
            '#type' => 'details',
            '#title' => 'User PIN',
            '#default_value' => $config->get('fernweh.user_pin_value'),
            '#description' => 'Please enter User PIN value',
        );
        return $form;
    }

    /**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {}

    /**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('fernweh.settings');
        $config->set('fernweh.user_pin_value', $form_state->getValue('user_pin_value'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }

    /**
	* {@inheritdoc}
	*/
    protected function getEditableConfigNames() {
        return ['fernweh.settings'];
    }
}