<?php

/**
 * @file
 * Contains \Drupal\pushnotification\Form\PushnotificationForm.
*/

namespace Drupal\pushnotification\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class PushnotificationForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'pushnotification_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$uid = \Drupal::currentUser()->id();
		$user = \Drupal\user\Entity\User::load($uid);

		if($user->roles->target_id == 'administrator') {
			$form['notification_token'] = array(
                '#type' => 'textarea',
                '#title' => 'Notification Token',
                '#required' => TRUE,
                '#default_value' => '',
            );
            $form['notification_title'] = array(
                '#type' => 'textfield',
                '#title' => 'Title',
                '#required' => TRUE,
                '#default_value' => '',
            );
            $form['notification_body'] = array(
                '#type' => 'textfield',
                '#title' => 'Body',
                '#required' => TRUE,
                '#default_value' => '',
            );
			$form['actions']['#type'] = 'actions';
			$form['actions']['submit'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Send Notification'),
				'#button_type' => 'primary',
			);
			return $form;
		}
		else {
			$form['form_info'] = array(
				'#markup' => '<strong>You are not authorized to view this page</strong>',
			);
			return $form;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($form_state->getValue('notification_token') == '') {
			$form_state->setErrorByName('notification_token', $this->t('Please provide notification token'));
		}
		if ($form_state->getValue('notification_title') == '') {
			$form_state->setErrorByName('notification_title', $this->t('Please provide a title'));
		}
		if ($form_state->getValue('notification_body') == '') {
			$form_state->setErrorByName('notification_body', $this->t('Please provide message body'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
        $notification_token = $form_state->getValue('notification_token');
        $msg_title = $form_state->getValue('notification_title');
        $msg_body = $form_state->getValue('notification_body');
		$send_notification = \Drupal::service('pushnotification_service')->send_push_notification($msg_title, $msg_body, $notification_token);
	}
}