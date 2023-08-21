<?php

/**
 * @file
 * Contains \Drupal\candidates\Form\CandidatesForm.
*/

namespace Drupal\candidates\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class CandidatesForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'candidates_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['candidate_name'] = [
			'#type' => 'textfield',
			'#title' => 'Candidate Name',
			'#required' => TRUE,
			'#default_value' => '',
		];
		$form['candidate_email'] = [
			'#type' => 'email',
			'#title' => 'Email-ID',
			'#required' => TRUE,
			'#default_value' => '',
		];
		$form['candidate_dob'] = [
			'#type' => 'date',
			'#title' => 'Date of Birth',
			'#required' => TRUE,
			'#default_value' => '',
		];
		$form['candidate_gender'] = [
			'#type' => 'select',
			'#title' => 'Gender',
			'#required' => TRUE,
			'#options' => [
				'' => '-- Select --',
				'male' => 'Male',
				'female' => 'Female',
			],
			'#default_value' => '',
		];
		$form['candidate_country'] = [
			'#type' => 'textfield',
			'#title' => 'Country',
			'#required' => TRUE,
			'#default_value' => '',
		];
		$form['candidate_passport'] = [
			'#type' => 'radios',
			'#title' => 'Passport Available',
			'#options' => [
			  	'yes' => 'Yes',
			  	'no' => 'No',
			],
			'#required' => TRUE,
		];
		$form['passport_number'] = [
			'#type' => 'textfield',
			'#title' => 'Passport Number',
			'#maxlength' => 10,
			'#attributes' => [
			  	'id' => 'passport-number',
			],
			'#states' => [
			  	'visible' => [
					':input[name="candidate_passport"]' => ['value' => 'yes'],
			  	],
			],
		];
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = [
			'#type' => 'submit',
			'#value' => 'Save',
			'#button_type' => 'primary',
		];
		return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($form_state->getValue('candidate_name') == '') {
			$form_state->setErrorByName('candidate_name', $this->t('Please enter candidate name'));
		}
		if ($form_state->getValue('candidate_email') == '') {
			$form_state->setErrorByName('candidate_email', $this->t('Please enter email-id'));
		}
		if ($form_state->getValue('candidate_dob') == '') {
			$form_state->setErrorByName('candidate_dob', $this->t('Please enter date of birth'));
		}
		if ($form_state->getValue('candidate_gender') == '') {
			$form_state->setErrorByName('candidate_gender', $this->t('Please select gender'));
		}
		if ($form_state->getValue('candidate_country') == '') {
			$form_state->setErrorByName('candidate_country', $this->t('Please enter country'));
		}
		if ($form_state->getValue('candidate_passport') == '') {
			$form_state->setErrorByName('candidate_passport', $this->t('Please select a choice'));
		}
		if ($form_state->getValue('candidate_passport') == 'yes' && $form_state->getValue('passport_number') == '') {
			$form_state->setErrorByName('passport_number', $this->t('Please enter passport number'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		// Mail details
		$mail = array();
		$mail["email"] = $form_state->getValue('candidate_email');
		$mail["subject"] = "Welcome ".$form_state->getValue('candidate_name')."!!";
		$mail["body"] = "Your details have been saved successfully in our database. A representative will reach out to you very soon..";

		// Create & Save candidate details 
		$node = Node::create(['type' => 'candidate']);
		$node->langcode = "en";
		$node->uid = 1;
		$node->promote = 0;
		$node->sticky = 0;
		$node->title= $form_state->getValue('candidate_name');
		$node->field_email_id = $form_state->getValue('candidate_email');
		$node->field_date_of_birth = $form_state->getValue('candidate_dob');
		$node->field_gender = $form_state->getValue('candidate_gender');
		$node->field_country = $form_state->getValue('candidate_country');
		$node->field_passport = $form_state->getValue('candidate_passport');
		$node->field_passport_number = $form_state->getValue('passport_number');
		$node->save();

		\Drupal::messenger()->addMessage('Candidate Data Saved Successfully');
		// $send_mail = \Drupal::service('candidates_service')->candidate_notify_mail($mail);
	}
}