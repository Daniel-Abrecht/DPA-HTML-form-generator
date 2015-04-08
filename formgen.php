<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

$loader = $_POST['loader'];
$name='';

switch($loader){
  case "json": {
    require_once("jsonFormLoader.php");
    $name = $_FILES['file']['tmp_name'];
    $formLoader = new JsonFormLoader();
  } break;
  default:
  case "mysql": {
    require_once("mysqlFormLoader.php");
    $name = $_POST['name'];
    $host = $_POST['host'];
    $user = $_POST['user'];
    $password = $_POST['password'];
    $dbname = $_POST['dbname'];
    $formLoader = new MysqlFormLoader($host,$user,$password,$dbname);
  } break;
}

$form = $formLoader->load($name);
$result = $form->toHtml();

?><!DOCTYPE html>
<html>
  <head>
    <title>Form Generator</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <style>
body {
  margin: 20px;
}
body > div {
  min-width: 600px;
  width: calc( 50% - 40px );
  display: inline-block;
  margin: 10px;
  vertical-align: top;
}
    </style>
    <script>
      onload = function(){
        document.getElementById("link").href
         = URL.createObjectURL(
             new Blob([
               "<!DOCTYPE html><html><head>\n",
               "<title>Titel</title>\n",
               "<meta charset=\"utf-8\">\n",
               "<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css\">\n",
               "<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css\">\n",
               "</head><body>\n",
                 document.getElementById("content").textContent
              || document.getElementById("content").innerText,
               "</body></html>\n"
             ],{
               type: "text/html"
             })
           )
        ;
      };
    </script>
  </head>
  <body>
    <div>
      <h1>Preview</h1>
      <?php echo $result; ?>
    </div>
    <div>
      <h1>Code</h1>
      <pre id="content"><?php echo htmlentities($result); ?></pre>
      <a id="link" download="formular.html">Download</a>
    </div>
  </body>
</html>
