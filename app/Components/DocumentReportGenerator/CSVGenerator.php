<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

class CSVGenerator extends ADocumentReport implements IGeneratable {
    private const FILE_EXTENSION = 'csv';

    public function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models) {
        parent::__construct($eec, $fm, $fsm, $sqlResult, $idCallingUser, $models);
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

        foreach($this->sqlResult as $row) {
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
        }

        return $this->saveFile($fileRow, $filename, self::FILE_EXTENSION);
    }
}

?>