<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/page/section">
	<section class="title">
		<xsl:call-template name="crumbs" />
		<h1><xsl:value-of select="$_sec/@title"/></h1>
	</section>
	<article class="content" id="content">
		<xsl:apply-templates/>
		<span class="top"></span><span class="bottom"></span>
	</article>
</xsl:template>
</xsl:stylesheet>