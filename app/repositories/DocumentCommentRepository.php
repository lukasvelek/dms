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
     * Method that checks if a given user is able to delete the comment. If true it deletes it and if not it throws exception.
     * 
     * User has to be owner of the comment or owner of the document to be able to perform this action.
     */
    public function deleteComment(int $idComment, int $idCallingUser) {
        $comment = $this->documentCommentModel->getCommentById($idComment);
        $document = $this->documentModel->getDocumentById($comment->getIdDocument());

        if($comment->getIdAuthor() != $idCallingUser &&
           $document->getIdAuthor() != $idCallingUser) {
            throw new \Exception('User must be owner of the comment or the document to be able to delete a comment.');
        }

        $this->documentCommentModel->deleteComment($idComment);

        return true;
    }
}

?>