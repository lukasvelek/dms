<?php

namespace DMS\Repositories;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentModel;

class DocumentRepository extends ARepository {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;
    
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
    }

    public function duplicateDocument(int $idDocument) {
        $docuRow = $this->documentModel->getDocumentRowById($idDocument);

        $data = [];
        foreach($docuRow as $key => $value) {
            if(!in_array($key, ['id', 'date_created', 'date_updated']) && $value !== NULL) {
                $data[$key] = $value;
            }
        }

        return $this->createDocument($data);
    }

    public function createDocument(array $data) {
        return $this->documentModel->insertNewDocument($data);
    }

    public static function composeCreateDocumentDataArray(array $post, int $idUser, mixed $file) {
        $data = [];

        $add = function(string $key, bool $delete = true) use ($post) {
            $value = $post[$key];

            if($delete == true) {
                unset($post[$key]);
            }

            return htmlspecialchars($value);
        };

        $data['name'] = $add('name');
        $data['id_manager'] = $add('manager');
        $data['status'] = $add('status');
        $data['id_group'] = $add('id_group');
        $data['id_author'] = $idUser;
        $data['shred_year'] = $add('shred_year');
        $data['after_shred_action'] = $add('after_shred_action');
        $data['shredding_status'] = DocumentShreddingStatus::NO_STATUS;

        if($post['id_folder'] != '-1') {
            $data['id_folder'] = $add('id_folder');
        }

        if($file != null) {
            $data['file'] = $file['name'];
        }

        $data = array_merge($data, $post);

        return $data;
    }
}

?>