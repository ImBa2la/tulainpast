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
<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
	var input=todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">'),
		testid=function(){
			if(this.value.match(/^[a-z]{1}[a-z0-9_-]{2,50}$/i)){
				this._chars = true;
				todo.ajax('../classes/ajax.php',function(o){return function(text,xml){
					console.log(text);
					console.log(xml);
					o._new_id=text=='1';
					o.style.border='1px solid '+(o._new_id?'green':'red');
				};}(this),{
					'section':'</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">',
					'md':'</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text disable-output-escaping="yes">',
					'action':'apSubsections',
					'isset':this.value
				});
			}else{
				this._chars=false;
				this.style.border='1px solid red';
			}
		};
	if(input){
		testid.call(input);
		if(input.addEventListener){
			input.addEventListener('change',testid,false);
			input.addEventListener('keyup',testid,false);
		}else{
			input.attachEvent('onchange',testid);
			input.attachEvent('onkeyup',testid);
		};
	}
});
</xsl:text>
</script>
</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'sectionId')]" mode="fieldcheck">if(!this['<xsl:value-of select="@name"/>'].value.match(/^[a-z]{1}[a-z0-9_-]{2,50}$/i)){alert('Поле "<xsl:value-of select="@label"/>" должно содержать не менее трех латинских символов\n в нижнем регистре, без пробелов и не должно совпадать с сылками других разделов');return false;};</xsl:template>

</xsl:stylesheet>