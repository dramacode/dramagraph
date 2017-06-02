<?php
/**
 * Visualisation relatives au réseaux de parole
 */
class Dramagraph_Table
{
  /**
   * Table des relations
   */
  public static function relations ( $pdo, $playcode )
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    // récupérer la distribution, pour avoir les noms de personnage
    $cast = array();
    foreach  ($pdo->query("SELECT id, code, label, c FROM role WHERE play = ".$play['id'], PDO::FETCH_ASSOC) as $row) {
      $cast[$row['id']] = $row;
    }
    $html = array();
    $html[] = '<table class="sortable" cellspacing="0" cellpadding="2">';
    $html[] = '  <caption>'.$play['author'].'. <i>'.$play['title'].'</i>. Statistiques par relation</caption>';
    $html[] = '  <tr>';
    $html[] = '    <th class="nosort">Relation</th>';
    $html[] = '    <th class="nosort"></th>';
    $html[] = '    <th title="Nombre de scènes avec les deux personnages en présence">Scènes</th>';
    // $html[] = '    <th title="Nombre de répliques dites  dans la relation">Répliques</th>';
    $html[] = '    <th title="Taille de la relation en part du texte">Texte</th>';
    $html[] = '    <th title="Nombre moyen de personnages parlants sur scène quand les deux sont ensemble">Interlocution</th>';
    // $html[] = '    <th title="Statistiques relatives de chaque peronnage">Pers. 1</th>';
    // $html[] = '    <th title="Part de ce personnage dans la relation">%</th>';
    // $html[] = '    <th title="Nombre de scènes">Scènes</th>';
    // $html[] = '    <th title="Nombre de répliques">Répliques</th>';
    // $html[] = '    <th title="Taille moyenne des répliques du personnage dans la relation en lignes (60 signes))">Repl. moy.</th>';
    // $html[] = '    <th title="Nom de personnage">Pers. 2</th>';
    // $html[] = '    <th title="Part de ce personnage dans la relation">%</th>';
    // $html[] = '    <th title="Taille moyenne des répliques du personnage dans la relation en lignes (60 signes))">Repl. moy.</th>';
    $html[] = '  </tr>';
    // $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $sql = "SELECT
        min (source, target) AS m1,
        max( source, target ) AS m2,
        edge.source,
        edge.target,
        count(sp) AS sp,
        sum(sp.c) AS c,
        count(DISTINCT sp.configuration) AS confs
      FROM edge, sp
      WHERE edge.sp = sp.id AND edge.play = ".$play['id']."
      GROUP BY edge.source, edge.target
      ORDER BY m1, m2
    ";
    // SELECT SUM( c * speakers ) FROM configuration WHERE id IN (SELECT DISTINCT configuration FROM presence WHERE presence.configuration )
    $qoccupation = $pdo->prepare( "SELECT 1.0*SUM(c*speakers)/SUM(c) FROM configuration WHERE id IN (SELECT DISTINCT configuration FROM edge WHERE (source=? AND target=?) OR (source=? and target=?))" );
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
      if ( $m1 && $m2 && $m1 == $row['m1'] && $m2 == $row['m2'] ) {
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
        $html[] = '  <tr>';
        $html[] = '    <td align="right">'.$cast[$m1]['label'].'<br/>'.$cast[$m2]['label'].'</td>';
        $html[] = '    <td>';
        $html[] = '        '.ceil( $m1c/60 ).' l.'
          .' ('.ceil( 100 * $m1c / $c ).' %)'
          .' '.$m1sp.' répl.'
          .' '.number_format( $m1c/$m1sp/60 , 1, ',', ' ').' l.'
        ;
        $html[] = '        <br/>'.ceil( $m2c/60 ).' l.'
          .' ('.ceil( 100 * $m2c / $c ).' %)'
          .' '.$m2sp.' répl.'
          .' '.number_format( $m2c/$m2sp/60, 1, ',', ' ').' l.'
        ;
        $html[] = '    </td>';
        // scènes
        $html[] = '    <td align="right">'.$confs.' sc.</td>';
        $html[] = '    <td align="right">'.number_format( $c/60, 0, ',', ' ').' l. ('.ceil( 100 * $c / $play['c'] ).' %)</td>';
        $qoccupation->execute(array( $m1, $m2, $m2, $m1));
        $html[] = '    <td align="right">'.number_format( current( $qoccupation->fetch() ), 1, ',', ' ').' pers.</td>';
        /*
        $html[] = '    <td>'.$cast[$m1]['label'].'</td>';
        $html[] = '    <td align="right">'.ceil( 100 * $m1c / $c ).' %</td>';
        $html[] = '    <td align="right">'.number_format( $m1c/$m1sp/60 , 1, ',', ' ').' l.</td>';
        $html[] = '    <td>'.$cast[$m2]['label'].'</td>';
        $html[] = '    <td align="right">'.ceil( 100 * $m2c / $c ).' %</td>';
        $html[] = '    <td align="right">'.number_format( $m2c/$m2sp/60 , 1, ',', ' ').' l.</td>';
        */
        $html[] = '  </tr>';
        $m1 = null;
        $m2 = null;
        $confs = 0;
      }
      // monologue
      if ( $row['m1'] && $row['m1'] == $row['m2'] ) {
        $html[] = '  <tr>';
        $html[] = '    <td align="right">'.$cast[ $row['m1']]['label'].'</td>';
        $html[] = '    <td>'.ceil( $row['c']/60 ).' l.'
          .' (100 %)'
          .' '.$row['sp'].' répl.'
          .' '.number_format( $row['c']/$row['sp']/60, 1, ',', ' ').' l.'
          .'</td>'
        ;
        $html[] = '    <td align="right">'.$row['confs'].' sc.</td>';
        $html[] = '    <td align="right">'.number_format($row['c']/60, 0, ',', ' ').' l. ('.ceil( 100 * $row['c'] / $play['c'] ).' %)</td>';
        $html[] = '    <td align="right">1,0 pers.</td>';
        $html[] = '  </tr>';
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
  public static function roles ($pdo, $playcode)
  {
    $play = $pdo->query("SELECT * FROM play where code = ".$pdo->quote($playcode))->fetch();
    $html = array();
    $html[] = '<table class="sortable" cellspacing="0">';
    $html[] = '  <caption>'.$play['author'].'. <i>'.$play['title'].'</i>. Table des rôles</caption>';
    $html[] = '  <thead>';
    $html[] = '  <tr>';
    $html[] = '    <th title="Nom du personnage dans l’ordre de la distribution">Rôle</th>';
    $html[] = '    <th title="Nombre de scènes où le personnage parle">Scènes</th>';
    $html[] = '    <th title="Nombre de répliques du personnages">Répl.</th>';
    $html[] = '    <th title="Taille moyenne des répliques du personnage, en lignes (60 signes)">Répl. moy.</th>';
    $html[] = '    <th title="Temps de présence du personnage, en part du texte">Présence</th>';
    $html[] = '    <th title="Part du texte dit par le personnage">Texte</th>';
    $html[] = '    <th title="Part de parole que le personnage prend pendant son temps de présence">Texte % prés.</th>';
    $html[] = '    <th title="Diffusion de la parole">Texte × pers.</th>';
    $html[] = '    <th title="Nombre moyen de personnages parlants dans une scène où le personnage parle">Interlocution</th>';
    $html[] = '  </tr>';
    $html[] = '  <tr>';
    $html[] = '    <td data-sort="0">[TOUS]</td>';
    $html[] = '    <td>'.$play['confs'].' sc.</td>';
    $html[] = '    <td>'.$play['sp'].' répl.</td>';
    $html[] = '    <td>'.number_format($play['c']/($play['sp']*60), 1, ',', ' ').' l.</td>';
    $html[] = '    <td>'.number_format($play['c']/60, 0, ',', ' ').' l.</td>';
    $html[] = '    <td>'.number_format($play['c']/60, 0, ',', ' ').' l.</td>';
    $html[] = '    <td>'.ceil(100 * $play['c']/$play['cspeakers'])." %</td>";
    $html[] = '    <td>'.number_format($play['cspeakers']/60, 0, ',', ' ')." l.</td>";
    $html[] = '    <td>'.number_format($play['cspeakers']/$play['c'], 1, ',', ' ').' pers.</td>';
    $html[] = '  </tr>';
    $html[] = '  </thead>';
    $i = 1;
    foreach ($pdo->query("SELECT * FROM role WHERE role.play = ".$play['id']." ORDER BY ord") as $role) {
      $html[] = "  <tr>";
      // nom
      $html[] = '    <td data-sort="'.$i.'" title="'.$role['title'].'">'.$role['label'].'</td>';
      // scènes
      $html[] = '    <td align="right">'. $role['confspeak'].' sc.</td>';
      // répliques
      $html[] = '    <td align="right">'.$role['sp']." répl.</td>";
      // réplique moyenne
      if ($role['sp']) $html[] = '    <td align="right">'.number_format($role['c']/$role['sp']/60, 1, ',', ' ').' l.</td>';
      else $html[] = '<td align="right">0</td>';
      // présence
      $html[] = '    <td align="right">'.number_format( $role['presence']/60, 0, ',', ' ' ).' l. ('.ceil(100 * $role['presence']/$play['c']).' %)</td>';
      // texte
      $html[] = '    <td align="right">'.number_format( $role['c']/60, 0, ',', ' ' ).' l. ('.ceil(100 * $role['c']/$play['c']).' %)</td>';
      // texte % présence
      if ($role['presence']) $html[] = '    <td align="right">'.ceil( 100 * $role['c']/$role['presence']).' %</td>';
      else $html[] = '    <td align="right">0 %</td>';
      // diffusion
      $html[] = '    <td align="right">'.number_format($role['cspeakers']/60, 0, ',', ' ').' l.</td>';
      // interlocution
      if ($role['participation']) $html[] = '    <td align="right">'.number_format($role['cspeakers']/$role['participation'], 1, ',', ' ').' pers.</td>';
      else $html[] = '    <td align="right">0</td>';
      $html[] = "  </tr>";
      $i++;
    }
    $html[] = '</table>';
    return implode("\n", $html);
  }

}

 ?>
