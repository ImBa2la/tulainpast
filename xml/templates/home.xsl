<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/page/section">
	<section class="title">
		<h1><xsl:value-of select="$_sec/@title"/></h1>
	</section>
	<article class="content" id="content">
		<xsl:apply-templates/>
		<xsl:apply-templates select="/page/news"/>
		<span class="top"></span><span class="bottom"></span>
	</article>
</xsl:template>

<xsl:template match="/page/news[row]">
	<xsl:apply-templates select="row"/>
	<script src="js/gallery/gallery.js" type="text/javascript"></script>
	<script type="text/javascript" src="js/articles.js"></script>
	<script src="js/gallery/gallery.js" type="text/javascript"></script>
	<p class="more">[<a href="news/" class="more">Архив новостей</a>]</p>
</xsl:template>

<xsl:template match="/page/news/row">
	<div class="new">
		<h2>
			<a href="news/row{@id}/">
				<xsl:value-of select="title/text()" disable-output-escaping="yes"/>
			</a>
			<xsl:apply-templates select="date" />
		</h2>
		<xsl:apply-templates select="img"/>
		<xsl:apply-templates select="announce"/>
	</div>
</xsl:template>
<xsl:template match="/page/news/row/date">
	<time datetime="{@value}"><xsl:value-of select="text()"/></time>
</xsl:template>
<xsl:template match="/page/news/row/announce">
	<xsl:value-of select="text()" disable-output-escaping="yes"/>
</xsl:template>

<xsl:template match="/page/news/row/img">
	<a href="{@src}" rel="gallery[news]">
		<img src="{preview/@src}" alt="{ancestor::row/title/text()}"  width="{preview/@width}" height="{preview/@height}">
			<xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt" /></xsl:attribute></xsl:if>
			<xsl:if test="preview/@width > 600">
				<xsl:attribute name="width">644</xsl:attribute>
				<xsl:attribute name="height">auto</xsl:attribute>
			</xsl:if>
		</img>
	</a>
</xsl:template>

</xsl:stylesheet>