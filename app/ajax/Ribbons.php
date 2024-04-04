<?php

use DMS\Exceptions\AException;
use DMS\Exceptions\ValueIsNullException;
use DMS\UI\LinkBuilder;

require_once('Ajax.php');

$action = null;

if(isset($_GET['action'])) {
    $action = htmlspecialchars($_GET['action']);
} else if(isset($_POST['action'])) {
    $action = htmlspecialchars($_POST['action']);
}

if($action == null) {
    throw new ValueIsNullException('$action');
}

try {
    echo($action());
} catch(AException $e) {
    echo('<b>Exception: </b>' . $e->getMessage() . '<br><b>Stack trace: </b>' . $e->getTraceAsString());
    exit;
}

function getDropdownRibbonContent() {
    global $ribbonModel;

    $results = [];

    $idRibbon = htmlspecialchars($_GET['id_ribbon']);

    $childRibbons = $ribbonModel->getRibbonsForIdParentRibbon($idRibbon);

    if($childRibbons === FALSE || $childRibbons === NULL || empty($childRibbons)) {
        $results['error'] = 'No data found';
    } else {
        foreach($childRibbons as $ribbon) {
            if($ribbon->hasImage()) {
                $results[] = LinkBuilder::createImgLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), $ribbon->getImage(), 'general-link', true);
            } else {
                $results[] = LinkBuilder::createLink($ribbon->getPageUrl() . '&id_ribbon=' . $ribbon->getId(), $ribbon->getName(), 'general-link', true);
            }
        }
    }

    $result = '';

    if(array_key_exists('error', $results)) {
        $result = 'No data found';
    } else {
        $i = 0;
        foreach($results as $r) {
            if(($i + 1) == count($results)) {
                $result .= $r;
            } else {
                $result .= $r . '<br>';
            }

            $i++;
        }
    }

    echo $result;
}

exit;

?>