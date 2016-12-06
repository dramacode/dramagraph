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
$linkf = ".?play=%s";


echo '
<table class="sortable" align="center">
  <tr>
    <th>n</th>
    <th>Auteur</th>
    <th>Date</th>
    <th>Titre</th>
    <th>Type</th>
    <th>Ordre</th>
    <th>Nom</th>
    <th>Description</th>
    <th>Confident ?</th>
    <th>*</th>
    <th>Texte</th>
    <th>*</th>
    <th>*</th>
  </tr>
';
$n = 0;
$qcast = $pdo->prepare("SELECT * FROM role WHERE id = ? ");
$qrole = $pdo->prepare("SELECT * FROM role WHERE play = ? ORDER BY ord");
$qheard = $pdo->prepare("SELECT
  count(sp) AS sp,
  sum(sp.c) AS c,
  count(DISTINCT sp.configuration) AS confs
FROM edge, sp
WHERE edge.sp = sp.id AND edge.target = ?
");
$qsource = $pdo->prepare("SELECT
  edge.source AS source,
  edge.target AS target,
  count(sp) AS sp,
  sum(sp.c) AS c,
  count(DISTINCT sp.configuration) AS confs
FROM edge, sp
WHERE edge.source = ? AND edge.sp = sp.id
GROUP BY edge.source, edge.target
ORDER BY c DESC
");
$qtarget = $pdo->prepare("SELECT
  edge.source AS source,
  edge.target As target,
  count(sp) AS sp,
  sum(sp.c) AS c,
  count(DISTINCT sp.configuration) AS confs
FROM edge, sp
WHERE edge.target = ? AND edge.sp = sp.id
GROUP BY edge.source, edge.target
ORDER BY c DESC
");
if( isset( $_REQUEST['author'] ) ) $sql = "SELECT * FROM play WHERE code LIKE '".$_REQUEST['author']."%' ORDER BY author, date";
else $sql = "SELECT * FROM play WHERE type='tragedy' ORDER BY author, date";

foreach ($pdo->query( $sql ) as $play) {
  $qrole->execute( array( $play['id'] ) );

  while ( $role = $qrole->fetch( PDO::FETCH_ASSOC ) ) {

    $qsource->execute( array( $role['id'] ) );
    $source = $qsource->fetch( PDO::FETCH_ASSOC );
    $qtarget->execute( array( $role['id'] ) );
    $target = $qtarget->fetch( PDO::FETCH_ASSOC );
    $qheard->execute( array( $role['id'] ) );
    $heard = $qheard->fetch( PDO::FETCH_ASSOC );


    if ( false );
    else if ( $heard['c']+$role['c'] == 0.0 ) continue;
    else if ( $heard['c']+$role['c'] <= 0.03*$play['c'] ) continue;
    else if ( !$role['c'] ) continue;
    else if ( !$heard['c'] ) continue;
    else if ( $target['source'] != $source['target'] ) continue;
    else if ( $target['source'] == $role['id'] )  continue;
    // else if ( $heard['c']+$role['c'] < 0.03*$play['c'] );

    $score = 0;
    $ratio = 0;
    if ( $role['c'] + $heard['c'] ) {
      $score = 100 * ( $source['c'] + $target['c'] ) / ( $role['c'] + $heard['c'] );
      $ratio = 100 * ( $source['c'] ) / ($source['c'] + $target['c']);
    }
    if ( $score <= 66 ) continue;

    echo '        <tr>'."\n";
    $n++;
    echo '          <td>'.$n.'</td>'."\n";
    echo '          <td>'.$play['author'].'</td>'."\n";
    echo '          <td>'.$play['date'].'</td>'."\n";
    $href = sprintf( $linkf, $play['code'] );
    echo '          <td>'.'<a href="'.$href.'">'.$play['title']."</a></td>\n";
    echo '          <td>'.$play['type']."</td>\n";
    echo '          <td>'.$role['ord'].'</td>'."\n";
    echo '          <td>'.$role['label'].'</td>'."\n";
    echo '          <td>'.$role['title'].'</td>'."\n";
    $qcast->execute( array($source['target']) );
    $row = $qcast->fetch();
    echo '          <td>'.$row['code'].'</td>'."\n";
    echo '          <td align="right">'.number_format( $score, 0, ',', ' ').' %</td>'."\n";
    echo '          <td align="right">'.number_format( $ratio, 0, ',', ' ').' %</td>'."\n";

    $qcast->execute( array($target['source']) );
    $row = $qcast->fetch();
    echo '          <td>'.$row['code'].'</td>'."\n";
    echo '          <td>'.number_format(100*$role['c']/$play['c'], 2, ',', ' ').'</td>'."\n";

    echo '          <td align="right">'.number_format($role['c']/60, 2, ',', ' ').' l.</td>'."\n";

    echo '        </tr>'."\n";
  }
}
echo '</table>'."\n";



 ?>
    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
