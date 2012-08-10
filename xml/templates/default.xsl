<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY laquo  "&#171;">
	<!ENTITY raquo  "&#187;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:include href="elements.xsl"/>
<xsl:output media-type="text/html" method="html" omit-xml-declaration="yes" indent="yes" encoding="utf-8"/>

<!-- Шаблон страницы -->
<xsl:template match="/">
<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE HTML&gt;</xsl:text>
<html>
<head>
<base href="{$_base_url}"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="shortcut icon" href="favicon.ico"/>
<xsl:call-template name="head"/>
<link href="css/default.css" rel="stylesheet" type="text/css" />
	
<link href='http://fonts.googleapis.com/css?family=Open+Sans:600&amp;subset=latin,cyrillic' rel='stylesheet' type='text/css' />
<link href="js/gallery/gallery.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/todo.js"></script>
<script type="text/javascript" src="js/subnews.login.js"></script>
<xsl:text disable-output-escaping="yes">&lt;!--[if lte IE 8]&gt;</xsl:text>
<script src="js/html5.js"></script>
<script type="text/javascript" src="css/PIE/PIE.js"></script>
<script type="text/javascript">$(function(){if(window.PIE){$('section.gallery a, form input, form textarea').each(function(){PIE.attach(this);});}});</script>
<xsl:text disable-output-escaping="yes">&lt;![endif]--&gt;</xsl:text>
</head>
<body>
<div id="wrapper">
	<span class="shadow-line"><xsl:comment /></span>
	<header>
		<a href="/" class="logo"><img src="images/__logo.jpg" alt="logo" height="124" width="112" /></a>
		<xsl:apply-templates select="/page/structure" mode="topmenu"/>
		<div class="forms">
			<div class="reg"><a href="registration/">Регистрация</a></div>
			<div class="log">
				<xsl:choose>
					<xsl:when test="/page/user/@login"><a href="{$_secId}/?logout=1">Вы зашли как <b><xsl:value-of select="/page/user/@login" /></b> (выйти)</a></xsl:when>
					<xsl:otherwise><a class="login">Вход</a></xsl:otherwise>
				</xsl:choose>
			</div>
			<div class="search">
				<form>
					<input value="поиск..." type="text" onfocus="if(!this._placeholder)this._placeholder=this.value;if(this.value==this._placeholder)this.value='';" onblur="if(!this.value)this.value=this._placeholder;"/>
					<input type="button" value=""/><br/>
					<a href="search.html">расширенный поиск</a>
				</form>
			</div>
		</div>
	</header>
	<div class="conteiner">
		<div class="side-bar">
			<span class="line-white"><xsl:comment /></span>
			<xsl:apply-templates select="/page/structure" mode="sidemenu"/>
			<div class="social">
				<span class="line-white"><xsl:comment /></span>
				<a target="_blank" href="http://www.facebook.com/pages/Тула-ушедшего-века/383384361727689" class="face"><xsl:comment /></a>
				<a target="_blank" href="https://twitter.com/miketentser" class="twit"><xsl:comment /></a>
				<a target="_blank" href="http://vk.com/club39195108" class="vk"><xsl:comment /></a>
			</div>
			<xsl:apply-templates select="/page/gallery | /page/story" />
			<div class="advertising">
				<p>2009-2012 &#169; Тенцер М.Б.</p>
				<p>Генеральный спонсор сайта Типография <a href="http://borus.ru/" target="_blank">"Борус"</a></p>
				<p>Разработка, техподдержка студия <a href="http://www.4whale.ru/" target="_blank">"Четвертый кит"</a></p>
			</div>
		</div>
		
		<div class="main">
			
			<xsl:choose>
				<xsl:when test="/page/user[@subscribe='1']"><a class="subnews" id="subnews" rel="0">Отписаться от новостей</a></xsl:when>
				<xsl:when test="/page/user[@subscribe='0']"><a class="subnews" id="subnews" rel="1">Подписка на новости</a></xsl:when>
				<xsl:otherwise><a class="subnews" id="subnews">Подписка на новости</a></xsl:otherwise>
			</xsl:choose>
			<xsl:apply-templates select="/page/section"/>
		</div><!-- end main block -->
		<footer><div class="inner">
				<span class="line-white"></span>
				<section class="copyright">Все материалы данного сайта охраняются в соответствии с законодательством РФ об авторских и смежных правах. Любое использование материалов сайта <a href="#">http://www.tulainpast.ru</a> без письменного разрешения редакции запрещается</section>
				<section class="developers">Дизайн Макарова И.И. © 2011 </section>
		</div></footer>
	</div><!-- end conteiner -->
</div>
<div class="subnews">
	<form>
		<h4>Подписка на новости</h4>
		<div class="mess"></div>
		<div class="form">
			<label>Ваше имя</label>
			<input type="text" name="name"/>
			<label>Ваш e-mail</label>
			<input type="text" name="email"/>
			<input type="button" value="Подписаться"/>
		</div>
	</form>
</div>
<div class="authoriz">
	<form action="{$_base_url}{$_secId}/" method="post" id="login-form">
		<h4>Форма входа</h4>
		<label for="login">Имя</label>
		<input type="text" name="login" id="login" value="" />
		<label for="password">Пароль</label>
		<input type="password" name="password" id="password" value="" />
		<input type="button" value="Войти"/>
	</form>
</div>
<div class="mod-window-bg"></div>
</body>	
</html>
</xsl:template>

<!-- gallery and story -->
<xsl:template match="/page/gallery">
	<section class="prod">
		<h1>Случайное фото из фотоархива сайта</h1>
		<a href="{@section}/row{@id}/" class="img">
			<img src=" {@prv}" alt="{@title}" width="190" />
		</a>
		<a href="{@section}/row{@id}/" class="title"><xsl:value-of select="@title" /></a>
	</section>
</xsl:template>
<xsl:template match="/page/story">
	<section class="prod">
		<a href="{@section}/row{@id}/">
			<xsl:value-of select="@title" />
		</a>
		<div><xsl:value-of select="@announce" /></div>
	</section>
</xsl:template>
</xsl:stylesheet>