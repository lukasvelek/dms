<?php

namespace App\Components\Form\Helpers;

use App\Components\Form\FormFactory;
use App\Components\Form\FormStateList;

class StateList2FormHelper {
    public static function convertStateList2Form(FormStateList $stateList) {
        $form = new FormFactory();

        foreach($stateList->__elements as $element) {
            $e = $stateList->$element;

            $type = $e->__type;

            var_dump($type);
        }
    }
}

?>