<?xml version="1.0" encoding="UTF-8"?>
<!-- 
Ramasser des informations chiffrées d’une pièce 
-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.1" xmlns="http://www.tei-c.org/ns/1.0" xmlns:tei="http://www.tei-c.org/ns/1.0" exclude-result-prefixes="tei">
  <!-- CSV -->
  <xsl:output method="text" encoding="UTF-8" indent="yes"/>
  <!-- Lister les rôles en tête, pour des listes par scènes -->
  <xsl:key name="role" match="tei:front//tei:role" use="@xml:id"/>
  <!-- Nom du fichier de pièce procédé -->
  <xsl:param name="filename"/>
  <!-- mode -->
  <xsl:param name="mode"/>
  <!-- constantes -->
  <xsl:variable name="lf" select="'&#10;'"/>
  <xsl:variable name="tab" select="'&#09;'"/>
  <xsl:variable name="apos">'</xsl:variable>
  <xsl:variable name="quot">"</xsl:variable>
  <xsl:variable name="scene">scene</xsl:variable>
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
      <xsl:text>l</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>ln</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>w</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>c</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>source</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>target</xsl:text>
      <xsl:value-of select="$tab"/>
      <xsl:text>text</xsl:text>
      <xsl:value-of select="$lf"/>
      <xsl:apply-templates select="/*/tei:text/tei:body/*"/>
    </root>
  </xsl:template>
  <xsl:template match="*"/>
  <!-- Acte -->
  <xsl:template match="tei:body/tei:div1 | tei:body/tei:div">
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:number format="I"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="n">
      <xsl:choose>
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
    <!-- object, type, code, n, l, ln, w, c, source, target, text  -->
    <xsl:text>div1</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$label"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <xsl:value-of select="$lf"/>
    <xsl:apply-templates select="tei:div2|tei:div|tei:sp">
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
    <!-- object, type, code, n, l, ln, w, c, source, target, text  -->
    <xsl:text>div2</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$label"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="@type"/>
    <xsl:value-of select="$lf"/>
    <xsl:apply-templates select="tei:sp">
      <xsl:with-param name="act" select="$act"/>
      <xsl:with-param name="scene" select="$code"/>
    </xsl:apply-templates>
  </xsl:template>
  <xsl:template match="tei:sp">
    <xsl:param name="act"/>
    <xsl:param name="scene"/>
    <xsl:variable name="code">
      <xsl:choose>
        <xsl:when test="@xml:id">
          <xsl:value-of select="@xml:id"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$scene"/>
          <xsl:text>-</xsl:text>
          <xsl:number/>
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
    <!-- object, type, code, n, l, ln, w, c, source, target, text  -->
    <xsl:text>sp</xsl:text>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$code"/>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="$n"/>
    <xsl:value-of select="$tab"/>
    <!-- head ? -->
    <xsl:value-of select="$tab"/>
    <!-- type ? -->
    <xsl:value-of select="$tab"/>
    <!-- verses -->
    <xsl:variable name="countl" select="count(.//tei:l)"/>
    <xsl:if test="$countl &gt; 0">
      <xsl:value-of select="$countl"/>
    </xsl:if>
    <xsl:value-of select="$tab"/>
    <xsl:value-of select="(.//tei:l)[1]/@n"/>
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
    <!-- source -->
    <xsl:value-of select="substring-before(concat(@who, ' '), ' ')"/>
    <xsl:value-of select="$tab"/>
    <!-- target -->
    <xsl:choose>
      <xsl:when test="preceding-sibling::tei:sp">
        <xsl:value-of select="substring-before(concat(preceding-sibling::tei:sp[1]/@who, ' '), ' ')"/>
      </xsl:when>
      <xsl:when test="following-sibling::tei:sp">
        <xsl:value-of select="substring-before(concat(following-sibling::tei:sp[1]/@who, ' '), ' ')"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="substring-before(concat(@who, ' '), ' ')"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:value-of select="$tab"/>
    <!-- text -->
    <xsl:text>"</xsl:text>
    <xsl:value-of select="translate($txt, $quot, '＂')"/>
    <xsl:text>"</xsl:text>
    <xsl:value-of select="$lf"/>
  </xsl:template>
  <!-- To Count chars -->
  <xsl:template match="tei:note|tei:stage|tei:speaker" mode="txt"/>
  <xsl:template match="tei:p|tei:l" mode="txt">
    <xsl:if test="preceding-sibling::tei:p or preceding-sibling::tei:l">
      <xsl:value-of select="$lf"/>
    </xsl:if>
    <xsl:variable name="txt">
      <xsl:apply-templates mode="txt"/>
    </xsl:variable>
    <xsl:value-of select="normalize-space($txt)"/>
  </xsl:template>
</xsl:transform>
