<?php
$sqlite = "base.sqlite";
$dramagraph = '../Dramagraph/';

$playcode = @$_REQUEST['play'];
include( $dramagraph.'Biblio.php');
include( $dramagraph.'Charline.php');
include( $dramagraph.'Net.php');
include( $dramagraph.'Table.php');



?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" charset="utf-8" type="text/css" href="http://oeuvres.github.io/Teinte/tei2html.css"/>
    <link rel="stylesheet" charset="utf-8" type="text/css" href="<?php echo $dramagraph ?>dramagraph.css"/>
    <script src="<?php echo $dramagraph ?>sigma/sigma.min.js">//</script>
    <script src="<?php echo $dramagraph ?>sigma/sigma.layout.forceAtlas2.min.js">//</script>
    <script src="<?php echo $dramagraph ?>sigma/sigma.plugins.dragNodes.min.js">//</script>
    <script src="<?php echo $dramagraph ?>sigma/sigma.exporters.image.min.js">//</script>
    <script src="<?php echo $dramagraph ?>Rolenet.js">//</script>
    <style>
div.graph { position: relative; height: 600px; }
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
  echo Dramagraph_Charline::population( $pdo, $playcode );
  echo '<div style="padding-left: 5rem; padding-right: 2rem; ">';
  echo '<section class="page" id="tables"> <p> </p>';
  echo Dramagraph_Table::roles( $pdo, $playcode );
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
  echo '<article style="margin-left: 300px; padding-top: 1rem; padding-right: 2rem; ">
  <h1>Le Dramagraphe</h1>
  <p>Le dramagraphe est un outil pour visualiser la distribution du texte dans une pièce de théâtre encodée en XML/TEI (<a href="https://github.com/dramacode/Dramagraph">sources sur GitHub</a>). Le projet est développé par <a href="#" onclick="this.href=\'mailto\u003Afrederic.glorieux\u0040fictif.org\'">Frédéric Glorieux</a>, il a été commencé pour répondre au <a href="http://litlab.stanford.edu/LiteraryLabPamphlet6.pdf">Pamphlet 6</a> de Franco Moretti, afin de vérifier son hypothèse en la généralisant (voir ce <a href="http://resultats.hypotheses.org/644">billet</a>). L’instrument a ensuite servi à la publication en ligne de pièces (par exemple le <a href="http://obvil.paris-sorbonne.fr/corpus/moliere/moliere_tartuffe">Molière</a> du LABEX OBVIL), ou à illustrer des <a href="http://resultats.hypotheses.org/749">études monographiques</a>. Cette installation a pour pour vocation de proposer différents textes classiques, libres de droits, pour situer un chiffre ou une configurations relativement à par exemple : Shakespeare, Sophocle, ou Molière… La bibliographie complète est disponible ci-dessous, cliquer un titre pour voir l‘effet du programme sur un texte.</p>
  ';
  echo Dramagraph_Biblio::table( $pdo, null, "?play=%s");
  echo '</article>';
}
 ?>
    <script type="text/javascript" src="http://oeuvres.github.io/Teinte/Sortable.js">//</script>
  </body>
</html>
