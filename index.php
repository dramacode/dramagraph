<?php
$playcode = @$_REQUEST['play'];
include(dirname(__FILE__).'/Biblio.php');
include(dirname(__FILE__).'/Charline.php');
include(dirname(__FILE__).'/Net.php');
include(dirname(__FILE__).'/Table.php');



?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" charset="utf-8" type="text/css" href="http://oeuvres.github.io/Teinte/tei2html.css"/>
    <link rel="stylesheet" charset="utf-8" type="text/css" href="dramagraph.css"/>
    <script src="sigma/sigma.min.js">//</script>
    <script src="sigma/sigma.layout.forceAtlas2.min.js">//</script>
    <script src="sigma/sigma.plugins.dragNodes.min.js">//</script>
    <script src="sigma/sigma.exporters.image.min.js">//</script>
    <script src="Rolenet.js">//</script>
    <style>
html, body { height: 100%; }
body { padding: 0 1px 0 0; margin: 0; }
div.graph { height: 95%; }
    </style>
  </head>
  <body id="top">
<?php
$sqlite = "test.sqlite";
$pdo = new PDO('sqlite:'.$sqlite);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$play = $pdo->query("SELECT * FROM play WHERE code = ".$pdo->quote( $playcode ))->fetch();

if ($play) {
  $qobj = $pdo->prepare("SELECT cont FROM object WHERE playcode = ? AND type = ?");

  echo '<form style="position: fixed; z-index: 3; background: rgba(255, 255, 255, 0.8); font-family: sans-serif; left:0; right: 0; top: 0;  ">  <a href=".?">◤</a> ';
  echo Dramagraph_Biblio::select( $pdo, $playcode );
  echo ' <a href="#tables">Tables</a>';
  echo ' | <a href="#text">Texte</a>';
  echo ' | <a href="#top">Graphe</a>';
  echo ' | <a href="data.php">Données</a>';
  echo '</form>';
  echo '<p/>';
  echo Dramagraph_Net::graph( $pdo, $playcode );
  echo '<section class="page" id="tables"> <p> </p>';
  echo Dramagraph_Table::roles( $pdo, $playcode );
  echo '</section>';
  echo '<section class="page"" id="a2">';
  echo Dramagraph_Table::relations( $pdo, $playcode );
  echo '</section>';
  echo '<section class="page"" id="text" style="height: 100%; margin-left: auto; margin-right: auto; width: 1050px;  ">
  <aside style="height: 100%; width: 250px; overflow: auto; float: left; ">
  ';
  $qobj->execute( array( $playcode, 'charline' ) );
  echo  current( $qobj->fetch(PDO::FETCH_ASSOC)) ;
  echo '
  </aside>
  <div style="height: 100%; width: 800px; overflow: auto; padding: 1em; position: relative; ">
  ';
  $qobj->execute( array( $playcode, 'article' ) );
  echo  current( $qobj->fetch(PDO::FETCH_ASSOC)) ;
  echo '
  </div>
</section>';
}
else {
  echo ' <a href="data.php">Données</a>';
  echo Dramagraph_Biblio::table( $pdo, null, "?play=%s");
}
 ?>
    <script type="text/javascript" src="http://oeuvres.github.io/Teinte/Sortable.js">//</script>
  </body>
</html>
