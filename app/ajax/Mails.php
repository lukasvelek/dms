<?php

use DMS\Core\AppConfiguration;
use DMS\Helpers\DatetimeFormatHelper;
use DMS\UI\GridBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    exit;
}

echo($action());

function getQueue() {
    global $mailModel, $user;

    $dataSourceCallback = function() use ($mailModel) {
        $mails = $mailModel->getMailQueue();
        $mailObjs = [];
        foreach($mails as $mail) {
            $mailObjs[] = new class($mail) {
                private string $recipient;
                private string $title;
                private string $body;
                private string $dateCreated;
    
                public function __construct(mixed $mailRow) {
                    $this->recipient = $mailRow['recipient'];
                    $this->title = $mailRow['title'];
                    $this->body = $mailRow['body'];
                    $this->dateCreated = $mailRow['date_created'];
                }
    
                public function getRecipient() {
                    return $this->recipient;
                }
    
                public function getTitle() {
                    return $this->title;
                }
    
                public function getBody() {
                    return $this->body;
                }
    
                public function getDateCreated() {
                    return $this->dateCreated;
                }
            };
        }

        return $mailObjs;
    };

    $gb = new GridBuilder();

    $gb->addColumns(['recipient' => 'Recipient', 'title' => 'Title', 'body' => 'Body', 'dateCreated' => 'Date created']);
    $gb->addDataSourceCallback($dataSourceCallback);
    $gb->addOnColumnRender('dateCreated', function(object $obj) use ($user) {
        if(!is_null($user)) {
            return DatetimeFormatHelper::formatDateByUserDefaultFormat($obj->getDateCreated(), $user);
        } else {
            return DatetimeFormatHelper::formatDateByFormat($obj->getDateCreated(), AppConfiguration::getDefaultDatetimeFormat());
        }
    });

    echo $gb->build();
}

?>