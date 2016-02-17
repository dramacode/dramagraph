<?php

class Dramaturgie_Charline {
  /** Lien à une base SQLite */
  public $pdo;

  public function __construct($sqlitefile) {
    // ? pouvois passer un pdo ?
    $this->pdo = new PDO('sqlite:'.$sqlitefile);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
  }

  /**
   * Ne marche pas encore, juste pour sauvegarde
   */
  public function rythm($playcode, $widthref) {
    // boucler
    $qconf = $charline->pdo->prepare("SELECT * FROM configuration WHERE act = ? ORDER BY rowid; ");
    $qcn = $charline->pdo->prepare("SELECT * FROM sp WHERE configuration = ? AND cn <= ? ORDER BY play, cn DESC LIMIT 1");
    $actsp = $charline->pdo->prepare("SELECT * FROM sp WHERE act = ? AND cn <= ? ORDER BY play, cn DESC LIMIT 1");
    $playwidth = 1000; // longueur de référence pour 100 000 signes
    $cwidth = $playwidth/100000; // largeur moyenne pour un caractère
    // boucler sur les pièces
    foreach ($charline->pdo->query("SELECT * FROM play ORDER BY author, year") as $play) {
      // prendre les actes
      $act = array();

      $acti = 0;
      echo '
    <table class="dramarythm">
    '."\n";

      // boucler sur les actes une fois pour les intitulés, puis une deuxième pour remplir
      echo '  <tr><th rowspan="2" class="year">'.$play['year'].'</th>'."\n";
      foreach ($charline->pdo->query("SELECT * FROM act WHERE play = ".$play['id']) as $row) {
        echo '    <td class="label">'.$row['label']."</td>\n";
      }
      echo "  </tr>\n";
      echo "  <tr>\n";
      foreach ($charline->pdo->query("SELECT * FROM act WHERE play = ".$play['id']) as $act) {
        if(!$act['c']) continue;
        echo '    <td class="act">'."\n";
        echo '      <table class="act"><tr>'."\n";
        /*
        $qconf->execute(array($act['id']));
        $conf1 = true;
        while ($conf = $qconf->fetch()) {
          if (!$conf['c']) continue; // conf sans parole
          // step in chars, relative to desired width (min=1) and size of conf in chars
          // ~350 or less when conf is short
          $cstep = $conf['c']/ceil($conf['c']*$cwidth/3);
          $tdclass = ' class="bleft"';
          $tot = 0; // see if we all sp
          // take first $sp
          $cn = $conf['cn'];
          $qcn->execute( array($conf['id'], $cn));
          $splast = $qcn->fetch();
          $first = 1;
          while ($cn < $conf['cn']+$conf['c']) {
            $cn = $cn + $cstep;
            $qcn->execute( array($conf['id'], $cn));
            $sp = $qcn->fetch();
            $dif = $first + $sp['id'] - $splast['id'];
            if ($dif>15) $dif = 15;
            $bclass = 'sp'.$dif;
            echo '<td'.$tdclass.'><b class="'.$bclass.'"> </b></td>';
            $tdclass='';
            $tot += $dif;
            $splast = $sp;
            $first = 0;
          }
        }
        */
        // pour tout un acte
        $cstep = $act['c']/ceil($act['c']*$cwidth)*3.6; // ~300s.
        $cn = $act['cn'];
        $actsp->execute( array($act['id'], $cn));
        $splast = $actsp->fetch();
        $first = 1;
        while ($cn < $act['cn']+$act['c']) {
          $cn = $cn + $cstep;
          $actsp->execute( array($act['id'], $cn));
          $sp = $actsp->fetch();
          $dif = $first + $sp['id'] - $splast['id'];
          if ($dif>15) $dif = 15;
          $bclass = 'sp'.$dif;
          echo '<td><b class="'.$bclass.'"> </b></td>';
          // $tot += $dif; // vérifié, OK
          $splast = $sp;
          $first = 0;
        }
        echo '</tr></table>'."\n";
        echo '</td>'."\n";
      }
      echo '  </tr>
    </table>'."\n";
    }
  }
  /**
   * Panneau vertical de pièce
   */
  public function pannel($p=array()) {
    $p = array_merge(array(
      'playcode' => null,
      'width' => 230,
      'refheight' => 600,
      'prehref' => '',
      'target' => '', // iframe target
    ), $p);
    if ($p['target']) $p['target'] = ' target="'.$p['target'].'"';
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($p['playcode']))->fetch();
    $playid = $play['id'];
    if (!$play) return false;
    $confwidth = $p['width'] - 75;


    // 1 pixel = 1000 caractères
    if (!$p['refheight']) $playheight = '800';
    else if (is_numeric($p['refheight']) && $p['refheight'] > 50) $playheight = round($play['c'] / (100000/$p['refheight']));
    else $playheight = '800';
    $csize = $playheight/100000; // largeur moyenne pour un caractère


    // requête sur le nombre de caractères d’un rôle dans une scène
    $qsp = $this->pdo->prepare("SELECT role.*, SUM(sp.c) AS ord FROM sp, role WHERE configuration = ? AND sp.role = role.id GROUP BY role ORDER BY ord DESC");
    $qcn = $this->pdo->prepare("SELECT * FROM sp WHERE configuration = ? AND cn <= ? ORDER BY cn DESC LIMIT 1");
    $qscene = $this->pdo->prepare("SELECT * FROM scene WHERE id = ?");
    echo '<div class="charline">'."\n";

    // loop on acts
    foreach ($this->pdo->query("SELECT * FROM act WHERE play = $playid ORDER BY rowid") as $act) {
      echo '  <a'.$p['target'].' href="'.$p['prehref'].'#'.$act['code'].'" class="acthead">'.$act['label']."</a>\n";
      if(!$act['c']) continue; // probably an interlude
      echo '  <div class="act">'."\n";
      $actheight = $playheight * $act['c']/$play['c'];
      $sceneid = null;
      $scene = null;
      // loop on configurations
      foreach ($this->pdo->query("SELECT * FROM configuration WHERE act = ".$act['id']) as $conf) {
        if(!$conf['c']) continue; // configuration with no sp, probably in <stage>
        $confheight = 3+ ceil($actheight * $conf['c']/$act['c']);
        if (!isset($conf['n'])) $conf['n'] = 0+ preg_replace('/\D/', '', $conf['code']);
        // new scene label (if there)
        if($sceneid != $conf['scene']) {
          $sceneid = $conf['scene'];
          $qscene->execute(array($conf['scene']));
          $scene = $qscene->fetch();
          if ($scene) echo '      <b class="n">'.$scene['n'].'</b>'."\n";
        }
        // Configuration content
        $title = 'Acte '.$act['n'];
        if ($scene) $title .= ', scène '.$scene['n'];
        echo '    <div class="conf" style="height: '.($confheight +1).'px;" title="'.$title.'">'."\n";
        // role bar
        echo '      <a'.$p['target'].' href="'.$p['prehref'].'#'.$conf['code'].'" class="cast">'."\n";
        $qsp->execute(array($conf['id']));
        // loop on role
        while ($role = $qsp->fetch()) {
          if (!$role['ord']) continue;
          $rolewidth = number_format($confwidth * $role['ord'] / $conf['c']) ;
          echo '<span class="role '.$role['rend'].'"';
          echo ' style="width: '.$rolewidth.'px"';
          $title = $role['label'].', acte '.$act['n'];
          if ($scene) $title .= ', scène '.$scene['n'];
          echo ' title="'.$title.', '.round(100*$role['ord'] / $conf['c']).'%"';
          echo '>';
          if ($rolewidth > 35 && $confheight > 12 ) { // && !isset($list[$role['code']])
            echo '<span>'.$role['label'].'</span>';
            $list[$role['code']] = true;
          }
          else echo ' ';
          echo '</span>';
        }
        echo "      </a>\n";
        // rythm
        echo '      <div class="sps">';
        // ~350 or less when conf is short
        $cstep = floor($conf['c'] / floor($confheight / 3));
        // echo $conf['c'].' - '.$cstep.' '.$confheight.' '.floor($confheight / 3);
        // take first $sp
        $cn = $conf['cn'];
        $qcn->execute( array($conf['id'], $cn));
        $splast = $qcn->fetch();
        $first = 1;
        while ($cn < $conf['cn']+$conf['c']) {
          $cn = $cn + $cstep +1;
          $qcn->execute( array($conf['id'], $cn));
          $sp = $qcn->fetch();
          $dif = $first + $sp['id'] - $splast['id'];
          if ($dif>15) $dif = 15;
          echo '<a'.$p['target'].' href="'.$p['prehref'].'#'.$splast['code'].'"><b style="width: '.($dif*3).'px"> </b></a>';
          $splast = $sp;
          $first = 0;
        }
        echo "      </div>\n";

        echo "    </div>\n";
      }
      echo "  </div>\n";
    }
    echo "</div>\n";
  }

}

?>
