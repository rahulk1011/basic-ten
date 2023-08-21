<?php

/**
 * @file
 * Contains \Drupal\fernweh\Form\AddUserForm.
*/
namespace Drupal\fernweh\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\user\Entity\User;

class AddUserForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'adduser_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['user_role'] = array(
			'#type' => 'select',
			'#title' => 'User Role',
			'#required' => TRUE,
			'#options' => array(
				'' => '-- Select --',
				'manager' => 'Manager',
				'engineer' => 'Engineer',
			),
			'#default_value' => '',
		);
		$form['email_id'] = array(
			'#type' => 'email',
			'#title' => t('Email-ID'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
		);
		$form['password'] = array(
			'#type' => 'password',
			'#title' => t('Password'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
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
		$form['phone_number'] = array(
			'#type' => 'textfield',
			'#title' => t('Phone Number'),
			'#required' => TRUE,
			'#maxlength' => 15,
			'#default_value' => '',
		);
		$form['address'] = array(
			'#type' => 'textfield',
			'#title' => t('Address'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
		);
		$form['city'] = array(
			'#type' => 'textfield',
			'#title' => t('City'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
		);
		$form['zipcode'] = array(
			'#type' => 'textfield',
			'#title' => t('Zipcode'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
		);
		$form['country'] = array(
			'#type' => 'textfield',
			'#title' => t('Country'),
			'#required' => TRUE,
			'#maxlength' => 50,
			'#default_value' => '',
		);
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Create User'),
			'#button_type' => 'primary',
		);
		return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		// Check for duplicate Email
		$user_email = $form_state->getValue('email_id');
		$email_query = \Drupal::entityQuery('user');
		$email_query->accessCheck(TRUE);
		$email_check = $email_query->condition('mail', $user_email)->execute();
		if (!empty($email_check)) {
			$form_state->setErrorByName('email_id', $this->t('Email-ID already exists. Please try with different Email-ID.'));
		}
		// Check for duplicate Username
		$user_name = strtolower($form_state->getValue('first_name').'_'.$form_state->getValue('last_name'));
		$username_query = \Drupal::entityQuery('user');
		$username_query->accessCheck(TRUE);
		$username_check = $username_query->condition('name', $user_name)->execute();
		if (!empty($username_check)) {
			$form_state->setErrorByName('first_name', $this->t('User already exists. Please try with different Name.'));
			$form_state->setErrorByName('last_name', $this->t('User already exists. Please try with different Name.'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		try {
			$user_name = strtolower($form_state->getValue('first_name').'_'.$form_state->getValue('last_name'));
			$user_full_name = ucfirst($form_state->getValue('first_name')).' '.ucfirst($form_state->getValue('last_name'));
			$user_email = $form_state->getValue('email_id');
			$user_role = $form_state->getValue('user_role');
			$user_status = ($user_role == 'manager') ? 1 : 0;
			$new_user = User::create([
				'name' => $user_name,
				'pass' => $form_state->getValue('password'),
				'mail' => $user_email,
				'roles' => array($user_role, 'authenticated'),
				'field_first_name' => ucfirst($form_state->getValue('first_name')),
				'field_last_name' => ucfirst($form_state->getValue('last_name')),
				'field_phone_number' => $form_state->getValue('phone_number'),
				'field_address' => $form_state->getValue('address'),
				'field_city' => $form_state->getValue('city'),
				'field_zipcode' => $form_state->getValue('zipcode'),
				'field_country' => $form_state->getValue('country'),
				'status' => $user_status,
			])->save();
			$send_mail = \Drupal::service('fernweh_service')->UserWelcomeMail($user_email, $user_full_name, $user_role);
			if($user_role == 'manager') {
				$message = 'The Manager account has been created';
			}
			if($user_role == 'engineer') {
				$message = 'The Employee account has been created & is pending verfication';
			}
			\Drupal::messenger()->addMessage($message);
		}
		catch(Exception $ex){
			\Drupal::messenger()->addError($ex->getMessage());
		}
	}
}