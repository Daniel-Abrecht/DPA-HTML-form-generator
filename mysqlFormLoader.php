<?php

require_once("formLoader.php");
require_once("formular.php");

class MysqlFormLoader implements FormLoader {

  private $db;
  private $dbname;

//  public function MysqlFormLoader($host,$user,$password,$dbname){
  public function __construct($host,$user,$password,$dbname){
    $this->dbname = $dbname;
    $this->db = @new mysqli($host,$user,$password,$dbname);
    if($this->db->connect_errno){
      $err = "Failed to connect to MySQL: " . $this->db->connect_error;
      $this->db = null;
      trigger_error( $err, E_USER_ERROR );
    }
    $this->db->set_charset("utf8");
  }

//  public function ~MysqlFormLoader(){
  public function __destruct(){
    if($this->db)
      $this->db->close();
    $this->db = null;
  }

  private function getDatas($tablename,$fields){
    if(!count($fields))
      return array();
    $query = 'SELECT `'.$this->db->real_escape_string($fields[0]).'`';
    for($i=1,$n=count($fields);$i<$n;$i++)
      $query .= ',`'.$this->db->real_escape_string($fields[$i]).'`';
    $query .= ' FROM `'.$this->db->real_escape_string($tablename).'`';
    $result = $this->db->query($query);
    if(!$result)
      return array();
    $allRows = array();
    while($row = $result->fetch_assoc())
      $allRows[] = $row;
    $result->free();
    return $allRows;
  }

  private function getTableDetails($tablename){
    $query = "
  SELECT
    c.COLUMN_TYPE AS type,
    c.COLUMN_NAME AS name,
    c.IS_NULLABLE AS nullable,
    c.COLUMN_DEFAULT AS `default`,
    r.REFERENCED_TABLE_NAME AS reftbl,
    r.REFERENCED_COLUMN_NAME AS refcol,
    c.COLUMN_COMMENT AS comment
  FROM
    INFORMATION_SCHEMA.COLUMNS AS c
  LEFT JOIN
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS r
  ON
        c.TABLE_NAME = r.TABLE_NAME
    AND c.TABLE_SCHEMA = r.TABLE_SCHEMA
    AND c.COLUMN_NAME = r.COLUMN_NAME
  WHERE
        c.TABLE_SCHEMA = ?
    AND c.TABLE_NAME = ?
  ORDER BY c.ORDINAL_POSITION ASC
";
    if(! $statement = $this->db->prepare($query) ){
      trigger_error( "mysqli::prepare failed!" . $this->db->error );
    }
    $statement->bind_param('ss',$this->dbname,$tablename);
    $statement->execute();
    $statement->bind_result(
      $type,
      $name,
      $nullable,
      $defaultValue,
      $reftbl,
      $refcol,
      $comment
    );
    $allResults = array();
    while($statement->fetch()){
      $allResults[] = array(
        'type' => $type,
        'name' => $name,
        'nullable' => $nullable=='YES',
        'defaultValue' => $defaultValue,
        'reftbl' => $reftbl,
        'refcol' => $refcol,
        'comment' => $comment
      );
    }
    $statement->close();
    return $allResults;
  }

  public function load($name){
    $form = new Formular();
    if(!$this->db)
      return $form;

    $td = $this->getTableDetails($name);

    foreach($td as $column){

      $type = "inputFormularItem"; // default input field

      $values = array();

      $name = $column['name']; // default label is column name
      $label = ucfirst( $column['name'] ); // default label is column name
      $value = $column['defaultValue'];

      $properties = @JSON_decode($column['comment'],true);

      if( isset($properties['type']) && $properties['type'] == 'textarea' ){
        $type="textareaFormularItem";
      }

      if( $properties && @$properties['hidden'] )
        continue;

      if( $properties && isset( $properties['label'] ) )
        $label = $properties['label'];

      $vars = null;
      if(
          $column['reftbl']
       && $column['refcol']
       && $properties
       && isset( $properties['format'] )
       && preg_match_all( '/{([^}]*)}/', $properties['format'], $vars )
      ){
        $type = "selectFormularItem";
        if(isset($properties['type'])&&$properties['type']=='radio'){
          $type = "radioFormularItem";
          if($column['nullable']){
            $values[] = array(
              'text' => "Keine Auswahl",
              'value' => ""
            );
            if(!$value)$value='';
          }
        }else{
          $values[] = array(
            'text' => isset($properties['placeholder']) 
                        ? $properties['placeholder']
                        : "$label auswählen...",
            'value' => '',
            'disabled' => !$column['nullable']
          );
        }
        $rows = $this->getDatas($column['reftbl'],array_merge(array($column['refcol']),$vars[1]));
        foreach($rows as $row){
          $replacement = array();
          foreach($vars[1] as $key)
            $replacement[] = $row[$key];
          $values[] = array(
            'text' => str_replace($vars[0],$replacement,$properties['format']),
            'value' => $row[$column['refcol']]
          );
        }
      }

      // extract db field type informations //
      $matches = null;
      $fieldtype = $column['type'];
      $typeDetails = '';
      if( preg_match('/^([^(]*)\\((.*)\\)$/', $fieldtype, $matches )){
        $fieldtype = $matches[1];
        $typeDetails = $matches[2];
      }

      $fieldtype = strtolower($fieldtype);

      if( $fieldtype == 'bit' && $typeDetails == '1' ){
        $fieldtype = "boolean";
        $value = $value == "b'1'" ? true : false;
      }

      // display items of a set as radio buttons //
      if($fieldtype=='set'){
        $type = "radioFormularItem";
        $result = strtr($typeDetails,'"\'','\'"'); // replace ' with " and " with '
        $v = JSON_decode('['.$result.']'); // get items of set as array, json decode handels all escaping properly
        if(isset($properties['type'])&&$properties['type']=='select'){
          $type = "selectFormularItem";
          $values[] = array(
            'text' => isset($properties['placeholder']) 
                        ? $properties['placeholder']
                        : "$label auswählen...",
            'value' => '',
            'disabled' => !$column['nullable']
          );
        }else{
          if($column['nullable']){
            $values[] = array(
              'text' => "Keine Auswahl",
              'value' => ""
            );
            if(!$value)$value='';
          }
        }
        foreach($v as $val){
          $values[] = array(
            'text' => $val,
            'value' => $val
          );
        }
      }

      switch($type){

        default:
        case "inputFormularItem": { // normal textfield
          $item = $form->createItem("inputFormularItem");
          if(!$column['nullable'])
            $item->set("required","required");
          if($value)
            $item->set("value", $value);
          $inputType = array(
            "float" => "number",
            "bouble" => "number",
            "int" => "number",
            "tinyint" => "number",
            "bigint" => "number",
            "integer" => "number",
            "date" => "date",
            "boolean" => "checkbox"
          );
          if( $properties && isset($properties['type']) ){
            $item->set("type",$properties['type']);
          }else{
            $item->set("type",isset($inputType[$fieldtype])?$inputType[$fieldtype]:"text");
          }
          if((int)$typeDetails)
            $item->set("maxlength", (int)$typeDetails);
          $item->set("name", $name);
          if( $properties && isset( $properties['placeholder'] ) )
            $item->set("placeholder",$properties['placeholder']);
          $item->setLabel( $label );
          $form->addItem( $item );
        } break;

        case "radioFormularItem": { // radios
          $item = $form->createItem("radioFormularItem");
          $item->setLabel( $label );
          if($value!==null)
            $item->set("value", $value);
          $item->set("name", $name);
          $item->set("radios", $values);
          $form->addItem($item);
        } break;

        case "selectFormularItem": {
          $item = $form->createItem("selectFormularItem");
          $item->setLabel( $label );
          if($value)
            $item->set("value", $value);
          $item->set("name", $name);
          $item->set("options", $values);
          $form->addItem($item);
        } break;

        case "textareaFormularItem": { // normal textfield
          $item = $form->createItem("textareaFormularItem");
          if(!$column['nullable'])
            $item->set("required","required");
          if($value)
            $item->set("value", $value);
          $item->set("name", $name);
          if( $properties && isset( $properties['placeholder'] ) )
            $item->set("placeholder",$properties['placeholder']);
          if((int)$typeDetails)
            $item->set("maxlength", (int)$typeDetails);
          $item->setLabel( $label );
          $form->addItem( $item );
        } break;

      }

    }

    return $form;
  }

}

?>
