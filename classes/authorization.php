<?
class authorization extends module{
function logout(){
	if(!session_id() && !headers_sent()) session_start();
	if(isset($_SESSION['login'])){
		unset($_SESSION['login']);
		if($e = authorization::getUserElement()){
		}
	}
}
static function login($login,$password = false){
	global $_out;
	if($xml = authorization::getUserXML($login,$password)){
		$_SESSION['login'] = $login;
		$_out->xmlInclude($xml);
		return authorization::getUserElement();
	}
	return false;
}
function run(){
	global $_out;
	if(!session_id() && !headers_sent()) session_start();
	if(param('logout')) $this->logout();
	if($backurl = param('backurl')){
		$_out->de()->setAttribute('backurl',$backurl);
	}
	$xml = null;
	if(($login = param('login'))
		&& ($pass = param('password'))
	){
		if((($basepath = pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME)) == '/') || ($basepath == '\\')) $basepath = '';
		if($xml = $this->getUserXML($login,$pass)){ 
			$_SESSION['login'] = $login;
			if(param('backurl')){
				header('Location: '.$backurl);
				die;
			}
		}
		else {
			$this->logout();
			header('Location: '.$basepath.'/auth/?auth=error'.($backurl ? '&backurl='.urlencode($backurl) : '&backurl='.urlencode($_SERVER["REQUEST_URI"])));
			die;
		}
	}elseif(isset($_SESSION['login'])){
		
		if($xml = $this->getUserXML($_SESSION['login']));
		else $this->logout();
	}
	if($xml) $_out->xmlInclude($xml);
}
static function getUserXML($login,$pass = false){
	$mysql = new mysql();
	//echo 'SELECT * FROM `'.$mysql->getTableName('users').'` WHERE `login`="'.addslashes($login).'" '.($backurl===false ? ' AND `active`=1' : null).''.($pass!==false ? ' AND `password`="'.md5($pass).'"' : null);
	if($login
		&& ($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName('users').'` WHERE `login`="'.addslashes($login).'" AND `active`=1 '.($pass!==false ? ' AND `password`="'.md5($pass).'"' : null)))
		&& ($r = mysql_fetch_assoc($rs))
	){
		$xml = new xml(null,'user',false);
		$xml->de()->setAttribute('id',$r['id']);
		$xml->de()->setAttribute('name',$r['name']);
		$xml->de()->setAttribute('surname',$r['surname']);
		$xml->de()->setAttribute('email',$r['email']);
		$xml->de()->setAttribute('phone',$r['phone']);
		$xml->de()->setAttribute('login',$r['login']);
		$xml->de()->setAttribute('subscribe',$r['subscribe']);
		return $xml;
	}
}
static function getUserElement(){
	global $_out;
	return $_out->query('/page/user')->item(0);
}
}
?>