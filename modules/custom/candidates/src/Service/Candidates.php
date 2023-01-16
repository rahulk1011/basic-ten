<?php

namespace Drupal\candidates\Service;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

class Candidates {
	function candidate_notify_mail($mail) {
		$mailManager = \Drupal::service('plugin.manager.mail');
		$module = 'candidates';
		$key = 'candidates_mail';
		$to = $mail['email'];
        $mail_body = $mail['body'];
        
        $params['message'] = 'Dear Candidate,<br><br>'.$mail_body.'<br><br>Best Regards,<br>D-10 Admin';
		$params['title'] = $mail['subject'];
		$langcode = 'en';
		$send = true;
	  
		$result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
		\Drupal::logger('candidate_mail')->notice('<pre><code>'.print_r($result, TRUE).'</code></pre>');
		if ($result['result'] != true) {
			$message = t('There was a problem sending mail to @email.', array('@email' => $to));
			\Drupal::messenger()->addMessage($message, 'error');
		  	\Drupal::logger('mail-log')->error($message);
		  	return;
		}
		$message = t('Notification mail has been sent to @email ', array('@email' => $to));
		\Drupal::messenger()->addMessage($message);
		\Drupal::logger('mail-log')->notice($message);
	}
}