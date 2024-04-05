<?php

namespace DMS\Modules\UserModule;

use DMS\Constants\UserLoginAttemptResults;
use DMS\Entities\UserLoginAttemptEntity;
use DMS\Helpers\GridDataHelper;
use DMS\Modules\APresenter;
use DMS\UI\GridBuilder;

class UserSettingsPresenter extends APresenter {
    public const DRAW_TOPPANEL = true;

    public function __construct() {
        parent::__construct('UserSettings', 'User settings');

        $this->getActionNamesFromClass($this);
    }

    protected function showLoginAttempts() {
        $template = $this->loadTemplate(__DIR__ . '/templates/settings/settings-grid.html');

        $data = [
            '$PAGE_TITLE$' => 'Login attempts',
            '$LINKS$' => [],
            '$SETTINGS_GRID$' => $this->internalCreateLoginAttemptsGrid()
        ];

        $this->fill($data, $template);

        return $template;
    }

    private function internalCreateLoginAttemptsGrid() {
        global $app;
        
        $dataSource = $app->userRepository->getLoginAttemptsByDate();
        
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