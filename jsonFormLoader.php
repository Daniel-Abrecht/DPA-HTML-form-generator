<?php

require_once("formLoader.php");
require_once("formular.php");

// Ein FormLoader der Formuare aus JSON dateien erstellt
class JsonFormLoader implements FormLoader {

  private $root;
  private $prefix;

  // Constructor
  public function __construct($root='',$prefix=''){
    $this->root = $root;
    $this->prefix = $prefix;
  }

  // Laden des Formular mit dem filenamen $name + $prefix in $root
  public function load($name){
    $datas = JSON_decode(file_get_contents(($this->root).'/'.$name.($this->prefix)),true);
    if(!$datas)
      return null; // Im fehlerfal null zurückgeben
    $form = new Formular(); // Neues formular erstellen
    foreach($datas['items'] as $data){ // Für jedes Formularelement
      $item = $form->createItem($data['type']); // Formularelement vom type $data['type']
      $item->setLabel( $data['label'] ); // Label des Formularelements setzen
      foreach($data['properties'] as $key => $val) // Alle eigenschaften des Elements setzen
        $item->set($key,$val);
      $form->addItem($item); // Formularelement zu Formular hinzufügen
    }
    return $form; // Formular zurückgeben
  }

}

?>
