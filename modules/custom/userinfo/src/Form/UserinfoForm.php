<?php

/**
 * @file
 * Contains \Drupal\userinfo\Form\UserinfoForm.
*/

namespace Drupal\userinfo\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;

class UserinfoForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'userinfo_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$uid = \Drupal::currentUser()->id();
		$user = \Drupal\user\Entity\User::load($uid);
		
		if($user->roles->target_id == 'administrator'){
			$form['form_info'] = array(
				'#markup' => '<h3>Saving User Info in a custom table in the database</h3><br>',
			);
			$form['first_name'] = array(
				'#type' => 'textfield',
				'#title' => t('First Name'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['last_name'] = array(
				'#type' => 'textfield',
				'#title' => t('Last Name'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['user_email'] = array(
				'#type' => 'textfield',
				'#title' => t('Email-ID'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['user_dob'] = [
				'#type' => 'date',
				'#title' => t('Date of Birth'),
				'#required' => TRUE,
				'#default_value' => '',
			];
			$form['user_city'] = array(
				'#type' => 'textfield',
				'#title' => t('City'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['user_state'] = array(
				'#type' => 'textfield',
				'#title' => t('State'),
				'#required' => TRUE,
				'#maxlength' => 50,
				'#default_value' => '',
			);
			$form['user_zipcode'] = array(
				'#type' => 'textfield',
				'#title' => t('Zipcode'),
				'#required' => TRUE,
				'#maxlength' => 6,
				'#default_value' => '',
			);

			$form['actions']['#type'] = 'actions';
			$form['actions']['submit'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Save Info'),
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
		if ($form_state->getValue('first_name') == '') {
			$form_state->setErrorByName('first_name', $this->t('Please Enter First Name'));
		}
		if ($form_state->getValue('last_name') == '') {
			$form_state->setErrorByName('last_name', $this->t('Please Enter Last Name'));
		}
		if ($form_state->getValue('user_email') == '') {
			$form_state->setErrorByName('user_email', $this->t('Please Enter Email-ID'));
		}
		if ($form_state->getValue('user_dob') == '') {
			$form_state->setErrorByName('user_dob', $this->t('Please Date of Birth'));
		}
		if ($form_state->getValue('user_city') == '') {
			$form_state->setErrorByName('user_city', $this->t('Please Enter City'));
		}
		if ($form_state->getValue('user_state') == '') {
			$form_state->setErrorByName('user_state', $this->t('Please Enter State'));
		}
		if ($form_state->getValue('user_zipcode') == '') {
			$form_state->setErrorByName('user_zipcode', $this->t('Please Enter Zipcode'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		try{
			$conn = Database::getConnection();
			$field = $form_state->getValues();

			$fields["firstname"] = $field['first_name'];
			$fields["lastname"] = $field['last_name'];
			$fields["email"] = $field['user_email'];
			$fields["dob"] = $field['user_dob'];
			$fields["city"] = $field['user_city'];
			$fields["state"] = $field['user_state'];
			$fields["zipcode"] = $field['user_zipcode'];

			$first_name = $fields["firstname"];
			$email_id = $fields["email"];

			$conn->insert('a_user_info')
			->fields($fields)->execute();
			\Drupal::messenger()->addMessage('User information has been saved succesfully..');
			$send_mail = \Drupal::service('userinfo_service')->user_mail_notification($first_name, $email_id);
		}
		catch(Exception $ex){
			\Drupal::messenger()->addError($ex->getMessage());
		}
	}
}