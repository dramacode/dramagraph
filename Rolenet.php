<?php
/**
 * Visualisation relatives au réseaux de parole
 */
class Dramagraph_Rolenet
{
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

  /**
   *
   */
  public static function graph( $pdo, $playcode )
  {
    $html = array();
    $id = 'graph_'.$playcode;
    $html[] = self::canvas( $id );
    $html[] = '<script> (function () { var data =';
    $html[] = self::sigma( $pdo, $playcode );
    $html[] = ' var graph = new Rolenet("'.$id.'", data ); //';
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
  public static function gephi( $pdo, $filename )
  {
    $data = self::nodes( $pdo, $filename );
    $f = $filename.'-nodes.csv';
    $w = fopen($f, 'w');
    for ($i=0; $i<count($data); $i++) {
      fwrite($w, implode("\t", $data[$i])."\n");
    }
    fclose($w);
    echo $f.'  ';
    $data = self::edges( $pdo, $filename );
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
  public static function reltable ( $pdo, $playcode )
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    // récupérer la distribution, pour avoir les noms de personnage
    $cast = array();
    foreach  ($pdo->query("SELECT id, code, label, c FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC) as $row) {
      $cast[$row['id']] = $row;
    }
    $html = array();
    $html[] = '
<table class="sortable">
  <caption>Table des relations</caption>
  <tr>
    <th title="Nombre de scènes avec les deux personnages en présence">Scènes</th>
    <th title="Quantité de texte de la relation">Texte</th>
    <th title="Part de la relation dans le texte">% texte</th>
    <th title="Nom de personnage">Pers. 1</th>
    <th title="Taille moyenne des répliques du personnage dans la relation en lignes (60 signes))">Repl. moy.</th>
    <th title="Part de ce personnage dans la relation">%</th>
    <th title="Nom de personnage">Pers. 2</th>
    <th title="Taille moyenne des répliques du personnage dans la relation en lignes (60 signes))">Repl. moy.</th>
    <th title="Part de ce personnage dans la relation">%</th>
  </tr>';
    // $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $sql = "SELECT
        min (source, target) AS m1,
        max( source, target ) AS m2,
        edge.source,
        edge.target,
        count(sp) AS sp,
        sum(sp.c) AS c,
        count(DISTINCT configuration) AS confs
      FROM edge, sp
      WHERE edge.sp = sp.id AND edge.play = ".$play['id']."
      GROUP BY edge.source, edge.target
      ORDER BY m1, m2
    ";
    $m1 = null;
    $m1c = 0;
    $m1sp = 0;
    $m2 = null;
    $m2c = 0;
    $m2sp = 0;
    $c = 0;
    $confs = 0;

    foreach ( $pdo->query( $sql, PDO::FETCH_ASSOC ) as $row ) {
      // $m1 != null && $m2 != null &&
      // 2e ligne
      if ( $m1 == $row['m1'] && $m2 == $row['m2'] ) {
        $c = $c + $row['c'];
        if ( $row['confs'] > $confs ) $confs = $row['confs'];
        if ( $row['source'] == $m1 ) {
          $m1c = $row['c'];
          $m1sp = $row['sp'];
        }
        else if ( $row['source'] == $m2 ) {
          $m2c = $row['c'];
          $m2sp = $row['sp'];
        }
        $html[] = '<tr>
  <td align="right">'.$confs.'</td>
  <td align="right">'.ceil( $c/60 ).' l.</td>
  <td align="right">'.ceil( 100 * $c / $play['c'] ).' %</td>
  <td>'.$cast[$m1]['label'].'</td>
  <td align="right">'.number_format( $m1c/$m1sp/60 , 1, ',', ' ').' l.</td>
  <td align="right">'.ceil( 100 * $m1c / $c ).' %</td>
  <td>'.$cast[$m2]['label'].'</td>
  <td align="right">'.number_format( $m2c/$m2sp/60 , 1, ',', ' ').' l.</td>
  <td align="right">'.ceil( 100 * $m2c / $c ).' %</td>
</tr>';
        $m1 = null;
        $m2 = null;
        $confs = 0;
      }
      // monologue
      if ( $row['m1'] == $row['m2'] ) {
        $html[] = '<tr>
  <td align="right">'.$row['confs'].'</td>
  <td align="right">'.ceil( $row['c']/60 ).' l.</td>
  <td align="right">'.ceil( 100 * $row['c'] / $play['c'] ).' %</td>
  <td>'.$cast[$row['m1']]['label'].'</td>
  <td align="right">'.number_format( $row['c']/60/$row['sp'] , 1, ',', ' ').' l.</td>
  <td align="right">100 %</td>
  <td>'.$cast[$row['m1']]['label'].'</td>
  <td align="right">'.number_format( $row['c']/60/$row['sp'] , 1, ',', ' ').' l.</td>
  <td align="right">100 %</td>
</tr>';
        $c = 0;
        $m1 = null;
        $m2 = null;
        $confs = 0;
      }
      // autre ligne
      else {
        $c = $row['c'];
        $m1 = $row['m1'];
        $m2 = $row['m2'];
        if ( $row['source'] == $m1 ) {
          $m1c = $row['c'];
          $m1sp = $row['sp'];
        }
        else if ( $row['source'] == $m2 ) {
          $m2c = $row['c'];
          $m2sp = $row['sp'];
        }
        $confs = $row['confs'];
      }
    }
    $html[] = "</table>";
    return implode("\n", $html);
  }
  /**
   * Table des rôles
   */
  public static function roletable ($pdo, $playcode)
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $html = array();
    $html[] = '<table class="sortable">';
    $html[] = '  <caption>Table des rôles</caption>';
    $html[] = '  <tr>';
    $html[] = '    <th title="Nom du personnage dans l’ordre de la distribution">Personnage</th>';
    $html[] = '    <th title="Quantité de texte du personnage en lignes (60 signes)">Texte</th>';
    $html[] = '    <th title="Part du personnager dans le texte de la pièce">% texte</th>';
    $html[] = '    <th title="Taille moyenne des répliques du personnage, en lignes (60 signes)">Répl. moy.</th>';
    // $html[] = '    <th title="Nombre de rôles interagissant avec le personnage">Interl.</th>';
    $html[] = '    <th title="Nombre de scènes où le personnage est présent">Scènes</th>';
    $html[] = '    <th title="Présence du personnage en proportion du texte dit de la pièce">Prés.</th>';
    $html[] = '    <th title="Part du texte que le personnage prononce, durant son temps de présence">Txt. % prés.</th>';
    // $html[] = '    <th title="Nombre de répliques du personnages">Répl.</th>';
    $html[] = '    <th title="Nombre moyen de personnages parlants sur scène au moment où le personnage est présent">Occupation</th>';
    $html[] = '  </tr>';
    $html[] = '  <tr>';
    $html[] = '    <td data-sort="0">[TOUS]</td>';
    $html[] = '    <td align="right">'.number_format($play['c']/60, 0, ',', ' ').' l.</td>';
    $html[] = '    <td align="right">100 %</td>';
    $html[] = '    <td align="right">'.number_format($play['c']/($play['sp']*60), 1, ',', ' ').' l.</td>';
    // $html[] = '    <td align="right" title="Nombre total de personnages">'.$play['roles'].'</td>';
    $html[] = '    <td align="right">'.$play['confs'].'</td>';
    $html[] = '    <td align="right">100 %</td>';
    // $html[] = '    <td align="right">'.number_format($play['entries']/$play['roles'], 1, ',', ' ').'</td>';
    $html[] = '    <td align="right">'.ceil(100 * $play['c']/$play['pspeakers'])." %</td>";
    // $html[] = '    <td align="right">'.$play['sp'].'</td>';
    $html[] = '    <td align="right">'.number_format($play['pspeakers']/$play['c'], 1, ',', ' ').' pers.</td>';
    $html[] = '  </tr>';
    $i = 1;
    foreach ($pdo->query("SELECT * FROM role WHERE role.play = ".$play['id']." ORDER BY ord") as $role) {
      $html[] = "  <tr>";
      $html[] = '    <td data-sort="'.$i.'" title="'.$role['title'].'">'.$role['label']."</td>";
      $html[] = '    <td align="right">'.number_format($role['c']/60, 0, ',', ' ').' l.</td>';
      $html[] = '    <td align="right">'.ceil(100 * $role['c']/$play['c'])." %</td>";
      if ($role['sp']) $html[] = '    <td align="right">'.number_format($role['c']/($role['sp']*60), 1, ',', ' ')." l.</td>";
      else $html[] = '<td align="right">0</td>';

      // $html[] = '    <td align="right">'.$role['targets']."</td>";
      /*
      */
      $html[] = '    <td align="right">'.$role['confs'].'</td>';
      $html[] = '    <td align="right">'.ceil(100 * $role['presence']/$play['c'])." %</td>";
      // $html[] = '    <td align="right">'.$role['entries'].'</td>';
      if ($role['presence']) $html[] = '    <td align="right">'.ceil( 100 * $role['c']/$role['presence'])." %</td>";
      else $html[] = '    <td align="right">0</td>';
      // $html[] = '    <td align="right">'.$role['sp']."</td>";
      // echo '    <td align="right">'.$node['ic']."</td>\n";
      // echo '    <td align="right">'.$node['isp']."</td>\n";
      // echo '    <td align="right">'.round($node['ic']/$node['isp'])."</td>\n";
      if ($role['presence']) $html[] = '    <td align="right">'.number_format($role['pspeakers']/$role['presence'], 1, ',', ' ').' pers.</td>';
      else $html[] = '    <td align="right">0</td>';
      $html[] = "  </tr>";
      $i++;
    }
    $html[] = '</table>';
    return implode("\n", $html);
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
    foreach ($pdo->query("SELECT * FROM role WHERE role.play = ".$play['id']." ORDER BY role.c DESC") as $role) {
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
      $role['class'] = $class;
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
    foreach  ($pdo->query("SELECT id, code, label, c FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC) as $row) {
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


}

 ?>
