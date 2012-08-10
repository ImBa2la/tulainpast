<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- навигация по страницам -->
<xsl:template name="page_navigator">
	<xsl:param name="numpages"/>	<!-- общее количество страниц -->
	<xsl:param name="page"/>		<!-- текущая страница -->
	<xsl:param name="url"/>			<!-- url родительского раздела -->
	<xsl:param name="pageparam"/>	<!-- имя параметра пагинации -->
	<xsl:param name="anchor"/>		<!-- якорь (опционально) -->
	<xsl:choose>
		<xsl:when test="$numpages &gt; 1">
		<div class="pagin-wrap">
			<div class="center">
				<ul class="pagination">
					<li class="baf p-first"><a href="{$url}">
						<xsl:if test="$page = 1"><xsl:attribute name="href"><xsl:value-of select="$url"/>#</xsl:attribute></xsl:if>
						Первая
					</a></li>
					<li class="baf p-prev"><a href="{$url}{$pageparam}{$page - 1}/">
						<xsl:if test="$page = 1"><xsl:attribute name="href"><xsl:value-of select="$url"/>#</xsl:attribute></xsl:if>
						<xsl:if test="($page - 1) = 1"><xsl:attribute name="href"><xsl:value-of select="$url"/></xsl:attribute></xsl:if>
						Предыдущая
					</a></li>
						<xsl:choose>
							<xsl:when test="$page - 3 &gt; 2">
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="1"/>
									<xsl:with-param name="numpages" select="1"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
								<li class="dot">...</li>
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="$page - 3"/>
									<xsl:with-param name="numpages" select="$page"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="1"/>
									<xsl:with-param name="numpages" select="$page"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:choose>
							<xsl:when test="$page + 3 &lt; $numpages - 1">
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="$page + 1"/>
									<xsl:with-param name="numpages" select="$page + 3"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
								<li class="dot">...</li>
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="$numpages"/>
									<xsl:with-param name="numpages" select="$numpages"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
							</xsl:when>
							<xsl:when test="$page &lt; $numpages">
								<xsl:call-template name="pages">
									<xsl:with-param name="i" select="$page + 1"/>
									<xsl:with-param name="numpages" select="$numpages"/>
									<xsl:with-param name="selected" select="$page"/>
									<xsl:with-param name="url" select="$url"/>
									<xsl:with-param name="pageparam" select="$pageparam"/>
									<xsl:with-param name="anchor" select="$anchor"/>
								</xsl:call-template>
							</xsl:when>
						</xsl:choose>
					<li class="baf  p-next"><a>
						<xsl:attribute name="href">
							<xsl:value-of select="$url"/>
							<xsl:value-of select="$pageparam"/>
							<xsl:choose>
								<xsl:when test="$page = $numpages">
									<xsl:value-of select="$page"/><xsl:text>/#</xsl:text>
									<xsl:if test="$anchor"><xsl:value-of select="$anchor"/></xsl:if>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$page + 1"/><xsl:text>/</xsl:text>
									<xsl:if test="$anchor">#<xsl:value-of select="$anchor"/></xsl:if>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>						
						Следующая
					</a></li>
					<li class="baf p-last"><a>
						<xsl:attribute name="href">
							<xsl:value-of select="$url"/>
							<xsl:value-of select="$pageparam"/>
							<xsl:value-of select="$numpages"/>
							<xsl:text>/</xsl:text>
							<xsl:if test="$page = $numpages"><xsl:text>#</xsl:text></xsl:if>
						</xsl:attribute>
						Последняя
					</a></li>
				</ul>
			</div>
		</div>
		</xsl:when>
	</xsl:choose>
</xsl:template>
<xsl:template name="pages">
	<xsl:param name="i"/>
	<xsl:param name="numpages"/>
	<xsl:param name="selected"/>
	<xsl:param name="url"/>
	<xsl:param name="pageparam"/>
	<xsl:param name="anchor"/>
	<xsl:if test="$i &lt;= $numpages">
		<li><a>
			<xsl:attribute name="href">
				<xsl:value-of select="$url"/>
				<xsl:if test="$i &gt; 1"><xsl:value-of select="$pageparam"/><xsl:value-of select="$i"/>/</xsl:if>
				<xsl:if test="$anchor"><xsl:text>#</xsl:text><xsl:value-of select="$anchor"/></xsl:if>
			</xsl:attribute>
			<xsl:if test="$selected=$i">
				<xsl:attribute name="class">selected</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="$i" />
		</a></li>
		<xsl:call-template name="pages">
			<xsl:with-param name="i" select="$i + 1"/>
			<xsl:with-param name="numpages" select="$numpages"/>
			<xsl:with-param name="selected" select="$selected"/>
			<xsl:with-param name="url" select="$url"/>
			<xsl:with-param name="pageparam" select="$pageparam"/>
			<xsl:with-param name="anchor" select="$anchor"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>
</xsl:stylesheet>