<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

class CSVGenerator extends ADocumentReport implements IGeneratable {
    private const FILE_EXTENSION = 'csv';

    public function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models, ?int $idReport) {
        parent::__construct($eec, $fm, $fsm, $sqlResult, $idCallingUser, $models, $idReport);
    }

    public function generate(?string $filename = null): array|bool {
        $metadata = parent::$defaultMetadata;
        $fileRow = [];

        $this->loadCustomMetadata();

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

        $total = $this->sqlResult->num_rows;
        $current = 0;
        $lastFinished = 0;

        foreach($this->sqlResult as $row) {
            if(($current % ADocumentReport::UPDATE_COUNT_CONST) == 0) {
                $finished = $this->calcFinishedPercent($current, $total);

                if($finished > $lastFinished) {
                    $this->updateFinishedProcent($finished, $this->idReport);
                    $lastFinished = $finished;
                }
            }

            $dataRow = '';

            $i = 0;
            foreach($metadata as $m) {
                $text = '-';
                if(isset($row[$m]) && ($row[$m] !== NULL)) {
                    if(!in_array($m, $this->customMetadataValues)) {
                        $text = $row[$m];
                    } else {
                        $text = $this->getCustomMetadataValue($m, $row[$m]);
                    }
                }

                if(($i + 1) == count($metadata)) {
                    $dataRow .= $text;
                } else {
                    $dataRow .= $text . ';';
                }

                $i++;
            }

            $fileRow[] = $dataRow . "\r\n";

            if(($current % parent::UPDATE_COUNT_CONST) == 0 || $current == $total) {
                $this->saveFile($fileRow, $filename, self::FILE_EXTENSION);
                $fileRow = [];
            }

            $current++;
        }

        return true;
    }
}

?>