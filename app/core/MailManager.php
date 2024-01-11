<?php

namespace DMS\Core;

use DMS\UI\LinkBuilder;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailManager {
    private string $fromEmail;
    private string $fromName;
    private string $server;
    private string $serverPort;
    private string $loginUsername;
    private string $loginPassword;

    public function __construct() {
        $this->fromEmail = AppConfiguration::getMailSenderEmail();
        $this->fromName = AppConfiguration::getMailSenderName();
        $this->server = AppConfiguration::getMailServer();
        $this->serverPort = AppConfiguration::getMailServerPort();
        $this->loginUsername = AppConfiguration::getMailLoginUsername();
        $this->loginPassword = AppConfiguration::getMailLoginPassword();
    }

    public function sendEmail(string $recipient, string $title, string $body) {
        $mail = new PHPMailer(true);

        $result = true;

        try {
            $mail->isSMTP();
            $mail->Host = $this->server;
            $mail->SMTPAuth = true;
            $mail->Username = $this->loginUsername;
            $mail->Password = $this->loginPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->serverPort;

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body = $body;

            $mail->send();
        } catch(Exception $e) {
            $result = false;
        }

        return $result;
    }

    public static function composeEmail(string $recipient, string $title, string $body) {
        return array(
            'recipient' => $recipient,
            'title' => $title,
            'body' => $body
        );
    }

    public static function composeForgottenPasswordEmail(string $recipient, string $hash) {
        $tm = TemplateManager::getTemporaryObject();

        $body = $tm->loadTemplate('app/templates/EmailTemplate.html');

        $link = LinkBuilder::createAdvLink(array(
            'page' => 'AnonymModule:ResetPassword:showForm',
            'hash' => $hash
        ), 'here');
        
        $bodyData = array(
            '$TITLE$' => 'Forgotten password | DMS Service',
            '$BODY$' => '<div><h2>Forgotten password</h2><p>' . 'This email was sent to you because you requested to change your password because you have forgotten it. <br>Click ' . $link . ' to reset your password.<br>DMS' . '</p></div>'
        );

        $tm->fill($bodyData, $body);
        
        $data = array(
            'recipient' => $recipient,
            'title' => 'Forgotten password recovery',
            'body' => $body
        );

        return $data;
    }
}

?>