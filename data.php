<?php
include(dirname(__FILE__).'/Biblio.php');
include(dirname(__FILE__).'/Charline.php');
include(dirname(__FILE__).'/Net.php');
include(dirname(__FILE__).'/Table.php');

$playcode = @$_REQUEST['play'];
$data = @$_REQUEST['data'];
$sqlite = "test.sqlite";
$pdo = new PDO('sqlite:'.$sqlite);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$play = $pdo->query("SELECT * FROM play WHERE code = ".$pdo->quote( $playcode ))->fetch();

if ($play && $data && $data == 'spline') {
  header("Content-type: text/plain; charset=UTF-8");
  echo "PLAY\tID\tCHARS\tTOKENS\t@who\tPERS\n";
  $dist = array();
  $qrole = $pdo->prepare("SELECT * FROM role WHERE play = ? ");
  $qrole->execute(array($play['id']));
  while ($role = $qrole->fetch()) {
    $dist[$role['id']] = $role['code'];
  }
  $qpres = $pdo->prepare("SELECT * FROM presence WHERE configuration = ? ORDER BY sp DESC, rowid");
  $qsp = $pdo->prepare("SELECT * FROM sp WHERE configuration = ? ");
  $qconf = $pdo->prepare("SELECT * FROM configuration WHERE play = ? ");
  $qconf->execute(array($play['id']));
  while ($conf = $qconf->fetch()) {
    // order pers in conf by size
    $pers = array();
    $qpres->execute(array($conf['id']));
    $char = 65;
    while ($pres = $qpres->fetch()) {
      $pers[$pres['role']] = chr($char);
      $char++;
    }
    echo $play['code'];
    echo "\t";
    echo "\t";
    echo "\t";
    echo "\t";
    echo "\t";
    echo "\n";
    $qsp->execute(array($conf['id']));
    while ($sp = $qsp->fetch()) {
      echo $play['code'];
      echo "\t";
      echo $sp['code'];
      echo "\t";
      echo $sp['c'];
      echo "\t";
      echo $sp['w'];
      echo "\t";
      echo $dist[$sp['role']];
      echo "\t";
      echo $pers[$sp['role']];
      echo "\n";
    }
  }
  exit();
}


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <link rel="stylesheet" charset="utf-8" type="text/css" href="http://oeuvres.github.io/Teinte/tei2html.css"/>
    <link rel="stylesheet" charset="utf-8" type="text/css" href="dramagraph.css"/>
    <style>
    </style>
  </head>
  <body id="top">
    <form style="position: fixed; z-index: 3; background: rgba(255, 255, 255, 0.8); font-family: sans-serif; left:0; right: 0; top: 0;  " target="_blank">  <a href=".?">◤</a>
      <?php echo Dramagraph_Biblio::select( $pdo, $playcode ); ?>
      <select name="data">
        <option/>
        <option value="spline">Ligne des répliques</option>
      </select>
      <input type="submit"/>
    </form>
<?php


 ?>
  </body>
</html>
