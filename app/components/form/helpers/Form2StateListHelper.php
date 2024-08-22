<?php

namespace App\Components\Form\Helpers;

use App\Components\Form\Elements\AElement;
use App\Components\Form\Elements\ElementDuo;
use App\Components\Form\FormFactory;
use App\Components\Form\FormStateEntity;
use App\Components\Form\FormStateList;

class Form2StateListHelper {
    public static function convertFormToStateList(FormFactory $form) {
        $fsl = new FormStateList();

        $elements = $form->getElements();

        $allElements = [];
        foreach($elements as $element) {
            if($element instanceof ElementDuo) {
                $name = $element->element->name;
                $allElements[$name] = $element->element;
                $allElements['lbl_' . $name] = $element->label;
            }
        }

        foreach($allElements as $element) {
            if(!($element instanceof AElement)) {
                die();
            }

            $fse = new FormStateEntity($element->__type);

            foreach($element->__elements as $key => $value) {
                $fse->$key = $value;
            }

            $name = $element->__id;

            $fsl->$name = $fse;
        }

        return $fsl;
    }
}

?>