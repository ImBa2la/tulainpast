<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="fieldset[@class='price']" priority="1">
	<div style="clear:left;"><xsl:apply-templates select="field" mode="price"/></div>
</xsl:template>

<xsl:template match="field" mode="price">
	<div class="field" style="float:left; margin-right:10px;">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="{@type}" name="{@name}" id="{@name}" maxlength="255" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>

</xsl:stylesheet>