<?php

namespace Drupal\fernweh\Service;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

class Fernweh {
    function UserWelcomeMail($user_email, $user_full_name, $user_role) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$module = 'fernweh';
		$key = 'user_welcome'; // Replace with Your key
		$to = $user_email;
		$site_name = \Drupal::config('system.site')->get('name');

		if($user_role == 'manager') {
			$params['message'] = '<p>Hello '.$user_full_name.',<br><br>Thank you for registering at '.$site_name.'. Your manager account has been created. You can now log in with your email & password.<br><br>Admin,<br>'.$site_name.'</p>';
		}
		if($user_role == 'engineer') {
			$params['message'] = '<p>Hello '.$user_full_name.',<br><br>Thank you for registering at '.$site_name.'. Your employee account has been created & is pending verfication. After verification, you will be able log in with your email & password.<br><br>Admin,<br>'.$site_name.'</p>';
		}
		$params['title'] = 'Welcome - '.$user_full_name;
		$langcode = 'en';
		$send = true;
		$reply = \Drupal::config('system.site')->get('mail');

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
		\Drupal::logger('user_registration')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = 'There was a problem sending your email notification to '.$user_email;
			\Drupal::logger('user_registration')->error($message);
			return;
		}
		else {
			$message = 'An email notification has been sent to '.$user_email;
			\Drupal::logger('user_registration')->notice($message);
		}
	}

	function UserActivationMail($user_email, $user_name, $manager_name) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$site_email = \Drupal::config('system.site')->get('mail');
		$module = 'fernweh';
		$key = 'user_activate'; // Replace with Your key
		$to = $user_email;
		$site_name = \Drupal::config('system.site')->get('name');

		$params['message'] = '<p>Hello '.$user_name.',<br><br>Your account has been verified & activated by '.$manager_name.'. You can now log in with your email & password.<br><br>Admin,<br>'.$site_name.'</p>';
		$params['title'] = 'Account Activated - '.$user_name;
		$langcode = 'en';
		$send = true;
		$reply = $site_email;

		$result = $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
		\Drupal::logger('activate_user')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = 'There was a problem sending your email notification to '.$user_email;
			\Drupal::logger('activate_user')->error($message);
			return;
		}
		else {
			$message = 'An email notification has been sent to '.$user_email;
			\Drupal::logger('activate_user')->notice($message);
		}
	}

	function DeleteUserAccount($user_email, $full_name) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$site_email = \Drupal::config('system.site')->get('mail');
		$site_name = \Drupal::config('system.site')->get('name');
		$module = 'fernweh';
		$key = 'user_delete'; // Replace with Your key
		$to = $site_email;

		$params['message'] = '<p>Hello Admin,<br><br>The following user has requested to delete their account:<br><br>Name: '.$full_name.'<br>Email: '.$user_email.'<br><br>Admin,<br>'.$site_name.'</p>';
		$params['title'] = 'Account Delete - '.$full_name;
		$langcode = 'en';
		$send = true;
		$reply = $site_email;

		$result = $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
		\Drupal::logger('delete_user')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = 'There was a problem sending your email notification to '.$site_email;
			\Drupal::logger('delete_user')->error($message);
			return;
		}
		else {
			$message = 'An email notification has been sent to '.$site_email;
			\Drupal::logger('delete_user')->notice($message);
		}
	}
}