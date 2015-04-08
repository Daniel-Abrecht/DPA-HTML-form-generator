<?php

// Interface für formularelement
interface FormularItem {
  public function set($key,$value); // Wert des Formularelements setzen
  public function setLabel($name); // Label des Formularelements setzen
  public function toHTML($id); //
}

class Formular {
  private $items = array(); // Liste aller Formularelemente
  private static $itemTypes = array(); // Liste aller Formularelementtypen
  // Neue instanz eines Formularelements vom typ $name erstellen 
  public function createItem( $name ){
    if(!in_array( $name, self::$itemTypes ))
      return null; // Ungültiger formulartyp, null zurückgeben
    return new $name(); // Formularelement instanzieren
  }
  // Formularelement zu formular hinzufügen
  public function addItem(FormularItem $item){
    $this->items[] = $item;
  }
  // Formularelementtyp hinzufügen
  public static function addItemType($item){
    self::$itemTypes[] = $item;
  }
  // HTML-Code aus formular generieren 
  public function toHTML(){
    $id = 0;
    // String repräsentation eines öffnenden form tag in variable $html speichern
    $html = "<form>\n";
    foreach($this->items as $item){ // Für jedes Formularelement
      // Generiere html code aus Formularelement als string, füge diesen zu $html hinzu
      // Die Formularelemente müssen für die label eindeutig identifizierbar sein, edshalb
      // werden sie durchnumeriert
      $html .= $item->toHtml($id++) . "\n"; 
    }
    // String repräsentation eines schliessenden form tag zum inhalt der Variable $html hinzufügen
    $html .= "\n</form>\n";
    return $html; // HTML-Code als string zurückgeben
  }
}

// Zusammensetzen der stringrepräsentation eines HTML-Attributs, 
// inclusive escapen Problematischer zeichen in dessen wert
function htmlAttr($name,$val){
  if($val===null)
    return '';
  return $name.'="'.htmlentities($val).'" ';
}

// Ein Eingabeformularelement
class inputFormularItem implements FormularItem {
  /* Eigenschaften des Eingabeelements */
  private $type = null;
  private $name = null;
  private $value = null;
  private $required = null;
  private $maxlength = null;
  private $placeholder = null;
  private $label = null;
  /****/
  public function setLabel($name){ // setter für label
    $this->label = $name;
  }
  // Setter für restliche eigenschaften
  public function set($key,$value){
    switch($key){
      case "type": 		$this->type		= $value; break;
      case "name":		$this->name		= $value; break;
      case "value":		$this->value		= $value; break;
      case "required":		$this->required		= $value; break;
      case "maxlength":		$this->maxlength	= $value; break;
      case "placeholder":	$this->placeholder	= $value; break;
    }
  }
  // HTML-Code aus Formularelement generieren
  public function toHTML($id){
    if($this->type=='checkbox') // Sonderbehandlung für checkboxen
      return "
  <div class=\"form-group\">
    <label><input type=\"checkbox\" "
    . htmlAttr("name",		$this->name			)
    . htmlAttr("checked",	$this->value?"checked":null	)
    . htmlAttr("required",	$this->required			)
    . "/> " . htmlentities($this->label) . "</label>
  </div>";
    else return "
  <div class=\"form-group\">
    <label for=\"i$id\">" . htmlentities($this->label) . "</label>
    <input id=\"i$id\" class=\"form-control\" "
    . htmlAttr("type",		$this->type		)
    . htmlAttr("name",		$this->name		)
    . htmlAttr("value",		$this->value		)
    . htmlAttr("required",	$this->required		)
    . htmlAttr("maxlength",	$this->maxlength	)
    . htmlAttr("placeholder",	$this->placeholder	)
    . "/>
  </div>";
  }
}

// Eingabeformularelementtyp als String zur Formularelementtypenliste des Formular types hinzufügen
Formular::addItemType("inputFormularItem");

class textareaFormularItem implements FormularItem {
  private $name = null;
  private $value = null;
  private $required = null;
  private $maxlength = null;
  private $placeholder = null;
  private $label = null;
  public function setLabel($name){
    $this->label = $name;
  }
  public function set($key,$value){
    switch($key){
      case "name":		$this->name		= $value; break;
      case "value":		$this->value		= $value; break;
      case "required":		$this->required		= $value; break;
      case "maxlength":		$this->maxlength	= $value; break;
      case "placeholder":	$this->placeholder	= $value; break;
    }
  }
  public function toHTML($id){
    return "
  <div class=\"form-group\">
    <label for=\"i$id\">" . htmlentities($this->label) . "</label>
    <textarea id=\"i$id\" class=\"form-control\" "
    . htmlAttr("name",		$this->name		)
    . htmlAttr("required",	$this->required		)
    . htmlAttr("maxlength",	$this->maxlength	)
    . htmlAttr("placeholder",	$this->placeholder	)
    . ">"
    . htmlentities($this->value)
    . "</textarea>
  </div>";
  }
}

Formular::addItemType("textareaFormularItem");

class selectFormularItem implements FormularItem {
  private $name = null;
  private $value = null;
  private $options = null;
  private $required = null;
  private $label = null;
  public function setLabel($name){
    $this->label = $name;
  }
  public function set($key,$value){
    switch($key){
      case "name":		$this->name		= $value; break;
      case "value":		$this->value		= $value; break;
      case "options":		$this->options		= $value; break;
    }
  }
  public function toHTML($id){
    $result = "
  <div class=\"form-group\">
    <label for=\"i$id\">" . htmlentities($this->label) . "</label>
    <select class=\"form-control\" "
    . htmlAttr("name",		$this->name	)
    . htmlAttr("required",	$this->required	)
    . ">\n";
    foreach($this->options as $option){ // HTML-Code für Auswahloptionen generieren
      $result .= "      <option "
      . htmlAttr("value", @$option['value'] )
      . htmlAttr("disabled", @$option['disabled']?'disabled':null )
      . htmlAttr("selected", @$option['value']==$this->value?"selected":null )
      . ">".htmlentities($option['text'])."</option>\n";
    }
    $result .= "    </select>
  </div>";
    return $result;
  }
}

Formular::addItemType("selectFormularItem");

class radioFormularItem implements FormularItem {
  private $name = null;
  private $value = null;
  private $options = null;
  private $required = null;
  private $label = null;
  public function setLabel($name){
    $this->label = $name;
  }
  public function set($key,$value){
    switch($key){
      case "name":		$this->name		= $value; break;
      case "value":		$this->value		= $value; break;
      case "radios":		$this->radios		= $value; break;
    }
  }
  public function toHTML($id){
    $result = "
  <div class=\"form-group\">
    <label>" . htmlentities($this->label) . "</label><br/>\n";
    $i=0;
    foreach($this->radios as $radio){ // Alle radio-input elemente generieren
      $result .= "    <label>
      <input type=\"radio\" id=\"i".$id."_".$i."\" "
      . htmlAttr("name", isset($this->name)?$this->name:'i'.$id )
      . htmlAttr("value", @$radio['value'] )
      . htmlAttr("checked", isset($this->value) ? ( $this->value === (isset($radio['value'])?$radio['value']:$i) ? "checked" : null ) : null )
      . "> ".htmlentities($radio['text'])."
    </label><br/>\n";
      $i++;
    }
    return "$result  </div>";
  }
}

Formular::addItemType("radioFormularItem");

?>
