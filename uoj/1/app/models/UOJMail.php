<?php

require $_SERVER['DOCUMENT_ROOT'].'/app/vendor/phpmailer/PHPMailerAutoload.php';

class UOJMail {
	public static function noreply() {
		$mailer = new PHPMailer();  
		$mailer->isSMTP();
		$mailer->Host = "smtp.sina.com";
		$mailer->SMTPAuth = true;
		$mailer->Username = UOJConfig::$data['mail']['noreply']['username'];
		$mailer->Password = UOJConfig::$data['mail']['noreply']['password'];
		$mailer->setFrom(UOJConfig::$data['mail']['noreply']['username'], "UOJ noreply");
		$mailer->CharSet = "utf-8";
		$mailer->Encoding = "base64";
		return $mailer;
	}
}
