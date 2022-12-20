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
		$form['candidate_name'] = array(
			'#type' => 'textfield',
			'#title' => 'Candidate Name',
			'#required' => TRUE,
		);
		$form['candidate_dob'] = array(
			'#type' => 'date',
			'#title' => 'Date of Birth',
			'#required' => TRUE,
		);
		$form['candidate_gender'] = array(
			'#type' => 'select',
			'#title' => ('Gender'),
			'#required' => TRUE,
			'#options' => array(
				'male' => 'Male',
				'female' => 'Female',
			),
		);
		$form['candidate_city'] = array(
			'#type' => 'textfield',
			'#title' => 'City',
			'#required' => TRUE,
		);
		$form['candidate_country'] = array(
			'#type' => 'textfield',
			'#title' => 'Country',
			'#required' => TRUE,
		);
		$form['candidate_description'] = array(
			'#type' => 'textarea',
			'#title' => 'Description',
			'#required' => TRUE,
		);
		
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Save'),
			'#button_type' => 'primary',
		);
		return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		if ($form_state->getValue('candidate_name') == '') {
			$form_state->setErrorByName('candidate_name', $this->t('Please Enter Candidate Name'));
		}
		if ($form_state->getValue('candidate_dob') == '') {
			$form_state->setErrorByName('candidate_dob', $this->t('Please Enter Candidate Date of Birth'));
		}
		if ($form_state->getValue('candidate_gender') == '') {
			$form_state->setErrorByName('candidate_gender', $this->t('Please Enter Candidate Gender'));
		}
		if ($form_state->getValue('candidate_city') == '') {
			$form_state->setErrorByName('candidate_city', $this->t('Please Enter Candidate City'));
		}
		if ($form_state->getValue('candidate_country') == '') {
			$form_state->setErrorByName('candidate_country', $this->t('Please Enter Candidate Country'));
		}
		if ($form_state->getValue('candidate_description') == '') {
			$form_state->setErrorByName('candidate_description', $this->t('Please Enter Candidate Description'));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$node = Node::create(['type' => 'candidate_details']);
		$node->langcode = "en";
		$node->uid = 1;
		$node->promote = 0;
		$node->sticky = 0;
		$node->title= $form_state->getValue('candidate_name');
		$node->field_date_of_birth = $form_state->getValue('candidate_dob');
		$node->field_gender = $form_state->getValue('candidate_gender');
		$node->field_city = $form_state->getValue('candidate_city');
		$node->field_country = $form_state->getValue('candidate_country');
		$node->field_description = $form_state->getValue('candidate_description');
		$node->save();

		drupal_set_message('Candidate Data Save Successful');
	}
}
