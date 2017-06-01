<?php
$sqlite = "base.sqlite";

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
div.graph { position: relative; height: 700px; }
    </style>
  </head>
  <body id="top">
<?php
$pdo = new PDO('sqlite:'.$sqlite);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$play = $pdo->query("SELECT * FROM play WHERE code = ".$pdo->quote( $playcode ))->fetch();

if ($play) {
  $qobj = $pdo->prepare("SELECT cont FROM object WHERE playcode = ? AND type = ?");

  echo'<aside style="height: 100%; top:0; position: fixed; width: 300px; padding-left: 1rem; overflow: auto; float: left; ">
    <ul>
      <li><a href="#top">Graphe</a></li>
      <li><a href="#tables">Tables</a></li>
      <li><a href="#text">Texte</a></li>
    </ul>
  ';
  $qobj->execute( array( $playcode, 'charline' ) );
  echo  current( $qobj->fetch(PDO::FETCH_ASSOC)) ;
  echo '
    <ul>
      <li><a href="data.php" target="_blank">Données</a></li>
    </ul>
  </aside>
';
echo '<form style="position: fixed; z-index: 3; background: rgba(255, 255, 255, 0.8); font-family: sans-serif; right: 0; top: 0;  "> ';
echo Dramagraph_Biblio::select( $pdo, $playcode );
echo '<a href=".?">▲</a>
</form>';
  echo '<main style="margin-left: 300px; "><p> </p>';
  echo Dramagraph_Net::graph( $pdo, $playcode );
  echo '<div style="padding-left: 5rem; padding-right: 2rem; ">';
  echo '<section class="page" id="tables"> <p> </p>';
  echo Dramagraph_Table::roles( $pdo, $playcode );
  echo '</section>';
  echo '<section class="page" id="a2">';
  echo Dramagraph_Table::relations( $pdo, $playcode );
  echo '</section> <a id="text"></a>';
  echo '<section style="padding: 2rem; background-color: #FFFFFF; max-width: 800px;  ">';
  $qobj->execute( array( $playcode, 'article' ) );
  echo  current( $qobj->fetch(PDO::FETCH_ASSOC)) ;
  echo '</section>
  </div>
</main>';
}
else {
  echo '<article style="margin-left: 300px; padding-top: 1rem; padding-right: 2rem; ">';
  echo Dramagraph_Biblio::table( $pdo, null, "?play=%s");
  echo '</article>';
}
 ?>
    <script type="text/javascript" src="http://oeuvres.github.io/Teinte/Sortable.js">//</script>
  </body>
</html>
