<?xml version="1.0" encoding="UTF-8"?>
<!-- 
Ramasser des informations chiffrées d’une pièce 
-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns="http://www.tei-c.org/ns/1.0" xmlns:tei="http://www.tei-c.org/ns/1.0" exclude-result-prefixes="tei">
  <!-- CSV -->
  <xsl:output method="text" encoding="UTF-8" indent="yes"/>
  <!-- Lister les rôles en tête, pour des listes par scènes -->
  <xsl:key name="roles" match="tei:role[@xml:id]|tei:person[@xml:id]" use="'roles'"/>
  <!-- Nom du fichier de pièce procédé -->
  <xsl:param name="filename"/>
  <!-- mode -->
  <xsl:param name="mode"/>
  <xsl:variable name="who1">ABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑŒÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöùúûüýœ’' </xsl:variable>
  <xsl:variable name="who2">abcdefghijklmnopqrstuvwxyzaaaaaaceeeeiiiin?ooooouuuuyaaaaaaceeeeiiiinooooouuuuy?---</xsl:variable>
  <!-- constantes -->
  <xsl:variable name="lf" select="'&#10;'"/>
  <xsl:variable name="tab" select="'&#09;'"/>
  <xsl:variable name="apos">'</xsl:variable>
  <xsl:variable name="quot">"</xsl:variable>
  <!-- Par défaut, pas de texte -->
  <xsl:template match="text()"/>
  <xsl:template match="tei:note"/>
  <!-- mais passer à travaers tout, notamment pour chercher les configuration (auf dans les notes) -->
  <xsl:template match="*">
    <xsl:apply-templates select="*"/>
  </xsl:template>
  <xsl:template match="/">
    <root>
      <!-- object, type, code, n, l, ln, w, c, source, target, text  -->
      <xsl:text>object</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>code</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>n</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>label</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>type</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>target</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>ln</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>l</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>w</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>c</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>text</xsl:text>
      <xsl:value-of select="$lf"/>
      <xsl:apply-templates select="/*/tei:text/tei:body/*[.//tei:sp]"/>
    </root>
  </xsl:template>
  <!-- Acte, some <div> may not have <sp> (intermede, etc) -->
  <xsl:template match="tei:body/tei:div1 | tei:body/tei:div">
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:when test="@type='act'">
          <xsl:number format="I" count="tei:div[@type='act']|tei:div1[@type='act']"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number format="A" count="tei:div[not(@type='act') and not(@xml:id)]|tei:div1[not(@type='act') and not(@xml:id)]"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="n">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:when test="@n">
          <xsl:value-of select="@n"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number format="I"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="label">
      <xsl:variable name="txt">
        <xsl:apply-templates select="tei:head[1]" mode="txt"/>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="normalize-space($txt) != ''">
          <xsl:value-of select="normalize-space($txt)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>Acte </xsl:text>
          <xsl:value-of select="$n"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- object	code	n	label	type	target	c	w	l	ln	text  -->
    <xsl:text>div1</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$label"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <xsl:value-of select="$tab"/>
    <!-- target -->
    <xsl:value-of select="$tab"/>
    <!-- ln -->
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="count(.//tei:l[not(@part) or @part='I' or @part='i'])"/>
    <xsl:value-of select="$lf"/>
    <!-- Si pas de scène et pas de conf, créer une configuration auto -->
    <xsl:if test="tei:sp and not(.//tei:listPerson)">
      <xsl:call-template name="conf">
        <xsl:with-param name="code" select="$code"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:apply-templates select="*">
      <xsl:with-param name="act" select="$code"/>
    </xsl:apply-templates>
  </xsl:template>

  <!-- Scène -->
  <xsl:template match="tei:body/tei:div1/tei:div2 | tei:body/tei:div/tei:div">
    <xsl:param name="act"/>
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$act"/>
          <xsl:number format="01"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="n">
      <xsl:choose>
        <xsl:when test="@n">
          <xsl:value-of select="@n"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="label">
      <xsl:variable name="txt">
        <xsl:apply-templates select="tei:head[1]" mode="txt"/>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="normalize-space($txt) != ''">
          <xsl:value-of select="normalize-space($txt)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>Acte </xsl:text>
          <xsl:value-of select="$n"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:text>div2</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$label"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <xsl:value-of select="$tab"/>
    <!-- target -->
    <xsl:value-of select="$tab"/>
    <!-- ln -->
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="count(.//tei:l[not(@part) or @part='I' or @part='i'])"/>
    <xsl:value-of select="$lf"/>
    <!--
      Si pas de configuration pour la scène, en créer
      -->
    <xsl:if test="not(.//tei:listPerson)">
      <xsl:call-template name="conf">
        <xsl:with-param name="code" select="$code"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:apply-templates select="*">
      <xsl:with-param name="act" select="$act"/>
      <xsl:with-param name="scene" select="$code"/>
    </xsl:apply-templates>
  </xsl:template>
  
  <!-- Conf auto -->
  <xsl:template name="conf">
    <xsl:param name="code"/>
    <xsl:text>configuration</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <!-- n -->
    <xsl:value-of select="$tab"/>
    <!--
    <xsl:variable name="txt">
      <xsl:variable name="context" select="."/>
      <xsl:for-each select="key('roles', 'roles')">
        <xsl:variable name="who" select="@xml:id"/>
        <xsl:if test="$context/tei:sp[contains(concat(' ', @who, ' '), concat(' ', $who, ' '))]">
          <xsl:text> </xsl:text>
          <xsl:value-of select="$who"/>
        </xsl:if>
      </xsl:for-each>
    </xsl:variable>
    <xsl:value-of select="normalize-space($txt)"/>
    -->
    <xsl:variable name="whochain">
      <xsl:for-each select="tei:sp">
        <xsl:value-of select="@who"/>
        <xsl:text> </xsl:text>
      </xsl:for-each>
    </xsl:variable>
    <xsl:call-template name="confexpl">
      <xsl:with-param name="who" select="concat( normalize-space($whochain), ' ')"/>
    </xsl:call-template> 
    <xsl:value-of select="$tab"/>
    <xsl:text>auto</xsl:text>
    <xsl:value-of select="$lf"/>
    
    
  </xsl:template>
  
  <xsl:template name="confexpl">
    <xsl:param name="who"/>
    <xsl:param name="done"/>
    <xsl:variable name="id" select="substring-before($who, ' ')"/>
    <xsl:choose>
      <xsl:when test="normalize-space($who) = ''"/>
      <xsl:when test="contains ($done, concat(' ', $id, ' '))">
        <xsl:call-template name="confexpl">
          <xsl:with-param name="who" select="substring-after($who, ' ')"/>
          <xsl:with-param name="done" select="$done"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$id"/>
        <xsl:text> </xsl:text>
        <xsl:call-template name="confexpl">
          <xsl:with-param name="who" select="substring-after($who, ' ')"/>
          <xsl:with-param name="done" select="concat($done, ' ', $id, ' ')"/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- configuration -->
  <xsl:template match="tei:listPerson">
    <xsl:text>configuration</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:choose>
      <xsl:when test="@xml:id">
        <xsl:value-of select="@xml:id"/>
      </xsl:when>
      <xsl:when test="@type = 'configuration'">
        <xsl:text>conf</xsl:text>
        <xsl:number count="tei:listPerson[@type='configuration']" level="any"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>listperson</xsl:text>
        <xsl:number count="tei:listPerson" level="any"/>        
      </xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="$tab"/>
    <xsl:choose>
      <xsl:when test="@n">
        <xsl:value-of select="@n"/>
      </xsl:when>
      <xsl:when test="@type = 'configuration'">
        <xsl:number count="tei:listPerson[@type='configuration']" level="any"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:number count="tei:listPerson" level="any"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="$tab"/>
    <xsl:variable name="txt">
      <xsl:for-each select=".//tei:person">
        <xsl:text> </xsl:text>
        <xsl:choose>
          <xsl:when test="@xml:id">
            <xsl:value-of select="@xml:id"/>
          </xsl:when>
          <xsl:when test="@corresp">
            <xsl:value-of select="substring-after(@corresp, '#')"/>
          </xsl:when>
        </xsl:choose>
      </xsl:for-each>  
    </xsl:variable>
    <xsl:value-of select="normalize-space($txt)"/>
    <xsl:value-of select="$tab"/>
    <xsl:text>listPerson</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:variable name="txt2">
      <xsl:variable name="context" select="ancestor::*[self::tei:div or self::tei:div2 or self::tei:div1 ][1]"/>
      <xsl:for-each select="key('roles', 'roles')">
        <xsl:variable name="who" select="@xml:id"/>
        <xsl:if test="$context/tei:sp[contains(concat(' ', @who, ' '), concat(' ', $who, ' '))]">
          <xsl:text> </xsl:text>
          <xsl:value-of select="$who"/>
        </xsl:if>
      </xsl:for-each>
    </xsl:variable>
    <xsl:value-of select="normalize-space($txt2)"/>
    <xsl:value-of select="$lf"/>
  </xsl:template>
  <!-- Didascalie -->
  <xsl:template match="tei:stage | tei:speaker/tei:hi">
    <xsl:variable name="n">
      <xsl:number count="tei:stage|tei:speaker/tei:hi" level="any"/>
    </xsl:variable>
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>stage</xsl:text>
          <xsl:value-of select="$n"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- object, type, code, n, l, ln, w, c, source  -->
    <xsl:text>stage</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <!-- label -->
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <xsl:value-of select="$tab"/>
    <!-- target -->
    <xsl:value-of select="$tab"/>
    <!-- l -->
    <xsl:value-of select="$tab"/>
    <!-- ln, verse index -->
    <xsl:value-of select="$tab"/>
    <xsl:variable name="txt">
      <xsl:variable name="raw">
        <xsl:apply-templates mode="txt"/>
      </xsl:variable>
      <xsl:choose>
        <!-- <stage> is not normalized by mode="txt" -->
        <xsl:when test="not(tei:p|tei:l)">
          <xsl:value-of select="normalize-space($raw)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$raw"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- words, compter les mots, algo bête, nombre d’espaces et d’apostrophes  -->
    <xsl:value-of select="1 + string-length($txt) - string-length(translate($txt, concat(' ’', $apos), ''))"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="string-length($txt)"/>
    <xsl:value-of select="$tab"/>
    <xsl:text>"</xsl:text>
    <xsl:value-of select="translate($txt, $quot, '＂')"/>
    <xsl:text>"</xsl:text>
    <xsl:value-of select="$lf"/>
    <xsl:apply-templates select="*"/>
  </xsl:template>
  <!-- Réplique -->
  <xsl:template match="tei:sp">
    <xsl:param name="act"/>
    <xsl:param name="scene"/>
    <!-- Explore configurations before what is said -->
    <xsl:apply-templates select="tei:speaker"/>
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:when test="$scene != ''">
          <xsl:value-of select="$scene"/>
          <xsl:text>-</xsl:text>
          <xsl:number/>
        </xsl:when>
        <!-- Réplique directement sous un acte -->
        <xsl:when test="$act != ''">
          <xsl:value-of select="$act"/>
          <xsl:text>-</xsl:text>
          <xsl:number/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>sp</xsl:text>
          <xsl:number level="any"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="n">
      <xsl:choose>
        <xsl:when test="@n">
          <xsl:value-of select="@n"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="who">
      <xsl:call-template name="who"/>
    </xsl:variable>
    <xsl:variable name="who-next">
      <xsl:for-each select="following-sibling::tei:sp[1]">
        <xsl:call-template name="who"/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:variable name="who-prev">
      <xsl:for-each select="preceding-sibling::tei:sp[1]">
        <xsl:call-template name="who"/>
      </xsl:for-each>
    </xsl:variable>
    
    <!-- object, type, code, n, l, ln, w, c, source, target, text  -->
    <xsl:text>sp</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$who"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <!-- prefered target -->
    <xsl:value-of select="$tab"/>
    <xsl:choose>
      <xsl:when test="$who-next != '' and $who != $who-next">
        <xsl:value-of select="$who-next"/>
      </xsl:when>
      <xsl:when test="$who-prev != '' and $who != $who-prev">
        <xsl:value-of select="$who-prev"/>
      </xsl:when>
      <!-- monologue d’une seule tirade -->
      <xsl:otherwise>
        <xsl:value-of select="$who"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="$tab"/>
    <!-- verse index -->
    <xsl:value-of select=".//tei:l[@n][1]/@n"/>
    <xsl:value-of select="$tab"/>
    <xsl:if test=".//tei:l">
      <xsl:value-of select="count(.//tei:l[not(@part) or @part='I' or @part='i'])"/>
    </xsl:if>
    <xsl:value-of select="$tab"/>
    <xsl:variable name="txt">
      <xsl:apply-templates select="*" mode="txt"/>
    </xsl:variable>
    <!-- words, compter les mots, algo bête, nombre d’espaces et d’apostrophes  -->
    <xsl:value-of select="1 + string-length($txt) - string-length(translate($txt, concat(' ’', $apos), ''))"/>
    <xsl:value-of select="$tab"/>
    <!-- chars -->
    <xsl:value-of select="string-length($txt)"/>
    <xsl:value-of select="$tab"/>
    <xsl:text>"</xsl:text>
    <xsl:value-of select="translate($txt, $quot, '＂')"/>
    <xsl:text>"</xsl:text>
    <xsl:value-of select="$lf"/>
    <!-- find <stage> and <listPerson> -->
    <xsl:apply-templates select="*[not(self::tei:speaker)]"/>
  </xsl:template>
  <!-- To Count chars -->
  <xsl:template match="tei:note|tei:stage|tei:speaker|tei:listPerson" mode="txt"/>
  <xsl:template match="tei:quote | tei:lg" mode="txt">
    <xsl:choose>
      <xsl:when test="tei:p|tei:lg|tei:l">
        <xsl:apply-templates select="*" mode="txt"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="txt"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="tei:p|tei:l|tei:label" mode="txt" name="txt">
    <xsl:if test="preceding-sibling::tei:p or preceding-sibling::tei:l or preceding-sibling::tei:quote or preceding-sibling::tei:label">
      <xsl:value-of select="$lf"/>
    </xsl:if>
    <xsl:variable name="txt">
      <xsl:apply-templates mode="txt"/>
    </xsl:variable>
    <xsl:value-of select="normalize-space($txt)"/>
  </xsl:template>
  <xsl:template name="who">
    <xsl:choose>
      <xsl:when test="@who">
        <xsl:value-of select="substring-before(concat(@who, ' '), ' ')"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="translate(substring-before(concat(normalize-space(tei:speaker), ' '), ' '), $who1, $who2)"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:transform>
