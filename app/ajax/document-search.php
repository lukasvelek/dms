<?php

use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

if(isset($_POST['q']) && isset($_POST['idFolder'])) {
    $query = htmlspecialchars($_POST['q']);
    $idFolder = htmlspecialchars($_POST['idFolder']);

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder'
    );

    $headerRow = null;

    if($idFolder == 'null') {
        $idFolder = null;
    }

    $documents = $documentModel->getDocumentsForName($query, $idFolder);

    if(empty($documents)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($documents as $document) {
            $actionLinks = array(
                '<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onchange="drawBulkActions()">',
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share')
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
} else if(!isset($_POST['q']) && isset($_POST['idFolder'])) {
    $idFolder = htmlspecialchars($_POST['idFolder']);

    $tb = TableBuilder::getTemporaryObject();

    $headers = array(
        '<input type="checkbox" id="select-all" onchange="selectAllDocumentEntries()">',
        'Actions',
        'Name',
        'Author',
        'Status',
        'Folder'
    );

    $headerRow = null;

    if($idFolder == 'null') {
        $idFolder = null;
    }

    $documents = [];

    if(is_null($idFolder)) {
        $documents = $documentModel->getStandardDocuments();
    } else {
        $documents = $documentModel->getStandardDocumentsInIdFolder($idFolder);
    }

    if(empty($documents)) {
        $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
    } else {
        foreach($documents as $document) {
            $actionLinks = array(
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showInfo', 'id' => $document->getId()), 'Information'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showEdit', 'id' => $document->getId()), 'Edit'),
                LinkBuilder::createAdvLink(array('page' => 'UserModule:SingleDocument:showShare', 'id' => $document->getId()), 'Share')
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

            $docuRow->addCol($tb->createCol()->setText('<input type="checkbox" id="select" name="select[]" value="' . $document->getId() . '" onupdate="drawBulkActions()" onchange="drawBulkActions()">'));
            
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
}

?>