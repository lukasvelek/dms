<?php

namespace DMS\Components;

use DMS\Core\FileManager;

class DocumentReportGeneratorComponent extends AComponent {
    private array $models;
    private FileManager $fm;

    public function __construct(array $models, FileManager $fm) {
        $this->models = $models;
        $this->fm = $fm;
    }

    public function generateReport($sqlResult, int $idUser, ?string $filename = null) {
        $fileRow = [];

        $metadata = [
            'id', 'id_folder', 'name', 'date_created', 'date_updated', 'id_officer', 'id_manager', 'status', 'id_group', 'is_deleted', 'rank', 'id_folder', 'file', 'shred_year', 'after_shred_action', 'shredding_status', 'id_archive_document', 'id_archive_box', 'id_archive_archive'
        ];

        // CUSTOM METADATA

        $customMetadata = $this->models['metadataModel']->getAllMetadataForTableName('documents');

        foreach($customMetadata as $cm) {
            if($cm->getIsSystem()) continue;

            $metadata[] = $cm->getName();
        }

        // END OF CUSTOM METADATA

        $headerRow = '';

        $i = 0;
        foreach($metadata as $m) {
            if(($i + 1) == count($metadata)) {
                $headerRow .= $m;
            } else {
                $headerRow .= $m . ';';
            }
            $i++;
        }

        $fileRow[] = $headerRow . "\r\n";

        foreach($sqlResult as $row) {
            $dataRow = '';

            $i = 0;
            foreach($metadata as $m) {
                $text = '-';
                if(isset($row[$m]) && ($row[$m] !== NULL)) {
                    $text = $row[$m];
                }

                if(($i + 1) == count($metadata)) {
                    $dataRow .= $text;
                } else {
                    $dataRow .= $text . ';';
                }

                $i++;
            }

            $fileRow[] = $dataRow . "\r\n";
        }

        if($filename === NULL) {
            $filename = $this->generateFilename($idUser);
        }

        $defaultFilename = $filename;
        $i = 1;
        while($this->fm->fileExists('cache/' . $filename)) {
            $filename = explode('.', $defaultFilename)[0];
            $filename = $filename . ' (' . $i . ').csv';
            $i++;
        }

        $writeResult = $this->fm->write('cache/' . $filename, $fileRow, false);

        if($writeResult === TRUE) {
            return 'cache/' . $filename;
        } else {
            return false;
        }
    }

    private function generateFilename(int $idUser) {
        // %ID_USER%_%DATE_OF_CREATION%_document_report.csv

        return $idUser . '_' . date('Y-m-d_H-i-s') . '_document_report.csv';
    }
}

?>