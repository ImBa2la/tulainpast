<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet  [
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
<xsl:output media-type="text/html" method="html" omit-xml-declaration="yes" indent="yes" encoding="utf-8"/>
<xsl:decimal-format name="rur" decimal-separator="," grouping-separator="."/>

<!-- Шаблон письма -->
<xsl:template match="/">
<html>
<head>
	<title>Вопрос с сайта <xsl:value-of select="/email/@name" /></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<style type="text/css">
*{margin:0; padding:0;}
body,table, td, th{font-size:12px;color:#727f92;font-family:Arial;}
a img{border:0;}
p{padding:0;margin:0 0 15px;}
a{color:#919daf;}
a:hover {text-decoration:none;}
hr{margin:10px 0;}
h1{
font-size:18px;
text-transform:uppercase;
font-weight:normal;
padding:0;
margin:0 0 10px;
color:#919daf;
}
div.mess{
background:rgb(200,230,240);
padding:10px 10px 10px 25px;
margin:10px 0;
}

</style>
	<xsl:apply-templates />
</body>
</html>
</xsl:template>
<xsl:template match="/email">
	<h1>Сообщение формы обратной связи</h1>
	Ваш вопрос
	<div class='mess'><xsl:value-of select="./field[@name='message']/text()" disable-output-escaping="yes" /></div>
	был успешно отправлен и принят в обработку. В скором времени вам обязательно ответят по электронной почте или телефону, указанными вами при составлении запроса.

	<hr/>
	<p>Письмо отправленно с сайта <a href="http://{@domain}/"><xsl:value-of select="@name" /></a> автоматически и не требует ответа</p>
</xsl:template>

</xsl:stylesheet>