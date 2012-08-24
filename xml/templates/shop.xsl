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
		<h1>
			<xsl:choose>
				<xsl:when test="/page/section/articlesRow"><xsl:value-of select="/page/section/articlesRow/title/text()" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="$_sec/@title"/></xsl:otherwise>
			</xsl:choose>
		</h1>
	</section>
	<article class="content shop" id="content">
		<xsl:apply-templates mode="catalog"/>
		<script><xsl:text>
			function addToBasket(id){
				alert(parseInt(id));
				return false;
			}
		</xsl:text></script>
		<span class="top"></span><span class="bottom"></span>
	</article>
</xsl:template>

<xsl:template match="catalog" mode="catalog">
	<xsl:apply-templates select="row" />
</xsl:template>

<xsl:template match="catalog/row">
	<div class="new">
		<h2><a href="{$_sec/@id}/row{@id}/"><xsl:value-of select="title/text()" /></a></h2>
		<figure class="pie" style="background-image:url('{img[@name='imageSubstrate']/@src}')">
			<xsl:apply-templates select="img[@name='imageSubstrate']" />
			<xsl:apply-templates select="img[@name='imageCover']" />
		</figure>
		<xsl:apply-templates select="price" />
		<xsl:apply-templates select="announce" />
		<a href="{$_sec/@id}/row{@id}/">Смотреть подробнее...</a>
	</div>	
</xsl:template>
<xsl:template match="catalog/row/price | catalogRow/price">
	<xsl:variable name="_row"><xsl:choose>
		<xsl:when test="ancestor::row/@id"><xsl:value-of select="ancestor::row/@id" /></xsl:when>
		<xsl:when test="ancestor::catalogRow/@id"><xsl:value-of select="ancestor::catalogRow/@id" /></xsl:when>
	</xsl:choose></xsl:variable>
	<div class="shop pie">
		<span class="price"><xsl:value-of select="./text()" /></span>
		Цена:
		<a href="#" onclick="addToBasket({$_row});" class="pie"><span>В корзину</span></a>
	</div>
</xsl:template>
<xsl:template match="catalog/row/announce">
	<div class="announce"><xsl:value-of select="./text()" disable-output-escaping="yes" /></div>
</xsl:template>
<xsl:template match="catalog/row/img[@name='imageSubstrate'] | catalogRow/img[@name='imageSubstrate']">
	<img src="{@src}" alt="{ancestor::row/title/text()}" width="400" height="270" class="sub pie" />
</xsl:template>
<xsl:template match="catalog/row/img[@name='imageCover'] | catalogRow/img[@name='imageCover']">
	<img src="{@src}" alt="{ancestor::row/title/text()}" width="185" height="245" class="cover pie" />
</xsl:template>

<!-- detail item -->
<xsl:template match="catalogRow" mode="catalog">
	<div class="new">
		<h2><xsl:value-of select="title/text()" /></h2>
		<figure style="background-image:url('{img[@name='imageSubstrate']/@src}')">
			<xsl:apply-templates select="img[@name='imageSubstrate']" />
			<xsl:apply-templates select="img[@name='imageCover']" />
		</figure>
		<xsl:apply-templates select="price" />
		<a href="{$_sec/@id}/">Вернуться к списку</a>
		<xsl:apply-templates select="article" />
	</div>
</xsl:template>
<xsl:template match="catalogRow/article">
	<div class="text pie"><xsl:value-of select="./text()" disable-output-escaping="yes" /></div>
</xsl:template>
</xsl:stylesheet>