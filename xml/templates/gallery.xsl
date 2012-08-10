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
	<article class="content" id="content">
		<xsl:if test="articlesRow">
			<section class="gallery">
				<span class="left"></span><span class="right"></span>
				<div>
					<ul>
						<xsl:apply-templates select="/page/section/articlesRow/img" mode="gallery"/>
					</ul>
				</div>
			</section>
		</xsl:if>
		<xsl:apply-templates select="/page/structure//sec[@id=$_secId]" mode="gallery"/>
		<xsl:apply-templates mode="gallery"/>
		<span class="top"></span><span class="bottom"></span>
	</article>
	
</xsl:template>


<xsl:template match="/page/structure//sec[sec][@class='photo' or count(/page/section/articles/row) > 0]" mode="gallery">
	<ul class="list">
		<xsl:apply-templates select="sec" mode="squaregallery"/>
	</ul>
</xsl:template>
<xsl:template match="/page/structure//sec/sec" mode="squaregallery">
	<li>
		<a href="{@id}/">
			<xsl:if test="@url"><xsl:attribute name="href"><xsl:value-of select="@url"/>/</xsl:attribute></xsl:if>
			<div class="img-wrapper2"><img src="image.php?src={@prv}&amp;w=167&amp;h=167" alt="{@title}" width="167" height="167">
				<xsl:if test="not(@img)">
					<xsl:attribute name="src">
						<xsl:text>image.php?src=</xsl:text>
						<xsl:choose>
							<xsl:when test="sec/@prv">
								<xsl:value-of select="sec/@prv" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>userfiles/176x176.jpg</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:text>&amp;w=167&amp;h=167</xsl:text>
					</xsl:attribute>
				</xsl:if>
			</img></div>
			<span><xsl:value-of select="@title" /></span>
		</a>
	</li>
</xsl:template>

<xsl:template match="/page/section/articles[row]" mode="gallery">
	<!-- empty template - blocking apply-templates /page/section/articles/row -->
</xsl:template>

<!-- Detail Gallery -->
<xsl:template match="/page/section/articlesRow" mode="gallery">
	<script type="text/javascript" src="js/gallery.slider.js"></script>
	<!--<h2><xsl:value-of select="title/text()" disable-output-escaping="yes"/></h2>-->
	<xsl:value-of select="article/text()" disable-output-escaping="yes" />
	<div class="big-img">
		<span class="left"><xsl:comment /></span>
		<span class="right"><xsl:comment /></span>
		<span class="play"><xsl:comment /></span>
		<div class="img"><img class="big-img" src="images/loader2.gif" /></div>
		<div class="text"><xsl:comment /></div>
	</div>
</xsl:template>

<xsl:template match="/page/section/articlesRow/img" mode="gallery">
	<li>
		<a href="{@src}" title="{@alt}" height="{@height}" width="{@width}">
			<img height="105" src="{preview/@src}" alt="{@alt}" />
		</a>
	</li>
</xsl:template>
</xsl:stylesheet>