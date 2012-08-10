<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="/page/section/form//field[@name='alias']" priority="1">
	<div class="field text">
		<label for="{@name}">*<xsl:value-of select="@label"/></label>
		<input type="{@type}" name="{@name}" id="{@name}" maxlength="63" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'sectionId')]" mode="fieldcheck">if(!this['<xsl:value-of select="@name"/>'].value.match(/^[a-z]{1}[a-z0-9_-]{2,50}$/i)){alert('Поле "<xsl:value-of select="@label"/>" должно содержать не менее трех латинских символов\n в нижнем регистре, без пробелов и не должно совпадать с сылками других разделов');return false;};</xsl:template>

</xsl:stylesheet>