<?php

namespace Drupal\userinfo\Service;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

class Userinfo {
    public function userinfo_birthday_mails() {
		// this is used to send HTML emails
		$send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail();

		$current_m_d = date('m-d', \Drupal::time()->getRequestTime());
		$fetch_query = \Drupal::database()->query("SELECT `firstname`, `email`, `dob` FROM `a_user_info`");
		$query_result = $fetch_query->fetchAll();

		foreach($query_result as $result){
			$birthday = explode('-', $result->dob);
			$birth_m_d = $birthday[1].'-'.$birthday[2];
	
			$user_name = $result->firstname;
			$user_mail = $result->email;
	
			if($birth_m_d == $current_m_d) {
				$mailManager = \Drupal::service('plugin.manager.mail');
				$module = 'userinfo';
				$key = 'user_birthday';
				$to = $user_mail;
				$params['message'] = 'Dear '.$user_name.',<br><br>We value your special day just as much as we value you. On your birthday, we send you our warmest and most heartfelt wishes..<br><br>Best Regards,<br>Admin Ten';
				$params['title'] = 'Happy Birthday '.$user_name.'!!';
				$langcode = 'en';
				$send = true;
			
				$result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
				\Drupal::logger('birthday_mail')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
				if ($result['result'] != true) {
					$message = t('There was a problem sending your email to @email.', array('@email' => $to));
					\Drupal::messenger()->addError($message);
					\Drupal::logger('mail-log')->error($message);
					return;
				}
				\Drupal::logger('birthday-log')->notice("Birthday mail sent to ".$user_name);
			};
		}
	}

	function user_mail_notification($first_name, $email_id) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$module = 'userinfo';
		$key = 'user_added';
		$to = $email_id;
		$params['message'] = 'Dear '.$first_name.',<br><br>Welcome to Basic-Ten. Your information has been stored successfully.<br><br>Best Regards,<br>Admin Eight';
		$params['title'] = 'Welcome '.$first_name.'!!';
		$langcode = 'en';
		$send = true;
	  
		$result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
		\Drupal::logger('user_mail')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = t('There was a problem sending your email to @email.', array('@email' => $to));
			\Drupal::messenger()->addError($message);
		  	\Drupal::logger('mail-log')->error($message);
		  	return;
		}
		$message = t('An email notification has been sent to @email ', array('@email' => $to));
		\Drupal::messenger()->addMessage($message);
		\Drupal::logger('mail-log')->notice($message);
	}
}