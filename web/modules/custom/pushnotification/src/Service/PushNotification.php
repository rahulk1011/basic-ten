<?php

namespace Drupal\pushnotification\Service;

class PushNotification {
    function send_push_notification($msg_title, $msg_body, $notification_token) {
        // Service Call
        $config = \Drupal::config('pushnotification.settings');
        $server_api_key = $config->get('pushnotification.server_key');
        $fcm_url = $config->get('pushnotification.fcm_url');
        $notification = [
			'title' => $msg_title,
			'body' => $msg_body,
		];
		$extra_data = [
			'message' => $notification,
			'date' => date('d-m-Y'),
		];
		$notification_body = [
			'to' => $notification_token,
			'notification' => $notification,
			'data' => $extra_data
		];
		$headers = [
			'Authorization: key=' . $server_api_key,
			'Content-Type: application/json'
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fcm_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_body));
		$result = curl_exec($ch);
        if($result) {
            \Drupal::messenger()->addMessage('Push notification sent');
        }
        else {
            \Drupal::messenger()->addMessage('An error occurred');
        }
        \Drupal::logger('push_notification')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
        \Drupal::logger('push_notification')->notice('<pre><code>'.print_r($notification_body, TRUE).'</code></pre>');
        curl_close($ch);
    }
}