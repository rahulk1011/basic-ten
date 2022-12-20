<?php

/**
 * @file
 * Contains \Drupal\sendmail\Form\SendmailForm.
*/

namespace Drupal\sendmail\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;

class SendmailForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'sendmail_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$uid = \Drupal::currentUser()->id();
		$user = \Drupal\user\Entity\User::load($uid);
		
		if($user->roles->target_id == 'administrator'){
			$form['form_info'] = array(
				'#markup' => '<h4><strong>Send Sample Mail to Anyone</strong></h4><hr>',
			);
			$form['email_id'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Email-ID'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['email_subject'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Subject'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['email_body'] = array(
				'#type' => 'textarea',
				'#title' => $this->t('Mail Body'),
				'#required' => TRUE,
				'#default_value' => '',
			);
			$form['actions']['#type'] = 'actions';
			$form['actions']['submit'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Send Mail'),
				'#button_type' => 'primary',
			);
			return $form;
		}
		else{
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
		if ($form_state->getValue('email_id') == '') {
			$form_state->setErrorByName('email_id', $this->t('Please Enter Email-ID'));
		}
		if ($form_state->getValue('email_subject') == '') {
			$form_state->setErrorByName('email_subject', $this->t('Please Enter Subject'));
		}
		if ($form_state->getValue('email_body') == '') {
			$form_state->setErrorByName('email_body', $this->t('Please Enter Mail Body'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		try{
			$field = $form_state->getValues();
            $mail = array();
			$mail["email"] = $field['email_id'];
			$mail["subject"] = $field['email_subject'];
			$mail["body"] = $field['email_body'];

            $send_mail = \Drupal::service('sendmail_service')->send_sample_mail($mail);
		}
		catch(Exception $ex){
			\Drupal::logger('sendmail')->error($ex->getMessage());
		}
	}
}