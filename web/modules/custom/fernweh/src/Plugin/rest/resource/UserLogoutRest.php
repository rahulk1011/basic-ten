<?php

namespace Drupal\fernweh\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;

/**
* Provides a resource to get view modes by entity and bundle.
* @RestResource(
*   id = "user_logout_rest",
*   label = @Translation("User Logout API"),
*   uri_paths = {
*     "canonical" = "/api/user-logout",
*   }
* )
*/

class UserLogoutRest extends ResourceBase {
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
            $container->get('logger.factory')->get('user_logout_api'),
            $container->get('current_user')
		);
	}

	/*
	* User Logout API
	*/
	public function get() {
		global $base_url;
		try {
			$logged_user = User::load(\Drupal::currentUser()->id());
			$user_id = $logged_user->get('uid')->value;
			user_logout();
			$final_api_reponse = array(
                "status" => "Success",
                "message" => "Logout Success",
                "result" => "You have successfully logged out from your account."
			);
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}
}