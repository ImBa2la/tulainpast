<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:exsl="http://exslt.org/common">

<xsl:output media-type="text/html" method="html" omit-xml-declaration="yes" indent="no" encoding="utf-8"/>
<!-- Текущий раздел -->
<xsl:variable name="_sec" select="/page/structure//sec[@selected='selected']"/>
<!-- Базовый URL -->
<xsl:variable name="_base_url">http://tulainpast.loc/</xsl:variable>
<!-- Id секции с учетом связи -->
<xsl:variable name="_secId">
	<xsl:choose>
		<xsl:when test="/page/section/articlesRow/@id">
			<xsl:value-of select="/page/structure//sec[@row=string(/page/section/articlesRow/@id)]/@id" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$_sec/@id" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<!-- top menu -->
<xsl:template match="/page/structure" mode="topmenu">
	<nav>
		<xsl:apply-templates select="sec[@class='menu']" mode="topmenu"/>
		<a href="forum/">Форум<span></span></a>
	</nav>
</xsl:template>
<xsl:template match="/page/structure/sec" mode="topmenu">
	<xsl:variable name="isSelected" select="$_sec/ancestor-or-self::sec/@id = @id"/>
	<a href="{@id}/">
		<xsl:if test="$isSelected">
			<xsl:attribute name="class">selected</xsl:attribute>
		</xsl:if>
		<xsl:value-of select="@title"/>
		<span></span>
	</a>
</xsl:template>

<!-- side menu -->
<xsl:template match="/page/structure[sec/@class='side']" mode="sidemenu">
	<nav>
		<ul>
			<xsl:apply-templates select="sec[@class='side']" mode="sidemenu"/>
			<script type="text/javascript" src="js/gallery.prev.js"></script>
		</ul>
	</nav>
</xsl:template>
<xsl:template match="/page/structure/sec" mode="sidemenu">
	<xsl:variable name="isSelected" select="$_sec/ancestor-or-self::sec/@id = @id"/>
	<li>
		<xsl:if test="$isSelected">
			<xsl:attribute name="class">selected</xsl:attribute>
		</xsl:if>
		<a href="{@id}/"><xsl:value-of select="@title"/></a>
		<xsl:if test="sec">
			<ul class="nest-1">
				<xsl:if test="$isSelected">
					<xsl:attribute name="style">display:block;</xsl:attribute>
				</xsl:if>
				<xsl:apply-templates select="sec" mode="sidemenu"/>
			</ul>
		</xsl:if>
	</li>
</xsl:template>
<xsl:template match="structure/sec//sec" mode="sidemenu">
	<xsl:variable name="isSelected" select="$_sec/ancestor-or-self::sec/@id = @id"/>
	<li>
		<xsl:if test="$isSelected">
			<xsl:attribute name="class">selected<xsl:if test="(@class = 'photo') or (@class = 'photo_ext')"> photo</xsl:if></xsl:attribute>
		</xsl:if>
		
		<a href="{@id}/">
			<xsl:if test="@url"><xsl:attribute name="href"><xsl:value-of select="@url" /></xsl:attribute></xsl:if>
			<xsl:value-of select="@title"/>
			<xsl:if test="(@class = 'photo' or @class = 'photo_ext') and @img"><img src="image.php?src={@img}&amp;h=76&amp;w=76" alt="{@title}"/></xsl:if>
		</a>
		<xsl:if test="sec">
			<ul class="nest-2">
				<xsl:if test="$isSelected">
					<xsl:attribute name="style">display:block;</xsl:attribute>
				</xsl:if>
				<!--<xsl:if test="$_secId = @id">
					<xsl:attribute name="class">nest-2 toggle</xsl:attribute>
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>-->
				<xsl:apply-templates select="sec" mode="sidemenu"/>
			</ul>
		</xsl:if>
	</li>
</xsl:template>

<!-- Заголовочные тэги -->
<xsl:template name="head">
	<title>
		<xsl:choose>
			<xsl:when test="/page/meta[@name='title']/text()">
				<xsl:value-of select="/page/meta[@name='title']/text()" />
				<xsl:text> - </xsl:text>
				<xsl:value-of select="/page/site/@name"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$_sec/@title"/>
				<xsl:text> - </xsl:text>
				<xsl:value-of select="/page/site/@name"/>
			</xsl:otherwise>
		</xsl:choose>
	</title>
	
	<meta name="keywords">
		<xsl:attribute name="content">
			<xsl:choose>
				<xsl:when test="/page/meta[@name='keywords']/text()">
					<xsl:value-of select="/page/meta[@name='keywords']/text()" />
				</xsl:when>
				<xsl:otherwise>старая Тула история Тулы</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
	</meta>
	
	<meta name="description">
		<xsl:attribute name="content">
			<xsl:choose>
				<xsl:when test="/page/meta[@name='description']/text()">
					<meta name="description" content="{/page/meta[@name='description']/text()}" />
				</xsl:when>
				<xsl:otherwise>Тула ушедшего века история достопримечательности</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
	</meta>
</xsl:template>
<!-- Крошки -->
<xsl:template name="crumbs">
	<xsl:param name="items" />	
	<nav class="crumbs">
		<a href="home/">Главная</a>
		<xsl:apply-templates select="$_sec/ancestor::sec" mode="crumbs"/>
		<xsl:text> &gt; </xsl:text>
		<a href="{$_sec/@id}/">
			<xsl:if test="$_sec/@url"><xsl:attribute name="href"><xsl:value-of select="$_sec/@url" />/</xsl:attribute></xsl:if>
			<xsl:if test="not(count(exsl:node-set($items)/item))">
				<xsl:attribute name="class">active</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="$_sec/@title"/>
		</a>
		<xsl:for-each select="exsl:node-set($items)/item">
			<xsl:text> &gt; </xsl:text>
			<a href="{$_sec/@id}/row{@row}/">
				<xsl:if test="not(following-sibling::item)">
					<xsl:attribute name="class">active</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@title"/>
			</a>
		</xsl:for-each>
	</nav>			
</xsl:template>
<xsl:template match="/page/structure//sec" mode="crumbs">
	<xsl:if test="not($_sec/@id = @id)">
		<xsl:text> &gt; </xsl:text><a href="{@id}/"><xsl:value-of select="@title"/></a>
	</xsl:if>
</xsl:template>
<!-- Остальное -->
<xsl:template match="html">
	<xsl:value-of select="text()"  disable-output-escaping="yes" />
</xsl:template>
<xsl:template match="finish">
	<xsl:value-of select="text()"  disable-output-escaping="yes" />
</xsl:template>
<xsl:template match="final">
	<xsl:value-of select="text()"  disable-output-escaping="yes" />
</xsl:template>
</xsl:stylesheet>