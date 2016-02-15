<?php
/**
 * Visualisation relatives au réseaux de parole
 */
class Dramaturgie_Rolenet {
  /** Lien à une base SQLite, unique */
  public $pdo;
  /** Couleurs pour le graphe, la clé est une classe de nœud, les valeurs son 1: nœud, 2: lien */
  public static $colors = array(
    1 => array("#FF4C4C", "rgba(255, 0, 0, 0.5)"),
    2 => array("#A64CA6", "rgba(128, 0, 128, 0.5)"),
    3 => array("#4C4CFF", "rgba(0, 0, 255, 0.5)"),
    4 => array("#4c4ca6", "rgba(0, 0, 128, 0.5)"),
    5 => array("#A6A6A6", "rgba(140, 140, 160, 0.6)"),
    "female" => array("#FF4C4C", "rgba(255, 0, 0, 0.5)"),
    "female superior" => array("#FF0000", "rgba(255, 0, 0, 0.5)"),
    "female junior" => array("#FFb0D0", "rgba(255, 128, 192, 0.5)"),
    "female inferior" => array("#D07070", "rgba(192, 96, 96, 0.3)"),
    "female veteran" => array("#903333", "rgba(128, 0, 0, 0.3)"),
    "male" => array("#4C4CFF", "rgba(0, 0, 255, 0.3)"),
    "male junior" => array("#B0D0FF", "rgba(128, 192, 255, 0.5)"),
    "male veteran" => array("#333390", "rgba(0, 0, 128, 0.3)"),
    "male superior" => array("#0000FF", "rgba(0, 0, 255, 0.3)"),
    "male inferior" => array("#C0C0FF", "rgba(96, 96, 192, 0.3)"),
    "male exterior" => array("#A0A0A0", "rgba(96, 96, 192, 0.3)"),
  );
  /** Se lier à la base */
  public function __construct($sqlitefile) {
    // ? pouvois passer un pdo ?
    $this->pdo = new PDO('sqlite:'.$sqlitefile);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  }
  /**
   * Json compatible avec la librairie sigma.js
   */
  public function sigma($playcode) {
    $nodes = $this->nodes($playcode);
    $edges = $this->edges($playcode);
    echo "{ ";
    echo "\n  edges: [\n    ";
    for ($i=0; $i < count($edges); $i++) {
      $edge = $edges[$i];
      if (!isset($nodes[$edge['source']])) continue;
      if (!isset($nodes[$edge['target']])) continue;
      if ($i) echo ",\n    ";
      $source = $nodes[$edge['source']];
      $col = "";
      if (isset(self::$colors[$source['class']])) {
        $col = ', color: "'.self::$colors[$source['class']][1].'"';
      }
      else if (isset(self::$colors['role'.$i])) {
        $col = ', color: "'.self::$colors[$source['rank']][1].'"';
      }

      echo '{id:"e'.$i.'", source:"'.$edge['source'].'", target:"'.$edge['target'].'", size:"'.$edge['c'].'"'.$col.', type:"drama"}';

    }
    echo "\n  ]";

    echo ",";

    echo "\n  nodes: [\n    ";


    $count = count($nodes);
    $i = 1;
    foreach ($nodes as $code=>$node) {
      if (!$code) continue;
      if ($i > 1) echo ",\n    ";
      // position initiale en cercle, à 1h30
      $angle =  -M_PI - (M_PI*2/$count) *  ($i-1);
      // $angle =  2*M_PI/$count * ($i -1);
      $x =  number_format(6.0*cos($angle), 4);
      $y =  number_format(6.0*sin($angle), 4);
      /*
      // position initiale en ligne
      // $x = $i ;
      $y = 1;
      // $x = -$i*(1-2*($i%2));
      $x=$i;
      */
      $col = "";
      /*
      if (isset(self::$colors[$node['class']])) {
        $col = ', color: "'.self::$colors[$node['class']][0].'"';
      }
      else if (isset(self::$colors['role'.$i])) {
        $col = ', color: "'.self::$colors[$node['class']][0].'"';
      }
      */
      // $json_options = JSON_UNESCAPED_UNICODE; // incompatible 5.3
      $json_options = null;
      echo "{id:'".$node['code']."', label:".json_encode($node['label'],  $json_options).", size:".(0+$node['c']).", x: $x, y: $y".$col.", title: ".json_encode($node['title'],  $json_options).', type:"drama"}';
      $i++;
    }
    echo "\n  ]";

    echo "\n};\n";
  }

  /**
   * Produire fichier de nœuds et de relations
   * TODO, à vérifier
   */
  public function gephi($filename) {
    $data = $this->nodes($filename);
    $f = $filename.'-nodes.csv';
    $w = fopen($f, 'w');
    for ($i=0; $i<count($data); $i++) {
      fwrite($w, implode("\t", $data[$i])."\n");
    }
    fclose($w);
    echo $f.'  ';
    $data = $this->edges($filename);
    $f = $filename.'-edges.csv';
    $w = fopen($f, 'w');
    for ($i=0; $i<count($data); $i++) {
      fwrite($w, implode("\t", $data[$i])."\n");
    }
    fclose($w);
    echo $f."\n";
  }
  /**
   * Table des relations
   */
  public function edgetable ($playcode) {
    echo '
<table class="sortable">
  <tr>
    <th>N°</th>
    <th>De</th>
    <th>À</th>
    <th>Scènes</th>
    <th>Paroles</th>
    <th>Répliques</th>
    <th>Rép. moy.</th>
  </tr>
  ';
    $edges = $this->edges($playcode);
    foreach ($edges as $key => $edge) {
      echo "  <tr>\n";
      echo '    <td>'.$edge['no']."</td>\n";
      echo '    <td>'.$edge['slabel']."</td>\n";
      echo '    <td>'.$edge['tlabel']."</td>\n";
      echo '    <td align="right">'.$edge['confs']."</td>\n";
      echo '    <td align="right">'.number_format($edge['c']/60, 0)." l.</td>\n";
      echo '    <td align="right">'.$edge['sp']."</td>\n";
      echo '    <td align="right">'.number_format($edge['c']/($edge['sp']*60), 2, ',', ' ')." l.</td>\n";
      echo "  </tr>\n";
    }

    echo '</table>';
  }
  /**
   * Table des rôles
   */
  public function nodetable ($playcode) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    echo '
<table class="sortable">
  <tr>
    <th>Personnage</th>
    <th>Interlocuteurs</th>
    <th>Présence</th>
    <th>Paroles</th>
    <th>Par. % prés.</th>
    <th>Répliques</th>
    <th>Rép. moy.</th>
  </tr>
  ';
    $nodes = $this->nodes($playcode);
    foreach ($nodes as $key => $node) {
      echo "  <tr>\n";
      echo '    <td>'.$node['label']."</td>\n";
      echo '    <td>'.$node['targets']."</td>\n";
      echo '    <td align="right">'.number_format(100 * $node['presence']/$play['c'], 0)." %</td>\n";
      echo '    <td align="right">'.number_format(100 * $node['c']/$play['c'], 0)." %</td>\n";
      echo '    <td align="right">'.number_format( 100 * $node['c']/$node['presence'] , 0)." %</td>";
      echo '    <td align="right">'.$node['sp']."</td>\n";
      if ($node['sp']) echo '    <td align="right">'.number_format($node['c']/($node['sp']*80), 2, ',', ' ')." l.</td>\n";
      else echo '<td align="right">0</td>'."\n";
      // echo '    <td align="right">'.$node['ic']."</td>\n";
      // echo '    <td align="right">'.$node['isp']."</td>\n";
      // echo '    <td align="right">'.round($node['ic']/$node['isp'])."</td>\n";
      echo "  </tr>\n";
    }
    echo '<tfoot>
<tr><td colspan="7">Le temps de présence est proprotionnel aux signes prononcés dans une scène.
<br/> l. : lignes (= 60 signes)</td></tr>
    </tfoot>
    ';
    echo '</table>';

  }
  /**
   * Liste de nœuds
   */
  public function nodes($playcode) {
    $playcode = $this->pdo->quote($playcode);
    $data = array();
    $rank = 1;
    $qpres = $this->pdo->prepare("SELECT sum(c) FROM configuration, presence WHERE presence.role = ? AND presence.configuration = configuration.id; ");
    foreach ($this->pdo->query("SELECT role.* FROM role, play WHERE role.play = play.id AND play.code = $playcode ORDER BY role.c DESC") as $role) {
      // role invisible dans les configurations
      if (!$role['sources']) continue;
      $class = "";
      if ($role['sex'] == 2) $class = "female";
      else if ($role['sex'] == 1) $class = "male";
      if ($role['status'] == 'exterior') $class .= " exterior";
      else if ($role['status'] == 'inferior') $class .= " inferior";
      else if ($role['status'] == 'superior') $class .= " superior";
      else if ($role['age'] == 'junior') $class .= " junior";
      else if ($role['age'] == 'veteran') $class .= " veteran";

      $qpres->execute(array($role['id']));
      list($presence) = $qpres->fetch();
      $data[$role['code']] = array(
        'id' => $role['id'],
        'code' => $role['code'],
        'label' => $role['label'],
        'title' => ($role['title'])?$role['title']:'',
        'targets' => $role['targets'],
        'confs' => $role['confs'],
        'class' => $class,
        'rank' => $rank,
        'c' => $role['c'],
        'sp' => $role['sp'],
        'presence' => $presence,
      );
      $rank++;
    }
    return $data;
  }
  /**
   * Relations paroles entre les rôles
   */
  public function edges($playcode) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    // load a dic of rowid=>code for roles
    $cast = array();
    foreach  ($this->pdo->query("SELECT id, code, label, c FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC) as $row) {
      $cast[$row['id']] = $row;
    }
    $sql = "SELECT
      edge.source,
      edge.target,
      count(sp) AS sp,
      sum(sp.c) AS c,
      count(DISTINCT configuration) AS confs,
      (SELECT c FROM role WHERE edge.source=role.id)+(SELECT c FROM role WHERE edge.target=role.id) AS sort
    FROM edge, sp
    WHERE edge.play = ? AND edge.sp = sp.id
    GROUP BY edge.source, edge.target
    ORDER BY sort DESC
    ";
    $q = $this->pdo->prepare($sql);

    $q->execute(array($play['id']));
    $data = array();
    $max = false;
    $nodes = array();
    $i = 1;
    while ($row = $q->fetch()) {
      if(!$max) $max = $row['c'];
      /*
      $dothreshold = false; // no threshold
      if ($sp['source']==$sp['target']);
      else if (!isset($nodes[$sp['source']])) {
        $nodes[$sp['source']] = 1;
        $dothreshold = false;
      }
      else {
        $nodes[$sp['source']]++;
      }

      if ($sp['source']==$sp['target']);
      else if (!isset($nodes[$sp['target']])) {
        $nodes[$sp['target']] = 1;
        $dothreshold = false;
      }
      else {
        $nodes[$sp['target']]++;
      }
      // a threshold, to apply only on relation already linked to the net
      if ($dothreshold && ( $sp['ord'] <100 || ($sp['ord']/$play['c']) < 0.01) ) {
        continue;
      }
      */
      $data[] = array(
        'no' => $i,
        'sort' => 0+$row['sort'],
        'source' => $cast[$row['source']]['code'],
        'slabel' => $cast[$row['source']]['label'],
        'target' => $cast[$row['target']]['code'],
        'tlabel' => $cast[$row['target']]['label'],
        'c' => $row['c'],
        'sp' => $row['sp'],
        'confs' => $row['confs'],
      );
      $i++;
    }
    return $data;
  }


}

 ?>
