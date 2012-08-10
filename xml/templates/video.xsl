<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:include href="page_navigator.xsl"/>

<xsl:template match="/page/section">
	<section class="title">
		<xsl:call-template name="crumbs" />
		<h1>
			<xsl:choose>
				<xsl:when test="/page/section/videoItem"><xsl:value-of select="/page/section/videoItem/title/text()" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="$_sec/@title"/></xsl:otherwise>
			</xsl:choose>
		</h1>
	</section>
	<article class="content">
		<xsl:apply-templates/>
		<span class="top"></span><span class="bottom"></span>
	</article>			
</xsl:template>

<xsl:template match="videos" >	
	<ul class="list">
		<xsl:apply-templates />
	</ul>
	<xsl:call-template name="page_navigator">
		<xsl:with-param name="numpages" select="number(@pages)"/>
		<xsl:with-param name="page" select="number(@page)"/>
		<xsl:with-param name="url">
			<xsl:value-of select="$_sec/@id"/><xsl:text>/</xsl:text>
		</xsl:with-param>
		<xsl:with-param name="pageparam" select="@pageParam"/>
	</xsl:call-template>
</xsl:template>

<xsl:template match="videos/row">
	<li>
		<a href="{$_sec/@id}/{@id}/">
			<xsl:apply-templates select="img"/>	
			<span><xsl:value-of select="title/text()" /></span>
		</a>
	</li>
</xsl:template>
<xsl:template match="videos//img[not(@preview)]">
	<div class="img-wrapper2"><img src="image.php?src={@src}&amp;w=167&amp;h=167" alt="{parent::row/title/text()}" width="167" height="167" /></div>
</xsl:template>

<!-- Detail -->
<xsl:template match="videoItem">	
	<section class="videoItem">
		<iframe class="youtube-player" type="text/html" width="480" height="360" src="http://www.youtube.com/embed/{@id}" frameborder="0"></iframe>
		<p><xsl:value-of select="desc/text()" disable-output-escaping="yes" /></p>
	</section>
	<a href="{base_url}{$_sec/@id}/" class="link-more">Вернуться к списку</a>
</xsl:template>
</xsl:stylesheet>