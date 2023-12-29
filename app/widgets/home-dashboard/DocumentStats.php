<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Constants\DocumentStatus;
use DMS\Models\DocumentModel;
use DMS\Widgets\AWidget;
use DMS\Widgets\IRenderable;

class DocumentStats extends AWidget {
    private DocumentModel $documentModel;

    public function __construct(DocumentModel $documentModel) {
        parent::__construct();
        
        $this->documentModel = $documentModel;
    }

    public function render() {
        $documents = $this->documentModel->getAllDocuments();
        $documentCount = count($documents);
        $shreddedCount = $archivedCount = $waitingForArchivationCount = $newCount = 0;

        foreach($documents as $document) {
            switch($document->getStatus()) {
                case DocumentStatus::SHREDDED:
                    $shreddedCount++;
                    break;

                case DocumentStatus::ARCHIVED:
                    $archivedCount++;
                    break;

                case DocumentStatus::ARCHIVATION_APPROVED:
                    $waitingForArchivationCount++;
                    break;

                case DocumentStatus::NEW:
                    $newCount++;
                    break;
            }
        }

        $this->add('Total documents', $documentCount);
        $this->add('Shredded documents', $shreddedCount);
        $this->add('Archived documents', $archivedCount);
        $this->add('New documents', $newCount);
        $this->add('Documents waiting for archivation', $waitingForArchivationCount);

        return parent::render();
    }
}

?>