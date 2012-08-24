<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="order">
	<h1>Заказ № <xsl:value-of select="@id"/> от <xsl:value-of select="@date"/></h1>
	<dl>
		<dt>Заказчик:</dt>
		<dd>
			<xsl:choose>
				<xsl:when test="@uid"><a href="?id=register&amp;md=m2&amp;row={@uid}&amp;action=edit&amp;page=1"><xsl:value-of select="@user"/></a></xsl:when>
				<xsl:otherwise><xsl:value-of select="@user"/></xsl:otherwise>
			</xsl:choose>
		</dd>
	</dl>
	<table class="rows">
		<tr>
			<th>№</th>
			<th>Название</th>
			<th class="cntr">Количество</th>
			<th class="num">Цена руб.</th>
			<th class="num">Сумма руб.</th>
		</tr>
		<xsl:apply-templates select="beer"/>
		<tr>
			<td colspan="4" style="text-align:right;"><b>Итого:</b></td>
			<td class="num"><b><xsl:value-of select="@sum"/></b></td>
		</tr>
	</table>
	<input type="button" value="Назад" onclick="window.location='?id={$_sec/@id}&amp;md={/page/section/@module}'"/>
</xsl:template>

<xsl:template match="order/beer">
	<tr>
		<td><xsl:value-of select="count(preceding-sibling::beer)+1"/></td>
		<td><xsl:value-of select="@title"/></td>
		<td class="cntr"><xsl:value-of select="@quantity"/></td>
		<td class="num"><xsl:value-of select="@price"/></td>
		<td class="num"><xsl:value-of select="@sum"/></td>
	</tr>
</xsl:template>

</xsl:stylesheet>