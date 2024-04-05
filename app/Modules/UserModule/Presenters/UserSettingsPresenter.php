<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserLoginAttemptResults;
use DMS\Entities\UserLoginAttemptEntity;
use DMS\Helpers\GridDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\GridBuilder;
use DMS\UI\LinkBuilder;

class UserSettingsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserSettings', 'User settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showLoginAttempts() {
        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $type = 'all';

        if($this->get('type') !== NULL) {
            $type = $this->get('type');
        }

        $data = [
            '$PAGE_TITLE$' => 'Login attempts',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $this->internalCreateLoginAttemptsGrid($type)
        ];

        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLoginAttempts'], 'All attempts') . '&nbsp;&nbsp;';
        $data['$LINKS$'][] = LinkBuilder::createAdvLink(['page' => 'showLoginAttempts', 'type' => 'unsuccessful'], 'Unsuccessful attempts');

        $this->fill($data, $template);

        return $template;
    }

    private function internalCreateLoginAttemptsGrid(string $type) {
        global $app;
        
        $dataSource = [];

        switch($type) {
            case 'all':
                $dataSource = $app->userRepository->getLoginAttemptsByDate();
                break;

            case 'unsuccessful':
                $dataSource = $app->userRepository->getUnsuccessfulLoginAttemptsByDate();
                break;
        }
        
        $gb = new GridBuilder();

        $gb->addDataSource($dataSource);
        $gb->addColumns(['username' => 'Username', 'result' => 'Result', 'description' => 'Description', 'dateCreated' => 'Date']);
        $gb->addOnColumnRender('result', function(UserLoginAttemptEntity $ulae) {
            $text = UserLoginAttemptResults::$texts[$ulae->getResult()];
            $value = ($ulae->getResult() == 1) ? true : false;
            return GridDataHelper::renderBooleanValueWithColors($value, $text, $text);
        });

        return $gb->build();
    }
}

?>