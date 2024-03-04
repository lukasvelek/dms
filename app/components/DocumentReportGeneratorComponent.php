<?php

namespace DMS\Components;

use DMS\Components\DocumentReports\CSVGenerator;
use DMS\Components\DocumentReports\HTMLGenerator;
use DMS\Components\DocumentReports\JSONGenerator;
use DMS\Constants\CacheCategories;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\FileStorageManager;

/**
 * Component that generates document reports.
 * 
 * @author Lukas Velek
 */
class DocumentReportGeneratorComponent extends AComponent {
    private array $models;
    private FileManager $fm;
    private ExternalEnumComponent $eec;
    private FileStorageManager $fsm;

    /**
     * Class constructor
     * 
     * @param array $models Database Models array
     * @param FileManager $fm FileManager instance
     * @param ExternalEnumComponent $eec ExternalEnumComponent instance
     */
    public function __construct(array $models, FileManager $fm, ExternalEnumComponent $eec, FileStorageManager $fsm) {
        $this->models = $models;
        $this->fm = $fm;
        $this->eec = $eec;
        $this->fsm = $fsm;
    }

    /**
     * Method generates the document report and saves it to cache and if successful it returns the result file path
     * 
     * @param mixed $sqlResult The result of calling a SQL string
     * @param int $idUser ID of the user for whom the document report is generated
     * @param null|string $filename Explicit filename
     */
    public function generateReport($sqlResult, int $idUser, string $fileFormat, ?string $filename = null) {
        $engine = null;

        switch($fileFormat) {
            case 'csv':
                $engine = new CSVGenerator($this->eec, $this->fm, $this->fsm, $sqlResult, $idUser, $this->models);
                break;

            case 'html':
                $engine = new HTMLGenerator($this->eec, $this->fm, $this->fsm, $sqlResult, $idUser, $this->models);
                break;

            case 'json':
                $engine = new JSONGenerator($this->eec, $this->fm, $this->fsm, $sqlResult, $idUser, $this->models);
                break;

            default:
                die('Undefined file format (exception thrown in ' . __CLASS__ . '::' . __METHOD__ . '()');
                break;
        }

        if($engine === NULL) {
            return false;
        }

        return $engine->generate($filename);
    }
}

?>