<?php

namespace DMS\Services;

use DMS\Constants\DocumentAfterShredActions;
use DMS\Constants\DocumentRank;
use DMS\Constants\DocumentShreddingStatus;
use DMS\Constants\DocumentStatus;
use DMS\Constants\FileStorageTypes;
use DMS\Constants\Groups;
use DMS\Constants\Metadata\DocumentMetadata;
use DMS\Constants\ServiceMetadata;
use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\FileStorageManager;
use DMS\Core\Logger\Logger;
use DMS\Models\GroupModel;
use DMS\Models\ServiceModel;
use DMS\Repositories\DocumentCommentRepository;
use DMS\Repositories\DocumentRepository;

class ExtractionService extends AService {
    private DocumentRepository $documentRepository;
    private DocumentCommentRepository $documentCommentRepository;
    private GroupModel $groupModel;
    private FileStorageManager $fileStorageManager;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm, DocumentRepository $documentRepository, DocumentCommentRepository $documentCommentRepository, FileStorageManager $fsm, GroupModel $groupModel) {
        parent::__construct('ExtractionService', $logger, $serviceModel, $cm);

        $this->documentRepository = $documentRepository;
        $this->documentCommentRepository = $documentCommentRepository;
        $this->fileStorageManager = $fsm;
        $this->groupModel = $groupModel;

        $this->loadCfg();
    }

    public function run() {
        $this->startService();

        if($this->scfg[ServiceMetadata::EXTRACTION_PATH] == '') {
            $this->log('No extraction path is defined.', __METHOD__);
            $this->stopService();
            return;
        }

        // FILES
        $files = [];
        $this->fileStorageManager->fm->readFilesInFolder($this->scfg[ServiceMetadata::EXTRACTION_PATH], $files);

        $this->log('Found ' . count($files) . ' files to be extracted and imported', __METHOD__);

        if(count($files) > 0) {
            // work

            $i = 1;
            foreach($files as $file) {
                $filename = explode('\\', $file)[count(explode('\\', $file)) - 1];

                $this->log('Processing file #' . $i, __METHOD__);

                $idGroup = 1;
                $this->log('Found group with ID #' . $idGroup, __METHOD__);

                $data = [
                    DocumentMetadata::NAME => $filename,
                    DocumentMetadata::ID_AUTHOR => AppConfiguration::getIdServiceUser(),
                    DocumentMetadata::ID_MANAGER => AppConfiguration::getIdServiceUser(),
                    DocumentMetadata::STATUS => DocumentStatus::NEW,
                    DocumentMetadata::RANK => DocumentRank::PUBLIC,
                    DocumentMetadata::SHRED_YEAR => (date('Y') + 10),
                    DocumentMetadata::AFTER_SHRED_ACTION => DocumentAfterShredActions::DELETE,
                    DocumentMetadata::SHREDDING_STATUS => DocumentShreddingStatus::NO_STATUS,
                    DocumentMetadata::ID_GROUP => $idGroup
                ];

                if($this->scfg[ServiceMetadata::DOCUMENT_FOLDER_FOR_IMPORTS] != '-1') {
                    $data[DocumentMetadata::ID_FOLDER] = $this->scfg[ServiceMetadata::DOCUMENT_FOLDER_FOR_IMPORTS];
                }

                $id = $this->documentRepository->createDocument($data, true);
                $this->log('Created document #' . $id, __METHOD__);

                if(is_integer($id)) {
                    $text = 'Document created by ' . $this->name . ' service.';
                    $this->documentCommentRepository->insertComment(AppConfiguration::getIdServiceUser(), $id, $text);
                    $this->log('Created comment for document #' . $id, __METHOD__);
                }

                if($this->scfg[ServiceMetadata::DELETE_EXTRACTED_FILES] == '1') {
                    $this->fileStorageManager->fm->deleteFile($file);
                    $this->log('Deleting extracted files is enabled. Deleting file \'' . $filename . '\' (#' . $i . ')', __METHOD__);
                } else {
                    $this->fileStorageManager->storeFile($file, $filename, $this->fileStorageManager->getDefaultLocationForStorageType(FileStorageTypes::FILES));
                    $this->fileStorageManager->fm->deleteFile($file);
                    $this->log('Deleting extracted files is disabled. File \'' . $filename . '\' (#' . $i . ') stored in file storage.', __METHOD__);
                }

                $i++;
            }
        }

        $this->stopService();
    }
}

?>