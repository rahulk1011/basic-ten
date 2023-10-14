<?php

namespace Drupal\mail_login;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserAuthInterface;

/**
 * Validates user authentication credentials.
 */
class AuthDecorator implements UserAuthInterface {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The original user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The original user authentication service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    UserAuthInterface $user_auth,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger) {

    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $config_factory = $this->configFactory;
    $config = $config_factory->get('mail_login.settings');

    // If we have an email lookup the username by email.
    if ($config->get('mail_login_enabled') && !empty($username)) {
      if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $user_storage = $this->entityTypeManager->getStorage('user');
        $account_search = $user_storage->loadByProperties(['mail' => $username]);
        if (!$account_search && !$config->get('mail_login_case_sensitive')) {
          // Allow case-insensitive matching of the email address, provided that
          // there is only a single match (as case-sensitive email addresses are
          // permitted by RFC 5321).
          $db = $this->connection;
          $user_ids = $this->entityTypeManager->getStorage('user')->getQuery()
            ->accessCheck(FALSE)
            ->condition('mail', $db->escapeLike($username), 'LIKE')
            ->execute();
          if (count($user_ids) === 1) {
            $account_search = $user_storage->loadMultiple($user_ids);
          }
        }
        if ($account = reset($account_search)) {
          $username = $account->getAccountName();
          if ($account->isBlocked()) {
            $this->messenger->addError($this->t('The user has not been activated yet or is blocked.'));
            return FALSE;
          }
        }
      }
      // Check if login by email only option is enabled.
      elseif ($config->get('mail_login_email_only')) {
        // Display a custom login error message.
        $this->messenger->addError(
          $this->t('Login by username has been disabled. Use your email address instead.')
        );
        return FALSE;
      }
    }
    return $this->userAuth->authenticate($username, $password);
  }

}
