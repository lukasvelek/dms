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

        $result = $qb->insert('user_widgets', 'id_user', 'location', 'widget_name')
                     ->values(':id_user', ':location', ':widget_name')
                     ->setParams(array(
                        ':id_user' => $idUser,
                        ':location' => $location,
                        ':widget_name' => $name
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function updateWidgetForIdUser(int $idUser, string $location, string $name) {
        $qb = $this->qb(__METHOD__);

        $result = $qb->update('user_widgets')
                     ->set(array('widget_name' => ':name'))
                     ->where('location=:location')
                     ->andWhere('id_user=:id_user')
                     ->setParams(array(
                        ':name' => $name,
                        ':location' => $location,
                        ':id_user' => $idUser
                     ))
                     ->execute()
                     ->fetch();

        return $result;
    }

    public function getWidgetForIdUserAndLocation(int $idUser, string $location) {
        $qb = $this->qb(__METHOD__);

        $row = $qb->select('*')
                  ->from('user_widgets')
                  ->where('id_user=:id_user')
                  ->andWhere('location=:location')
                  ->setParams(array(
                    ':id_user' => $idUser,
                    ':location' => $location
                  ))
                  ->execute()
                  ->fetchSingle();

        return $row;
    }
}

?>