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

<xsl:template match="/page/section/guestbook[row]">
	<section class="guestBook">		
		<xsl:apply-templates select="row"/>
		<xsl:call-template name="page_navigator">
			<xsl:with-param name="numpages" select="number(@pages)"/>
			<xsl:with-param name="page" select="number(@page)"/>
			<xsl:with-param name="url">
				<xsl:value-of select="$_sec/@id"/><xsl:text>/</xsl:text>
			</xsl:with-param>
			<xsl:with-param name="pageparam" select="@pageParam"/>
		</xsl:call-template>
	</section>
</xsl:template>

<xsl:template match="/page/section/guestbook/row">
	<article>
		<h2>
			<xsl:value-of select="title/text()" disable-output-escaping="yes"/>
			<xsl:apply-templates select="date" />
			<!--<time datetime="{date/@value}"><xsl:value-of select="@date" /></time>-->
		</h2>
		<xsl:apply-templates select="announce" />
		<xsl:apply-templates select="author" />
		<xsl:apply-templates select="article" />
	</article>
</xsl:template>


<xsl:template match="/page/section/guestbook/row/announce">
	<div class="question"><xsl:value-of select="text()"/></div>
</xsl:template>
<xsl:template match="/page/section/guestbook/row/article">
	<div class="answer"><xsl:value-of select="text()" disable-output-escaping="yes"/></div>
</xsl:template>
<xsl:template match="/page/section/guestbook/row/author">
	<div class="author"><xsl:value-of select="text()" /></div>
</xsl:template>


<!-- Detail News -->
<xsl:template match="/page/section/guestbookRow"><!-- empty tpl --></xsl:template>

<xsl:template match="date">
	<time datetime="{@value}"><xsl:value-of select="text()"/></time>
</xsl:template>
<xsl:template match="/page/section/articleRow/article | /page/section/articlesRow/article">
	<xsl:value-of select="text()" disable-output-escaping="yes"/>
</xsl:template>
</xsl:stylesheet>