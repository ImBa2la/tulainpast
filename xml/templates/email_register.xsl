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

<!-- Шаблон письма регистрации -->
<!-- XML
<email domain="tulainpast.ru" name="Тула">
	<field name="name" label="Имя">test</field>
	<field name="email" label="Адрес электронной почты">kirill@forumedia.ru</field>
	<field name="login" label="Логин">qwertyqwe</field>
</email>-->
<xsl:template match="/">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			body,table{font-size:14px;color:#545557;font-family:Georgia;}
			a img{border:0;}
			p {margin-bottom:10px !important; line-height: 18px !important;}
			a {color:#e43b07;}
			a:hover {text-decoration:none;}
			h1{
				font-size:32px;
				color:#680606;
				font-weight: normal;
				margin-bottom:7px;
			}
			h2{
				color:#31261c;
				font-size:28px;
				font-weight: normal;
				margin:0 !important;
				padding:0 0 10px !important;
			}
			h3{
				color:#880408;
				font-size:20px;
				font-weight: normal;
			}
			table{
				border-spacing: 0;
				border-collapse: collapse;
				margin-bottom: 15px;
			}
			table tr th,
			table tr td{
				padding:5px 10px;
				color:#726b65;
			}
			table tr th{background:#ededed; text-align:right;}
			table td{color:#726b65;}
			table.info tr th{text-align:right !important;}
			table.order{width:610px;margin:0;}
			table.order tr th,
			table.order tr.footer td{
				padding:4px 0;
			}
			table tr.odd td{background:#ededed;}
			table tr th,
			table tr.odd td{
				background:#ededed !important;
			}
		</style>
	</head>
	<body>
		<h1>Регистрация на сайте &#171;<xsl:value-of select="/email/@name" />&#187;</h1>
		<p>Здравствуйте,&#160;<xsl:value-of select="//field[@name='name']/text()" />&#160;<xsl:value-of select="//field[@name='first-name']/text()" />! Ваш электронный почтовый ящик указали при регистрации на сайте.</p>
		<p>Если это были вы, необходимо подтвердить регистрацию.<br />Для подтверждения перейдите по этой ссылке:</p>
		<p><a href="http://{/email/@domain}/registration/?active={/email/@hash}">http://<xsl:value-of select="/email/@domain" />/registration/?active=<xsl:value-of select="/email/@hash" /></a></p>
		<h2>Регистрационные данные</h2>
		<table сlass="info">
			<tr>
				<th>Логин</th>
				<td><xsl:value-of select="//field[@name='loginR']/text()"/></td>
			</tr>
			<tr>
				<th>Пароль</th>
				<td><xsl:value-of select="//field[@name='passwordR']/text()"/></td>
			</tr>
		</table>
		<p>Благодарим за регистрацию!</p>
		<hr />
		<p>Письмо отправленно с сайта <a href="http://{/email/@domain}/"><xsl:value-of select="/email/@name" /></a> автоматически и не тебует ответа.</p>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
