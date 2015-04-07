<?php

require_once("formLoader.php");
require_once("formular.php");

class JsonFormLoader implements FormLoader {

  private $root;
  private $prefix;

  public function JsonFormLoader($root,$prefix){
    $this->root = $root;
    $this->prefix = $prefix;
  }

  public function load($name){
    $datas = JSON_decode(file_get_contents(($this->root).'/'.$name.($this->prefix)),true);
    if(!$datas)
      return null;
    $form = new Formular();
    foreach($datas['items'] as $data){
      $item = $form->createItem($data['type']);
      $item->setLabel( $data['label'] );
      foreach($data['properties'] as $key => $val)
        $item->set($key,$val);
      $form->addItem($item);
    }
    return $form;
  }

}

?>
