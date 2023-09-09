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
*   id = "dashboard_rest",
*   label = @Translation("Dashboard API"),
*   uri_paths = {
*     "canonical" = "/api/user-dashboard",
*   }
* )
*/

class DashboardRest extends ResourceBase {
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
            $container->get('logger.factory')->get('user_dashboard_api'),
            $container->get('current_user')
		);
	}

	/*
    * User Dashboard API
	*/
	public function get() {
		global $base_url;
		try {
			$logged_user = User::load(\Drupal::currentUser()->id());
			$user_id = $logged_user->get('uid')->value;
            $role = $logged_user->get('roles')->getValue();
            $user_role = $role[0]['target_id'];

            $user_data['user_id'] = $user_id;
            $user_data['role'] = ucfirst($user_role);
            $user_data['first_name'] = $logged_user->get('field_first_name')->value;
            $user_data['last_name'] = $logged_user->get('field_last_name')->value;
            $user_data['email'] = $logged_user->get('mail')->value;
            $user_data['phone_number'] = $logged_user->get('field_phone_number')->value;
            $user_data['address'] = $logged_user->get('field_address')->value;
            $user_data['city'] = $logged_user->get('field_city')->value;
            $user_data['zipcode'] = $logged_user->get('field_zipcode')->value;
            $user_data['country'] = $logged_user->get('field_country')->value;

			$final_api_reponse = array(
                "status" => "Success",
                "message" => "User Dashboard",
                "result" => $user_data
			);
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}
}