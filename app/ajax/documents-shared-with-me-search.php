<?php

use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

/*if(isset($_POST['q'])) {
    $query = htmlspecialchars($_POST['q']);

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder',
        'Shared from',
        'Shared to',
        'Shared by'
    );

    $headerRow = null;
    $documents = [];

    if(!is_null($user)) {
        $documents = $documentModel->getSharedDocumentsWithUser($user->getId());
    }

    if(empty($documents)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($documents as $document) {
            $actionLinks = array(
                '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawBulkActions()">',
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit')
            );

            $shared = false;

            if(!$shared) {
                $actionLinks[] = LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share');
            }

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)
                                           ->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $headerRow = $row;

                $tb->addRow($row);
            }

            $docuRow = $tb->createRow();

            foreach($actionLinks as $actionLink) {
                $docuRow->addCol($tb->createCol()->setText($actionLink));
            }

            $docuRow->addCol($tb->createCol()->setText($document->getName()))
                    ->addCol($tb->createCol()->setText($userModel->getUserById($document->getIdAuthor())->getFullname()))
            ;

            $dbStatuses = $metadataModel->getAllValuesForIdMetadata($metadataModel->getMetadataByName('status', 'documents')->getId());

            foreach($dbStatuses as $dbs) {
                if($dbs->getValue() == $document->getStatus()) {
                    $docuRow->addCol($tb->createCol()->setText($dbs->getName()));
                }
            }

            $folderName = '-';

            if($document->getIdFolder() !== NULL) {
                $folder = $folderModel->getFolderById($document->getIdFolder());
                $folderName = $folder->getName();
            }

            $docuRow->addCol($tb->createCol()->setText($folderName));
                
            $tb->addRow($docuRow);
        }
    }

    echo $tb->build();
}*/

//$query = htmlspecialchars($_POST['q']);

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder',
        'Shared from',
        'Shared to',
        'Shared by'
    );

    $headerRow = null;
    $documents = [];

    if(!is_null($user)) {
        $documents = $documentModel->getSharedDocumentsWithUser($user->getId());
    }

    if(empty($documents)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($documents as $document) {
            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit')
            );

            if(is_null($headerRow)) {
                $row = $tb->createRow();

                foreach($headers as $header) {
                    $col = $tb->createCol()->setText($header)
                                           ->setBold();

                    if($header == 'Actions') {
                        $col->setColspan(count($actionLinks));
                    }

                    $row->addCol($col);
                }

                $headerRow = $row;

                $tb->addRow($row);
            }

            $docuRow = $tb->createRow();
            $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawBulkActions()">'));

            foreach($actionLinks as $actionLink) {
                $docuRow->addCol($tb->createCol()->setText($actionLink));
            }

            $docuRow->addCol($tb->createCol()->setText($document->getName()))
                    ->addCol($tb->createCol()->setText($userModel->getUserById($document->getIdAuthor())->getFullname()))
            ;

            $dbStatuses = $metadataModel->getAllValuesForIdMetadata($metadataModel->getMetadataByName('status', 'documents')->getId());

            foreach($dbStatuses as $dbs) {
                if($dbs->getValue() == $document->getStatus()) {
                    $docuRow->addCol($tb->createCol()->setText($dbs->getName()));
                }
            }

            $folderName = '-';

            if($document->getIdFolder() !== NULL) {
                $folder = $folderModel->getFolderById($document->getIdFolder());
                $folderName = $folder->getName();
            }

            $docuRow->addCol($tb->createCol()->setText($folderName));

            if(isset($user)) {
                $documentSharing = $documentModel->getDocumentSharingByIdDocumentAndIdUser($user->getId(), $document->getId());

                $documentSharingAuthor = $userModel->getUserById($documentSharing['id_author']);

                $dateFrom = date('Y-m-d', strtotime($documentSharing['date_from']));
                $dateTo = date('Y-m-d', strtotime($documentSharing['date_to']));

                $docuRow->addCol($tb->createCol()->setText($dateFrom))
                        ->addCol($tb->createCol()->setText($dateTo))
                        ->addCol($tb->createCol()->setText($documentSharingAuthor->getFullname()))
                ;
            } else {
                $docuRow->addCol($tb->createCol()->setText('-'))
                        ->addCol($tb->createCol()->setText('-'))
                        ->addCol($tb->createCol()->setText('-'))
                ;
            }
                
            $tb->addRow($docuRow);
        }
    }

    echo $tb->build();

?>