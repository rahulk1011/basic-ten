<?php

namespace Drupal\firebase\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for managing topics.
 */
class FirebaseTopicManagerService extends FirebaseServiceBase {

  /**
   * Endpoint for subscribe device on topic.
   */
  const SUBSCRIBE_ENDPOINT = 'https://iid.googleapis.com/iid/v1:batchAdd';

  /**
   * Endpoint for subscribe device on topic.
   */
  const UNSUBSCRIBE_ENDPOINT = 'https://iid.googleapis.com/iid/v1:batchRemove';

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory, ClientInterface $client, LoggerChannelInterface $loggerChannel) {
    parent::__construct($configFactory, $client, $loggerChannel);
    $config = $this->configFactory->get('firebase.settings');
    $this->key = $config->get('server_key');
  }

  /**
   * Process topic un/subscription.
   *
   * @param string $topic
   *   The topic, which will be processed.
   * @param string|array $tokens
   *   Token or tokens this will be subscriber\unsubscribe by topic.
   * @param string $endpoint
   *   The endpoint for processing by topic.
   */
  public function processTopicSubscription($topic, $tokens, $endpoint) {
    $this->endpoint = $endpoint;
    $this->body = [
      'to' => '/topics/' . $topic,
      'registration_tokens' => is_array($tokens) ? $tokens : [$tokens],
    ];
    $this->send();
  }

}
