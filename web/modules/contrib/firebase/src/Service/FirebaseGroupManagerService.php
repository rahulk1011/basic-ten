<?php

namespace Drupal\firebase\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

/**
 * Service for managing device groups.
 */
class FirebaseGroupManagerService extends FirebaseServiceBase {

  /**
   * Maximum devices in group.
   */
  const MAX_DEVICES = 20;

  /**
   * Endpoint for send message.
   */
  const ENDPOINT = 'https://fcm.googleapis.com/fcm/notification';

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory, Client $client, LoggerChannelInterface $loggerChannel) {
    parent::__construct($configFactory, $client, $loggerChannel);
    $config = $this->configFactory->get('firebase.settings');
    $this->key = $config->get('server_key');
    $this->endpoint = self::ENDPOINT;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return parent::buildHeader() + [
      'project_id' => $this->configFactory->get('firebase.settings')->get('sender_id'),
    ];
  }

  /**
   * Method for creation new device group.
   *
   * @param string $groupName
   *   Unique name for the group of devices.
   * @param array $deviceTokens
   *   Device or devices tokens, which should be combined into one group.
   *
   * @return bool|array
   *   Result from FCM.
   */
  public function createGroup($groupName, array $deviceTokens = []) {
    if (!$groupName || empty($deviceTokens)) {
      return FALSE;
    }

    if (count($deviceTokens) > self::MAX_DEVICES) {
      throw new \OutOfRangeException('Device in group limit exceeded. Firebase supports a maximum of %u devices in one group.', self::MAX_DEVICES);
    }

    $this->body = [
      'operation' => 'create',
      'notification_key_name' => $groupName,
      'registration_ids' => $deviceTokens,
    ];
    return $this->send();
  }

  /**
   * Method for adding new devices to an existing group.
   *
   * @param string $groupName
   *   Unique name for the group of devices.
   * @param string $groupToken
   *   The token for identify group in FCM.
   * @param array $deviceTokens
   *   Device or devices tokens, which should be combined into one group.
   *
   * @return bool|array
   *   Result from FCM.
   */
  public function addToGroup($groupName, $groupToken, array $deviceTokens) {
    if (!$groupName || empty($deviceTokens) || !$groupToken) {
      return FALSE;
    }

    if (count($deviceTokens) > self::MAX_DEVICES) {
      throw new \OutOfRangeException('Device in group limit exceeded. Firebase supports a maximum of %u devices in one group.', self::MAX_DEVICES);
    }

    $this->body = [
      'operation' => 'add',
      'notification_key_name' => $groupName,
      'notification_key' => $groupToken,
      'registration_ids' => $deviceTokens,
    ];
    return $this->send();
  }

  /**
   * Method for removing devices from existing group.
   *
   * @param string $groupName
   *   Unique name for the group of devices.
   * @param string $groupToken
   *   The token for identify group in FCM.
   * @param array $deviceTokens
   *   Device or devices tokens, which should be combined into one group.
   *
   * @return bool|array
   *   Result from FCM.
   */
  public function removeFromGroup($groupName, $groupToken, array $deviceTokens) {
    if (!$groupName || empty($deviceTokens) || !$groupToken) {
      return FALSE;
    }

    $this->body = [
      'operation' => 'remove',
      'notification_key_name' => $groupName,
      'notification_key' => $groupToken,
      'registration_ids' => $deviceTokens,
    ];
    return $this->send();
  }

}
