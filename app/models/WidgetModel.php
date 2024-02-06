<?php

namespace DMS\Models;

use DMS\Core\DB\Database;
use DMS\Core\Logger\Logger;

class WidgetModel extends AModel {
    public function __construct(Database $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function removeAllWidgetsForIdUser(int $idUser) {
        return $this->deleteByCol('id_user', $idUser, 'user_widgets');
    }

    public function insertWidgetForIdUser(int $idUser, string $location, string $name) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->insert('user_widgets', ['id_user', 'location', 'widget_name'])
            ->values([$idUser, $location, $name])
            ->execute();

        return $qb->fetchAll();
    }

    public function updateWidgetForIdUser(int $idUser, string $location, string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_widgets')
            ->set(['widget_name' => $name])
            ->where('location = ?', [$location])
            ->andWhere('id_user = ?', [$idUser])
            ->execute();

        return $qb->fetchAll();
    }

    public function getWidgetForIdUserAndLocation(int $idUser, string $location) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_widgets')
            ->where('id_user = ?', [$idUser])
            ->andWhere('location = ?', [$location])
            ->execute();

        return $qb->fetch();
    }
}

?>