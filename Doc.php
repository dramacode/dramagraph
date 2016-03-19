<?php
/**
 * Porte tout ce qui dépend du fichier XML
 */
class Dramaturgie_Doc {
  /** Document XML */
  private $dom;
  /** Processeur xpath */
  private $xpath;
  /** Chemin original du fichier */
  public $file;
  /** Dossier où trouver le paquer Teine */
  public $Teinte;
  /** Précharger des xsl une seule fois */
  static $xsl = array();
  /**
   * Charger un fichier XML
   */
  public function __construct($file) {
    $this->Teinte = dirname(__FILE__).'/../Teinte/';

    $this->dom = new DOMDocument();
    $this->dom->preserveWhiteSpace = false;
    $this->dom->formatOutput=true;
    $this->dom->substituteEntities=true;
    $this->dom->load($file, LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOERROR | LIBXML_NOWARNING);
    $this->xpath = new DOMXpath($this->dom);
    $this->xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");
    $this->file = $file;
  }
  /**
   * Métadonnées de pièce
   */
  public function meta() {
    $play = array();
    $play['code'] = pathinfo($this->file, PATHINFO_FILENAME);
    $nl = $this->xpath->query("/*/tei:teiHeader//tei:author");
    if ($nl->length) $play['author'] = $nl->item(0)->textContent;
    else $play['author'] = null;
    $nl = $this->xpath->query("/*/tei:teiHeader/tei:profileDesc/tei:creation/tei:date");
    if ($nl->length) {
      $play['year'] = $nl->item(0)->getAttribute ('when');
      if(!$play['year']) $play['year'] = $nl->item(0)->nodeValue;
      $play['year'] = substr(trim($play['year']), 0, 4);
    }
    else $play['year'] = null;
    $nl = $this->xpath->query("/*/tei:teiHeader//tei:title");
    if ($nl->length) $play['title'] = $nl->item(0)->textContent;
    else $play['title'] = null;
    $nl = $this->xpath->query("/*/tei:teiHeader//tei:term[@type='genre']/@subtype");
    if ($nl->length) $play['genre'] = $nl->item(0)->nodeValue;
    else $play['genre'] = null;

    $play['acts'] = $this->xpath->evaluate("count(/*/tei:text/tei:body//tei:*[@type='act'])");
    if (!$play['acts']) $play['acts'] = $this->xpath->evaluate("count(/*/tei:text/tei:body/*[tei:div|tei:div2])");
    if (!$play['acts']) $play['acts'] = 1;
    $l = $this->xpath->evaluate("count(//tei:sp/tei:l)");
    $p = $this->xpath->evaluate("count(//tei:sp/tei:p)");
    if ($l > 2*$p) $play['verse'] = true;
    else if ($p > 2*$l) $play['verse'] = false;
    else $play['verse'] = null;
    return $play;
  }
  /**
   * Liste des rôles
   */
  function cast() {
    $nodes = $this->xpath->query("//tei:role[@xml:id]|//tei:person[@xml:id]");
    $cast = array();
    $i = 1;
    foreach ($nodes as $n) {
      $role = array();
      $role['ord'] = $i;
      $role['code'] = $n->getAttribute ('xml:id');
      if (!$role['code']) continue;

      $role['label'] = $n->getAttribute ('n');
      if (!$role['label']) $role['label'] = $n->nodeValue;
      if (!$role['label']) $role['label'] = $role['code'];

      $nl = @$n->parentNode->getElementsByTagName("roleDesc");
      if ($nl->length) $role['title'] = trim($nl->item(0)->nodeValue);
      else {
        $role['title'] = '';
        $nl = $n->parentNode->firstChild;
        while($nl) {
          if ($nl->nodeType == XML_TEXT_NODE ) $role['title'] .= $nl->nodeValue;
          $nl = $nl->nextSibling;
        }
        $role['title'] = preg_replace(array("/^[\s :;,\.]+/u", "/[\s :,;\.]+$/u"), array('', ''), $role['title']);
        if (!$role['title']) $role['title'] = null;
      }
      $role['rend'] = ' '.$n->getAttribute ('rend').' '; // espace séparateur
      $role['sex'] = null;
      $role['age'] = null;
      $role['status'] = null;
      // TODO person/@*
      if (!trim($role['rend'])) {
        $role['rend'] = null;
      }
      else {
        if (preg_match('@ female @i', $role['rend'])) $role['sex'] = 2;
        else if (preg_match('@ male @i', $role['rend'])) $role['sex'] = 1;

        preg_match('@ (cadet|junior|senior|veteran) @i', $role['rend'], $matches);
        if (isset($matches[1]) && $matches[1]) $role['age'] = $matches[1];

        preg_match('@ (inferior|superior|exterior) @i', $role['rend'], $matches);
        if (isset($matches[1]) && $matches[1]) $role['status'] = $matches[1];
      }
      $role['note'] = null;
      $cast[$role['code']] = $role;
      $i++;
    }
    return $cast;
  }
  /**
   * Collecter les identifiants dans les <role>
   * Alerter sur les identifiants inconnus
   */
  public function valid() {
    $nodes = $this->xpath->query("//tei:role/@xml:id");
    $castlist = array();
    foreach ($nodes as $n) {
      $castlist[$n->nodeValue] = true;
    }
    $nodes = $this->xpath->query("//@who");
    foreach ($nodes as $n) {
      $who = $n->nodeValue;
      if (isset($castlist[$who])) continue;
      if (STDERR) fwrite(STDERR, $who.' l. '.$n->getLineNo()."\n");
    }
  }
  /**
   * Retourner un csv d’objets
   */
  function csv() {
    if (!isset(self::$xsl['drama2csv']) ) {
      self::$xsl['drama2csv'] = new DOMDocument("1.0", "UTF-8");
      self::$xsl['drama2csv']->load(dirname(__FILE__).'/drama2csv.xsl');
    }
    $trans = new XSLTProcessor();
    $trans->importStyleSheet(self::$xsl['drama2csv']);
    // $trans->setParameter('', 'filename', $play['code']); // ?
    return $trans->transformToXML($this->dom);
  }

}

?>
