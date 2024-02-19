<?php

namespace DMS\Components;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;

/**
 * Component that contains useful functions for sharding documents
 * 
 * @author Lukas Velek
 */
class SharingComponent extends AComponent {
    private DocumentModel $documentModel;

    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param DocumentModel $documentModel DocumentModel instance
     */
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
    }

    /**
     * Shares a given document to user for a period of time
     * 
     * @param int $idDocument Document ID
     * @param int $idAuthor Author ID
     * @param int $idUser User ID
     * @param null|string $dateFrom Date from
     * @param string $dateTo Date to
     * @param string $hash Sharing hash
     * @return mixed Result of the database operation
     */
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

    /**
     * Unshares a document
     * 
     * @param int $idShare Sharing ID
     * @return mixed Result of the database operation
     */
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