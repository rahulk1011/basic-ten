<?php

namespace Drupal\firebase;

/**
 * Provides an interface for services, working with FCM.
 */
interface FirebaseServiceInterface {

  /**
   * Build the header.
   *
   * @return array
   *   Array with request header.
   */
  public function buildHeader();

  /**
   * Send request to FCM.
   *
   * @return bool|mixed
   *   Result of request.
   */
  public function send();

}
