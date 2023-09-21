<?php

namespace DMS\UI\FormBuilder;

class Select implements IBuildable {
  /**
   * @var string
   */
  private $name;

  /**
   * @var array
   */
  private $options;

  /**
   * @var string
   */
  private $disable;

  public $script;

  public function __construct() {
    $this->name = '';
    $this->options = array();
    $this->script = '';
    $this->disable = '';

    return $this;
  }

  public function setName(string $name) {
    $this->name = $name;

    return $this;
  }

  public function addOptions(array $options) {
    foreach($options as $o) {
      $this->options[] = $o;
    }

    return $this;
  }

  public function addOptionsBasedOnArray(array $array) {
    $options = array();

    foreach($array as $a) {
      $value = $a['value'];
      $text = $a['text'];

      if(isset($a['selected'])) {
        $selected = $a['selected'];
      } else {
        $selected = '';
      }

      $option = new Option();
      $option = $option->setValue($value)
                       ->setText($text);

      if($selected != '') {
        $option = $option->select();
      }

      $options[] = $option;
    }

    $this->addOptions($options);

    return $this;
  }

  public function disable() {
    $this->disable = 'disabled';

    return $this;
  }

  public function build() {
    $script = '<select name="' . $this->name . '" ' . $this->disable . '>';

    foreach($this->options as $o) {
      $script .= $o->build()->script;
    }

    $script .= '</select>';

    $this->script = $script;

    return $this;
  }
}

?>
