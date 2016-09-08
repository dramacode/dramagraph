<?php
if (realpath($_SERVER['SCRIPT_FILENAME']) != realpath(__FILE__)); // file is include do nothing
else if (php_sapi_name() == "cli") {
  Dramagraph_Net::cli();
}
/**
 * Visualisation relatives au réseaux de parole
 */
class Dramagraph_Net
{
  /** Couleurs pour le graphe, la clé est une classe de nœud, les valeurs son 1: nœud, 2: lien */
  public static $colors = array(
    1 => array("#FF4C4C", "rgba(255, 0, 0, 0.5)"),
    2 => array("#A64CA6", "rgba(128, 0, 128, 0.5)"),
    3 => array("#4C4CFF", "rgba(0, 0, 255, 0.5)"),
    4 => array("#4c4ca6", "rgba(0, 0, 128, 0.5)"),
    5 => array("#A6A6A6", "rgba(140, 140, 160, 0.6)"),
    "superior" => array("#333333", "rgba(00, 00, 00, 0.3)"),
    "inferior" => array("#A0A0A0", "rgba(96, 96, 96, 0.3)"),
    "female" => array("#FF4C4C", "rgba(255, 0, 0, 0.5)"),
    "female superior" => array("#FF0000", "rgba(255, 0, 0, 0.5)"),
    "female junior" => array("#FFb0D0", "rgba(255, 128, 192, 0.5)"),
    "female inferior" => array("#D07070", "rgba(192, 96, 96, 0.3)"),
    "female veteran" => array("#903333", "rgba(128, 0, 0, 0.3)"),
    "male" => array("#4C4CFF", "rgba(0, 0, 255, 0.3)"),
    "male junior" => array("#B0D0FF", "rgba(128, 192, 255, 0.5)"),
    "male veteran" => array("#333390", "rgba(0, 0, 128, 0.3)"),
    "male superior" => array("#0000FF", "rgba(0, 0, 255, 0.3)"),
    "male inferior" => array("#C0C0EE", "rgba(96, 96, 192, 0.3)"),
    "male exterior" => array("#A0A0A0", "rgba(96, 96, 192, 0.3)"),
  );

  /**
   * Write the graph html
   */
  public static function graph( $pdo, $playcode )
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $role = $pdo->query("SELECT * FROM role WHERE play = ".$play['id']." ORDER BY c DESC LIMIT 1")->fetch();
    $html = array();
    $id = 'graph_'.$playcode;
    $html[] = self::canvas( $id );
    $html[] = '<script> (function () { var data =';
    $html[] = self::sigma( $pdo, $playcode );

    $html[] = ' var graph = new Rolenet("'.$id.'", data, '.( ceil($role['c']/900) ).'); //';
    $html[] = " })(); </script>\n";
    return implode("\n", $html);
  }

  /**
   * Html for canvas
   */
  public static function canvas($id='graph')
  {
    $html = '
    <div id="'.$id.'" class="graph" oncontextmenu="return false">
      <div class="sans-serif" style="position: absolute; top: 0; left: 1ex; font-size: 70%; ">Cliquer un nœud pour le glisser-déposer. Clic droit pour le supprimer</div>
      <div style="position: absolute; bottom: 0; right: 2px; z-index: 2; ">
        <button class="colors but" title="Gris ou couleurs">◐</button>
        <button class="shot but" type="button" title="Prendre une photo">📷</button>
        <button class="zoomin but" style="cursor: zoom-in; " type="button" title="Grossir">+</button>
        <button class="zoomout but" style="cursor: zoom-out; " type="button" title="Diminuer">-</button>
        <button class="but restore" type="button" title="Recharger">O</button>
        <button class="mix but" type="button" title="Mélanger le graphe">♻</button>
        <button class="grav but" type="button" title="Démarrer ou arrêter la gravité">►</button>
        <span class="resize interface" style="cursor: se-resize; font-size: 1.3em; " title="Redimensionner la feuille">⬊</span>
      </div>
    </div>
    ';
    return $html;
  }

  /**
   * Json compatible avec la librairie sigma.js
   */
  public static function sigma( $pdo, $playcode )
  {
    $nodes = self::nodes( $pdo, $playcode, 'act' );
    $edges = self::edges( $pdo, $playcode, 'act' );
    $html = array();
    $html[] = "{ ";
    $html[] = "  edges: [";
    for ($i=0; $i < count($edges); $i++) {
      $edge = $edges[$i];
      if (!isset($nodes[$edge['source']])) continue;
      if (!isset($nodes[$edge['target']])) continue;
      $source = $nodes[$edge['source']];
      $col = "";
      if (isset(self::$colors[$source['class']])) {
        $col = ', color: "'.self::$colors[$source['class']][1].'"';
      }
      else if (isset(self::$colors['role'.$i])) {
        $col = ', color: "'.self::$colors[$source['rank']][1].'"';
      }

      $html[] = '    {id:"e'.$i.'", source:"'.$edge['source'].'", target:"'.$edge['target'].'", size:"'.$edge['c'].'"'.$col.', type:"drama"},';

    }
    $html[] = "  ],";
    $html[] = "  nodes: [";


    $count = count($nodes);
    $i = 1;
    foreach ($nodes as $code=>$node) {
      if (!$code) continue;
      /*
      // position initiale en cercle, à 1h30
      $angle =  -M_PI - (M_PI*2/$count) *  ($i-1);
      // $angle =  2*M_PI/$count * ($i -1);
      $x =  number_format( 6.0*cos($angle), 4 );
      $y =  number_format( 6.0*sin($angle), 4 );
      */
      // position initiale en ligne verticale
      // $x = $i ;
      if ( $node['sex'] == 2 ) $x = -0.5*$count ;
      else if ( $node['sex'] == 1 ) $x = 0.5 * $count;
      else $x = 0.1 * ($i % 2); // légère variation si rien
      // if ( $node['status'] == 'inferior' ) $x = 2*$x;
      $y=$i;
      $col = "";

      if (isset(self::$colors[$node['class']])) {
        $col = ', color: "'.self::$colors[$node['class']][0].'"';
      }
      else if (isset(self::$colors['role'.$i])) {
        $col = ', color: "'.self::$colors[$node['class']][0].'"';
      }
      // $json_options = JSON_UNESCAPED_UNICODE; // incompatible 5.3
      $json_options = null;
      $html[] = "    {id:'".$node['code']."', label:".json_encode($node['label'],  $json_options).", size:".(0+$node['c']).", x: $x, y: $y".$col.", title: ".json_encode($node['title'],  $json_options).', type:"drama"},';
      $i++;
    }
    $html[] = "  ]";

    $html[] = "};";
    return implode("\n", $html);
  }

  /**
   * Produire fichier de nœuds et de relations
   * TODO, à vérifier
   */
  public static function gephi( $pdo, $playcode )
  {
    $data = self::nodes( $pdo, $playcode, 'act' );
    $f = $playcode.'-nodes.csv';
    $w = fopen($f, 'w');
    fwrite($w, "Id,Label,Weight\n");
    foreach ( $data as $key => $row ) {
      fwrite($w, $key.",".$row['label'].",".$row['c']."\n");
    }
    fclose($w);
    echo $f.'  ';
    $data = self::edges( $pdo, $playcode, 'act' );
    $f = $playcode.'-edges.csv';
    $w = fopen($f, 'w');
    fwrite($w, "Source,Target,Weight\n");
    foreach ( $data as $key => $row ) {
      fwrite($w, $row['source'].",".$row['target'].",".$row['c']."\n");
    }
    fclose($w);
    echo $f."\n";
  }

  /**
   * Liste de nœuds, pour le graphe, on filtre selon le type d'acte
   */
  public static function nodes($pdo, $playcode, $acttype=null)
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $data = array();
    $rank = 1;

    $qact = $pdo->prepare("SELECT act.* FROM presence, configuration, act WHERE act.type = ? AND presence.role = ? AND presence.configuration = configuration.id AND configuration.act = act.id ");
    foreach ($pdo->query("SELECT * FROM role WHERE role.play = ".$play['id']." ORDER BY role.ord") as $role) {
      // role invisible dans les configurations
      if (!$role['sources']) continue;
      if ($acttype) {
        $qact->execute(array($acttype, $role['id']));
        if (!$qact->fetch()) continue;
      }
      $class = "";
      if ($role['sex'] == 2) $class = "female";
      else if ($role['sex'] == 1) $class = "male";
      if ($role['status'] == 'exterior') $class .= " exterior";
      else if ($role['status'] == 'inferior') $class .= " inferior";
      else if ($role['status'] == 'superior') $class .= " superior";
      else if ($role['age'] == 'junior') $class .= " junior";
      else if ($role['age'] == 'veteran') $class .= " veteran";
      $role['class'] = trim($class);
      $role['rank'] = $rank;
      $data[$role['code']] = $role;
      $rank++;
    }
    return $data;
  }

  /**
   * Relations paroles entre les rôles
   */
  public static function edges( $pdo, $playcode, $acttype = null )
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    // load a dic of rowid=>code for roles
    $cast = array();
    foreach  ($pdo->query( "SELECT id, code, label, c FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC ) as $row) {
      $cast[$row['id']] = $row;
    }
    $sql = "SELECT
      edge.source,
      edge.target,
      count(sp) AS sp,
      sum(sp.c) AS c,
      count(DISTINCT sp.configuration) AS confs,
      (SELECT c FROM role WHERE edge.source=role.id)+(SELECT c FROM role WHERE edge.target=role.id) AS sort
    FROM edge, sp
    WHERE edge.play = ? AND edge.sp = sp.id
    GROUP BY edge.source, edge.target
    ORDER BY sort DESC
    ";
    $q = $pdo->prepare($sql);

    $q->execute(array($play['id']));
    $data = array();
    $max = false;
    $nodes = array();
    $i = 1; // more
    while ($row = $q->fetch()) {
      if(!isset($cast[$row['source']])) continue; // sortie de la liste des rôles
      if(!isset($cast[$row['target']])) continue; // sortie de la liste des rôles

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
      $i++;
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
    }
    return $data;
  }
  static function cli() {
    $usage = '
    usage    : php -f '.basename(__FILE__).' base.sqlite auteur_titre '."\n\n";
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit($usage);
    $sqlite = array_shift($_SERVER['argv']);
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $playcode = array_shift($_SERVER['argv']);
    self::gephi( $pdo, $playcode );
  }

}

 ?>
