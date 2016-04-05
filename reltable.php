<?php
include(dirname(__FILE__).'/Biblio.php');
include(dirname(__FILE__).'/Rolenet.php');
$pdo = new PDO('sqlite:classiques.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$playcode = @$_REQUEST['play'];
$play = $pdo->query("SELECT * FROM play WHERE code = ".$pdo->quote($playcode))->fetch();
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
  </head>
  <body>
<?php
if ($play) {
  echo '<form>';
  echo Dramagraph_Biblio::select( $pdo, $playcode );
  echo '</form>';
  echo Dramagraph_Rolenet::reltable( $pdo, $playcode );
} else {
  echo Dramagraph_Biblio::table($pdo, null, "?play=%s");
}
 ?>

    <script type="text/javascript" src="https://oeuvres.github.io/Teinte/Sortable.js">//</script>
  </body>
</html>
