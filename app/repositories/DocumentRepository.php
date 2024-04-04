<?php

namespace DMS\Repositories;

use DMS\Authorizators\DocumentAuthorizator;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\Metadata\DocumentCommentsMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;

/**
 * DocumentRepository is a repository that is used to perform actions on documents while checking the privileges.
 * 
 * @author Lukas Velek
 */
class DocumentRepository extends ARepository {
    private DocumentModel $documentModel;
    private DocumentAuthorizator $documentAuthorizator;
    private DocumentCommentModel $documentCommentModel;
    
    /**
     * Class constructor
     * 
     * @param Database $db Database instance
     * @param Logger $logger Logger instance
     * @param DocumentModel $documentModel DocumentModel instance
     * @param DocumentAuthorizator $documentAuthorizator DocumentAuthorizator instance
     * @param DocumentCommentModel $documentCommentModel DocumentCommentModel instance
     */
    public function __construct(Database $db, Logger $logger, DocumentModel $documentModel, DocumentAuthorizator $documentAuthorizator, DocumentCommentModel $documentCommentModel) {
        parent::__construct($db, $logger);

        $this->documentModel = $documentModel;
        $this->documentAuthorizator = $documentAuthorizator;
        $this->documentCommentModel = $documentCommentModel;
    }

    /**
     * Adds a comment to given document
     * 
     * @param int $idDocument Document ID
     * @param string $text Comment text
     * @param int $idAuthor Comment author
     * @return mixed Database query result
     */
    public function addCommentToDocument(int $idDocument, string $text, int $idAuthor) {
        $data = [
            DocumentCommentsMetadata::ID_AUTHOR => $idAuthor,
            DocumentCommentsMetadata::ID_DOCUMENT => $idDocument,
            DocumentCommentsMetadata::TEXT => $text
        ];

        return $this->documentCommentModel->insertComment($data);
    }

    /**
     * Duplicates given document
     * 
     * @param int $idDocument Original document ID
     * @param bool $returnId True if the newly created document's ID should be returned or false if database query result should be returned
     * @return int|mixed Document ID (int) if $returnId is true or mixed as the database query result
     */
    public function duplicateDocument(int $idDocument, bool $returnId = false) {
        $docuRow = $this->documentModel->getDocumentRowById($idDocument);

        $data = [];
        foreach($docuRow as $key => $value) {
            if(!in_array($key, ['id', 'date_created', 'date_updated']) && $value !== NULL) {
                $data[$key] = $value;
            }
        }

        return $this->createDocument($data, $returnId);
    }

    /**
     * Creates a document with given metadata values
     * 
     * @param array $data Document data
     * @param bool $returnId True if the newly created document's ID should be returned or false if database query result should be returned
     * @return int|mixed Document ID (int) if $returnId is true or mixed as the database query result
     */
    public function createDocument(array $data, bool $returnId = false) {
        return $this->documentModel->insertNewDocument($data, $returnId);
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