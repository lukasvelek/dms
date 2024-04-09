<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

class JSONGenerator extends ADocumentReport implements IGeneratable {
    private const FILE_EXTENSION = 'json';

    public function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models, ?int $idReport) {
        parent::__construct($eec, $fm, $fsm, $sqlResult, $idCallingUser, $models, $idReport);
    }

    public function generate(?string $filename = null): array|bool {
        $metadata = parent::$defaultMetadata;

        $this->loadCustomMetadata();

        $data = [];

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

            foreach($metadata as $m) {
                $text = '-';
                if(isset($row[$m]) && $row[$m] !== NULL) {
                    if(!in_array($m, $this->customMetadataValues)) {
                        $text = $row[$m];
                    } else {
                        $text = $this->getCustomMetadataValue($m, $row[$m]);
                    }
                }
                
                $data[$row['id']][$m] = $text;
            }

            $current++;
        }

        return $this->saveFile(json_encode($data), $filename, self::FILE_EXTENSION);
    }
}

?>