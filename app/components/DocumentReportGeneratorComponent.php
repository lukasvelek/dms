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
        $fileRow = [
            'id;id_folder;name;date_created;date_updated' . "\r\n"
        ];

        foreach($sqlResult as $row) {
            $fileRow[] = $row['id'] . ';' . ($row['id_folder'] ?? '-') . ';' . $row['name'] . ';' . $row['date_created'] . "\r\n";
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