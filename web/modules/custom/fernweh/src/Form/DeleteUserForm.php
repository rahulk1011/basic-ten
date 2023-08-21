<?php

/**
 * @file
 * Contains \Drupal\fernweh\Form\DeleteUserForm.
*/
namespace Drupal\fernweh\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeleteUserForm extends FormBase {
	/**
	* {@inheritdoc}
	*/
	public function getFormId() {
		return 'deleteuser_form';
	}

	/**
	* {@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {
        $uid = \Drupal::currentUser()->id();
        $user = \Drupal\user\Entity\User::load($uid);
        $full_name = $user->get('field_first_name')->value.' '.$user->get('field_last_name')->value;
        $user_email = $user->get('mail')->value;

        if($uid != 0) {
            $form['delete_account'] = array(
                '#type' => 'select',
                '#title' => 'Delete Account',
                '#options' => array(
                    '' => '- Select -',
                    '1' => 'Yes',
                    '0' => 'No',
                ),
            );
            $form['user_id'] = array(
                '#type' => 'hidden',
                '#value' => $uid, 
            );
            $form['user_email'] = array(
                '#type' => 'hidden',
                '#value' => $user_email, 
            );
            $form['full_name'] = array(
                '#type' => 'hidden',
                '#value' => $full_name, 
            );
            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = array(
                '#type' => 'submit',
                '#value' => 'Delete Account',
                '#button_type' => 'primary',
                '#attributes' => array('class' => array('btn-danger')),
            );
            $form['actions']['cancel'] = array (
                '#type' => 'button',
                '#weight' => 999,
                '#value' => 'Cancel',
                '#attributes' => array('onClick' => 'pageReload();'),
            );
            $form['#attached']['library'][] = 'fernweh/fernweh';
        }
		else {
            $form['form_info'] = array(
			    '#markup' => '<strong>You must login to view this page</strong>',
			);
        }
        return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
        if($form_state->getValue('delete_account') == '') {
            $form_state->setErrorByName('delete_account', $this->t('Please select a choice'));
        }
    }

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		try {
            $delete_account = $form_state->getValue('delete_account');
			$user_id = $form_state->getValue('user_id');
            $user_email = $form_state->getValue('user_email');
            $full_name = $form_state->getValue('full_name');
            $log_message = 'Account delete request from '.$full_name.' with email '.$user_email;

            if ($delete_account == 1) {
                if($user_id != 0 && $user_id != 1) {
                    \Drupal::logger('delete_account')->notice($log_message);
                    $delete_user = \Drupal\user\Entity\User::load($user_id);
                    $delete_user->set('status', 0);
                    $delete_user->save();
                    $send_mail = \Drupal::service('fernweh_service')->DeleteUserAccount($user_email, $full_name);
                    $message = 'Your request to delete the account has been submitted to the admin.';
                }
                else {
                    $message = 'Sorry. An error occurred.';
                }
            }
            else {
                $message = 'Sorry. An error occurred.';
            }
            \Drupal::messenger()->addMessage($message);
		}
		catch(Exception $ex){
			\Drupal::messenger()->addError($ex->getMessage());
		}
	}
}