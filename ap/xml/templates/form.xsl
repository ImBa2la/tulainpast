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
	<xsl:if test="@title">
		<h1><xsl:value-of select="@title"/></h1>
	</xsl:if>
	<xsl:apply-templates select="message"/>
	<script type="text/javascript" src="js/form.js"></script>
	<form id="{$formId}" class="default" action="{@action}#{$formId}" enctype="multipart/form-data">
		<xsl:attribute name="method">
			<xsl:choose>
				<xsl:when test="@method"><xsl:value-of select="@method"/></xsl:when>
				<xsl:otherwise>post</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<input type="hidden" name="id" value="{$_sec/@id}"/>
		<input type="hidden" name="md" value="{/page/section/@module}"/>
		<input type="hidden" name="lang" value="{/page/@lang}"/>
		<input type="hidden" name="action" value="{$formId}"/>
		<xsl:apply-templates select=".//param"/>
		<xsl:apply-templates select="title | field | button | fieldset | buttonset"/>
	</form>
	<xsl:if test="@autocheck">
		<script type="text/javascript">
			<xsl:text>todo.onload(function(){try{todo.get('</xsl:text>
			<xsl:value-of select="$formId"/>
			<xsl:text>').onsubmit=function(){var p=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;</xsl:text>
			<xsl:text>if(this.action.value!='cancel'){</xsl:text>
			<xsl:apply-templates select=".//field[@check]" mode="fieldcheck"/>
			<xsl:text>};return true;}}catch(er){alert(er.message);}});</xsl:text>
		</script>
	</xsl:if>
</xsl:template>

<xsl:template match="/page/section/form//field[contains(@check,'empty')]" mode="fieldcheck">if(!this['<xsl:value-of select="@name"/>'].value){alert('Поле "<xsl:value-of select="@label"/>" должно быть заполнено.');return false;};</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'email')]" mode="fieldcheck">if(<xsl:if test="not(contains(@check,'empty'))">this['<xsl:value-of select="@name"/>'].value <xsl:text disable-output-escaping="yes">&amp;&amp;</xsl:text> </xsl:if>!p.test(this['<xsl:value-of select="@name"/>'].value)){alert('В поле  "<xsl:value-of select="@label"/>" введен неверный адрес электронной почты');return false;};</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'num')]" mode="fieldcheck">if(<xsl:if test="not(contains(@check,'empty'))">this['<xsl:value-of select="@name"/>'].value <xsl:text disable-output-escaping="yes">&amp;&amp;</xsl:text> </xsl:if>isNaN(parseFloat(this['<xsl:value-of select="@name"/>'].value.replace(',','.')))){alert('В поле "<xsl:value-of select="@label"/>" можно вводить только числа');return false;};</xsl:template>

<xsl:template match="/page/section/form//fieldset">
	<fieldset>
		<xsl:if test="@title">
			<legend><xsl:value-of select="@title"/></legend>
		</xsl:if>
		<xsl:apply-templates select="field | button | buttonset | fieldset"/>
	</fieldset>
</xsl:template>

<xsl:template match="/page/section/form//param">
	<input type="hidden" name="{@name}" value="{@value}"/>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='text' or @type='email']">
	<div class="field text">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="{@type}" name="{@name}" id="{@name}" maxlength="255" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='password']">
	<div class="field text">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="{@type}" name="{@name}" id="{@name}" maxlength="255">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='date']">
	<div class="field text">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="text" name="{@name}" id="{@name}" size="10" maxlength="10" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='image']">
	<xsl:variable name="fieldName" select="@name"/>
	<div class="field file">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="button" class="add" value="Добавить изображение" id="editimage{$fieldName}"/>
		<textarea id="textarea{$fieldName}" style="display:none;"><xsl:comment/></textarea>
		<input type="hidden" id="{$fieldName}_sort_order" name="{$fieldName}_sort_order"/>
	</div>
	<div class="fieldset gallery" id="fieldset_{$fieldName}" style="display:none;">
		<h4 class="legend">Управление фото</h4>
	</div>
	<script type="text/javascript">
<xsl:text disable-output-escaping="yes">todo.onload(function(){
editor('textarea</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>');
var input=todo.get('editimage</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>');
input.onclick=function(){
	var fs=todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text disable-output-escaping="yes">');
	tinyMCE.activeEditor.windowManager.params.imageLimit = 10;
	tinyMCE.activeEditor.windowManager.open({
		file:'uploader.php?opener=tinymce&amp;type=image',
		title:'Active Page File Manager',
		width:700,height:500,
		resizable:"yes",inline:true,close_previous:"no",popup_css:false
	},{limit:</xsl:text><xsl:choose>
		<xsl:when test="@max"><xsl:value-of select="@max"/></xsl:when>
		<xsl:otherwise>30</xsl:otherwise>
	</xsl:choose><xsl:text disable-output-escaping="yes">,
	list:todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text disable-output-escaping="yes">').getElementsByTagName('figure'),
	callback:function(url){todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>')._addImage(url);}});
};
var fs=todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text disable-output-escaping="yes">');
</xsl:text><xsl:if test="@hasTitle">fs._hasTitle=true;</xsl:if><xsl:text disable-output-escaping="yes">
initImageFieldset(fs,'</xsl:text><xsl:value-of select="$fieldName"/><xsl:text disable-output-escaping="yes">',</xsl:text><xsl:choose>
		<xsl:when test="@max"><xsl:value-of select="@max"/></xsl:when>
		<xsl:otherwise>30</xsl:otherwise>
	</xsl:choose><xsl:text disable-output-escaping="yes">);
</xsl:text><xsl:apply-templates select="param[@value]" mode="js"/><xsl:text>
});</xsl:text>
	</script>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='image']/param[@value]" mode="js">fs._addImage('<xsl:value-of select="@value"/>','<xsl:value-of select="@name"/>','<xsl:value-of select="@title"/>');</xsl:template>

<xsl:template match="/page/section/form//field[@type='file']">
	<xsl:variable name="fieldName" select="@name"/>
	<xsl:variable name="fieldId" select="translate(@name,'[]','__')"/>
	<div class="field file">
		<label class="legend" for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="button" class="add" value="Добавить файл" id="editfile{$fieldId}"/>
		<div id="fileinfo{$fieldId}" class="fileinfo" style="display:none;"></div>
		<textarea id="textarea{$fieldId}" style="display:none;"><xsl:comment/></textarea>
		<xsl:apply-templates select="desc"/>
	</div>
	<script type="text/javascript">
<xsl:text disable-output-escaping="yes">todo.onload(function(){
editor('textarea</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>');
var input=todo.get('editfile</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>');
function </xsl:text><xsl:value-of select="$fieldId"/><xsl:text>SetFileInfo(xml){
	var get=function(name){try{return xml.getElementsByTagName(name)[0].firstChild.data;}catch(er){}},
		inp=todo.get('editfile</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>'),
		div=todo.get('fileinfo</xsl:text><xsl:value-of select="$fieldId"/><xsl:text disable-output-escaping="yes">');
	inp.style.display=xml?'none':'';
	div.style.display=xml?'':'none';
	div.className=='fileinfo'+(xml?' '+get('extension'):'');
	div.innerHTML=xml?'&lt;a href="'+get('path')+'"&gt;'+get('basename')+'&lt;/a&gt; '+get('size')+'&lt;input type="button" class="delete"&gt;'
		+'&lt;input type="hidden" name="</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>"&gt;':'';
	div.getElementsByTagName('input')[1].value=get('path');
	div.getElementsByTagName('input')[0].onclick=function(){
		if(confirm('Подтвердите удаление'))</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>SetFileInfo();
	};
};
function </xsl:text><xsl:value-of select="$fieldId"/><xsl:text>RequestFileInfo(url){
	todo.ajax(window.location.pathname+window.location.search+'&amp;action=fileinfo&amp;path='+encodeURIComponent(url),function(text,xml){
		if(xml)</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>SetFileInfo(xml);
	});
};
input.onclick=function(){
	tinyMCE.activeEditor.windowManager.open({
		file:'uploader.php?opener=tinymce&amp;type=file',
		title:'Active Page File Manager',
		width:700,height:500,
		resizable:"yes",inline:true,close_previous:"no",popup_css:false
	},{callback:</xsl:text><xsl:value-of select="$fieldId"/><xsl:text>RequestFileInfo});
};
</xsl:text><xsl:if test="string-length(text())"><xsl:value-of select="$fieldId"/>RequestFileInfo('<xsl:value-of select="text()"/>');</xsl:if><xsl:text>
});</xsl:text>
	</script>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='checkbox']">
	<div class="field checkbox">
		<input name="{@name}" id="{@name}" type="checkbox">
			<xsl:attribute name="value">
				<xsl:choose>
					<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
					<xsl:otherwise>1</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:if test="@checked"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
		</input>
		<xsl:text>&#160;</xsl:text>
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='checkboxset' or @type='radio']">
	<div>
		<xsl:attribute name="class">
			<xsl:text>field checkbox</xsl:text>
			<xsl:if test="@inline"> inline</xsl:if>
		</xsl:attribute>
		<xsl:if test="@label">
			<h4><xsl:call-template name="required"/><xsl:value-of select="@label"/></h4>
		</xsl:if>
		<ul>
			<xsl:apply-templates select="option"/>
		</ul>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='checkboxset']/option">
	<xsl:variable name="fid">
		<xsl:value-of select="parent::field/@name"/>
		<xsl:value-of select="position()"/>
	</xsl:variable>
	<li><input type="checkbox" name="{parent::field/@name}[]" id="{$fid}">
			<xsl:attribute name="value"><xsl:choose>
				<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="text()"/></xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:if test="@checked">
				<xsl:attribute name="checked">checked</xsl:attribute>
			</xsl:if>
		</input>&#160;<label for="{$fid}"><xsl:value-of select="text()"/></label></li>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='radio']/option">
	<xsl:variable name="fid">
		<xsl:value-of select="parent::field/@name"/>
		<xsl:value-of select="position()"/>
	</xsl:variable>
	<li><input type="radio" name="{parent::field/@name}[]" id="{$fid}">
			<xsl:attribute name="value"><xsl:choose>
				<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="text()"/></xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:if test="@checked">
				<xsl:attribute name="checked">checked</xsl:attribute>
			</xsl:if>
		</input>&#160;<label for="{$fid}"><xsl:value-of select="text()"/></label></li>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='textarea']">
	<div class="field textarea">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<textarea name="{@name}" id="{@name}">
			<xsl:attribute name="cols"><xsl:choose>
				<xsl:when test="@cols"><xsl:value-of select="@cols"/></xsl:when>
				<xsl:otherwise>40</xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:attribute name="rows"><xsl:choose>
				<xsl:when test="@rows"><xsl:value-of select="@rows"/></xsl:when>
				<xsl:otherwise>3</xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:if test="string-length(text())"><xsl:value-of select="text()"/></xsl:if>
		</textarea>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='html']">
	<div class="field textarea">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<textarea name="{@name}" id="{@name}" class="html">
			<xsl:attribute name="cols"><xsl:choose>
				<xsl:when test="@cols"><xsl:value-of select="@cols"/></xsl:when>
				<xsl:otherwise>40</xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:attribute name="rows"><xsl:choose>
				<xsl:when test="@rows"><xsl:value-of select="@rows"/></xsl:when>
				<xsl:otherwise>3</xsl:otherwise>
			</xsl:choose></xsl:attribute>
			<xsl:if test="string-length(text())"><xsl:value-of select="text()"/></xsl:if>
		</textarea>
		<script type="text/javascript">
			<xsl:text>editor("</xsl:text>
			<xsl:value-of select="@name"/>
			<xsl:text>",{</xsl:text>
			<xsl:if test="@height">'height':'<xsl:value-of select="@height"/>','theme_advanced_resizing_min_height':'<xsl:value-of select="@height"/>'</xsl:if>
			<xsl:text>})</xsl:text>
		</script>
	</div>
</xsl:template>

<xsl:template match="/page/section/form//field[@type='select']">
	<div class="field select">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/>:</label>
		<select name="{@name}" id="{@name}">
			<xsl:if test="@multiple">
				<xsl:attribute name="size">3</xsl:attribute>
				<xsl:attribute name="multiple"></xsl:attribute>
				<xsl:attribute name="style">height:auto;</xsl:attribute>
			</xsl:if>
			<xsl:apply-templates/>
		</select>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='select']/option">
	<option value="{@value}">
		<xsl:if test="@value = parent::field/@value">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		<xsl:value-of select="text()"/>
	</option>
</xsl:template>

<xsl:template match="/page/section/form/buttonset">
	<div class="buttons">
		<xsl:apply-templates/>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//button">
	<input value="{@value}">
		<xsl:attribute name="type">
			<xsl:choose>
				<xsl:when test="@type"><xsl:value-of select="@type"/></xsl:when>
				<xsl:otherwise>button</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:if test="@class">
			<xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="@type='submit' and @action">
				<xsl:attribute name="onclick">
					<xsl:text>this.form.action.value='</xsl:text><xsl:value-of select="@action"/><xsl:text>';</xsl:text>
					<xsl:if test="@onclick"><xsl:value-of select="@onclick"/></xsl:if>
				</xsl:attribute>
			</xsl:when>
			<xsl:when test="@onclick">
				<xsl:attribute name="onclick"><xsl:value-of select="@onclick"/></xsl:attribute>
			</xsl:when>
		</xsl:choose>
	</input>
</xsl:template>

<xsl:template match="/page/section/form//field/desc">
	<div class="desc"><xsl:value-of select="text()"/></div>
</xsl:template>

<xsl:template name="attach">
	<xsl:if test="@attach">
		<div class="attach"><input type="file" name="{@name}_attach" id="{@name}_attach" /></div>
	</xsl:if>
</xsl:template>

<xsl:template name="required">
	<xsl:if test="not(contains(@check,'empty-or-')) and contains(@check,'empty')"><span class="red">*</span></xsl:if>
</xsl:template>

<xsl:template match="/page/section/form/message">
	<p class="message"><xsl:value-of select="text()" disable-output-escaping="yes"/></p>
</xsl:template>

</xsl:stylesheet>