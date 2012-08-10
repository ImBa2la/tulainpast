<?xml version="1.0" encoding="utf-8"?><!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="/page/section/form">
	<xsl:variable name="formId">
		<xsl:choose>
			<xsl:when test="@id"><xsl:value-of select="@id"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="generate-id()"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="enctype">
		<xsl:choose>
			<xsl:when test="@enctype"><xsl:value-of select="@enctype"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="multipart/form-data"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="method">
		<xsl:choose>
			<xsl:when test="@method"><xsl:value-of select="@method"/></xsl:when>
			<xsl:otherwise>post</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="class">
		<xsl:choose>
			<xsl:when test="@class"><xsl:value-of select="@class"/></xsl:when>
			<xsl:otherwise>default</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<a id="{$formId}_anchor"><xsl:comment/></a>
	<xsl:if test="@title">
		<h3><xsl:value-of select="@title"/></h3>
	</xsl:if>
	<form id="{$formId}" class="{$class}" action="{@action}#{$formId}_anchor" method="{$method}" enctype="{$enctype}">
		<div class="message"><xsl:apply-templates select="message"/></div>
		<input type="hidden" name="id" value="{$_sec/@id}"/>
		<input type="hidden" name="lang" value="{/page/@lang}"/>
		<xsl:apply-templates select="param"/>
		<dl><xsl:apply-templates select="title | field | button | group"/></dl>
		<xsl:apply-templates select="buttongroup" />
	</form>
	<xsl:if test="@autocheck">
		<script type="text/javascript">
			<xsl:text>$(document).ready(function(){try{document.getElementById('</xsl:text>
			<xsl:value-of select="$formId"/>
			<xsl:text>').onsubmit=function(){var p=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;</xsl:text>
			<xsl:apply-templates select="field[@check]" mode="fieldcheck"/>
			<xsl:text>return true;}}catch(er){alert(er.message);}});</xsl:text>
		</script>
	</xsl:if>
</xsl:template>

<xsl:template match="/page/section/form/group">
	<tr>
		<th colspan="2" class="group_header"><xsl:value-of select="@title"/>&#160;</th>
	</tr>
	<xsl:apply-templates select="title | field | button | buttongroup"/>
	<tr>
		<td colspan="2" class="group_footer">&#160;</td>
	</tr>
</xsl:template>

<xsl:template match="/page/section/form//field[contains(@check,'empty-or-')]" mode="fieldcheck" priority="1">
	<xsl:variable name="or_field_name" select="substring-after(@check,'empty-or-')"/>
	<xsl:variable name="or_field" select="ancestor::form//field[@name=$or_field_name]"/>
	if(!this.<xsl:value-of select="@name"/>.value<xsl:text disable-output-escaping="yes">&amp;&amp;</xsl:text>!this.<xsl:value-of select="$or_field/@name"/>.value){
		alert('<xsl:call-template name="_ln_empty-or_field"><xsl:with-param name="or_field" select="$or_field"/></xsl:call-template>');
		return false;
	};
</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'empty')]" mode="fieldcheck">
	if(!this.<xsl:value-of select="@name"/>.value){alert('<xsl:call-template name="_ln_empty_field"/>');return false;};
</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'email')]" mode="fieldcheck">
	if(<xsl:if test="not(contains(@check,'empty'))">this.<xsl:value-of select="@name"/>.value <xsl:text disable-output-escaping="yes">&amp;&amp;</xsl:text> </xsl:if>!p.test(this.<xsl:value-of select="@name"/>.value)){alert('<xsl:call-template name="_ln_wrong_email"/>');return false;};
</xsl:template>

<xsl:template name="required">
	<xsl:if test="not(contains(@check,'empty-or-')) and contains(@check,'empty')"><span class="required">*</span></xsl:if>
</xsl:template>

<xsl:template match="/page/section/form/message">
	<span class="warning"><xsl:value-of select="text()" disable-output-escaping="yes"/></span><br />
</xsl:template>

<xsl:template match="/page/section/form/param">
	<input type="hidden" name="{@name}" value="{@value}"/>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='text' or @type='email' or @type='password']">
		<dt><label for="{@name}"><xsl:value-of select="@label"/><xsl:call-template name="required"/></label></dt>
		<dd><input type="{@type}" name="{@name}" id="{@name}" value="{text()}" class="{@class}" maxlength="255" /></dd>
</xsl:template>

<!-- CHECKBOX -->
<xsl:template match="/page/section/form//field[@type='checkbox']">
	<input type="{@type}" name="{@name}" id="{@name}">
		<xsl:if test="@checked"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
	</input>
	<label for="{@name}"><xsl:value-of select="@label" disable-output-escaping="yes" /><xsl:call-template name="required"/></label>
</xsl:template>

<!-- RADIO -->
<xsl:template match="/page/section/form//field[@type='radio']">
	<dt><label><xsl:value-of select="@label"/><xsl:call-template name="required"/></label></dt>
	<dd><xsl:apply-templates/></dd>
	<xsl:call-template name="attach"/>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='radio']/option">
	<input type="radio" name="{parent::field/@name}" id="{@id}">
		<xsl:attribute name="value"><xsl:choose>
			<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="text()"/></xsl:otherwise>
		</xsl:choose></xsl:attribute>
		<xsl:if test="@checked">
			<xsl:attribute name="checked">checked</xsl:attribute>
		</xsl:if>
	</input>
	<label for="{@id}"><xsl:value-of select="text()"/></label><xsl:call-template name="nbsp"/>
</xsl:template>

<!-- SELECT -->
<xsl:template match="/page/section/form//field[@type='select']">
	<dt><label for="{@name}"><xsl:value-of select="@label"/><xsl:call-template name="required"/></label></dt>
	<dd><select name="{@name}" id="{@name}" size="1">
		<xsl:apply-templates/>
	</select></dd>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='select']/option">
	<option>
		<xsl:attribute name="value">
			<xsl:choose>
				<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="text()"/></xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:if test="@value = parent::field/@value">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		<xsl:value-of select="text()"/>
	</option>
</xsl:template>

<!-- TEXTAERA -->
<xsl:template match="/page/section/form//field[@type='textarea']">
	<dt><label for="{@name}"><xsl:value-of select="@label"/><xsl:call-template name="required"/></label></dt>
	<dd><textarea name="{@name}" id="{@name}">
		<xsl:attribute name="cols"><xsl:choose>
			<xsl:when test="@cols"><xsl:value-of select="@cols"/></xsl:when>
			<xsl:otherwise>40</xsl:otherwise>
		</xsl:choose></xsl:attribute>
		<xsl:attribute name="rows"><xsl:choose>
			<xsl:when test="@rows"><xsl:value-of select="@rows"/></xsl:when>
			<xsl:otherwise>3</xsl:otherwise>
		</xsl:choose></xsl:attribute>
		<xsl:choose>
		<xsl:when test="string-length(text())"><xsl:value-of select="text()"/></xsl:when>
		<xsl:otherwise><xsl:comment/></xsl:otherwise>
	</xsl:choose></textarea></dd>
	<xsl:call-template name="attach"/>
</xsl:template>

<!-- CAPCHA -->
<xsl:template match="/page/section/form//field[@type='captcha']">
	<dt><label class="{@class}" for="{@name}">
		<xsl:value-of select="@label" disable-output-escaping="yes"/><xsl:call-template name="required"/><br/>
	</label></dt>
	<dd><img width="200" height="25" alt="капча" class="capcha"><xsl:attribute name="src">
	<xsl:choose>
			<xsl:when test="@src"><xsl:value-of select="@src"/></xsl:when>
			<xsl:otherwise>userfiles/cptch.jpg</xsl:otherwise>
		</xsl:choose>
		<xsl:text>?x=</xsl:text>
		<xsl:value-of select="generate-id()"/>
	</xsl:attribute></img>
	<input type="text" name="{@name}" id="{@name}" maxlength="255" value="{@value}" /></dd>
</xsl:template>

<xsl:template match="/page/section/form/buttongroup">
	<div class="tooltip">(* – поля обязательные для заполнения)</div>
	<div class="buttons">
		<xsl:apply-templates/>
	</div>
</xsl:template>
<xsl:template match="/page/section/form/buttongroup/button[@type='submit']">
	<input type="{@type}" value="{@value}" name="{@name}">
		<xsl:if test="string-length(@class)">
			<xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="@linkText and @linkUrl">
		<a href="{@linkUrl}"><xsl:value-of select="@linkText" /></a>
	</xsl:if>
</xsl:template>

<xsl:template name="attach">
	<xsl:if test="@attach">
		<div class="attach"><input type="file" name="{@name}_attach" id="{@name}_attach" /></div>
	</xsl:if>
</xsl:template>

<xsl:template name="_ln_empty_field">Поле "<xsl:value-of select="@label"/>" должно быть заполнено</xsl:template>
<xsl:template name="_ln_empty-or_field"><xsl:param name="or_field"/>Поле "<xsl:value-of select="@label"/>" или "<xsl:value-of select="$or_field/@label"/>"  должно быть заполнено</xsl:template>
<xsl:template name="_ln_wrong_email">Адрес электронной почты в поле "<xsl:value-of select="@label"/>"\nвведен неверно</xsl:template>

</xsl:stylesheet>
