<?php

namespace Drupal\fernweh\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
* Provides a resource to get view modes by entity and bundle.
* @RestResource(
*   id = "activate_user_rest",
*   label = @Translation("Activate User API"),
*   uri_paths = {
*     "canonical" = "/api/pending-user",
*     "create" = "/api/activate-user",
*   }
* )
*/

class ActivateUserRest extends ResourceBase {
	/**
    * A current user instance which is logged in the session.
    * @var \Drupal\Core\Session\AccountProxyInterface
	*/
	protected $loggedUser;

	/**
    * Constructs a Drupal\rest\Plugin\ResourceBase object.
    *
    * @param array $config
    *   A configuration array which contains the information about the plugin instance.
    * @param string $module_id
    *   The module_id for the plugin instance.
    * @param mixed $module_definition
    *   The plugin implementation definition.
    * @param array $serializer_formats
    *   The available serialization formats.
    * @param \Psr\Log\LoggerInterface $logger
    *   A logger instance.
    * @param \Drupal\Core\Session\AccountProxyInterface $current_user
    *   A currently logged user instance.
	*/
	public function __construct(array $config, $module_id, $module_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {
		parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);
		$this->loggedUser = $current_user;
	}

	/**
    * {@inheritdoc}
	*/
	public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
		return new static(
            $config,
            $module_id,
            $module_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('activate_user_api'),
            $container->get('current_user')
		);
	}

	/*
    * List of Pending Users API
	*/
	public function get() {
		global $base_url;
		try {
			$logged_user = User::load(\Drupal::currentUser()->id());
			$user_id = $logged_user->get('uid')->value;
            $role = $logged_user->get('roles')->getValue();
            $user_role = $role[0]['target_id'];

            if($user_role == 'administrator' || $user_role == 'manager') {
                $all_pending_users = array();
                $pending_user_list = \Drupal::database()->query("SELECT `uid` FROM `users_field_data` WHERE `status` = 0 AND `uid` != 0")->fetchAll();
                if(empty($pending_user_list)) {
                    $final_api_reponse = array(
                        "status" => "Success",
                        "message" => "Pending Users",
                        "result" => "No pending users found"
                    );
                }
                else {
                    foreach($pending_user_list as $pending_ids) {
                        $pending_uid = $pending_ids->uid;
                        $pending_user = User::load($pending_uid);
                        $user_data['user_id'] = $pending_uid;
                        $full_name = $pending_user->get('field_first_name')->value.' '.$pending_user->get('field_last_name')->value;
                        $user_data['name'] = $full_name;
                        $user_data['email'] = $pending_user->get('mail')->value;
                        $user_data['role'] = ucfirst($pending_user->get('roles')->getValue()[0]['target_id']);
                        $user_data['phone'] = $pending_user->get('field_phone_number')->value;
                        $full_address = $pending_user->get('field_address')->value.', '.$pending_user->get('field_city')->value.' - '.$pending_user->get('field_zipcode')->value;
                        $user_data['address'] = $full_address;
                        $user_data['country'] = $pending_user->get('field_country')->value;
                        array_push($all_pending_users, $user_data);
                    }
                    $final_api_reponse = array(
                        "status" => "Success",
                        "message" => "Pending Users",
                        "result" => $all_pending_users
                    );
                }
            }
            else {
                $final_api_reponse = array(
                    "status" => "Error",
                    "message" => "Sorry, you are not authorized to view this page."
                );
            }
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}

    /*
    * Activation of Users API
	*/
	public function post(Request $data) {
		global $base_url;
		try {
			$logged_user = User::load(\Drupal::currentUser()->id());
			$user_id = $logged_user->get('uid')->value;
            $role = $logged_user->get('roles')->getValue();
            $user_role = $role[0]['target_id'];
            $manager_name = $logged_user->get('field_first_name')->value.' '.$logged_user->get('field_last_name')->value;

            $content = $data->getContent();
			$params = json_decode($content, TRUE);
            $pending_uid = $params['pending_uid'];

            if($user_role == 'administrator' || $user_role == 'manager') {
                if(empty($pending_uid)) {
                    $final_api_reponse = array(
                        "status" => "Error",
                        "message" => "User ID is missing"
                    );
                }
                else {
                    // Fetch user details
                    $activate_user = User::load($pending_uid);
                    $user_email = $activate_user->get('mail')->value;
                    $user_name = $activate_user->get('field_first_name')->value.' '.$activate_user->get('field_last_name')->value;
                    // Activate the user account
                    $activate_user->set('status', 1);
                    $activate_user->save();
                    $message = 'User account of '.$user_name.' has been activated.';
                    \Drupal::logger('activate_user')->notice($message);
                    // Send account activation confirmation to the user
                    $send_mail = \Drupal::service('fernweh_service')->UserActivationMail($user_email, $user_name, $manager_name);
                    $final_api_reponse = array(
                        "status" => "Success",
                        "message" => "User Activated",
                        "result" => $message
                    );
                }
            }
            else {
                $final_api_reponse = array(
                    "status" => "Error",
                    "message" => "Sorry, you are not authorized to view this page."
                );
            }
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}
}