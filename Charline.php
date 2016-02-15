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
   * Structuration
   */
   public function pannelcss() {
     return '
     <style>
 /* couleur des rôles par genre */
 .charline .role { background-color: rgba(192, 192, 192, 0.7); color: rgba(0, 0, 0, 0.5); }
 .charline .female { background-color: rgba(255, 0, 0, 0.5); color: rgba(255, 255, 255, 1);}
 .charline .female.junior { background-color: rgba(255, 64, 128, 0.3); color: rgba(0, 0, 0, 0.7);}
 .charline .female.inferior { background-color: rgba(192, 96, 128, 0.4); color: rgba(255, 255, 255, 1);}
 .charline .female.veteran { background-color: rgba(128, 0, 0, 0.4); color: rgba(255, 255, 255, 1);}
 .charline .male { background-color: rgba(0, 0, 255, 0.4); color:  rgba(255, 255, 255, 1);}
 .charline .male.junior { background-color: rgba(0, 192, 255, 0.2); color: rgba(0, 0, 0, 0.7);}
 .charline .male.inferior { background-color: rgba(96, 96, 192, 0.3); color: rgba(255, 255, 255, 1);}
 .charline .male.veteran { background-color: rgba(0, 0, 128, 0.4); color:  rgba(255, 255, 255, 1);}
 .charline .male.superior { background-color: rgba(0, 0, 255, 0.6); color:  rgba(255, 255, 255, 1);}


.charline { font-family: sans-serif; font-size: 13px; line-height: 1.2em; position: relative; }
.charline, .charline * { box-sizing: border-box; }
.charline:after, .charline .act:after, .charline .scene:after, .charline .conf:after, .charline .cast:after, .charline .sps:after { content:""; display:table; clear:both; }
.charline a { border-bottom: none; text-decoration: none; }
.charline a:hover { background: transparent; }

b.n { position: absolute; left: 0; font-weight: bold; color: #999; }

.charline div.scene { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.charline div.conf { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.charline .acthead { display: block; text-align: center; padding: 5px  30px 1px 0; clear: both;  }
.charline a.cast { display: block; height: 100%; float: left; padding-left: 2ex; }
.charline div.sps { margin-left: 5px; margin-bottom: 1px; float: left; width: 40px; border-right: 1px dotted #CCCCCC; background: #FFFFFF; }
.charline div.sps a { display: block; line-height: 3px; position: relative; }
.charline div.sps b { background: #AAAAAA; display: block; }
.charline .role { float: left; height: 100%; border-left: 1px rgba(255, 255, 255, 0.5) solid; border-radius: 3px/1em; font-stretch: ultra-condensed; }
.charline .role span { overflow: hidden; padding-left: 1ex; padding-top: 2px;}

     </style>
     ';
   }
   public function rythmcss() {
     return '
   <style>
.dramarythm { font-family: sans-serif; font-size: 14px; border-collapse: collapse; margin-bottom: 10px;  }
.dramarythm th.year { width: 5ex; padding-left: 1ex; }
.dramarythm td.act, .dramarythm td.label { padding: 0 5px 0 4px; border-left: 1px solid #000000; }
.dramarythm td.act {  vertical-align: bottom; }
.dramarythm table { border-collapse: collapse; }
.dramarythm table td { padding: 0; vertical-align: bottom; }
.dramarythm table.act { border-collapse: collapse; }
.dramarythm table.act td { background: #EEE; height: 45px; }
.dramarythm table.act td.conf { border-left: 1px solid #FFF; }
.dramarythm b { background: #888; display: block; width: 3px;}
/*
.dramarythm span.conf { display: table-row;  float:left;  background-color: #EEE; border-top: 3px solid #888; border-right: 3px #FFF solid; height: 100%; }
.dramarythm b { display: table-cell; vertical-align: bottom; width: 3px; background: #888;  bottom: 0; }
*/
   </style>';

   }
  /**
   *
   */
  public function rythm($playcode, $widthref) {


  }
  /**
   * Panneau vertical de pièce
   */
  public function pannel($playcode, $width=230, $heightref=600) {
    $play = $this->pdo->query("SELECT * FROM play where code = ".$this->pdo->quote($playcode))->fetch();
    $playid = $play['id'];
    if (!$play) return false;
    $confwidth = $width - 75;


    // 1 pixel = 1000 caractères
    if (!$heightref) $playheight = '800';
    else if (is_numeric($heightref) && $heightref > 50) $playheight = round($play['c'] / (100000/$heightref));
    else $playheight = '800';


    // requête sur le nombre de caractères d’un rôle dans une scène
    $qsp = $this->pdo->prepare("SELECT sum(c) FROM sp WHERE configuration = ? AND role = ?");
    $qcn = $this->pdo->prepare("SELECT * FROM sp WHERE configuration = ? AND cn <= ? ORDER BY cn DESC LIMIT 1");
    $qscene = $this->pdo->prepare("SELECT * FROM scene WHERE id = ?");
    echo '<div class="charline">'."\n";

    // loop on acts
    foreach ($this->pdo->query("SELECT * FROM act WHERE play = $playid ORDER BY rowid") as $act) {
      echo '  <a href="#'.$act['code'].'" class="acthead">'.$act['label']."</a>\n";
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
        // Configuration content
        echo '    <div class="conf" style="height: '.($confheight +1).'px;" title="Acte '.$act['n'].', scène '.$conf['n'].'">'."\n";
          // new scene label (if there)
          if($sceneid != $conf['scene']) {
            $sceneid = $conf['scene'];
            $qscene->execute(array($conf['scene']));
            $scene = $qscene->fetch();
            if ($scene) echo '      <b class="n">'.$scene['n'].'</b>'."\n";
          }
          //
          // role bar
          echo '      <a href="#'.$conf['code'].'" class="cast">'."\n";
          $i = 0;
          // loop on role
          foreach ($this->pdo->query("SELECT * FROM role WHERE play = $playid ORDER BY c DESC") as $role) {
            $qsp->execute(array($conf['id'], $role['id']));
            list($c) = $qsp->fetch();
            $i++;
            if (!$c) continue;
            $rolewidth = number_format($confwidth * $c / $conf['c']) ;
            echo '<span class="role role'.$i.' '.$role['rend'].'"';
            echo ' style="width: '.$rolewidth.'px"';
            $title = $role['label'].', acte '.$act['n'];
            if ($scene) $title .= ', scène '.$scene['n'];
            echo ' title="'.$title.', '.round(100*$c / $conf['c']).'%"';
            echo '>';
            if ($rolewidth > 35 && $confheight > 12 ) { // && !isset($list[$role['code']])
              echo '<span>'.$role['label'].'</span>';
              $list[$role['code']] = true;
            }
            else echo ' ';
            echo '</span>';
          }
          echo "      </a>\n";
          echo '      <div class="sps">';
          $splast = null;
          for ($pixel = 0; $pixel <= $confheight; $pixel = $pixel +3) {
            $cn = $conf['cn'] + ceil($conf['c'] * $pixel / $confheight);
            $qcn->execute( array($conf['id'], $cn));
            $sp = $qcn->fetch();
            if(!$sp) continue;
            if($sp == $splast) {
              echo '<a href="#'.$splast['code'].'"> </a>';
              continue;
            }
            if (!$splast) {
              $splast = $sp;
              continue;
            }
            $width = 3*($sp['id'] - $splast['id']);
            echo '<a href="#'.$splast['code'].'"><b style="width: '.$width.'px"> </b></a>';
            $splast = $sp;
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
