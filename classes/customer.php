<?
class customer extends module{
const table = 'customers';
function run(){
	global $_out,$_params;
	customer::auth();
	if($_params->exists('change')
		&& ($e = $this->query('form[@id="change_customer_data_form"]')->item(0))
		&& ($id = customer::getCustomerId())
		&& ($c = customer::getById($id,true))
	){
		$form = new xmlform($e);
		if($form->sent()){
			if(count($err = $form->check())){
				$form->message(implode('<br/>',$errors));
			}elseif($id = customer::getCustomerId()){
				$mysql = new mysql();
				$data = array(
					'name' => mysql::str(param('name'))
					,'phone' => mysql::str(param('phone'))
					,'email' => mysql::str(param('email'))
					,'address' => mysql::str(param('address'))
					,'comment' => mysql::str(param('comment'))
					,'subscribe' => param('subscribe') ? '1' : '0'
				);
				if($v = param('pass'))
					$data['pass'] = mysql::str(customer::getHash($v));
				$mysql->update(customer::table,$data,'`id`='.$id);
				header('Location: '.BASE_URL.$this->getSection()->getId().'/');
				die;
			}
		}else{
			foreach($c as $name => $value) switch($name){
				case 'pass': break;
				default: $form->setValue($name,$value);
			}
			$_out->addSectionContent($form->getElement());
		}
	}
}
function onSectionReady(){
	global $_out;
	if(!session_id()) session_start();
	$xml = null;
	if(param('action')=='logout') $this->logout();
	elseif(isset($_SESSION['login'])){
		if($xml = $this->getUserXML($_SESSION['login']));
		else $this->logout();
	}
	if($xml) $_out->xmlInclude($xml);
}
function logout(){
	if(!session_id()) session_start();
	if(isset($_SESSION['login'])){
		unset($_SESSION['login']);
	}
}
static function auth(){
	if(!customer::getCustomerId()){
		header('Location: '.BASE_URL.'login/?backurl='.urlencode($_SERVER['REQUEST_URI']));
		die;
	}
}
static function add($v){
	$mysql = new mysql();
	if($mysql->insert(customer::table,$v))
		return $mysql->getInsertId();
}
static function activate($hash){
	$mysql = new mysql();
	if($hash && $mysql->update(customer::table,array('active' => 1),'`active`=0 and `active_hash`="'.addslashes($hash).'"'))
		return $mysql->affectedRows();
}
static function getByLogin($v,$checkActive = false){
	$mysql = new mysql();
	if($v && ($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName(customer::table).'` WHERE `login`="'.addslashes($v).'"'
		.($checkActive ? ' AND `active`=1' : null)))
	) return mysql_fetch_assoc($rs);
}
static function getByEmail($v,$checkActive = false){
	$mysql = new mysql();
	if($v && ($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName(customer::table).'` WHERE `email`="'.addslashes($v).'"'
		.($checkActive ? ' AND `active`=1' : null)))
	) return mysql_fetch_assoc($rs);
}
static function getById($v,$checkActive = false){
	$mysql = new mysql();
	if($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName(customer::table).'` WHERE `id`='.intval($v)
		.($checkActive ? ' AND `active`=1' : null))
	) return mysql_fetch_assoc($rs);
}
static function getNextId(){
	$mysql = new mysql();
	return $mysql->getNextId(customer::table);
}
static function getHash($v){
	return md5($v);
}
static function changePassword($hash,$pass){
	$mysql = new mysql();
	if($hash && $pass && $mysql->update(customer::table,array('pass' => '"'.customer::getHash($pass).'"'),'`pass`="'.addslashes($hash).'"'))
		return $mysql->affectedRows();
}
static function login($login,$password = false){
	global $_out;
	if($xml = customer::getUserXML($login,$password)){
		$_SESSION['login'] = $login;
		$_out->xmlInclude($xml);
		return customer::getCustomerElement();
	}
	return false;
}
static function getUserXML($login,$pass = false){
	$mysql = new mysql();
	if($login
		&& ($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName(customer::table).'` WHERE `login`="'.addslashes($login).'" '.($pass!==false ? ' AND `pass`="'.md5($pass).'"' : null)))
		&& ($r = mysql_fetch_assoc($rs))
	){
		$xml = new xml(null,'user',false);
		$xml->de()->setAttribute('id',$r['id']);
		$xml->de()->setAttribute('login',$r['login']);
		$xml->de()->setAttribute('date',date('d.m.Y',strtotime($r['date'])));
		$ar = array('name','email','phone','address','comment','subscribe');
		foreach($ar as $name)
			$xml->de()->appendChild($xml->createElement($name,null,$r[$name]));
		return $xml;
	}
}
static function getCustomerElement(){
	global $_out;
	return $_out->query('/page/user')->item(0);
}
static function getCustomerId(){
	global $_out;
	$v = $_out->evaluate('number(/page/user/@id)');
	return is_nan($v) ? null : $v;
}
}
?>