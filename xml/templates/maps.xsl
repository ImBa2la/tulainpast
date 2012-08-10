<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/page/section">
	<section class="title">
		<xsl:call-template name="crumbs" />
		<h1><xsl:value-of select="$_sec/@title"/></h1>
	</section>
	<article class="content">
		<xsl:apply-templates/>
		<xsl:if test="$_sec/@id = 'map'">
			<xsl:apply-templates select="/page/structure//sec[@id='map']" mode="gallery" />
		</xsl:if>
		<span class="top"></span><span class="bottom"></span>
	</article>
</xsl:template>
<xsl:template match="gallery">
	<div align="center">
		<object 
			classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" 
			codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" 
			width="600" 
			height="450"
			id="map">
					<param name="FlashVars"	value="zoomifyImagePath={substring-before(img[1]/@src,concat('/',$_sec/@id,'_'))}"/>
					<param name="menu"		value="false"/>
					<param name="wmode"		value="opaque"/>
					<param name="src"		value="ZoomifyViewer.swf"/>
					<embed
						FlashVars="zoomifyImagePath={substring-before(img[1]/@src,concat('/',$_sec/@id,'_'))}" 
						src="ZoomifyViewer.swf" 
						menu="false" 
						wmode="opaque"
						pluginspace="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"  
						width="600" 
						height="450"
						name="map"></embed>
		</object>
	  </div>
	<p style="font-size:9px; text-align-left;">Для более удобного просмотра можно управлять приближением с помощью клавиш ctr (уменьшить) и shift (увеличить) или двойным щелчком мыши на изображении.</p>
</xsl:template>
<xsl:template match="/page/structure//sec[sec]" mode="gallery">
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
								<xsl:text>userfiles/167x167.jpg</xsl:text>
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
</xsl:stylesheet>