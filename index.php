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
  <body>
<?php
$sqlite = "test.sqlite";
$pdo = new PDO('sqlite:'.$sqlite);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$play = $pdo->query("SELECT * FROM play WHERE code = ".$pdo->quote( $playcode ))->fetch();

if ($play) {
  $qobj = $pdo->prepare("SELECT cont FROM object WHERE playcode = ? AND type = ?");

  echo '<form>';
  echo Dramagraph_Biblio::select( $pdo, $playcode );
  echo '</form>';
  echo '<form style="position: fixed; right: 0; top: 0; z-index: 3;">';
  echo '  <select onchange="location.hash = \'#\'+this.options[this.selectedIndex].value; ">';
  echo '    <option value="a1">Rôles</option>';
  echo '    <option value="a2">Relations</option>';
  echo '    <option value="a3">graphe</option>';
  echo '    <option value="a4">texte</option>';
  echo '  <select>';
  echo '<a href="#top">▲</a>';
  echo '</form>';
  echo '<a id="a3"></a>';
  echo Dramagraph_Net::graph( $pdo, $playcode );
  echo '<section class="page" id="a1">';
  echo Dramagraph_Table::roles( $pdo, $playcode );
  echo '</section>';
  echo '<section class="page"" id="a2">';
  echo Dramagraph_Table::relations( $pdo, $playcode );
  echo '</section>';
  echo '<section class="page"" id="a4" style="height: 100%;  ">
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
  echo Dramagraph_Biblio::table( $pdo, null, "?play=%s");
}
 ?>
    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
