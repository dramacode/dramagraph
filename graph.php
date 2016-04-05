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
    <link rel="stylesheet" charset="utf-8" type="text/css" href="dramagraph.css"/>
    <script src="sigma/sigma.min.js">//</script>
    <script src="sigma/sigma.layout.forceAtlas2.min.js">//</script>
    <script src="sigma/sigma.plugins.dragNodes.min.js">//</script>
    <script src="sigma/sigma.exporters.image.min.js">//</script>
    <script src="Rolenet.js">//</script>
  </head>
  <body style="margin: 0; padding: 0; ">
<?php
if ($play) {
  echo '<form>';
  echo Dramagraph_Biblio::select( $pdo, $playcode );
  echo '</form>';
  echo Dramagraph_Rolenet::graph( $pdo, $playcode );
} else {
  echo Dramagraph_Biblio::table($pdo, null, "?play=%s");
}
 ?>

    <script type="text/javascript" src="https://oeuvres.github.io/Teinte/Sortable.js">//</script>
  </body>
</html>
