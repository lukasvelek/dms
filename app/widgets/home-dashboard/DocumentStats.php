<?php

namespace DMS\Widgets\HomeDashboard;

use DMS\Constants\DocumentStatus;
use DMS\Models\DocumentModel;
use DMS\UI\LinkBuilder;
use DMS\Widgets\AWidget;

class DocumentStats extends AWidget {
    private DocumentModel $documentModel;

    public function __construct(DocumentModel $documentModel) {
        parent::__construct();
        
        $this->documentModel = $documentModel;
    }

    public function render() {
        $data = $this->documentModel->getLastDocumentStatsEntry();

        $documentCount = $data['total_count'];
        $shreddedCount = $data['shredded_count'];
        $archivedCount = $data['archived_count'];
        $newCount = $data['new_count'];
        $waitingForArchivationCount = $data['waiting_for_archivation_count'];
        $lastUpdateDate = $data['date_created'];

        $this->add('Total documents', $documentCount);
        $this->add('Shredded documents', $shreddedCount);
        $this->add('Archived documents', $archivedCount);
        $this->add('New documents', $newCount);
        $this->add('Documents waiting for archivation', $waitingForArchivationCount);

        $this->updateLink(LinkBuilder::createAdvLink(array('page' => 'UserModule:Widgets:updateDocumentStats'), 'Update'), $lastUpdateDate);

        return parent::render();
    }
}

?>