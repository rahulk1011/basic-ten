<?php

namespace Drupal\firebase\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for pushing message to mobile devices using Firebase.
 */
class FirebaseMessageService extends FirebaseServiceBase {

  /**
   * Maximum devices: https://firebase.google.com/docs/cloud-messaging/http-server-ref#send-downstream.
   */
  const MAX_DEVICES = 1000;

  /**
   * Maximum topics: https://firebase.google.com/docs/cloud-messaging/http-server-ref#send-downstream.
   */
  const MAX_TOPICS = 3;

  /**
   * Endpoint for send message.
   */
  const ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

  /**
   * Condition pattern for sending message to multiple topics.
   *
   * @var string
   */
  protected $condition;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory, ClientInterface $client, LoggerChannelInterface $loggerChannel) {
    parent::__construct($configFactory, $client, $loggerChannel);
    $config = $this->configFactory->get('firebase.settings');
    $this->key = $config->get('server_key');
    $this->endpoint = self::ENDPOINT;
  }

  /**
   * Condition pattern for sending message to multiple topics.
   *
   * Supported operators: &&, ||.
   * Maximum two operators per topic message supported.
   *
   * @param string $condition
   *   String for building condition for topics.
   *
   * @see https://firebase.google.com/docs/cloud-messaging/send-message#send_messages_to_topics
   *
   * Example:
   * "%s && %s" : Send to devices subscribed to topic 1 and topic 2
   * "%s && (%s || %s)" : Send to devices subscribed to topic 1 and topic 2 or 3
   */
  public function createCondition($condition) {
    $this->condition = $condition;
  }

  /**
   * Validate reserved keywords on data.
   *
   * The key should not be a reserved word
   * ("from" or any word starting with "google" or "gcm").
   * Do not use any of the words defined here
   * https://firebase.google.com/docs/cloud-messaging/http-server-ref.
   *
   * Not checking ALL reserved keywords. Just eliminating the common ones.
   * Created this function to document this important restriction.
   *
   * @param array $data
   *   Params that builds Push message payload.
   *
   * @return bool
   *   TRUE if keys are fine, and FALSE if not.
   */
  private function checkReservedKeywords(array $data) {
    foreach ($data as $key => $value) {
      if (preg_match('/(^from$)|(^gcm)|(^google)/', $key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Add data to message.
   *
   * @param array $data
   *   Data options.
   */
  public function setData(array $data) {
    if ($this->checkReservedKeywords($data)) {
      $this->body['data'] = $data;
    }
    else {
      throw new \InvalidArgumentException('Keys in data shouldn\'t contain "form" and any keys starting with "google" and "gcm".');
    }
  }

  /**
   * Add options to message.
   *
   * @param array $options
   *   Message options.
   *
   * @see https://firebase.google.com/docs/cloud-messaging/http-server-ref#downstream-http-messages-json
   */
  public function setOptions(array $options) {
    if (isset($options['priority']) && in_array(strtolower($options['priority']), [
      'high',
      'normal',
    ])) {
      $this->body['priority'] = $options['priority'];
    }
    if (isset($options['content_available'])) {
      $this->body['content_available'] = (bool) $options['content_available'];
    }
    if (isset($options['mutable_content'])) {
      $this->body['mutable_content'] = (bool) $options['mutable_content'];
    }
    if (isset($options['time_to_live']) && ((int) $options['time_to_live'] >= 0 && (int) $options['time_to_live'] <= 2419200)) {
      $this->body['time_to_live'] = $options['time_to_live'];
    }
    if (isset($options['dry_run'])) {
      $this->body['dry_run'] = (bool) $options['dry_run'];
    }
  }

  /**
   * Add single device, group of devices or multiple devices to message target.
   *
   * @param string|array $recipients
   *   Recipients of message.
   */
  public function setRecipients($recipients) {
    if (is_array($recipients)) {
      if (count($recipients) <= self::MAX_DEVICES) {
        $this->body['registration_ids'] = $recipients;
      }
      else {
        throw new \OutOfRangeException(sprintf('Message device limit exceeded. Firebase supports a maximum of %u devices.', self::MAX_DEVICES));
      }
    }
    else {
      $this->body['to'] = $recipients;
    }
  }

  /**
   * Add topic or list of topics message target.
   *
   * @param string|array $topics
   *   Topics without "/topics/".
   *
   * @see https://firebase.google.com/docs/cloud-messaging/send-message#send_messages_to_topics
   */
  public function setTopics($topics) {
    if (is_array($topics) and count($topics) > 1) {
      if (count($topics) > self::MAX_TOPICS) {
        throw new \OutOfRangeException(sprintf('Topics limit exceeded. Firebase supports a maximum of %u topics.', self::MAX_TOPICS));
      }
      elseif (!$this->condition) {
        throw new \InvalidArgumentException('Missing message condition. You must specify a condition pattern when sending to combinations of topics.');
      }
      elseif (count($topics) != substr_count($this->condition, '%s')) {
        throw new \UnexpectedValueException('The number of message topics must match the number of occurrences of "%s" in the condition pattern.');
      }
      else {
        foreach ($topics as &$topic) {
          $topic = vsprintf("'%s' in topics", $topic);
        }
        $this->body['condition'] = vsprintf($this->condition, $topics);
      }
    }
    else {
      if (is_array($topics)) {
        $topics = reset($topics);
      }
      $this->body['to'] = sprintf('/topics/%s', $topics);
    }
  }

  /**
   * Add notification to message.
   *
   * @param array $notification
   *   Notification options.
   */
  public function setNotification(array $notification) {
    if (!empty($notification['title'])) {
      $this->body['notification'] = $notification;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    // Message should contain notification or data array, thereby referring to
    // one of the types of messages.
    // @see https://firebase.google.com/docs/cloud-messaging/concept-options#notifications_and_data_messages
    if (!isset($this->body['notification'])
      && !isset($this->body['data'])) {
      throw new \InvalidArgumentException('The message must belong to one of the message types (notification or data message).');
    }

    // We shouldn't send message without target.
    // @see https://firebase.google.com/docs/cloud-messaging/http-server-ref
    if (!isset($this->body['to'])
      && !isset($this->body['registration_ids'])
      && !isset($this->body['condition'])) {
      throw new \InvalidArgumentException('The message should contain target.');
    }

    return parent::send();
  }

}
