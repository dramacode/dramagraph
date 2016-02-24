<?php
$sqlite = "test.sqlite";
include('../Dramaturgie/Charline.php');
$charline = new Dramaturgie_Charline($sqlite);


?><!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
  </head>
  <body>
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
      print_r($qroles);
      foreach ($charline->pdo->query("SELECT * FROM play ORDER BY author, year") as $play) {
        echo '
      <tr>
        <td>'.$play['author'].'</td>
        <td>'.$play['year'].'</td>
        <td>'.$play['title'].'</td>';
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
    <script type="text/javascript" src="../Teinte/Sortable.js">//</script>
  </body>
</html>
