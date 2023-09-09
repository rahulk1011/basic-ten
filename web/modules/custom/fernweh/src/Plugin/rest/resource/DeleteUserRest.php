<?php

namespace Drupal\rkservice\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

/**
* Provides a resource to get view modes by entity and bundle.
* @RestResource(
*   id = "delete_user_rest",
*   label = @Translation("Delete User API"),
*   uri_paths = {
*     "create" = "/api/delete-user",
*   }
* )
*/

class DeleteUserRest extends ResourceBase {
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
            $container->get('logger.factory')->get('delete_user_api'),
            $container->get('current_user')
		);
	}

	/*
    * Delete User Account API
	*/
	public function post(Request $data) {
		try {
			$logged_user = User::load(\Drupal::currentUser()->id());
			$user_id = $logged_user->get('uid')->value;
            $full_name = $logged_user->get('field_first_name')->value.' '.$logged_user->get('field_last_name')->value;
            $user_email = $logged_user->get('mail')->value;
            $message = 'Account delete request from '.$full_name.' with email '.$user_email;
			$content = $data->getContent();
			$params = json_decode($content, TRUE);

            if ($params['confirm'] == 1) {
                if($user_id != 0 && $user_id != 1) {
                    \Drupal::logger('delete_user_api')->notice($message);
                    // Send account delete request mail to Admin
					$send_mail = \Drupal::service('fernweh_service')->DeleteUserAccount($user_email, $full_name);
                    // Delete the whole user account
                    // $logged_user->delete();

                    // Disable the user account
                    $logged_user->set('status', 0);
                    $logged_user->save();
                    $final_api_reponse = array(
                        "status" => "Success",
                        "message" => "Delete Account Request",
                        "result" => "Your request to delete the account has been submitted to the admin."
                    );
                }
                else {
                    $final_api_reponse = array(
                        "status" => "Error",
                        "message" => "Sorry. An error occurred."
                    );
                }
            }
            else {
                $final_api_reponse = array(
                    "status" => "Error",
                    "message" => "Sorry. An error occurred."
                );
            }
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}
}