<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\WidgetLocations;
use DMS\Modules\APresenter;

class HomePagePresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('HomePage', 'Home page');

        $this->getActionNamesFromClass($this);
    }

    protected function showHomePage() {
        global $app;

        $documentStats = $app->documentModel->getLastDocumentStatsEntry();
        $processStats = $app->processModel->getLastProcessStatsEntry();

        if(is_null($documentStats) || is_null($processStats)) {
            $app->redirect('Widgets:updateAllStats');
        }

        $idUser = $app->user->getId();

        $template = $this->templateManager->loadTemplate('app/modules/UserModule/presenters/templates/homepage.html');

        $data = array(
            '$PAGE_TITLE$' => 'Home page'
        );

        $widget00 = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET00);
        $widget01 = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET01);
        $widget10 = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET10);
        $widget11 = $app->widgetModel->getWidgetForIdUserAndLocation($idUser, WidgetLocations::HOME_DASHBOARD_WIDGET11);

        $emptyWidget = function() {
            return 
            '<div class="widget">
                <p>No widget</p>
                <!--<a class="general-link" href="?page=UserModule:Settings:showDashboardWidgets">Set up</a>-->
            </div>';
        };

        if(!is_null($widget00)) {
            $data['$WIDGET0-0$'] = $app->widgetComponent->render($widget00['widget_name']);
        } else {
            $data['$WIDGET0-0$'] = $emptyWidget();
        }

        if(!is_null($widget01)) {
            $data['$WIDGET0-1$'] = $app->widgetComponent->render($widget01['widget_name']);
        } else {
            $data['$WIDGET0-1$'] = $emptyWidget();
        }

        if(!is_null($widget10)) {
            $data['$WIDGET1-0$'] = $app->widgetComponent->render($widget10['widget_name']);
        } else {
            $data['$WIDGET1-0$'] = $emptyWidget();
        }

        if(!is_null($widget11)) {
            $data['$WIDGET1-1$'] = $app->widgetComponent->render($widget11['widget_name']);
        } else {
            $data['$WIDGET1-1$'] = $emptyWidget();
        }

        $this->templateManager->fill($data, $template);

        return $template;
    }
}

?>