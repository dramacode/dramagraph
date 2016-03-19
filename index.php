<?php
$playcode = @$_REQUEST['play'];
$sqlite = "test.sqlite";
include('../Dramaturgie/Charline.php');
$charline = new Dramaturgie_Charline($sqlite);
include('../Dramaturgie/Rolenet.php');
$rolenet = new Dramaturgie_Rolenet($sqlite);
$play = $charline->pdo->query("SELECT * FROM play WHERE code = ".$charline->pdo->quote($playcode))->fetch();


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
  </head>
  <body>
<?php if ($play) {
  echo $rolenet->nodetable($playcode);

} else { ?>
  <table class="sortable">
    <tr>
      <th>Auteur</th>
      <th>Date</th>
      <th>Titre</th>
      <th>Acte I</th>
      <th>Acte II</th>
      <th>Acte III</th>
      <th>Acte IV</th>
      <th>Acte V</th>
    </tr>
    <?php
    $qroles = $charline->pdo->prepare("SELECT SUM(roles * c) FROM configuration WHERE act = ?");
    foreach ($charline->pdo->query("SELECT * FROM play ORDER BY author, year") as $play) {
      echo '
    <tr>
      <td>'.$play['author'].'</td>
      <td>'.$play['year'].'</td>
      <td><a href="?play='.$play['code'].'">'.$play['title'].'</a></td>';
      foreach ($charline->pdo->query("SELECT * FROM act WHERE play = ".$play['id']." ORDER BY rowid") as $act) {
        if($act['type'] != 'act') continue;
        $qroles->execute(array($act['id']));
        list($v) = $qroles->fetch();
        echo '        <td>'.number_format($v/$act['c'], 1)."</td>\n";
      }
      echo '
    </tr>';
    }
    ?>
  </table>
  <table class="sortable">
    <table class="sortable">
      <tr>
        <th>Auteur</th>
        <th>Titre</th>
        <th>Personnage</th>
        <th>Interlocuteurs</th>
        <th>Présence</th>
        <th>Paroles</th>
        <th>Par. % prés.</th>
        <th>Répliques</th>
        <th>Rép. moy.</th>
        <th>Entrées</th>
      </tr>
      <pre>
    <?php
    // $qroles = $charline->pdo->prepare("SELECT SUM(roles * c) FROM configuration WHERE act = ?");
    foreach ($charline->pdo->query("SELECT role.*, play.c AS 'play.c', play.title AS 'play.title', play.author AS 'play.author' FROM role, play WHERE role.play = play.id") as $row) {
      $html = array();
      $html[] = "  <tr>";
      $html[] = '    <td>'.$row['play.author']."</td>";
      $html[] = '    <td>'.$row['play.title']."</td>";
      $html[] = '    <td>'.$row['label']."</td>";
      $html[] = '    <td align="right">'.$row['targets']."</td>";
      $html[] = '    <td align="right">'.number_format(100 * $row['presence']/$row['play.c'], 0)." %</td>";
      $html[] = '    <td align="right">'.number_format(100 * $row['c']/$row['play.c'], 0)." %</td>";
      if ($row['presence']) $html[] = '    <td align="right">'.number_format( 100 * $row['c']/$row['presence'] , 0)." %</td>";
      else $html[] = '<td/>';
      $html[] = '    <td align="right">'.$row['sp']."</td>";
      if ($row['sp']) $html[] = '    <td align="right">'.number_format($row['c']/($row['sp']*60), 2, ',', ' ')." l.</td>";
      else $html[] = '<td align="right">0</td>';
      // echo '    <td align="right">'.$node['ic']."</td>\n";
      // echo '    <td align="right">'.$node['isp']."</td>\n";
      // echo '    <td align="right">'.round($node['ic']/$node['isp'])."</td>\n";
      if ($row['entries']) $html[] = '    <td align="right">'.$row['entries']."</td>";
      else $html[] = '<td align="right">0</td>';
      $html[] = "  </tr>";
      echo implode("\n", $html);
    }
    ?>
    </pre>
  </table>
<?php } ?>

    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
