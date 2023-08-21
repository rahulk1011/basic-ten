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
*   id = "user_registration_rest",
*   label = @Translation("User Registration API"),
*   uri_paths = {
*     "create" = "/api/user-registration",
*   }
* )
*/
class UserRegistrationRest extends ResourceBase {
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
            $container->get('logger.factory')->get('user_registration_api'),
            $container->get('current_user')
		);
	}

	/*
	* User Registration API
	*/
	public function post(Request $data) {
		global $base_url;
		try {
			$content = $data->getContent();
			$params = json_decode($content, TRUE);

			$message_string = "";
			$message_string .= empty($params['user_role']) ? "User Role. " : "";
			$message_string .= empty($params['email_id']) ? "Email ID. " : "";
			$message_string .= empty($params['password']) ? "Password. " : "";
			$message_string .= empty($params['first_name']) ? "First Name. " : "";
			$message_string .= empty($params['last_name']) ? "Last Name. " : "";
			$message_string .= empty($params['phone_number']) ? "Phone Number. " : "";
            $message_string .= empty($params['address']) ? "Address. " : "";
            $message_string .= empty($params['city']) ? "City. " : "";
            $message_string .= empty($params['zipcode']) ? "Zipcode. " : "";
            $message_string .= empty($params['country']) ? "Country. " : "";
			if($message_string) {
				$final_api_reponse = array(
                    "status" => "Error",
                    "message" => "Mandatory Fields Missing",
                    "result" => "Required fields: ".$message_string
				);
			}
			else {
				$user_name = strtolower($params['first_name'].'_'.$params['last_name']);
				$user_full_name = ucfirst($params['first_name']).' '.ucfirst($params['last_name']);
				$user_email = $params['email_id'];

				$email_query = \Drupal::entityQuery('user');
                $email_query->accessCheck(TRUE);
                $email_check = $email_query->condition('mail', $user_email)->execute();
                $username_query = \Drupal::entityQuery('user');
                $username_query->accessCheck(TRUE);
				$username_check = $username_query->condition('name', $user_name)->execute();

				if (!empty($email_check) || !empty($username_check)) {
					$final_api_reponse = array(
                        "status" => "Error",
                        "message" => "Registration Failed",
                        "result" => "User details already exists. Please try with different Name or Email-ID."
					);
				}
				else {
					$user_role = $params['user_role'];
					$user_status = ($user_role == 'manager') ? 1 : 0;
					$new_user = User::create([
                        'name' => $user_name,
                        'pass' => $params['password'],
                        'mail' => $user_email,
                        'roles' => array($user_role, 'authenticated'),
                        'field_first_name' => ucfirst($params['first_name']),
                        'field_last_name' => ucfirst($params['last_name']),
                        'field_phone_number' => $params['phone_number'],
                        'field_address' => $params['address'],
                        'field_city' => $params['city'],
                        'field_zipcode' => $params['zipcode'],
                        'field_country' => $params['country'],
                        'status' => $user_status,
					])->save();
					// Send Welcome Mail to the User //
					$send_mail = \Drupal::service('fernweh_service')->UserWelcomeMail($user_email, $user_full_name, $user_role);

                    $final_api_reponse = array(
                        "status" => "Success",
                        "message" => "Registration Successful",
                        "result" => "Thank You. Account for ".$user_full_name." has been created."
					);
				}
			}
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}
}