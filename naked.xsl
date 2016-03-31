<?xml version="1.0" encoding="UTF-8"?>
<!--
© 2016 Frédéric Glorieux, licence  <a href="http://www.gnu.org/licenses/lgpl.html">LGPL</a>

No paratext 

-->
<xsl:transform version="1.0"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:tei="http://www.tei-c.org/ns/1.0"

  exclude-result-prefixes="tei">
  <xsl:output encoding="UTF-8" indent="yes" method="xml"/>
  <xsl:template match="node()|@*" name="copy">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="tei:note"/>
  <!--  -->
  <xsl:template match="tei:front">
    <xsl:choose>
      <xsl:when test=".//tei:castList">
        <xsl:call-template name="copy"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="tei:back"/>
</xsl:transform>