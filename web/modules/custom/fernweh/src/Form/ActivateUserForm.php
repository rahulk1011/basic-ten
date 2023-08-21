<?php

/**
 * @file
 * Contains \Drupal\fernweh\Form\ActivateUserForm.
*/
namespace Drupal\fernweh\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Xss;

class ActivateUserForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'activateuser_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
        $manager_id = \Drupal::currentUser()->id();
		$manager_user = \Drupal\user\Entity\User::load($manager_id);
        $manager_name = $manager_user->get('field_first_name')->value.' '.$manager_user->get('field_last_name')->value;

        if($manager_user->roles->target_id == 'administrator' || $manager_user->roles->target_id == 'manager') {
            $form['manager_id'] = array(
                '#type' => 'hidden',
                '#value' => $manager_id, 
            );
            $form['manager_name'] = array(
                '#type' => 'hidden',
                '#value' => $manager_name, 
            );
            $pending_users = array();
            $pending_user_list = \Drupal::database()->query("SELECT `uid` FROM `users_field_data` WHERE `status` = 0 AND `uid` != 0")->fetchAll();
            foreach($pending_user_list as $key => $pid) {
                $pending_uid = $pid->uid;
                $pending_user = User::load($pending_uid);
                $full_name = $pending_user->get('field_first_name')->value.' '.$pending_user->get('field_last_name')->value;
                // Populate dropdown with user-id & name
                $pending_users[$pending_uid] = $full_name;
                $user_email = $pending_user->get('mail')->value;
                $phone_number = $pending_user->get('field_phone_number')->value;
                $full_address = $pending_user->get('field_address')->value.', '.$pending_user->get('field_city')->value.' - '.$pending_user->get('field_zipcode')->value;
                $country = $pending_user->get('field_country')->value;
            }
            $form['user_id'] = array(
                '#type' => 'select',
                '#title' => 'Pending Users List',
                '#required' => TRUE,
                '#options' => $pending_users,
                '#ajax' => array(
                    'callback' => '::fetch_user_data_ajax_callback',
                    'wrapper' => 'user-detail-wrapper',
                    'progress' => [
                        'type' => 'throbber',
                        'message' => 'Fetching data..',
                    ],
                ),
            );
            
            if(empty($pending_user_list)) {
                $form['user_details'] = array(
                    '#type'=> 'item',
                    '#title' => '',
                    '#markup' => '<div id="user-detail-wrapper"><h4>No pending users found</h4></div>',
                );
            }
            else {
                $user_id = $form_state->getValue('user_id');
                if(empty($user_id)) {
                    $form['user_details'] = array(
                        '#type'=> 'item',
                        '#title' => '',
                        '#markup' => '<div id="user-detail-wrapper"><h4>Please select a user</h4></div>',
                    );
                }
                else {
                    $form['user_details'] = array(
                        '#type'=> 'item',
                        '#title' => '',
                        '#markup' => '<div id="user-detail-wrapper">
                        <strong>Name: '.Xss::filter($full_name).'</strong><br>
                        <strong>Email:</strong> '.Xss::filter($user_email).'<br>
                        <strong>Address:</strong> '.Xss::filter($full_address).'<br>
                        <strong>Phone:</strong> '.Xss::filter($phone_number).'<br>
                        <strong>Country:</strong> '.Xss::filter($country).'
                        </div>',
                    );
                }
            }
            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = array(
                '#type' => 'submit',
                '#value' => $this->t('Activate User'),
                '#button_type' => 'primary',
            );
        }
		else {
            $form['form_info'] = array(
			    '#markup' => '<strong>You are not authorized to view this page</strong>',
			);
        }
		return $form;
	}

    public function fetch_user_data_ajax_callback(array &$form, FormStateInterface $form_state) {
        $ajax_response = new AjaxResponse();
        $pending_user = User::load($form_state->getValue('user_id'));
        $full_name = $pending_user->get('field_first_name')->value.' '.$pending_user->get('field_last_name')->value;
        $user_email = $pending_user->get('mail')->value;
        $phone_number = $pending_user->get('field_phone_number')->value;
        $full_address = $pending_user->get('field_address')->value.', '.$pending_user->get('field_city')->value.' - '.$pending_user->get('field_zipcode')->value;
        $country = $pending_user->get('field_country')->value;

        $ajax_response->addCommand(new InvokeCommand('#user-detail-wrapper', 'html' , array(
        '<strong>Name: '.Xss::filter($full_name).'</strong><br>
        <strong>Email:</strong> '.Xss::filter($user_email).'<br>
        <strong>Address:</strong> '.Xss::filter($full_address).'<br>
        <strong>Phone:</strong> '.Xss::filter($phone_number).'<br>
        <strong>Country:</strong> '.Xss::filter($country))));
        return $ajax_response;
    }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		try {
            $manager_id = $form_state->getValue('manager_id');
            $manager_name = $form_state->getValue('manager_name');
			$user_id = $form_state->getValue('user_id');
			// Fetch user details
            $activate_user = \Drupal\user\Entity\User::load($user_id);
            $user_email = $activate_user->get('mail')->value;
            $user_name = $activate_user->get('field_first_name')->value.' '.$activate_user->get('field_last_name')->value;
            // Activate the user account
            $activate_user->set('status', 1);
            $activate_user->save();
            // Send account activation confirmation to the user
			$send_mail = \Drupal::service('fernweh_service')->UserActivationMail($user_email, $user_name, $manager_name);
			$message = 'The Employee account for '.$user_name.' has been activated by '.$manager_name;
			\Drupal::messenger()->addMessage($message);
            \Drupal::logger('activate_user')->notice($message);
		}
		catch(Exception $ex){
			\Drupal::messenger()->addError($ex->getMessage());
		}
	}
}