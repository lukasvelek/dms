<?php

namespace DMS\Core;

use DMS\UI\LinkBuilder;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailManager {
    private array $cfg;

    private string $fromEmail;
    private string $fromName;
    private string $server;
    private string $serverPort;
    private string $loginUsername;
    private string $loginPassword;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->fromEmail = $cfg['mail_sender_email'];
        $this->fromName = $cfg['mail_sender_name'];
        $this->server = $cfg['mail_server'];
        $this->serverPort = $cfg['mail_server_port'];
        $this->loginUsername = $cfg['mail_login_username'];
        $this->loginPassword = $cfg['mail_login_password'];
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