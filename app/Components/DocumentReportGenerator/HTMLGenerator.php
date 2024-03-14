<?php

namespace DMS\Components\DocumentReports;

use DMS\Components\ExternalEnumComponent;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;
use DMS\UI\TableBuilder\TableBuilder;

class HTMLGenerator extends ADocumentReport implements IGeneratable {
    private const FILE_EXTENSION = 'html';

    public function __construct(ExternalEnumComponent $eec, FileManager $fm, FileStorageManager $fsm, mixed $sqlResult, int $idCallingUser, array $models) {
        parent::__construct($eec, $fm, $fsm, $sqlResult, $idCallingUser, $models);
    }

    public function generate(?string $filename = null): array|bool {
        $metadata = parent::$defaultMetadata;

        $this->loadCustomMetadata();

        $tb = TableBuilder::getTemporaryObject();

        $tr = $tb->createRow();
        foreach($metadata as $m) {
            $tr->addCol($tb->createCol()->setBold()->setText($m));
        }
        $tb->addRow($tr);

        foreach($this->sqlResult as $row) {
            $tr = $tb->createRow();
            foreach($metadata as $m) {
                $td = $tb->createCol();

                $text = '-';
                if(isset($row[$m]) && ($row[$m] !== NULL)) {
                    if(!in_array($m, $this->customMetadataValues)) {
                        $text = $row[$m];
                    } else {
                        $text = $this->getCustomMetadataValue($m, $row[$m]);
                    }
                }

                $td->setText($text);
                $tr->addCol($td);
            }

            $tb->addRow($tr);
        }

        return $this->saveFile($tb->build(), $filename, self::FILE_EXTENSION);
    }
}

?>