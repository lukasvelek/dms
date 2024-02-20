<?php

namespace DMS\Core;

use DMS\UI\LinkBuilder;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Manager responsible for sending mails
 * 
 * @author Lukas Velek
 */
class MailManager {
    private string $fromEmail;
    private string $fromName;
    private string $server;
    private string $serverPort;
    private string $loginUsername;
    private string $loginPassword;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->fromEmail = AppConfiguration::getMailSenderEmail();
        $this->fromName = AppConfiguration::getMailSenderName();
        $this->server = AppConfiguration::getMailServer();
        $this->serverPort = AppConfiguration::getMailServerPort();
        $this->loginUsername = AppConfiguration::getMailLoginUsername();
        $this->loginPassword = AppConfiguration::getMailLoginPassword();
    }

    /**
     * Tries to send an email
     * 
     * @param string $recipient Email recipient
     * @param string $title Email title
     * @param string $body Email body
     * @return bool True if successful or false if not
     */
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

    /**
     * Composes an email
     * 
     * @param string $recipient Email recipient
     * @param string $title Email title
     * @param string $body Email body
     * @return array Email composition
     */
    public static function composeEmail(string $recipient, string $title, string $body) {
        return array(
            'recipient' => $recipient,
            'title' => $title,
            'body' => $body
        );
    }

    /**
     * Composes an email for forgotten password
     * 
     * @param string $recipient Email recipient
     * @param string $hash Generated hash
     * @return array Composed email body
     */
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