<?php
$playcode = @$_REQUEST['play'];
$sqlite = "test.sqlite";
include(dirname(__FILE__).'/Base.php');
// TODO lourd
$base = new Dramagraph_Base($sqlite);
$charline = new Dramagraph_Charline($sqlite);
$rolenet = new Dramagraph_Rolenet($sqlite);
$play = $charline->pdo->query("SELECT * FROM play WHERE code = ".$charline->pdo->quote($playcode))->fetch();


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <!-- <link rel="stylesheet" charset="utf-8" type="text/css" href="../Teinte/tei2html.css"/> -->
    <link rel="stylesheet" charset="utf-8" type="text/css" href="dramagraph.css"/>
    <script src="sigma/sigma.min.js">//</script>
    <script src="sigma/sigma.layout.forceAtlas2.min.js">//</script>
    <script src="sigma/sigma.plugins.dragNodes.min.js">//</script>
    <script src="sigma/sigma.exporters.image.min.js">//</script>
    <script src="Rolenet.js">//</script>
  </head>
  <body>
<?php if ($play) {

  echo $base->biblioselect( $playcode );
  $hrefToDramagraph = '';
  echo $rolenet->graph( $playcode, $hrefToDramagraph );
  echo $rolenet->roletable( $playcode );
} else {
  echo $base->bibliotable(null, "?play=%s");
}
 ?>

    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
