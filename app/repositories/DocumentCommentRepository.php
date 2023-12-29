<?php

namespace DMS\Repositories;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;
use DMS\Models\DocumentCommentModel;
use DMS\Models\DocumentModel;

class DocumentCommentRepository extends ARepository {
    private DocumentCommentModel $documentCommentModel;
    private DocumentModel $documentModel;

    public function __construct(Database $db, Logger $logger, DocumentCommentModel $documentCommentModel, DocumentModel $documentModel) {
        parent::__construct($db, $logger);

        $this->documentCommentModel = $documentCommentModel;
        $this->documentModel = $documentModel;
    }

    /**
     * Checks if a given user is able to delete the comment. If true it deletes it and if not it returns false.
     * 
     * User has to be owner of the comment or owner of the document to be able to perform this action.
     * 
     * @param int $idComment Comment ID
     * @param int $IdCallingUser Calling user ID
     * @return bool True if comment has been deleted and false if not
     */
    public function deleteComment(int $idComment, int $idCallingUser) {
        $comment = $this->documentCommentModel->getCommentById($idComment);
        $document = $this->documentModel->getDocumentById($comment->getIdDocument());

        if($comment->getIdAuthor() != $idCallingUser &&
           $document->getIdAuthor() != $idCallingUser) {
            return false;
        }

        $this->documentCommentModel->deleteComment($idComment);

        return true;
    }

    /**
     * Checks if a given user is able to create a new comment. If true it creates a new comment and if not it returns false.
     * 
     * @param int $idCallingUser Calling user ID (Comment Author)
     * @param int $idDocument Document ID
     * @param string $text Comment text
     * @return bool True if a new comment has been created successfully and false if not
     */
    public function insertComment(int $idCallingUser, int $idDocument, string $text) {
        $data = array(
            'id_author' => $idCallingUser,
            'id_document' => $idDocument,
            'text' => $text
        );

        $this->documentCommentModel->insertComment($data);
        
        return true;
    }
}

?>