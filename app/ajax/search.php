<?php

use DMS\UI\LinkBuilder;
use DMS\UI\TableBuilder\TableBuilder;

require_once('Ajax.php');

if(isset($_POST['q'])) {
    $query = htmlspecialchars($_POST['q']);

    $tb = TableBuilder::getTemporaryObject();

        $headers = array(
            'Actions',
            'Name',
            'Author',
            'Status',
            'Folder'
        );

        $headerRow = null;

        $documents = $documentModel->getDocumentsForName($query);

        if(empty($documents)) {
            $tb->addRow($tb->createRow()->addCol($tb->createCol()->setText('No data found')));
        } else {
            foreach($documents as $document) {
                $actionLinks = array(
                    '<input type="checkbox" name="select[]" value="' . $document->getId() . '">',
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