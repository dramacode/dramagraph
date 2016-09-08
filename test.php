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

$cols = array(
  'n',
  'author',
  'date',
  'title',
  'genre',
  'type',
  'verse',
  'acts',
  'c',
  'dist',
  'role',
  'roleavg',
  'rol1',
  'roledev',
  'sp',
  'spavg',
);
$linkf = ".?play=%s";

echo '      <table class="sortable" align="center">'."\n";
echo '        <tr>'."\n";
foreach ($cols as $key) {
  if ( 'acts' == $key)
    echo '          <th>Actes</th>'."\n";
  if ( 'author' == $key)
    echo '          <th>Auteur</th>'."\n";
  else if ( 'c' == $key)
    echo '          <th title="Quantité de texte prononcé en lignes (60 signes).">Paroles</th>'."\n";
  else if ( 'created' == $key)
    echo '          <th title="Date de création">Créé</th>'."\n";
  else if ( 'date' == $key)
    echo '          <th>Date</th>'."\n";
  else if ( 'dist' == $key)
    echo '          <th>Dist</th>'."\n";
  else if ( 'genre' == $key)
    echo '          <th title="Genre tel qu’inscrit sur la page de titre">Genre</th>'."\n";
  if ( 'n' == $key)
    echo '          <th>N°</th>'."\n";
  else if ( 'issued' == $key)
    echo '          <th title="Date de publication">Publié</th>'."\n";
  else if ( 'publisher' == $key)
    echo '          <th>Éditeur</th>'."\n";
  else if ( 'role' == $key)
    echo '          <th title="Nombre de personnages déclarés dans la distribution.">Pers.</th>'."\n";
  else if ( 'roleavg' == $key)
    echo '          <th title="Nombre moyen de personnages parlants sur scène.">Interlocution</th>'."\n";
  else if ( 'roledev' == $key)
    echo '          <th>Interl. écart-type</th>'."\n";
  else if ( 'rol1' == $key)
    echo '          <th>Rôles/interl.</th>'."\n";
  else if ( 'sp' == $key)
    echo '          <th title="Nombre de répliques.">Répliques</th>'."\n";
  else if ( 'spavg' == $key)
    echo '          <th title="Taille moyenne d’une réplique, en lignes (60 signes).">Rép. moy.</th>'."\n";
  else if ( 'title' == $key)
    echo '          <th>Titre</th>'."\n";
  else if ( 'type' == $key)
    echo '          <th title="Genre normalisé">Type</th>'."\n";
}
echo '        </tr>'."\n";
$n = 0;
$qdist = $pdo->prepare("SELECT c FROM role WHERE play = ? ORDER BY ord");
$qrole = $pdo->prepare("SELECT c FROM role WHERE play = ? ORDER BY c DESC");
$qconf = $pdo->prepare("SELECT * FROM configuration WHERE play = ?");

foreach ($pdo->query("SELECT * FROM play ORDER BY author, date") as $play) {
  // distribution, décalage entre ordre et taille de parole
  // boucler sur la distribution dans l’ordre
  $dist = array();
  $score = 0;
  $qdist->execute( array( $play['id'] ) );
  while ( $role = $qdist->fetch() ) {
    $dist[] = $role[0];
  }
  $qrole->execute( array( $play['id'] ) );
  $i = 0;
  while ( $role = $qrole->fetch() ) {
    $score += abs( $dist[$i] - $role['c']);
    $i++;
  }

  // écart-type de l’interlocution
  $avg = 1.0*$play['cspeakers'] / $play['c'];
  $dev = 0.0;
  $qconf->execute( array( $play['id'] ) );
  while ( $conf = $qconf->fetch( PDO::FETCH_ASSOC ) ) {
    $dev += $conf['c'] * pow( $conf['speakers'] - $avg , 2);
  }
  $dev = sqrt( $dev / $play['c'] );


  // if (!$row['c']) continue; // pièce boguée
  $n++;
  echo '        <tr>'."\n";
  foreach ($cols as $key) {
    if ( 'acts' == $key)
      echo '          <td>'.$play['acts'].'</td>'."\n";
    if ( 'author' == $key)
      echo '          <td>'.$play['author'].'</td>'."\n";
    else if ( 'c' == $key)
      echo '          <td align="right">'.number_format($play['c']/60, 0, ',', ' ').' l.</td>';
    else if ( 'created' == $key)
      echo '          <td>'.$play['created'].'</td>'."\n";
    else if ( 'date' == $key)
      echo '          <td>'.$play['date'].'</td>'."\n";
    else if ( 'dist' == $key)
      echo '          <td>'.number_format( 100 * $score / $play['roles'] / $play['c'], 1, ',', ' ' ).'</td>'."\n";
    else if ( 'genre' == $key)
      echo '          <td>'.$play['genre'].'</td>'."\n";
    else if ( 'issued' == $key)
      echo '          <td>'.$play['issued'].'</td>'."\n";
    if ( 'n' == $key)
      echo '          <td>'.$n.'</td>'."\n";
    else if ( 'publisher' == $key) {
      if ( $pos = strpos( $play['publisher'], '(' ) ) $play['publisher'] = trim( substr( $play['publisher'], 0, $pos) );
      if ($play['identifier']) echo '          <td><a href="'.$play['identifier'].'">'.$play['publisher'].'</a></td>'."\n";
      else echo '          <td>'.$play['publisher'].'</td>'."\n";
    }
    else if ( 'role' == $key)
      echo '          <td align="right">'.$play['roles'].'</td>';
    else if ( 'roleavg' == $key)
      echo '          <td align="right">'.number_format($play['cspeakers']/$play['c'], 2, ',', ' ').' pers.</td>';
    else if ( 'roledev' == $key)
      echo '          <td align="right">'.$dev/$avg.'</td>';
    else if ( 'rol1' == $key)
    echo '          <td align="right">'.$play['roles']/$avg.'</td>';
    else if ( 'sp' == $key)
      echo '          <td align="right">'.$play['sp'].'</td>';
    else if ( 'spavg' == $key)
      echo '          <td align="right">'.number_format($play['c']/$play['sp']/60, 2, ',', ' ').' l.</td>';
    else if ( 'title' == $key) {
      $href = sprintf( $linkf, $play['code'] );
      echo '          <td>'.'<a href="'.$href.'">'.$play['title']."</a></td>\n";
    }
    else if ( 'type' == $key)
      echo '          <td>'.$play['type'].'</td>'."\n";
  }
  echo '        </tr>'."\n";
}
echo '</table>'."\n";



 ?>
    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
