<?php

namespace Drupal\fernweh\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserFloodControlInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Drupal\user\Entity\User;

/**
* Provides a resource to get view modes by entity and bundle.
* @RestResource(
*   id = "user_login_rest",
*   label = @Translation("User Login API"),
*   uri_paths = {
*     "create" = "/api/user-login",
*   }
* )
*/
class UserLoginRest extends ResourceBase {
	/**
    * String sent in responses, to describe the user as being logged in.
    * @var string
	*/
	const LOGGED_IN = 1;
	/**
    * String sent in responses, to describe the user as being logged out.
    * @var string
	*/
	const LOGGED_OUT = 0;
	/**
    * The user flood control service.
    * @var \Drupal\user\UserFloodControl
	*/
	protected $userFloodControl;
	/**
    * The user storage.
    * @var \Drupal\user\UserStorageInterface
	*/
	protected $userStorage;
	/**
    * The CSRF token generator.
    * @var \Drupal\Core\Access\CsrfTokenGenerator
	*/
	protected $csrfToken;
	/**
    * The user authentication.
    * @var \Drupal\user\UserAuthInterface
	*/
	protected $userAuth;
	/**
    * The route provider.
    * @var \Drupal\Core\Routing\RouteProviderInterface
	*/
	protected $routeProvider;
	/**
    * The serializer.
    * @var \Symfony\Component\Serializer\Serializer
	*/
	protected $serializer;
	/**
    * The available serialization formats.
    * @var array
	*/
	protected $serializerFormats = [];
	/**
    * A logger instance.
    * @var \Psr\Log\LoggerInterface
	*/
	protected $logger;
	/**
    * Constructs a new UserAuthenticationController object.
    *
    * @param \Drupal\user\UserFloodControlInterface $user_flood_control
    *   The user flood control service.
    * @param \Drupal\user\UserStorageInterface $user_storage
    *   The user storage.
    * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
    *   The CSRF token generator.
    * @param \Drupal\user\UserAuthInterface $user_auth
    *   The user authentication.
    * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
    *   The route provider.
    * @param \Symfony\Component\Serializer\Serializer $serializer
    *   The serializer.
    * @param array $serializer_formats
    *   The available serialization formats.
    * @param \Psr\Log\LoggerInterface $logger
    *   A logger instance.
	*/

	public function __construct(array $config, $module_id, $module_definition, array $serializer_formats, UserStorageInterface $user_storage, CsrfTokenGenerator $csrf_token, UserAuthInterface $user_auth, RouteProviderInterface $route_provider, LoggerInterface $logger, AccountProxyInterface $current_user) {
		parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);
		$this->loggedUser = $current_user;
		$this->userStorage = $user_storage;
		$this->csrfToken = $csrf_token;
		$this->userAuth = $user_auth;
		$this->routeProvider = $route_provider;
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
            $container->get('entity_type.manager')->getStorage('user'),
            $container->get('csrf_token'),
            $container->get('user.auth'),
            $container->get('router.route_provider'),
            $container->get('logger.factory')->get('user_login_api'),
            $container->get('current_user')
		);
	}

	/**
    * Logs in a user.
    * @param \Symfony\Component\HttpFoundation\Request $request
    *   The request.
    * @return \Symfony\Component\HttpFoundation\Response
    *   A response which contains the ID and CSRF token.
	*/
	public function post(Request $data) {
		global $base_url;
		try {
			$content = $data->getContent();
			$params = json_decode($content, TRUE);
			$email = $params['email'];
			$password = $params['pass'];
			if (empty($email) || empty($password)) {
				$final_api_reponse = array(
					"status" => "Error",
					"message" => "Missing Credentials"
				);
			}
			else {
				$user = user_load_by_mail($email);
				if(empty($user)) {
					$final_api_reponse = array(
						"status" => "Error",
						"message" => "User Not Found",
						"result" => "No user registered with ".$email
					);
				}
				else {
					$user_name = $user->get('name')->value;
					if ($uid = $this->userAuth->authenticate($user_name, $password)) {
						$user_status = $user->get('status')->value;
						if($user_status != 1) {
							$final_api_reponse = array(
								"status" => "Error",
								"message" => "Login Failed",
								"result" => "Your account is disabled. Please contact the administrator."
							);
						}
						else {
							$user = $this->userStorage->load($uid);
							$this->userLoginFinalize($user);
							$response_data = array();
							if ($user->get('uid')->access('view', $user)) {
								$response_data['current_user']['uid'] = $user->id();
							}
							if ($user->get('name')->access('view', $user)) {
								$response_data['current_user']['name'] = $user->getAccountName();
							}
							$response_data['csrf_token'] = $this->csrfToken->get('rest');
							$logout_route = $this->routeProvider->getRouteByName('user.logout.http');
							$logout_path = ltrim($logout_route->getPath(), '/');
							$response_data['logout_token'] = $this->csrfToken->get($logout_path);
							$final_api_reponse = array(
                                "status" => "Success",
                                "message" => "Login Success",
                                "result" => $response_data
							);
						}
					}
					else {
						$final_api_reponse = array(
                            "status" => "Error",
                            "message" => "Invalid Credentials"
						);
					}
				}
			}
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}

	/**
    * Finalizes the user login.
    * @param \Drupal\user\UserInterface $user
    * The user.
	*/
	protected function userLoginFinalize(UserInterface $user) {
		user_login_finalize($user);
	}
}