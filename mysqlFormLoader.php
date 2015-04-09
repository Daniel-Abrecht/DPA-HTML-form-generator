<?php

require_once("formLoader.php");
require_once("formular.php");

// Ein FormLoader der Formuare aus MySQL tabellen erstellt
class MysqlFormLoader implements FormLoader {

  private $db;
  private $dbname;

//  public function MysqlFormLoader($host,$user,$password,$dbname){
  public function __construct($host,$user,$password,$dbname){ // constructor
    $this->dbname = $dbname;
    $this->db = @new mysqli($host,$user,$password,$dbname); // Datenbankverbindung aufgauen
    if($this->db->connect_errno){ // Fehler abfangen
      $err = "Failed to connect to MySQL: " . $this->db->connect_error;
      $this->db = null;
      trigger_error( $err, E_USER_ERROR );
    }
    $this->db->set_charset("utf8");
  }

//  public function ~MysqlFormLoader(){
  public function __destruct(){ // destructor
    if($this->db)
      $this->db->close(); // Datenbankverbindung schliessen
    $this->db = null;
  }

  // Alle Daten der angegebenen Felder der angegebenen Tabelle als Array zurückgeben
  private function getDatas($tablename,$fields){
    if(!count($fields))
      return array();
    // Datenbank query zusammensetzen
    $query = 'SELECT `'.$this->db->real_escape_string($fields[0]).'`';
    for($i=1,$n=count($fields);$i<$n;$i++)
      $query .= ',`'.$this->db->real_escape_string($fields[$i]).'`';
    $query .= ' FROM `'.$this->db->real_escape_string($tablename).'`';
    $result = $this->db->query($query); // Datenbankquery ausführen
    if(!$result)
      return array();
    $allRows = array();
    while($row = $result->fetch_assoc())
      $allRows[] = $row;
    $result->free(); // Speicher freigeben
    return $allRows;
  }

  // Alle Tabellenfelder und deren Eigenschaften Abfragen
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
";  // Datenbankquery "Vorbereiten" (parsen) und als Mysql Statemant in $statement speichern
    if(! $statement = $this->db->prepare($query) ){
      trigger_error( "mysqli::prepare failed!" . $this->db->error );
    }
    $statement->bind_param('ss',$this->dbname,$tablename); // parameter in Query setzen
    $statement->execute(); // Mysqlquery ausführen
    $statement->bind_result( // Referenz von Variablen übergeben
      $type,
      $name,
      $nullable,
      $defaultValue,
      $reftbl,
      $refcol,
      $comment
    );
    $allResults = array();
    while($statement->fetch()){ // Nächstes resultat abfragen, setzt in bind_result referenzierte variablen
      $allResults[] = array( // Resultat zu Array Hinzufügen
        'type' => $type,
        'name' => $name,
        'nullable' => $nullable=='YES',
        'defaultValue' => $defaultValue,
        'reftbl' => $reftbl,
        'refcol' => $refcol,
        'comment' => $comment
      );
    }
    $statement->close(); // Aufraumen
    return $allResults; // Alle Resultate zurückgeben
  }

  // Datenbanktabelle mit entsprechendem Namen laden, Formular daraus zusammensetzen und dieses dann zurückgeben
  public function load($name){
    $form = new Formular(); // Formular erstellen
    if(!$this->db)
      return $form;

    $td = $this->getTableDetails($name); // Tabellenfelder abfragen

    foreach($td as $column){

      $type = "inputFormularItem"; // Standard Formularelementtyp

      $values = array();

      $name = $column['name']; // default label is column name
      $label = ucfirst( $column['name'] ); // default label is column name
      $value = $column['defaultValue'];

      $properties = @JSON_decode($column['comment'],true); // Zusatzinformationen aus commentarfeld laden

      if( isset($properties['type']) && $properties['type'] == 'textarea' ){ // Soll als textarea dargestellt werden?
        $type="textareaFormularItem"; // Formularelementtyp muss textarea sein
      }

      if( $properties && @$properties['hidden'] ) // Soll nicht als Formularelement vorhanden Sein?
        continue; // Nächstes element

      if( $properties && isset( $properties['label'] ) )
        $label = $properties['label'];

      $vars = null;
      if( // Foreigenkey constrain und darstellungsformat vorhanden
          $column['reftbl']
       && $column['refcol']
       && $properties
       && isset( $properties['format'] )
       && preg_match_all( '/{([^}]*)}/', $properties['format'], $vars )
      ){
        $type = "selectFormularItem"; // Als select element darstellen
        if(isset($properties['type'])&&$properties['type']=='radio'){ // Soll als radio buttons dargestellt werden
          $type = "radioFormularItem"; // Eingabeelementtyp anpassen
          if($column['nullable']){ // Kann null sein, leere option hinzufügen
            $values[] = array(
              'text' => "Keine Auswahl",
              'value' => ""
            );
            if(!$value)$value='';
          }
        }else{
          $values[] = array( // Defaultoption hinzufügen
            'text' => isset($properties['placeholder']) 
                        ? $properties['placeholder']
                        : "$label auswählen...",
            'value' => '',
            'disabled' => !$column['nullable']
          );
        }
        // Daten der referenzierten tabelle abfragen
        $rows = $this->getDatas($column['reftbl'],array_merge(array($column['refcol']),$vars[1]));
        foreach($rows as $row){
          $replacement = array();
          foreach($vars[1] as $key)
            $replacement[] = $row[$key];
          $values[] = array( // Auswahlmöglichkeit setzen
            'text' => str_replace($vars[0],$replacement,$properties['format']), // Daten formatieren, als auswahltext
            'value' => $row[$column['refcol']] // value ist id
          );
        }
      }

      // extract db field type informations //
      $matches = null;
      $fieldtype = $column['type'];
      $typeDetails = '';
      // Feldtyp aufteilen in basistyp und zusatzinformation (z.B. länge)
      if( preg_match('/^([^(]*)\\((.*)\\)$/', $fieldtype, $matches ) ){
        $fieldtype = $matches[1];
        $typeDetails = $matches[2];
      }

      $fieldtype = strtolower($fieldtype);

      if( $fieldtype == 'bit' && $typeDetails == '1' ){
        $fieldtype = "boolean";
        $value = $value == "b'1'" ? true : false;
      }

      // display items of a set as radio buttons //
      if($fieldtype=='set'){ // Feldtyp ist ein set (Auswahl aus Werten)
        $type = "radioFormularItem"; // Defaultmassig mit radiobuttons darstellen
        $result = strtr($typeDetails,'"\'','\'"'); // ersetze ' mit " und " mit '
        $v = JSON_decode('['.$result.']'); // get items of set as array, json decode handels all escaping properly
        if(isset($properties['type'])&&$properties['type']=='select'){ // Soll als select dargestellt werden
          $type = "selectFormularItem"; // Formularfeldtyp ändern
          $values[] = array( // default option erstellen
            'text' => isset($properties['placeholder']) 
                        ? $properties['placeholder']
                        : "$label auswählen...",
            'value' => '',
            'disabled' => !$column['nullable']
          );
        }else{
          if($column['nullable']){ // Leere auswahlmöglichkeit hinzufügen
            $values[] = array(
              'text' => "Keine Auswahl",
              'value' => ""
            );
            if(!$value)$value='';
          }
        }
        foreach($v as $val){ // Für alle Auswahlmöglichkeiten
          $values[] = array( // zur liste hinzufügen
            'text' => $val, // anzeigetext
            'value' => $val // optional
          );
        }
      }

      switch($type){ // Je nach Formularfeldtyp

        default:
        case "inputFormularItem": { // Normales Textfeld
          $item = $form->createItem("inputFormularItem"); // Formularfeld erstellen
          /* attribute setzen */
          if(!$column['nullable'])
            $item->set("required","required");
          if($value)
            $item->set("value", $value);
          $inputType = array(
            "float" => "number",
            "double" => "number",
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
          /****/
          $form->addItem( $item ); // Zu formular hinzufügen
        } break;

        case "radioFormularItem": { // radios
          $item = $form->createItem("radioFormularItem"); // Formularfeld erstellen
          /* attribute setzen */
          $item->setLabel( $label );
          if($value!==null)
            $item->set("value", $value);
          $item->set("name", $name);
          $item->set("radios", $values);
          /****/
          $form->addItem($item); // Zu formular hinzufügen
        } break;

        case "selectFormularItem": {
          $item = $form->createItem("selectFormularItem");
          /* attribute setzen */
          $item->setLabel( $label );
          if($value)
            $item->set("value", $value);
          $item->set("name", $name);
          $item->set("options", $values);
          /****/
          $form->addItem($item); // Zu formular hinzufügen
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
