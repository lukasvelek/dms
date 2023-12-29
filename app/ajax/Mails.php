<?php

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
    global $mailModel;

    $tb = TableBuilder::getTemporaryObject();
    $tb->showRowBorder();

    $headers = array(
        'Recipient',
        'Title',
        'Body',
        'Date created'
    );

    $headerRow = null;

    $mails = $mailModel->getMailQueue();

    if($mails->num_rows == 0) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($mails as $row) {
            if(is_null($headerRow)) {
                $hr = $tb->createRow();

                foreach($headers as $header) {
                    $hc = $tb->createCol()->setText($header)
                                          ->setBold();

                    $hr->addCol($hc);
                }

                $headerRow = $hr;

                $tb->addRow($hr);
            }

            $mailRow = $tb->createRow();

            $mailRow->addCol($tb->createCol()->setText($row['recipient']))
                    ->addCol($tb->createCol()->setText($row['title']))
                    ->addCol($tb->createCol()->setText($row['body']))
                    ->addCol($tb->createCol()->setText($row['date_created']))
            ;

            $tb->addRow($mailRow);
        }
    }

    echo $tb->build();
}

?>