<?php

namespace DMS\Services;

use DMS\Core\AppConfiguration;
use DMS\Core\CacheManager;
use DMS\Core\FileManager;
use DMS\Core\Logger\Logger;
use DMS\Models\ServiceModel;

class LogRotateService extends AService {
    private array $cfg;

    public function __construct(Logger $logger, ServiceModel $serviceModel, CacheManager $cm) {
        parent::__construct('LogRotateService', $logger, $serviceModel, $cm);
        
        $this->loadCfg();
    }

    public function run() {
        $fm = FileManager::getTemporaryObject();

        $this->startService();

        $files = [];
        $fm->readFilesInFolder(AppConfiguration::getLogDir(), $files);

        $toDelete = [];
        $newFilenames = [];
        foreach($files as $f) {
            $filename = explode('/', $f)[1];
            $filename = explode('.', $filename)[0];
            $date = explode('_', $filename)[1];

            $days = $this->scfg['files_keep_length'];

            $maxOldDate = time() - (60 * 60 * 24 * $days);

            $year = explode('-', $date)[0];
            $month = explode('-', $date)[1];
            $day = explode('-', $date)[2];

            if(strtotime($date) < $maxOldDate) {
                $toDelete[] = $f;
                $newFilenames[$f] = ['dir' => ($year . '/' . $month . '/' . $day . '/'), 'name' => $filename];
            }
        }

        if($this->scfg['archive_old_logs'] == '1') {
            // archive
            $this->log('Found ' . count($toDelete) . ' log files to archive', __METHOD__);

            foreach($newFilenames as $f => $nf) {
                $fm->createDirectory(AppConfiguration::getLogDir() . $nf['dir']);
            }

            foreach($toDelete as $td) {
                $fm->moveFileToDirectory($td, AppConfiguration::getLogDir() . $newFilenames[$td]['dir'] . $newFilenames[$td]['name'] . '.log');
            }
        } else {
            //delete
            $this->log('Found ' . count($toDelete) . ' log files to delete', __METHOD__);

            foreach($toDelete as $td) {
                unlink($td);
            }
        }

        $this->stopService();
    }
}

?>