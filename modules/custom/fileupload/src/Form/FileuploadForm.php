<?php

/**
* @file
* Contains \Drupal\fileupload\Form\FileuploadForm.
*/

namespace Drupal\fileupload\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class FileuploadForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'fileupload_form';
	}
	
	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
		$uid = \Drupal::currentUser()->id();
		$user = \Drupal\user\Entity\User::load($uid);
		
		if($user->roles->target_id == 'administrator'){
			$form = array(
				'#attributes' => array('enctype' => 'multipart/form-data'),
			);
			$validators = array(
				'file_validate_extensions' => array('csv'),
			);
			$form['csv_upload_file'] = array(
				'#type' => 'managed_file',
				'#name' => 'csv_upload_file',
				'#title' => t('File Name'),
				'#size' => 20,
				'#required' => TRUE,
				'#description' => t('CSV format only'),
				'#upload_validators' => $validators,
				'#upload_location' => 'public://excel_files/',
			);
			$form['csv_table_name'] = array(
				'#type' => 'select',
				'#title' => ('Select File For'),
				'#required' => TRUE,
				'#options' => array(
					'a_cars' => t('Cars'),
					'a_employees' => t('Employees'),
				),
			);
			$form['actions']['#type'] = 'actions';
			$form['actions']['submit'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Upload File'),
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
		if ($form_state->getValue('csv_upload_file') == NULL) {
			$form_state->setErrorByName('csv_upload_file', 'Please upload a file');
		}
	}
	
	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$db_table_name = $form_state->getValue('csv_table_name');
		$file_name = $form_state->getValue('csv_upload_file');
		
		$file = File::load($file_name[0]);
		$file_destination = $file->getFileUri();
		$file_to_read = fopen($file_destination, 'r');
		while (($line = fgetcsv($file_to_read, 1000, ',')) !== FALSE) {
			$record_lines[] = $line;
		}
		if($db_table_name == 'a_cars'){
			\Drupal::database()->truncate('a_cars')->execute();
			foreach($record_lines as $key => $record_value) {
				\Drupal::database()->insert('a_cars')
				->fields(array(
				'car_brand' => $record_value[0],
				'car_name' => $record_value[1],
				'car_country' => $record_value[2],
				'car_price' => $record_value[3],
				))->execute();
			}
			fclose($file_to_read);
			unlink($file_destination);
		}
		else if($db_table_name == 'a_employees'){
			\Drupal::database()->truncate('a_employees')->execute();
			foreach($record_lines as $key => $record_value) {
				\Drupal::database()->insert('a_employees')
				->fields(array(
				'emp_name' => $record_value[0],
				'emp_email' => $record_value[1],
				'emp_mobile' => $record_value[2],
				'emp_city' => $record_value[3],
				))->execute();
			}
			fclose($file_to_read);
			unlink($file_destination);
		}
		\Drupal::messenger()->addMessage('CSV data uploaded to the database');
	}
}	