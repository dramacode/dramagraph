<?php
/**
 * Porte tout ce qui dépend du fichier XML
 */
class Dramagraph_Doc {
  /** Document XML */
  private $_dom;
  /** Processeur xpath */
  private $_xpath;
  /** Chemin original du fichier */
  public $file;
  /** Précharger des transformations courantes */
  static $trans = array();
  /**
   * Charger un fichier XML
   */
  public function __construct($file, $cont=null)
  {
    $this->_dom = new DOMDocument();
    $this->_dom->preserveWhiteSpace = false;
    $this->_dom->formatOutput=true;
    $this->_dom->substituteEntities=true;
    $options = LIBXML_NOENT | LIBXML_NONET | LIBXML_NSCLEAN | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOWARNING;
    if ($cont) $this->_dom->loadXML($cont, $options);
    else $this->_dom->load($file, $options);
    $this->file = $file;
  }
  /**
   * Set and return an XPath processor
   */
   public function xpath()
   {
     if ($this->_xpath) return $this->_xpath;
     $this->_xpath = new DOMXpath($this->_dom);
     $this->_xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");
     return $this->_xpath;
   }
  /**
   * Wash notes and paratext
   */
   public function naked()
   {
     if (!isset(self::$trans['naked']) ) {
       $xsl = new DOMDocument("1.0", "UTF-8");
       $xsl->load(dirname(__FILE__).'/naked.xsl');
       self::$trans['naked'] = new XSLTProcessor();
       self::$trans['naked']->importStyleSheet($xsl);
     }
     $this->_dom = self::$trans['naked']->transformToDoc($this->_dom);
     $this->_xpath = null;
     return $this->_dom;
   }

  /**
   * Reuse DOM
   */
  public function dom()
  {
      return $this->_dom;
  }
  /**
   * Métadonnées de pièce
   */
  public function meta()
  {
    $this->xpath();
    $meta = array();
    $meta['code'] = pathinfo($this->file, PATHINFO_FILENAME);
    // author
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:author");
    if (!$nl->length)
      $meta['author'] = null;
    else if ($nl->item(0)->hasAttribute("key"))
      $meta['author'] = $nl->item(0)->getAttribute("key");
    else
      $meta['author'] = $nl->item(0)->textContent;
    if (($pos = strpos($meta['author'], '('))) $meta['author'] = trim(substr($meta['author'], 0, $pos));
    // publisher
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:publisher");
    if ($nl->length) $meta['publisher'] = $nl->item(0)->textContent;
    else $meta['publisher'] = null;
    // identifier
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno");
    if ($nl->length) $meta['identifier'] = $nl->item(0)->textContent;
    else $meta['identifier'] = null;
    // dates
    $nl = $this->_xpath->query("/*/tei:teiHeader/tei:profileDesc/tei:creation/tei:date");
    $meta['created'] = null;
    $meta['issued'] = null;
    $meta['date'] = null;
    foreach ($nl as $date) {
      $value = $date->getAttribute('when');
      if (!$value) $value = $date->nodeValue;
      $value = substr(trim($value), 0, 4);
      if (!is_numeric($value)) {
        $value = null;
        continue;
      }
      if (!$meta['date']) $meta['date'] = $value;
      if ($date->getAttribute ('type') == "created" && !$meta['created']) $meta['created'] = $value;
      else if ($date->getAttribute ('type') == "issued" && !$meta['issued']) $meta['issued'] = $value;
    }
    if (!$meta['issued'] && isset($value) && is_numeric($value)) $meta['issued'] = $value;



    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:title");
    if ($nl->length) $meta['title'] = $nl->item(0)->textContent;
    else $meta['title'] = null;
    $nl = $this->_xpath->query("/*/tei:teiHeader//tei:term[@type='genre']/@subtype");
    if ($nl->length) $meta['genre'] = $nl->item(0)->nodeValue;
    else $meta['genre'] = null;

    $meta['acts'] = $this->_xpath->evaluate("count(/*/tei:text/tei:body//tei:*[@type='act'])");
    if (!$meta['acts']) $meta['acts'] = $this->_xpath->evaluate("count(/*/tei:text/tei:body/*[tei:div|tei:div2])");
    if (!$meta['acts']) $meta['acts'] = 1;
    $l = $this->_xpath->evaluate("count(//tei:sp/tei:l)");
    $p = $this->_xpath->evaluate("count(//tei:sp/tei:p)");
    if ($l > 2*$p) $meta['verse'] = true;
    else if ($p > 2*$l) $meta['verse'] = false;
    else $meta['verse'] = null;
    return $meta;
  }
  /**
   *
   */
  function elValue( $el )
  {
    $text = array();
    $nl = $this->_xpath->query(".//text()[not(ancestor::tei:note)]", $el);
    foreach ( $nl as $n ) {
      $text[] = $n->wholeText;
    }
    return implode('', $text);
    /*
    // suppress notes from a clone of this node
    libxml_use_internal_errors();
    $clone = $n->cloneNode( true );
    foreach ( $clone->childNodes as $n2 ) {
      if ( $n2->nodeName == "note" ) $clone->removeChild( $n2 );
    }
    libxml_use_internal_errors(true);
    */
  }
  /**
   * Liste des rôles
   */
  function cast()
  {
    $this->xpath();
    $nodes = $this->_xpath->query("//tei:role[@xml:id]|//tei:person[@xml:id]");
    $cast = array();
    $i = 1;
    foreach ($nodes as $n) {
      $role = array();
      $role['ord'] = $i;
      $role['code'] = $n->getAttribute ('xml:id');
      if (!$role['code']) continue;

      $role['label'] = $n->getAttribute ('n');
      if (!$role['label']) {
        $role['label'] = rtrim( $this->elValue($n), ' ,');
      }
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
  public function valid()
  {
    $this->xpath();
    // TODO configurations
    $nodes = $this->_xpath->query("//tei:role/@xml:id|//tei:person[@xml:id]");
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
   * Retourner un csv d’objets
   */
  function csv()
  {
    if (!isset(self::$trans['drama2csv']) ) {
      $xsl = new DOMDocument("1.0", "UTF-8");
      $xsl->load(dirname(__FILE__).'/drama2csv.xsl');
      self::$trans['drama2csv'] = new XSLTProcessor();
      self::$trans['drama2csv']->importStyleSheet($xsl);
    }
    // $trans->setParameter('', 'filename', $play['code']); // ?
    return self::$trans['drama2csv']->transformToXML($this->_dom);
  }

}

?>
