<?php
// declare(encoding = 'utf-8');
setlocale(LC_ALL, 'fr_FR.utf8');
mb_internal_encoding("UTF-8");

if (realpath($_SERVER['SCRIPT_FILENAME']) != realpath(__FILE__)); // file is include do nothing
else if (php_sapi_name() == "cli") {
  Dramaturgie_Base::cli();
}
class Dramaturgie_Base {
  /** filtrer */
  const FILTER = true;
  /** Lien à une base SQLite, unique */
  public $pdo;
  /** Document XML */
  private $_dom;
  /** Processeur xpath */
  private $_xpath;
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
   * Charger un fichier XML
   */
  public function __construct($sqlitefile=null) {
    if ($sqlitefile) $this->connect($sqlitefile);
  }
  /** Charger un fichier XML */
  public function load($xmlfile) {
    $this->_dom = new DOMDocument();
    $this->_dom->preserveWhiteSpace = false;
    $this->_dom->formatOutput=true;
    $this->_dom->substituteEntities=true;
    $this->_dom->load($xmlfile, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOERROR | LIBXML_NOWARNING);
    $this->_xpath = new DOMXpath($this->_dom);
    $this->_xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");

  }


  /** Connexion à la base */
  private function connect($sqlite) {
    $sql = 'dramaturgie.sql';
    $dsn = "sqlite:" . $sqlite;
    // si la base n’existe pas, la créer
    if (!file_exists($sqlite)) {
      if (!file_exists($dir = dirname($sqlite))) {
        mkdir($dir, 0775, true);
        @chmod($dir, 0775);  // let @, if www-data is not owner but allowed to write
      }
      $this->pdo = new PDO($dsn);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
      @chmod($sqlite, 0775);
      $this->pdo->exec(file_get_contents(dirname(__FILE__).'/'.$sql));
    }
    else {
      $this->pdo = new PDO($dsn);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }
    // table temporaire en mémoire
    $this->pdo->exec("PRAGMA temp_store = 2;");
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
   * Json compatible avec la librairie sigma.js
   */
  public function sigma($playcode) {
    $nodes = $this->nodes($playcode, self::FILTER);
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
      $angle =  -M_PI + (M_PI*2/$count) *  ($i-1);
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
      echo "{id:'".$node['code']."', label:".json_encode($node['label'],  $json_options).", size:".(0+$node['oc']).", x: $x, y: $y".$col.", title: ".json_encode($node['title'],  $json_options).', type:"drama"}';
      $i++;
    }
    echo "\n  ]";

    echo "\n};\n";
  }
  /**
   * Ligne bibliographique pour une pièce
   */
  public function bibl($play) {
    if (is_string($play)) {
      $playcode = $this->pdo->quote($playcode);
      $play = $this->pdo->query("SELECT * FROM play WHERE code = $playcode")->fetch();
    }
    $bibl = $play['author'].', '.$play['title'];
    $meta = array();
    if ($play['year']) $meta[] = $play['year'];
    if ($play['genre'] == 'tragedy') $meta[] = 'tragédie';
    else if ($play['genre'] == 'comedy') $meta[] = 'comédie';
    if ($play['acts']) $meta[] = $play['acts'].(($play['acts']>2)?" actes":" acte");
    $meta[] = (($play['verse'])?"vers":"prose");
    if (count($meta)) $bibl .= " (".implode(", ", $meta).")";
    return $bibl;
  }


  /**
   * TODO, pour étudier le rapport entre nombre de caractère et nombre de répliques
   */
  public function rolerate($playcode, $max=1200) {
    $playcode = $this->pdo->quote($playcode);
    if(! (0+$max)) $max=1000;
    $play = $this->pdo->query("SELECT * FROM play WHERE code = $playcode")->fetch();
    $playwidth = $play['c'] / (100000/$max);

    echo '
<style>
.rolerate { font-family: sans-serif; font-size: 15px; }
.rolerate, .rolerate * { box-sizing: border-box; }
div.rolerate { white-space: nowrap; border-bottom: 1px solid #FFFFFF; width: '.floor($playwidth+120).'px;  }
div.rolerate:after { content:""; display: table; clear: both; }
.rolerate label { text-align: right; width: 90px; padding: 0 1ex 0 0; float: left; display: block}
.rolerate .role { overflow: hidden; background-color: rgba(192, 192, 192, 0.7); color: rgba(0, 0, 0, 0.5); font-stretch: ultra-condensed; float: left; height: 2em; border-left: 1px #FFFFFF solid; }
.rolerate .role1 { background-color: rgba(255, 0, 0, 0.5); border-bottom: none; color: rgba(255, 255, 255, 1);}
.rolerate .role2 { background-color: rgba(128, 0, 128, 0.5); border-bottom: none; color: rgba(255, 255, 255, 1);}
.rolerate .role3 { background-color: rgba(0, 0, 255, 0.5); border-bottom: none; color:  rgba(255, 255, 255, 1);}
.rolerate .role4 { background-color: rgba(0, 0, 128, 0.5); border-bottom: none; color:  rgba(255, 255, 255, 1);}
.rolerate .role5 { background-color: rgba(128, 128, 128, 0.5); border-bottom: none; color: rgba(255, 255, 255, 1); }
</style>
    ';
    $dist = array();
    foreach ($this->pdo->query("SELECT * FROM role WHERE play = $playcode ORDER BY c DESC") as $role) {
      $dist[$role['code']] = array(
        'label'=>$role['label'],
        'sp' => $role['sp'],
        'w' => $role['w'],
        'c' => $role['c']
      );
    }
    foreach (array('c', 'w', 'sp') as $unit) {
      echo '<div class="rolerate">'."\n";
      echo '  <label class="unit">'."\n";
      if ($unit=='c') echo "Caractères";
      else if ($unit=='w') echo "Mots";
      if ($unit=='sp') echo "Répliques";
      echo '</label>';
      $i=1;
      foreach ($dist as $code=>$stats) {
        $width = round($playwidth*$stats[$unit]/$play[$unit]);
        echo '<span class="role role'.$i.'" title="'.$stats['label'].' " style="width: '.$width.'px"> '.$stats['label'].' </span>';
        $i++;
      }
      echo '</div>';
    }
  }

  /**
   * Rythme des didascalies
   */
  public function stageline($playcode, $widthref=1000) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    // boucler sur les actes
  }

  /**
   * Panneau vertical de pièce
   */
  public function charline($playcode, $width=230, $heightref=600) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    $playid = $play['id'];
    if (!$play) return false;
    $scenewidth = $width - 75;
    echo '
<style>
.charline { font-family: sans-serif; font-size: 13px; line-height: 1.2em; position: relative; }
.charline, .charline * { box-sizing: border-box; }
.charline:after, .charline .act:after, .charline .scene:after, .charline .cast:after, .charline .sps:after { content:""; display:table; clear:both; }
.charline a { border-bottom: none; text-decoration: none; }
.charline a:hover { background: transparent; }

b.n { position: absolute; left: 0; font-weight: bold; color: #999; }

.charline div.scene { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.charline .acthead { display: block; text-align: center; padding: 5px  30px 1px 0; clear: both;  }
.charline a.cast { display: block; height: 100%; float: left; padding-left: 2ex; }
.charline div.sps { margin-left: 5px; margin-bottom: 1px; float: left; width: 35px; border-right: 1px dotted #CCCCCC; background: #FFFFFF; }
.charline div.sps a { display: block; line-height: 3px; position: relative; }
.charline div.sps b { background: #AAAAAA; display: block; }
.charline .role { float: left; height: 100%; border-left: 1px rgba(255, 255, 255, 0.5) solid; border-radius: 3px/1em; font-stretch: ultra-condensed; }
.charline .role span { overflow: hidden; padding-left: 1ex; padding-top: 2px;}

</style>
    ';

    // 1 pixel = 1000 caractères
    if (!$heightref) $playheight = '800';
    else if (is_numeric($heightref) && $heightref > 50) $playheight = round($play['c'] / (100000/$heightref));
    else $playheight = '800';


    // requête sur le nombre de caractère d’un rôle dans une scène
    $qsp = $this->pdo->prepare("SELECT sum(c) FROM sp WHERE play = $playid AND scene = ? AND source = ?");
    $qcn = $this->pdo->prepare("SELECT * FROM sp WHERE play = $playid AND scene = ? AND cn <= ? ORDER BY play, cn DESC LIMIT 1");
    echo '<div class="charline">'."\n";

    // loop on acts
    foreach ($this->pdo->query("SELECT * FROM act WHERE play = $playid ORDER BY rowid") as $act) {
      echo '  <a href="#'.$act['code'].'" class="acthead">'.$act['label']."</a>\n";
      echo '  <div class="act">'."\n";
      $actheight = $playheight * $act['c']/$play['c'];
      if(!$act['c']){
        echo "  </div>\n";
        continue; // probably an interlude
      }
      // loop on scene
      foreach ($this->pdo->query("SELECT * FROM scene WHERE act = ".$act['id']) as $scene) {
        $sceneheight = 3+ ceil($actheight * $scene['c']/$act['c']);
        if (!isset($scene['n'])) $scene['n'] = 0+ preg_replace('/\D/', '', $scene['code']);
        // scene cont
        echo '    <div class="scene" style="height: '.($sceneheight +1).'px;" title="Acte '.$act['n'].', scène '.$scene['n'].'">'."\n";
          echo '      <b class="n">'.$scene['n'].'</b>'."\n";
          // role bar
          echo '      <a href="#'.$scene['code'].'" class="cast">'."\n";
          $i = 0;
          // loop on role
          foreach ($this->pdo->query("SELECT * FROM role WHERE play = $playid ORDER BY c DESC") as $role) {
            $qsp->execute(array($scene['id'], $role['code']));
            list($c) = $qsp->fetch();
            $i++;
            if (!$c) continue;
            $rolewidth = number_format($scenewidth * $c / $scene['c']) ;
            echo '<span class="role role'.$i.' '.$role['rend'].'"';
            echo ' style="width: '.$rolewidth.'px"';
            echo ' title="'.$role['label'].', acte '.$act['n'].', scène '.$scene['n'].', '.round(100*$c / $scene['c']).'%"';
            echo '>';
            if ($rolewidth > 35 && $sceneheight > 12 ) { // && !isset($list[$role['code']])
              echo '<span>'.$role['label'].'</span>';
              $list[$role['code']] = true;
            }
            else echo ' ';
            echo '</span>';
          }
          echo "      </a>\n";
          echo '      <div class="sps">';
          $splast = null;
          for ($pixel = 0; $pixel <= $sceneheight; $pixel = $pixel +3) {
            $cn = $scene['cn'] + ceil($scene['c'] * $pixel / $sceneheight);
            $qcn->execute( array($scene['id'], $cn));
            $sp = $qcn->fetch();
            if (!$sp) $sp = $splast;
            if($splast) {
              $width = 3*($sp['id'] - $splast['id']);
              echo '<a href="#'.$splast['code'].'"><b style="width: '.$width.'px"> </b></a>';
            }
            $splast = $sp;
          }
          echo "      </div>\n";

        echo "    </div>\n";
      }
      echo "  </div>\n";
    }
    echo "</div>\n";
  }

  /**
   * Table
   */
  public function timetable($playcode, $max=null) {
    $playcode = $this->pdo->quote($playcode);
    $play = $this->pdo->query("SELECT * FROM play WHERE code = $playcode")->fetch();
    // 1 pixel = 1000 caractères
    if (!$max) $width = '';
    if (is_numeric($max) && $max > 50) $width = ' width="'.round($play['c'] / (100000/$max)).'"';
    else $width = ' width="'.$max.'"';

    echo '
<table class="timetable" '.$width.'>
  <caption>'.($this->bibl($play)).'</caption>
';
    // timeline des scènes
    $actlast = null;
    echo '<thead>
  <tr class="scenes">
';
    // attention les pourcentages de la largeur sont comptés sans les noms de personnages
    foreach ($this->pdo->query("SELECT * FROM scene WHERE play = $playcode") as $scene) {
      $class = ' scene';
      if ($actlast != $scene['act']) $class .= " scene1";
      $actlast = $scene['act'];
      $width = number_format(100*($scene['c']/$play['c']), 1);
      $n = 0+ preg_replace('/\D/', '', $scene['code']);
      echo '    <td class="'.$class.'" style="width: '.$width.'%;" title="Acte '.$scene['act'].', scène '.$n.'"/>'."\n";
    }
    echo "  </tr>
  </thead>
";

    // requête sur le nombre de caractère d’un rôle dans une scène
    $qsp = $this->pdo->prepare("SELECT sum(c) FROM sp WHERE play = $playcode AND scene = ? AND source = ?");
    // Boucler sur les personnages, un par ligne
    foreach ($this->pdo->query("SELECT * FROM role WHERE play = $playcode ORDER BY c DESC") as $role) {
      echo '  <tr class="'.$role['code'].'">'."\n";
      // boucler sur les scènes
      $label = $role['label'];
      foreach ($this->pdo->query("SELECT * FROM scene WHERE play = $playcode") as $scene) {
        $class = "";
        if ($actlast != $scene['act']) $class .= " scene1";
        $actlast = $scene['act'];
        $qsp->execute(array($scene['code'], $role['code']));
        list($c) = $qsp->fetch();
        $opacity = number_format($c / $scene['c'], 1);
        if (trim($class)) $class = ' class="'.trim($class).'"';
        $n = 0+ preg_replace('/\D/', '', $scene['code']);
        echo '<td'.$class.' style="opacity: '.$opacity.'" title="'.$label.', acte '.$scene['act'].', scène '.$n.', '.round(100*$c / $scene['c']).'%"';
        if (!$c) echo "/>\n";
        else echo "> </td>\n";
      }
      $title='';
      if ($role['title']) $title .= $role['title'].', '.round(100*$role['c'] / $play['c']).'%';
      echo '<th style="position: absolute; " title="'.$title.'">'.$label.'</td>';
      echo '  </tr>'."\n";
    }
    echo '
</table>
';
  }
  /**
   * Liste de nœuds
   */
  public function nodes($playcode, $filter=false) {
    $playcode = $this->pdo->quote($playcode);
    $data = array();
    $rank = 1;
    foreach ($this->pdo->query("SELECT role.* FROM role, play WHERE role.play = play.id AND play.code = $playcode ORDER BY role.oc DESC") as $role) {
      if (!$role['sources']) continue;
      /*
      if ($filter);
      else if (!$role['oc'] && !$role['ic']) continue;
      else if (!$role['targets']) continue;
      else if (strpos('   '.$role['rend'].' ', ' nograph ') !== false) continue;
      // threshold of <sp>, 20 ?
      else if ($role['osp'] < 21 ) continue;
      */

      $class = "";
      if ($role['sex'] == 2) $class = "female";
      else if ($role['sex'] == 1) $class = "male";
      if ($role['status'] == 'exterior') $class .= " exterior";
      else if ($role['status'] == 'inferior') $class .= " inferior";
      else if ($role['status'] == 'superior') $class .= " superior";
      else if ($role['age'] == 'junior') $class .= " junior";
      else if ($role['age'] == 'veteran') $class .= " veteran";

      // 'c' => $sp['c'],
      // 'sp' => $sp['sp'],
      // 'conf' => $sp['conf'],

      $data[$role['code']] = array(
        'code' => $role['code'],
        'label' => $role['label'],
        'title' => ($role['title'])?$role['title']:'',
        'targets' => $role['targets'],
        'confs' => $role['confs'],
        'class' => $class,
        'rank' => $rank,
        'oc' => $role['oc'],
        'osp' => $role['osp'],
        'ic' => $role['ic'],
        'isp' => $role['isp'],
      );
      $rank++;
    }
    return $data;
  }
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
      echo '    <td align="right">'.number_format($edge['c']/80, 0)." l.</td>\n";
      echo '    <td align="right">'.$edge['sp']."</td>\n";
      echo '    <td align="right">'.number_format($edge['c']/($edge['sp']*80), 2)." l.</td>\n";
      echo "  </tr>\n";
    }

    echo '</table>';
  }
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
      echo '    <td align="right">'.number_format(100 * ($node['oc']+$node['ic'])/$play['c'], 0)." %</td>\n";
      echo '    <td align="right">'.number_format(100 * $node['oc']/$play['c'], 0)." %</td>\n";
      echo '    <td align="right">'.number_format( 100 * $node['oc']/($node['oc']+$node['ic']) , 0)." %</td>";
      echo '    <td align="right">'.$node['osp']."</td>\n";
      if ($node['osp']) echo '    <td align="right">'.number_format($node['oc']/($node['osp']*80), 2)." l.</td>\n";
      else echo "<td/>";
      // echo '    <td align="right">'.$node['ic']."</td>\n";
      // echo '    <td align="right">'.$node['isp']."</td>\n";
      // echo '    <td align="right">'.round($node['ic']/$node['isp'])."</td>\n";
      echo "  </tr>\n";
    }
    echo '<tfoot>
<tr><td colspan="7">Le temps de présence est relatif aux signes prononcés.
<br/> l. : lignes (= 80 signes)</td></tr>
    </tfoot>
    ';
    echo '</table>';

  }
  /**
   * Évolution de la parole selon les personnages
   */
  public function edges($playcode) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    // load a dic of rowid=>code for roles
    $cast = array();
    foreach  ($this->pdo->query("SELECT id, code, label, oc FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC) as $row) {
      $cast[$row['id']] = $row;
    }
    $sql = "SELECT
      edge.source,
      edge.target,
      count(sp) AS sp,
      sum(sp.c) AS c,
      count(DISTINCT configuration) AS confs,
      (SELECT oc FROM role WHERE edge.source=role.id)+(SELECT oc FROM role WHERE edge.target=role.id) AS sort
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
  /**
   * Lister les fichiers XML d’un répertoire pour en proposer une table avec quelques métadonnées
   */
  public function dirtable($dir) {
    $dir = rtrim($dir, ' /\\').'/';
    foreach(glob($dir.'*.xml') as $file) {
      $play = $this->play($file);
      echo '
  <tr>
    <td class="author">'.$play['author'].'</td>
    <td class="year">'.$play['year'].'</td>
    <td class="title"><a href="'.basename($file).'">'.$play['title'].'</a></td>
    <td class="genre">'.$play['genre'].'</td>
    <td class="verse">'.(($play['verse'])?"vers":"prose").'</td>
    <td class="acts">'.$play['acts'].'</td>
  </tr>
';
    }
  }
  /**
   * Métadonnées de pièce
   */
  public function play($file) {
    $play = array();
    $this->load($file);
    $play['code'] = pathinfo($file, PATHINFO_FILENAME);
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:author");
    if ($nl->length) $play['author'] = $nl->item(0)->textContent;
    else $play['author'] = null;
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:profileDesc/tei:creation/tei:date");
    if ($nl->length) {
      $play['year'] = $nl->item(0)->getAttribute ('when');
      if(!$play['year']) $play['year'] = $nl->item(0)->nodeValue;
      $play['year'] = substr(trim($play['year']), 0, 4);
    }
    else $play['year'] = null;
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:title");
    if ($nl->length) $play['title'] = $nl->item(0)->textContent;
    else $play['title'] = null;
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:term[@type='genre']/@subtype");
    if ($nl->length) $play['genre'] = $nl->item(0)->nodeValue;
    else $play['genre'] = null;

    $play['acts'] = $this->_xpath->evaluate("count(/*/tei:text/tei:body//tei:*[@type='act'])");
    if (!$play['acts']) $play['acts'] = $this->_xpath->evaluate("count(/*/tei:text/tei:body/*[tei:div|tei:div2])");
    if (!$play['acts']) $play['acts'] = 1;
    $l = $this->_xpath->evaluate("count(//tei:sp/tei:l)");
    $p = $this->_xpath->evaluate("count(//tei:sp/tei:p)");
    if ($l > 2*$p) $play['verse'] = true;
    else if ($p > 2*$l) $play['verse'] = false;
    else $play['verse'] = null;
    return $play;
  }
  /**
   * Charger un XML en base
   */
  public function insert($file) {
    $time = microtime(true);
    if (STDERR) fwrite(STDERR, $file);
    $play = $this->play($file);
    $this->pdo->exec("DELETE FROM play WHERE code = ".$this->pdo->quote($play['code']));
    $q = $this->pdo->prepare("
    INSERT INTO play (code, author, title, year, acts, verse, genre)
              VALUES (?,    ?,      ?,     ?,    ?,    ?,     ?);
    ");
    $q->execute(array(
      $play['code'],
      $play['author'],
      $play['title'],
      $play['year'],
      $play['acts'],
      $play['verse'],
      $play['genre'],
    ));
    $playid = $this->pdo->lastInsertId();
    // roles
    $this->pdo->beginTransaction();
    $q = $this->pdo->prepare("
    INSERT INTO role (play, code, label, title, note, rend, sex, age, status)
              VALUES (?,    ?,    ?,     ?,     ?,    ?,    ?,   ?,   ?);
    ");
    $nodes = $this->_xpath->query("//tei:role[@xml:id]|//tei:person[@xml:id]");
    $cast = array();
    foreach ($nodes as $n) {
      $note = null;
      $code = $n->getAttribute ('xml:id');
      if (!$code) continue;
      $label = $n->getAttribute ('n');
      if (!$label) $label = $n->nodeValue;
      $nl = @$n->parentNode->getElementsByTagName("roleDesc");
      if ($nl->length) $title = trim($nl->item(0)->nodeValue);
      else {
        $title = '';
        $nl = $n->parentNode->firstChild;
        while($nl) {
          if ($nl->nodeType == XML_TEXT_NODE ) $title .= $nl->nodeValue;
          $nl = $nl->nextSibling;
        }
        $title = preg_replace(array("/^[\s :;,\.]+/u", "/[\s :,;\.]+$/u"), array('', ''), $title);
        if (!$title) $title = null;
      }
      $rend = ' '.$n->getAttribute ('rend').' '; // espace séparateur
      if (preg_match('@ female @i', $rend)) $sex = 2;
      else if (preg_match('@ male @i', $rend)) $sex = 1;
      else $sex = null;
      preg_match('@ (cadet|junior|senior|veteran) @i', $rend, $matches);
      if (isset($matches[1]) && $matches[1]) $age = $matches[1];
      else $age = null;
      preg_match('@ (inferior|superior|exterior) @i', $rend, $matches);
      if (isset($matches[1]) && $matches[1]) $status = $matches[1];
      else $status = null;
      if (!trim($rend)) $rend = null;
      if (!$label) $label = $code;
      $q->execute(array(
        $playid,
        $code,
        $label,
        $title,
        $note,
        $rend,
        $sex,
        $age,
        $status,
      ));
      // conserver le rowid du role
      $cast[$code] = $this->pdo->lastInsertId();;
    }
    $this->pdo->commit();
    if (STDERR) fwrite(STDERR, " play+role: ".number_format(microtime(true) - $time, 3)."s. ");
    $time = microtime(true);
    $xsl = new DOMDocument("1.0", "UTF-8");
    $xsl->load(dirname(__FILE__).'/drama2csv.xsl');
    $trans = new XSLTProcessor();
    $trans->importStyleSheet($xsl);
    $trans->setParameter('', 'filename', $play['code']);
    $csv = $trans->transformToXML($this->_dom);

    // placer la chaîne dans un stream pour profiter du parseur fgetscsv
    $stream = fopen('php://memory', 'w+');
    fwrite($stream, $csv);
    rewind($stream);
    $inact = $this->pdo->prepare("
    INSERT INTO act (play, code, n, label, type, cn, wn, ln)
            VALUES  (?,    ?,    ?, ?,     ?,    ?,  ?,  ?);
    ");
    $inscene = $this->pdo->prepare("
    INSERT INTO scene (play, act, code, n, label, type, cn, wn, ln)
               VALUES (?,    ?,   ?,    ?, ?,     ?,    ?,  ?,  ?);
    ");
    $inconf = $this->pdo->prepare("
    INSERT INTO configuration (play, code, n, label, cn, wn, ln)
                       VALUES (?,    ?,   ?,  ?,     ?,  ?,  ?);
    ");
    $insp = $this->pdo->prepare("
    INSERT INTO sp (play, act, scene, configuration, role, code, cn, wn, ln, c, w, l, text)
            VALUES (?,    ?,   ?,     ?,             ?,    ?,    ?,  ?,  ?,  ?, ?, ?, ?);
    ");
    $instage = $this->pdo->prepare("
    INSERT INTO stage (play, code, n, cn, wn, ln, c, w, text)
               VALUES (?,    ?,    ?, ?,  ?,  ?,  ?, ?, ?);
    ");
    $intarget = $this->pdo->prepare("
    INSERT INTO edge (play, sp, source, target)
              VALUES (?,    ?,  ?,      ?);
    ");
    // première ligne, nom de colonne, à utiliser comme clé pour la collecte des lignes
    $keys = fgetcsv($stream, 0, "\t");
    // boucle pour charger la base, ne pas oublier de démarrer une transaction
    $this->pdo->beginTransaction();
    $wn = 1;
    $cn = 1;
    $ln = null;
    $act = null;
    $scene= null;
    $conf = array();
    while (($values = fgetcsv($stream, 0, "\t")) !== FALSE) {
      // print_r(array_merge($values, array_fill(0, count($keys) - count($values), null)));
      // étiqueter les cases avec les noms de colonne de la première ligne, les deux tableaux combinés doivent avoir la même taille
      if (count($keys) > count($values)) $data = array_combine($keys, array_merge($values, array_fill(0, count($keys) - count($values), null)));
      else $data = array_combine($keys, $values);
      // réplique
      if ($data['object'] == 'sp' ) {
        if (!isset($cast[$data['label']]) && STDERR) fwrite(STDERR, "sp/@who not in castlist ".$data['label']. " [".$data['code']."]\n");
        if (!isset($conf[$data['label']]) && STDERR) fwrite(STDERR, 'sp/@who="'.$data['label'].'" not in configuration ("'.implode('", "', array_keys($conf)).'")  ['.$data['code']."]\n");
        if (!$data['c']) {
          if (STDERR) fwrite(STDERR, "Empty <sp> [".$data['code']."]\n");
          continue;
        }
        if (!$data['l']) $data['l'] = null;
        if ($data['ln']) $ln = $data['ln'];
        $wn = $wn + $data['w'];
        $cn = $cn + $data['c'];
        $source = $cast[$data['label']];
        try {
          $row = array(
            $playid,
            $actid,
            $sceneid,
            $confid,
            $source,
            $data['code'],
            $cn,
            $wn,
            $ln,
            $data['c'],
            $data['w'],
            $data['l'],
            $data['text'], // text
          );
          $insp->execute($row);
          $spid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      <sp>".implode(', ', $row)."\n".$data['code']."\n".$e."\n\n");
        }
        $target = '';
        try {
          // ajouter les destinataires de la réplique, dépend de la conf
          if (!count($conf)); // erreur ?
          // monologue
          else if (1 == count($conf)) {
            $target = $source;
            $intarget->execute(array(
              $playid,
              $spid,
              $source,
              $target,
            ));
          }
          //
          else {
            foreach ($conf as $k=>$null) {
              if (!isset($cast[$k])) continue; // error
              // ne se parle pas si plus d’une personne
              if ($cast[$k] == $source) continue;
              $target = $cast[$k];
              $intarget->execute(array(
                $playid,
                $spid,
                $source,
                $target,
              ));
            }
          }
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      edge: ".$source.'  > '.$target.' '.$k."\n".$e."\n\n");
        }
      }
      // configuration
      else if ($data['object'] == 'configuration' ) {
        $confid = null;
        $data['label'] = trim($data['label']);
        // record the availabe roles for this configuration
        if ($data['label']) {
          $conf = array_flip(explode(' ', $data['label']));
          foreach ($conf as $k=>$v) {
            if (STDERR && !isset($cast[$k])) fwrite(STDERR, 'person/@corresp="'.$k. '" unknown role ['.$data['code']."]\n");
          }
        }
        else $conf = array();
        try {
            $inconf->execute(array(
            $playid,
            $data['code'],
            $data['n'],
            $data['label'],
            $cn,
            $wn,
            $ln,
          ));
          $confid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      NOT UNIQUE scene ? ".$data['code']."\n".$e."\n\n");
        }
      }
      // scènes, parfois non contenues dans un acte (pièces en un acte)
      else if ($data['object'] == 'div2' || $data['type'] == 'scene' ) {
        $sceneid = null;
        if (!$data['type']) $data['type'] = null;
        try {
          $inscene->execute(array(
            $playid,
            $actid,
            $data['code'],
            $data['n'],
            $data['label'],
            $data['type'],
          ));
          $sceneid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      NOT UNIQUE scene ? ".$data['code']."\n".$e."\n\n");
        }
      }
      // actes
      else if ($data['object'] == 'div1' ) {
        $sceneid = null;
        $actid = null;
        if(!$data['type']) $data['type'] = 'act';
        try {
          $inact->execute(array(
            $playid,
            $data['code'],
            $data['n'],
            $data['label'],
            $data['type'],
          ));
          $actid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      NOT UNIQUE act ? ".$data['code']."\n".$e."\n\n");
        }
      }
      // didascalies
      else if ($data['object'] == 'stage' ) {
        // (play, code, n, cn, wn, ln, c, w, text)
        $instage->execute(array(
          $playid,
          $data['code'],
          $data['n'],
          $cn,
          $wn,
          $ln,
          $data['c'],
          $data['w'],
          $data['text'],
        ));
      }

    }
    $this->pdo->commit();
    // différentes stats prédef
    if (STDERR) fwrite(STDERR, " sp: ".number_format(microtime(true) - $time, 3)."s. ");
    $time = microtime(true);
    $this->_sqlstats($playid);
    if (STDERR) fwrite(STDERR, " stats: ".number_format(microtime(true) - $time, 3)."s.");
    $time = microtime(true);
    /*
    $this->_insobj($playid, $play['code']);
    if (STDERR) fwrite(STDERR, " html: ".number_format(microtime(true) - $time, 3)."s.");
    */
    if (STDERR) fwrite(STDERR, "\n");
  }
  /**
   * Statistiques SQL précalculées
   */
  function _sqlstats($playid) {
    $this->pdo->beginTransaction();
    $this->pdo->exec("UPDATE play SET sp = (SELECT COUNT(*) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET l = (SELECT SUM(l) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET w = (SELECT SUM(w) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET c = (SELECT SUM(c) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET scenes = (SELECT COUNT(*) FROM scene WHERE play = $playid) WHERE id = $playid;");

    $this->pdo->exec("UPDATE act SET sp = (SELECT COUNT(*) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET l = (SELECT SUM(l) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET w = (SELECT SUM(w) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET c = (SELECT SUM(c) FROM sp WHERE sp.act = act.id ) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET confs = (SELECT COUNT(DISTINCT configuration) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE scene SET sp = (SELECT COUNT(*) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET c = (SELECT SUM(c) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET w = (SELECT SUM(w) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET l = (SELECT SUM(l) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET confs = (SELECT COUNT(DISTINCT configuration) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE configuration SET sp = (SELECT COUNT(*) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET c = (SELECT SUM(c) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET w = (SELECT SUM(w) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET l = (SELECT SUM(l) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");



    $this->pdo->exec("UPDATE role SET targets = (SELECT COUNT(DISTINCT target) FROM edge WHERE edge.source = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET sources = (SELECT COUNT(DISTINCT source) FROM edge WHERE edge.target = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET confs = (SELECT COUNT(DISTINCT configuration) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE role SET osp = (SELECT COUNT(*) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET ol = (SELECT SUM(sp.l) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET ow = (SELECT SUM(sp.w) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET oc = (SELECT SUM(sp.c) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE role SET isp = (SELECT COUNT(*) FROM sp, edge WHERE edge.sp = sp.id AND edge.target = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET il = (SELECT SUM(sp.l) FROM sp, edge WHERE edge.sp = sp.id AND edge.target = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET iw = (SELECT SUM(sp.w) FROM sp, edge WHERE edge.sp = sp.id AND edge.target = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET ic = (SELECT SUM(sp.c) FROM sp, edge WHERE edge.sp = sp.id AND edge.target = role.id) WHERE play = $playid;");

    $this->pdo->commit();
  }
  /**
   * Insérer de contenus, à ne pas appeller n’importe comment (demande à ce qu’un TEI soit chargé en DOM)
   */
  function _insobj($playid, $playcode) {
    $insert = $this->pdo->prepare("
    INSERT INTO object (play, playcode, type, code, cont)
                VALUES (?,    ?,        ?,    ?,    ?)
    ");
    // insérer charline
    ob_start();
    $this->charline($playcode);
    $cont = ob_get_contents();
    ob_end_clean();
    $insert->execute(array(
      $playid,
      $playcode,
      'charline',
      null,
      $cont,
    ));
    // insérer json sigma
    ob_start();
    $this->sigma($playcode);
    $cont = ob_get_contents();
    ob_end_clean();
    $insert->execute(array(
      $playid,
      $playcode,
      'sigma',
      null,
      $cont,
    ));

    // insérer le texte
    if (file_exists($dir=dirname(__FILE__).'/../Teinte/')) {
      $xsl = new DOMDocument("1.0", "UTF-8");
      $xsl->load($dir.'tei2html.xsl');
      $trans = new XSLTProcessor();
      $trans->importStyleSheet($xsl);
      $trans->setParameter('', 'root', 'article');
      $cont = $trans->transformToXML($this->_dom);
      $insert->execute(array(
        $playid,
        $playcode,
        'article',
        null,
        $cont,
      ));
      $xsl->load($dir.'tei2toc.xsl');
      $trans = new XSLTProcessor();
      $trans->importStyleSheet($xsl);
      // complete toc
      $trans->setParameter('', 'root', 'nav');
      $cont = $trans->transformToXML($this->_dom);
      $insert->execute(array(
        $playid,
        $playcode,
        'toc',
        null,
        $cont,
      ));
      // toc of <front>
      $trans->setParameter('', 'root', 'front');
      $cont = $trans->transformToXML($this->_dom);
      if ($cont) {
        $insert->execute(array(
          $playid,
          $playcode,
          'toc-front',
          null,
          $cont,
        ));
      }
      // toc of <back>
      $trans->setParameter('', 'root', 'back');
      $cont = $trans->transformToXML($this->_dom);
      if ($cont) {
        $insert->execute(array(
          $playid,
          $playcode,
          'toc-back',
          null,
          $cont,
        ));
      }
    }
  }
  /**
   * Collecter les identifiants dans les <role>
   * Alerter sur les identifiants inconnus
   */
  public function valid($file) {
    $this->load($file);
    $nodes = $this->_xpath->query("//tei:role/@xml:id");
    $castlist = array();
    foreach ($nodes as $n) {
      $castlist[$n->nodeValue] = true;
    }
    $nodes = $this->_xpath->query("//@who");
    foreach ($nodes as $n) {
      $who = $n->nodeValue;
      if (isset($castlist[$who])) continue;
      if (STDERR) fwrite(STDERR, $who.' l. '.$n->getLineNo()."\n");
    }
  }

  /**
   * Command line API
   */
  static function cli() {
    $timeStart = microtime(true);
    $usage = '
    usage    : php -f '.basename(__FILE__).' base.sqlite {action} {arguments}
    where action can be
    valid  "../*.xml"
    insert "../*.xml"
    gephi playcode
';
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit($usage);
    $sqlite = array_shift($_SERVER['argv']);
    $base = new Dramaturgie_Base($sqlite);
    if (!count($_SERVER['argv'])) exit('
    action  ? (valid|insert|gephi)
');
    $action = array_shift($_SERVER['argv']);
    if ($action == 'insert') {
      if (!count($_SERVER['argv'])) exit('
    insert requires a file or a glob expression to insert XML/TEI play file
');
      foreach ($_SERVER['argv'] as $glob) {
        foreach(glob($glob) as $file) {
          // spécifique Molière
          if (preg_match('@-livret\.@', $file)) continue;
          $base->insert($file);
        }
      }
    }
    if ($action == 'gephi') {
      $base->gephi(array_shift($_SERVER['argv']));
    }
  }
}
?>
