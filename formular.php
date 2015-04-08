<?php

interface FormularItem {
  public function set($key,$value);
  public function setLabel($name);
  public function toHTML($id);
}

class Formular {
  private $items = array();
  private static $itemTypes = array();
  public function createItem( $name ){
    if(!in_array( $name, self::$itemTypes ))
      return null;
    return new $name();
  }
  public function addItem(FormularItem $item){
    $this->items[] = $item;
  }
  public static function addItemType($item){
    self::$itemTypes[] = $item;
  }
  public function toHTML(){
    $id = 0;
    $html = "<form>\n";
    foreach($this->items as $item){
      $html .= $item->toHtml($id++) . "\n";
    }
    $html .= "\n</form>\n";
    return $html;
  }
}

function htmlAttr($name,$val){
  if($val===null)
    return '';
  return $name.'="'.htmlentities($val).'" ';
}

class inputFormularItem implements FormularItem {
  private $type = null;
  private $name = null;
  private $value = null;
  private $required = null;
  private $placeholder = null;
  private $label = null;
  public function setLabel($name){
    $this->label = $name;
  }
  public function set($key,$value){
    switch($key){
      case "type": 		$this->type		= $value; break;
      case "name":		$this->name		= $value; break;
      case "value":		$this->value		= $value; break;
      case "required":		$this->required		= $value; break;
      case "placeholder":	$this->placeholder	= $value; break;
    }
  }
  public function toHTML($id){
    if($this->type=='checkbox')
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
    . htmlAttr("placeholder",	$this->placeholder	)
    . "/>
  </div>";
  }
}

Formular::addItemType("inputFormularItem");

class textareaFormularItem implements FormularItem {
  private $name = null;
  private $value = null;
  private $required = null;
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
    foreach($this->options as $option){
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
    foreach($this->radios as $radio){
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
