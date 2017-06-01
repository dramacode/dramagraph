<?php
// declare(encoding = 'utf-8');
setlocale(LC_ALL, 'fr_FR.utf8');
mb_internal_encoding("UTF-8");
foreach (array('Charline.php', 'Net.php', 'Table.php', 'Doc.php') as $file) include(dirname(__FILE__).'/'.$file);
include(dirname(__FILE__).'/../Teinte/Doc.php'); // dépendance déclarée

if (realpath($_SERVER['SCRIPT_FILENAME']) != realpath(__FILE__)); // file is include do nothing
else if (php_sapi_name() == "cli") {
  Dramagraph_Base::cli();
}
class Dramagraph_Base {
  /** Lien à une base SQLite, unique */
  public $pdo;
  /** fichier de la base sqlite */
  public $sqlitefile;
  /** Test de date d’une pièce */
  private $_sqlmtime;


  /**
   * Connexion à une base sqlite
   */
  public function __construct($sqlitefile=null) {
    $this->sqlitefile = $sqlitefile;
    if ($sqlitefile) $this->connect($sqlitefile);
    $this->_sqlmtime = $this->pdo->prepare("SELECT filemtime FROM play WHERE code = ?");
  }
  /**
   * Connexion à la base
   */
  private function connect($sqlite) {
    $sql = 'dramagraph.sql';
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
   * Charger un XML en base
   */
  public function insert( $p, $force = FALSE ) {
    if (is_string($p)) {
      $p = array( 'source'=>$p );
    }

    $time = microtime(true);
    // test freshness
    if ( !$force ) {
      $code = pathinfo( $p['source'], PATHINFO_FILENAME);
      $this->_sqlmtime->execute( array( $code ) );
      list( $basemtime ) = $this->_sqlmtime->fetch();
      $this->_sqlmtime->closeCursor();
      if ( strpos( $p['source'], 'http') === 0 ) $srcmtime = 0;
      else $srcmtime = filemtime( $p['source'] );
      if ($basemtime && $basemtime == $srcmtime) return;
    }

    if (STDERR) fwrite(STDERR, $p['source'].'… ' );
    try {
      $doc = new Dramagraph_Doc( $p['source'] );
    }
    catch (Exception $e) {
      return;
    }
    $play = $doc->meta();

    // default is naked, do better for bibdramatique site
    $doc->naked();
    // the value provided by caller wil override the one extracted from TEI source
    if ( !isset( $p['publisher'] ) ) $p['publisher'] = $play['publisher'];
    if ( !isset( $p['identifier'] ) ) $p['identifier'] = $play['identifier'];
    if ( !isset( $p['source'] ) ) $p['source'] = null;

    $this->pdo->exec("DELETE FROM play WHERE code = ".$this->pdo->quote($play['code']));
    $q = $this->pdo->prepare("
    INSERT INTO play (code, filemtime, publisher, identifier, source,  author, title, date, created, issued, acts, verse, genre, type)
              VALUES (?,    ?,         ?,         ?,          ?,       ?,      ?,     ?,    ?,       ?,      ?,    ?,     ?,     ?);
    ");
    $q->execute(array(
      $play['code'],
      $play['filemtime'],
      $p['publisher'],
      $p['identifier'],
      $p['source'],
      $play['author'],
      $play['title'],
      $play['date'],
      $play['created'],
      $play['issued'],
      $play['acts'],
      $play['verse'],
      $play['genre'],
      $play['type'],
    ));
    $playid = $this->pdo->lastInsertId();
    // roles
    $cast = $doc->cast();
    $this->pdo->beginTransaction();
    $q = $this->pdo->prepare("
    INSERT INTO role (play, ord, code, label, title, note, rend, sex, age, status)
              VALUES (?,    ?,   ?,    ?,     ?,     ?,    ?,    ?,   ?,   ?);
    ");
    foreach ($cast as $role) {
      $q->execute(array(
        $playid,
        $role['ord'],
        $role['code'],
        $role['label'],
        $role['title'],
        $role['note'],
        $role['rend'],
        $role['sex'],
        $role['age'],
        $role['status'],
      ));
      // conserver le rowid du role
      $cast[$role['code']]['id'] = $this->pdo->lastInsertId();;
    }
    $this->pdo->commit();

    if (STDERR) fwrite(STDERR, " meta+role: ".number_format(microtime(true) - $time, 3)."s. ");
    $time = microtime(true);


    $csv = $doc->csv();
    file_put_contents("test-play.csv", $csv);
    // placer la chaîne dans un stream pour profiter du parseur fgetscsv
    $stream = fopen('php://memory', 'w+');
    fwrite($stream, $csv);
    rewind($stream);
    $inact = $this->pdo->prepare("
    INSERT INTO act (play, code, n, label, type, ln, l, wn, cn)
            VALUES  (?,    ?,    ?, ?,     ?,    ?,  ?,  ?,  ?);
    ");
    $inscene = $this->pdo->prepare("
    INSERT INTO scene (play, act, code, n, label, type, ln, l, wn, cn)
               VALUES (?,    ?,   ?,    ?, ?,     ?,    ?,  ?,  ?,  ?);
    ");
    $inconf = $this->pdo->prepare("
    INSERT INTO configuration (play, act, scene, code, n, label, ln, wn, cn)
                       VALUES (?,    ?,   ?,     ?,   ?,  ?,     ?,  ?,  ?);
    ");
    $inpresence = $this->pdo->prepare("
    INSERT INTO presence (play, configuration, role, type)
                  VALUES (?,    ?,             ?,    ?);
    ");
    $insp = $this->pdo->prepare("
    INSERT INTO sp (play, act, scene, configuration, role, code, ln, l, wn, w, cn, c, text)
            VALUES (?,    ?,   ?,     ?,             ?,    ?,    ?,  ?, ?,  ?, ?,  ?, ?);
    ");
    $instage = $this->pdo->prepare("
    INSERT INTO stage (play, act, scene, configuration, code, n, ln, wn, cn, w, c, text)
               VALUES (?,    ?,   ?,     ?,             ?,    ?, ?,  ?,  ?,  ?, ?, ?);
    ");
    $insedge = $this->pdo->prepare("
    INSERT INTO edge (source, target, play, act, scene, configuration, sp )
              VALUES (?,      ?,      ?,    ?,   ?,     ?,             ?);
    ");
    // première ligne, nom de colonne, à utiliser comme clé pour la collecte des lignes
    $keys = fgetcsv($stream, 0, "\t");
    // boucle pour charger la base, ne pas oublier de démarrer une transaction
    $this->pdo->beginTransaction();
    $wn = 1;
    $cn = 1;
    $ln = null;
    $actid = null;
    $sceneid = null;
    $conf = array(); // personnages présents
    $speakers = array(); // personnages parlants
    $confid = null; // peut ne pas commencer tout de suite
    while (($values = fgetcsv($stream, 0, "\t")) !== FALSE) {
      // fabriquer un tableau clé valeur avec la ligne csv
      if (count($keys) > count($values)) { // moins de valeurs que de clés, il faut combler
        $data = array_combine($keys, array_merge($values, array_fill(0, count($keys) - count($values), null)));
      }
      else {
        $data = array_combine($keys, $values);
      }
      // configuration
      if ($data['object'] == 'configuration' ) {
        $confid = null;
        $data['label'] = trim($data['label']);
        try {
            $inconf->execute(array(
            $playid,
            $actid,
            $sceneid,
            $data['code'],
            $data['n'],
            $data['label'],
            $ln,
            $wn,
            $cn,
          ));
          $confid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      NOT UNIQUE scene ? ".$data['code']."\n".$e."\n\n");
        }
        // echo " ——— ".$data['code']."\n";
        // record the availabe roles for this configuration
        $conf = array();
        $speakers = array();
        if ($data['label']) {
          $oldconf = $conf;
          // space separated who codes
          $conf = array_flip(explode(' ', $data['label']));
          // test if unknow role, if known add it as a presence
          foreach ($conf as $k=>$v) {
            // a stage entry
            if (!isset($oldconf[$k])) {
              if ( !isset($cast[$k]) ); // bug @who
              else if (!isset($cast[$k]['entries']) || !$cast[$k]['entries']) $cast[$k]['entries'] = 1;
              else $cast[$k]['entries']++;
            }
            if (STDERR && ( !isset($cast[$k]) || !isset($cast[$k]['id']) ) ) {
              fwrite(STDERR, "\n".'person/@corresp="'.$k. '" unknown role ['.$data['code']."]");
            }
            else {
              if ( !isset($cast[$k]['id']) ) print_r( $cast );
              $inpresence->execute(array(
                $playid,
                $confid,
                $cast[$k]['id'],
                null
              ));
            }
          }
          $speakers = array_flip(explode(' ', $data['target']));
        }
      }
      // réplique
      else if ($data['object'] == 'sp' ) {
        if (!isset($cast[$data['label']]) && STDERR) {
          fwrite(STDERR, "\nsp/@who not in castlist ".$data['label']. " [".$data['code']."]");
          continue;
        }
        if (!isset($conf[$data['label']]) && STDERR) {
          fwrite(STDERR, 'sp/@who="'.$data['label'].'" not in configuration '.$confid.' ("'.implode('", "', array_keys($conf)).'")  ['.$data['code']."]\n");
          continue;
        }
        if (!$data['c']) {
          if (STDERR) fwrite(STDERR, "Empty <sp> [".$data['code']."]\n");
          continue;
        }
        if (!$data['l']) $data['l'] = null;
        if ($data['ln']) $ln = $data['ln'];
        $sourceid = $cast[$data['label']]['id'];
        try {
          $row = array(
            $playid,
            $actid,
            $sceneid,
            $confid,
            $sourceid,
            $data['code'],
            $ln,
            $data['l'],
            $wn,
            $data['w'],
            $cn,
            $data['c'],
            $data['text'], // text
          );
          $insp->execute($row);
          // increment after insert
          $wn = $wn + $data['w'];
          $cn = $cn + $data['c'];
          $spid = $this->pdo->lastInsertId();
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      <sp>".implode(', ', $row)."\n".$data['code']."\n".$e."\n\n");
        }
        $target = '';
        try {
          // ajouter les destinataires de la réplique, dépend de la conf
          if (!count($conf)) { // erreur ?
            if ( STDERR ) fwrite( STDERR, "<sp> not in configuration [".$data['code']."]\n" );
          }
          // monologue
          else if (1 == count($conf)) {
            $insedge->execute(array(
              $sourceid,
              $sourceid,
              $playid,
              $actid,
              $sceneid,
              $confid,
              $spid,
            ));
          }
          //
          else {
            $targetid = null;
            // destinataire principal de la réplique (le suivant)
            if ( !$data['target'] );
            else if ( !isset( $cast[$data['target']] ) ); // @who error
            else {
              $targetid = $cast[$data['target']]['id'];
              $insedge->execute(array(
                $sourceid,
                $targetid,
                $playid,
                $actid,
                $sceneid,
                $confid,
                $spid,
              ));
              // ne doit pas arriver
              // if ($sourceid == $targetid) echo "\n––––– ".$data['label'].' : '.$data['text'].' ';
            }
            $i = 1;
            // on considère que l’on ne parle pas à un muet
            foreach ( $speakers as $k=>$null ) {
              if (!isset($cast[$k])) continue; // error
              // ne se parle pas si plus d’une personne
              if ($cast[$k]['id'] == $sourceid) continue;
              // déjà fait
              if ($cast[$k]['id'] == $targetid) continue;
              $insedge->execute(array(
                $sourceid,
                $cast[$k]['id'],
                $playid,
                $actid,
                $sceneid,
                $confid,
                $spid,
              ));
              // echo $k."\n";
              if (!--$i) break;
            }
          }
        }
        catch (Exception $e) {
          if (STDERR) fwrite(STDERR, "\n\n      edge: ".$source.'  > '.$target.' '.$k."\n".$e."\n\n");
        }
      }
      // scènes, parfois non contenues dans un acte (pièces en un acte)
      else if ($data['object'] == 'div2' || $data['type'] == 'scene' ) {
        $sceneid = null;
        // ne pas annuler la configuration, peut courir entre les actes et les scènes
        if (!$data['type']) $data['type'] = "scene";
        try {
          $inscene->execute(array(
            $playid,
            $actid,
            $data['code'],
            $data['n'],
            $data['label'],
            $data['type'],
            $ln,
            $data['l'],
            $wn,
            $cn,
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
        // ne pas annuler la configuration, peut courir entre les actes et les scènes
        if(!$data['type']) $data['type'] = 'act';
        if($data['type'] == 'acte') $data['type'] = 'act';
        try {
          $inact->execute(array(
            $playid,
            $data['code'],
            $data['n'],
            $data['label'],
            $data['type'],
            $ln,
            $data['l'],
            $wn,
            $cn,
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
          $actid,
          $sceneid,
          $confid,
          $data['code'],
          $data['n'],
          $ln,
          $wn,
          $cn,
          $data['w'],
          $data['c'],
          $data['text'],
        ));
      }

    }
    $q = $this->pdo->prepare("UPDATE role SET entries = ? WHERE id = ?");
    foreach ($cast as $role) {
      if (!isset($role['entries'])) continue; // role muet non détecté
      $q->execute(array($role['entries'], $role['id']));
    }
    $this->pdo->commit();
    // différentes stats prédef
    if (STDERR) fwrite(STDERR, " sp: ".number_format(microtime(true) - $time, 3)."s. ");
    $time = microtime(true);
    $this->_sqlstats($playid);
    if (STDERR) fwrite(STDERR, " stats: ".number_format(microtime(true) - $time, 3)."s.");
    $time = microtime(true);

    $this->_insobj($doc->dom(), $playid, $play['code']);
    if (STDERR) fwrite(STDERR, " html: ".number_format(microtime(true) - $time, 3)."s.");
    if (STDERR) fwrite(STDERR, "\n");
  }

  /**
   * Statistiques SQL précalculées pour une pièce
   */
  function _sqlstats($playid) {
    $this->pdo->beginTransaction();
    $this->pdo->exec("UPDATE play SET sp = (SELECT COUNT(*) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET l = (SELECT SUM(l) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET w = (SELECT SUM(w) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET c = (SELECT SUM(c) FROM sp WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET scenes = (SELECT COUNT(*) FROM scene WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET confs = (SELECT COUNT(*) FROM configuration WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE play SET roles = (SELECT COUNT(*) FROM role WHERE play = $playid) WHERE id = $playid;");
    // ???
    // $this->pdo->exec("UPDATE play SET entries = (SELECT SUM(entries) FROM role WHERE play = $playid) WHERE id = $playid;");

    $this->pdo->exec("UPDATE act SET sp = (SELECT COUNT(*) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    // $this->pdo->exec("UPDATE act SET l = (SELECT SUM(l) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET w = (SELECT SUM(w) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET c = (SELECT SUM(c) FROM sp WHERE sp.act = act.id ) WHERE play = $playid;");
    $this->pdo->exec("UPDATE act SET confs = (SELECT COUNT(DISTINCT configuration) FROM sp WHERE sp.act = act.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE scene SET sp = (SELECT COUNT(*) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET c = (SELECT SUM(c) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET w = (SELECT SUM(w) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    // $this->pdo->exec("UPDATE scene SET l = (SELECT SUM(l) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE scene SET confs = (SELECT COUNT(DISTINCT configuration) FROM sp WHERE sp.scene = scene.id) WHERE play = $playid;");

    $this->pdo->exec("UPDATE presence SET c = (SELECT SUM(c) FROM sp WHERE sp.configuration = presence.configuration AND sp.role = presence.role) WHERE play = $playid;");
    $this->pdo->exec("UPDATE presence SET sp = (SELECT count(*) FROM sp WHERE sp.configuration = presence.configuration AND sp.role = presence.role) WHERE play = $playid;");

    $this->pdo->exec("UPDATE configuration SET sp = (SELECT COUNT(*) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET c = (SELECT SUM(c) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET w = (SELECT SUM(w) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET l = (SELECT SUM(l) FROM sp WHERE sp.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE configuration SET roles = (SELECT COUNT(*) FROM presence WHERE presence.configuration = configuration.id) WHERE play = $playid;");


    $this->pdo->exec("UPDATE role SET confs = (SELECT COUNT(*) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET confspeak = (SELECT COUNT(*) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id AND presence.c > 0) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET presence = (SELECT SUM(configuration.c) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id) WHERE play = $playid ");

    $this->pdo->exec("UPDATE role SET sp = (SELECT COUNT(*) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET l = (SELECT SUM(sp.l) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET w = (SELECT SUM(sp.w) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET c = (SELECT SUM(sp.c) FROM sp WHERE sp.role = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET targets = (SELECT COUNT(DISTINCT target) FROM edge WHERE edge.source = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET sources = (SELECT COUNT(DISTINCT source) FROM edge WHERE edge.target = role.id) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET participation = (SELECT SUM(configuration.c) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id AND presence.c > 0) WHERE play = $playid ");
    // vu, des faux rôles
    // $this->pdo->exec("DELETE FROM role WHERE play = $playid AND presence IS NULL");
    $this->pdo->exec("UPDATE play SET croles = (SELECT SUM(c * roles) FROM configuration WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->commit();
    // stats needing a commit
    $this->pdo->exec("UPDATE configuration SET speakers = (SELECT COUNT(*) FROM presence WHERE presence.configuration = configuration.id AND presence.c > 0) WHERE play = $playid;");
    $this->pdo->exec("UPDATE play SET cspeakers = (SELECT SUM(c * speakers) FROM configuration WHERE play = $playid) WHERE id = $playid;");
    $this->pdo->exec("UPDATE role SET cspeakers = (SELECT SUM(configuration.c * configuration.speakers) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id AND presence.c > 0) WHERE play = $playid;");
    $this->pdo->exec("UPDATE role SET croles = (SELECT SUM(configuration.c * configuration.roles) FROM configuration, presence WHERE presence.role = role.id AND presence.configuration = configuration.id ) WHERE play = $playid;");
  }
  /**
   * Insérer des contenus, à ne pas appeller n’importe comment (demande à ce qu’un TEI soit chargé en DOM)
   */
  private function _insobj($dom, $playid, $playcode) {
    $insert = $this->pdo->prepare("
    INSERT INTO object (play, playcode, type, code, cont)
                VALUES (?,    ?,        ?,    ?,    ?)
    ");

    $this->pdo->beginTransaction();
    // global objects

    // navigations
    $cont = Dramagraph_Charline::pannel($this->pdo, array('playcode'=>$playcode));
    $insert->execute(array($playid, $playcode, 'charline', null, $cont));
    $cont = Dramagraph_Net::graph($this->pdo, $playcode);
    $insert->execute(array($playid, $playcode, 'graph', null, $cont));
    $cont = Dramagraph_Table::roles($this->pdo, $playcode);
    $insert->execute(array($playid, $playcode, 'roles', null, $cont));
    $cont = Dramagraph_Table::relations($this->pdo, $playcode);
    $insert->execute(array($playid, $playcode, 'relations', null, $cont));
    // text
    $teinte = new Teinte_Doc($dom);
    $insert->execute(array( $playid, $playcode, 'article', null, $teinte->article() ));
    $insert->execute(array( $playid, $playcode, 'toc', null, $teinte->toc() ));
    // $insert->execute(array( $playid, $playcode, 'tocfront', null, $teinte->toc('front') ));
    // $insert->execute(array( $playid, $playcode, 'tocback', null, $teinte->toc('back') ));

    $this->pdo->commit();
  }


  /**
   * Command line API
   */
  static function cli()
  {
    $timeStart = microtime(true);
    $usage = '
    usage    : php -f '.basename(__FILE__).' base.sqlite *.xml
    usage    : php -f '.basename(__FILE__).' base.sqlite uri-list.txt
';
    $timeStart = microtime(true);
    array_shift($_SERVER['argv']); // shift first arg, the script filepath
    if (!count($_SERVER['argv'])) exit($usage);
    $sqlite = array_shift($_SERVER['argv']);
    $base = new Dramagraph_Base($sqlite);
    /*
    if (!count($_SERVER['argv'])) exit('
    action  ? (valid|insert|gephi)
');
    $action = array_shift($_SERVER['argv']);
    */
    $action = "insert";
    if ($action == 'insert') {
      $force = false;
      if (!count($_SERVER['argv'])) exit("\n  insert requires a file or a glob expression to insert XML/TEI play file\n");
      foreach ($_SERVER['argv'] as $glob) {
        if ( $glob == 'force' ) {
          $force = true;
          continue;
        }
        foreach( glob($glob) as $file ) {
          $ext = pathinfo ($file, PATHINFO_EXTENSION);
          // seems a list of uri
          if ( $ext == 'tsv' || $ext == 'csv' || $ext == "txt") {
            if ( $ext == 'tsv' ) $sep = "\t";
            else if ( $ext == 'csv') $sep = ";";
            else if ( $ext == "txt" ) $sep = ",";
            $handle = fopen($file, "r");
            $keys = fgetcsv($handle, 0, $sep);
            while (($values = fgetcsv($handle, 0, "\t")) !== FALSE) {
              if ( count( $values ) < 1) continue;
              if ( count( $values ) == 1 && !$values[0] ) continue;
              if ( $values[0][0] == '#' ) continue;
              if ( count( $keys ) > count( $values ) ) // less values than keys, fill for a good combine
                $values = array_merge( $values, array_fill( 0, count( $keys ) - count( $values ), null ) ) ;
              $row = array_combine($keys, $values);
              $base->insert($row, $force);
            }
            fclose($handle);
            // first
          }
          // spécifique Molière
          else if (preg_match('@-livret\.@', $file)) continue;
          else $base->insert($file, $force);
        }
      }
      $base->pdo->exec("VACUUM");
    }
    if ($action == 'gephi') {
      $base->gephi(array_shift($_SERVER['argv']));
    }
  }
}
?>
