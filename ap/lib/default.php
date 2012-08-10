<?
function param($name,$value = null){
	if($value) setParam($name,$value);
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}
function lang($mode,$default){
	$name = 'ap_lang_'.$mode;
	$ln = param('ln');
	if($ln = param('ln'));
	elseif(isset($_COOKIE[$name])) $ln = $_COOKIE[$name];
	else $ln = $default;
	if(!isset($_COOKIE[$name]) || $_COOKIE[$name]!=$ln) setcookie($name,$ln,time()+7776000);
	return $ln;
}
function setParam($name,$value,$method = 'GET',$add_slashes = false){
	$_REQUEST[$name] = $add_slashes ? addslashes($value) : $value;
	switch(strtoupper($method)){
		case 'GET': $_GET[$name] = $_REQUEST[$name]; break;
		case 'POST': $_POST[$name] = $_REQUEST[$name]; break;
	}
}
function vdump($v,$die = true){
	if($die){
		throw new Exception('<pre>'.print_r($v,true).'</pre>');
	}else{
		?><pre><? print_r($v) ?></pre><?
	}
	
}
function translit($str){
	$tr = array(
	"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
	"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
	"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH","Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
	"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
	"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
	"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
	"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"," "=>"_","\""=>"","'"=>"","/"=>"","\\"=>""
    );
    $str = strtr($str,$tr);
	$maxStrLen = 64;
	if(strlen($str)>$maxStrLen) return substr($str,0,$maxStrLen);
	return $str;
}
?>