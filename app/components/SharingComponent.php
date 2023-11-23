<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;

class SharingComponent extends AComponent {
    private DocumentModel $documentModel;

    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
    }

    public function shareDocument(int $idDocument, int $idAuthor, int $idUser, ?string $dateFrom, string $dateTo, string $hash) {
        $data = array(
            'id_document' => $idDocument,
            'id_user' => $idUser,
            'id_author' => $idAuthor,
            'date_to' => $dateTo,
            'hash' => $hash
        );

        if(!is_null($dateFrom)) {
            $data['date_from'] = $dateFrom;
        }

        $result = $this->documentModel->insertDocumentSharing($data);

        if($result == true) {
            $this->logger->info('Shared document #' . $idDocument . ' to user #' . $idUser, __METHOD__);
        } else {
            $this->logger->error('Document #' . $idDocument . ' could not be shared to user #' . $idUser, __METHOD__);
        }

        return $result;
    }

    public function unshareDocument(int $idShare) {
        $result = $this->documentModel->removeDocumentSharing($idShare);

        if($result == true) {
            $this->logger->info('Unshared document with share #' . $idShare, __METHOD__);
        } else {
            $this->logger->error('Document share #' . $idShare . ' could not be unshared', __METHOD__);
        }

        return $result;
    }
}

?>