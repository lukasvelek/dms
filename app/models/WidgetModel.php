<?php

namespace DMS\Models;

use DMS\Constants\Metadata\UserWidgetMetadata;
use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class WidgetModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeAllWidgetsForIdUser(int $idUser) {
        return $this->deleteByCol(UserWidgetMetadata::ID_USER, $idUser, 'user_widgets');
    }

    public function insertWidgetForIdUser(int $idUser, string $location, string $name) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->insert('user_widgets', [UserWidgetMetadata::ID_USER, UserWidgetMetadata::LOCATION, UserWidgetMetadata::WIDGET_NAME])
            ->values([$idUser, $location, $name])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateWidgetForIdUser(int $idUser, string $location, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_widgets')
            ->set([UserWidgetMetadata::WIDGET_NAME => $name])
            ->where(UserWidgetMetadata::LOCATION . ' = ?', [$location])
            ->andWhere(UserWidgetMetadata::ID_USER . ' = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getWidgetForIdUserAndLocation(int $idUser, string $location) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_widgets')
            ->where(UserWidgetMetadata::ID_USER . ' = ?', [$idUser])
            ->andWhere(UserWidgetMetadata::LOCATION . ' = ?', [$location])
            ->execute();

        return $qb->fetch();
    }
}

?>