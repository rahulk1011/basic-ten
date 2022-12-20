<?php

namespace Drupal\sendmail\Service;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

class Sendmail {
	function send_sample_mail($mail) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$module = 'sendmail';
		$key = 'sample_mail';
		$to = $mail['email'];
        $mail_body = $mail['body'];
        
        $params['message'] = 'Hello User,<br><br>'.$mail_body.'<br><br>Best Regards,<br>D-10 Admin';
		$params['title'] = $mail['subject'];
		$langcode = 'en';
		$send = true;
	  
		$result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
		\Drupal::logger('send_mail')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = t('There was a problem sending your email to @email.', array('@email' => $to));
			\Drupal::messenger()->addMessage($message, 'error');
		  	\Drupal::logger('mail-log')->error($message);
		  	return;
		}
		$message = t('The sample mail has been sent to @email ', array('@email' => $to));
		\Drupal::messenger()->addMessage($message);
		\Drupal::logger('mail-log')->notice($message);
	}
}