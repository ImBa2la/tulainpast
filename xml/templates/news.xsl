<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:include href="page_navigator.xsl"/>

<xsl:template match="/page/section">
	<section class="title">
		<xsl:call-template name="crumbs">
			<xsl:with-param name="items">
				<xsl:if test="articlesRow[@id]">
					<item row="{articlesRow/@id}" title="{articlesRow/title/text()}"/>
				</xsl:if>
			</xsl:with-param>
		</xsl:call-template>
		<h1><xsl:value-of select="$_sec/@title"/></h1>
	</section>
	<article class="content" id="content">
		<xsl:apply-templates/>
		<script type="text/javascript" src="js/articles.js"></script>
		<script src="js/gallery/gallery.js" type="text/javascript"></script>
		<span class="top"></span><span class="bottom"></span>
	</article>
</xsl:template>

<xsl:template match="/page/section/news[row] | /page/section/articles[row]">
	<xsl:apply-templates select="row"/>
	<xsl:call-template name="page_navigator">
		<xsl:with-param name="numpages" select="number(@pages)"/>
		<xsl:with-param name="page" select="number(@page)"/>
		<xsl:with-param name="url">
			<xsl:value-of select="$_sec/@id"/><xsl:text>/</xsl:text>
		</xsl:with-param>
		<xsl:with-param name="pageparam" select="@pageParam"/>
	</xsl:call-template>
</xsl:template>

<xsl:template match="/page/section/news/row | /page/section/articles/row">
	<div class="new">
		<h2>
			<a href="{$_sec/@id}/row{@id}/">
				<xsl:value-of select="title/text()" disable-output-escaping="yes"/>
			</a>
			<time datetime="{date/@value}"><xsl:value-of select="@date" /></time>
		</h2>
		<xsl:apply-templates select="img"/>
		<xsl:apply-templates select="announce"/>
	</div>
</xsl:template>


<xsl:template match="/page/section/news/row/announce | /page/section/articles/row/announce">
	<xsl:value-of select="text()" disable-output-escaping="yes"/>
</xsl:template>

<xsl:template match="/page/section/news/row/img | /page/section/articles/row/img">
	<div class="img">
		<xsl:apply-templates select="preview" />
		<xsl:if test="@alt"><div class="desc"><xsl:value-of select="@alt" /></div></xsl:if>
	</div>
</xsl:template>

<xsl:template match="/page/section/news/row/img/preview | /page/section/articles/row/img/preview">
	<img src="{@src}" alt="{ancestor::row/title/text()}" width="{@width}" height="{@height}">
		<xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt" /></xsl:attribute></xsl:if>
		<xsl:if test="@width > 600">
			<xsl:attribute name="width">644</xsl:attribute>
			<xsl:attribute name="height">auto</xsl:attribute>
		</xsl:if>
	</img>
</xsl:template>


<!-- Detail News -->
<xsl:template match="/page/section/articleRow | /page/section/articlesRow">
	<div class="new" id="new">
		<h2>
			<xsl:value-of select="title/text()" disable-output-escaping="yes"/>
			<xsl:apply-templates select="date" />
		</h2>
		<xsl:apply-templates select="img"/>
		<xsl:apply-templates select="article"/>
	</div>
	<a href="{$_sec/@id}/" class="link-more">Читать еще</a>
</xsl:template>

<xsl:template match="date">
	<time datetime="{@value}"><xsl:value-of select="text()"/></time>
</xsl:template>
<xsl:template match="/page/section/articleRow/article | /page/section/articlesRow/article">
	<xsl:value-of select="text()" disable-output-escaping="yes"/>
</xsl:template>

<xsl:template match="/page/section/articleRow/img | /page/section/articlesRow/img">
	<a href="{@src}" >
		<xsl:attribute name="href"><xsl:if test="@src"><xsl:value-of select="@src" /></xsl:if></xsl:attribute>
		<xsl:if test="@alt">
			<xsl:attribute name="title"><xsl:value-of select="@alt" /></xsl:attribute>
		</xsl:if>
		<img src="{preview/@src}" alt="{ancestor::row/title/text()}"  width="{preview/@width}" height="{preview/@height}">
			<xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt" /></xsl:attribute><xsl:attribute name="title"><xsl:value-of select="@alt" /></xsl:attribute></xsl:if>
			<xsl:if test="preview/@width > 600">
				<xsl:attribute name="width">644</xsl:attribute>
				<xsl:attribute name="height">auto</xsl:attribute>
			</xsl:if>
		</img>
	</a>
</xsl:template>
</xsl:stylesheet>