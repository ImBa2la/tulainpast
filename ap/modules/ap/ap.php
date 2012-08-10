<?
class ap extends module{
function run(){
	global $_struct,$_events;
	//подключаем структуру разделов сайта
	$xml = ap::getClientStructure();
	if($root = $_struct->query('/structure/sec[@id="apData"]')->item(0)){
		$res = $xml->query('/structure/*');
		foreach($res as $node) $root->appendChild($_struct->importNode($node));
		//$res = $_struct->query('/structure/sec[@id="apData"]//sec[@id]');
		//foreach($res as $node) $node->setAttribute('id','_d_'.$node->getAttribute('id'));
	}
	$_struct->setDefaultSectionId($xml->getDefaultSectionId());
	
	if($root = $_struct->query('/structure/sec[@id="apStruct"]')->item(0)){
		$res = $xml->query('/structure/*');
		foreach($res as $node) $root->appendChild($_struct->importNode($node));
		$res = $_struct->query('/structure/sec[@id="apStruct"]//sec[@id]');
		foreach($res as $node) $node->setAttribute('id','_s_'.$node->getAttribute('id'));
	}
	$_events->addListener('SectionReady',$this);
	
	//авторизация
	$users = new users();
	
	switch(param('action')){
		case 'login':
			$login = param('login');
			$pass = param('pass');
			if(($usr = $users->getUser($login))
				&& !$usr->getDisabled()
				&& $usr->checkPass($pass)
			){
				$users->setCurrentUser($usr);
			}
			break;
		case 'logout':
			if(!session_id() && !headers_sent()) session_start();
			unset($_SESSION['apUser']);
			break;
	}
	if($usr = $users->getUser())
		$users->setCurrentUser($usr);

	$_events->addListener('PageReady',$this);
		
}
static function getCurrentModule(){
	global $_sec;
	$modules = $_sec->getModules();
	$m = isset($_REQUEST['md']) ? $modules->getById($_REQUEST['md']) : null;
	if(!$m) $m = $modules->get(1);
	if(!$m) throw new Exception('Section has no modules, probably XML syntax error.');
	return $m;
}
static function isCurrentModule($val){
	global $_sec;
	$sec = $_sec;
	$id = $val;
	if(is_object($val) && $val instanceof module){
		$sec = $val->getSection();
		$id = $val->getId();
	}
	$m = ap::getCurrentModule();
	//echo $m->getId().' '.$id.'<br>';
	return $m ? $m->getId()==$id : false;
}
static function addTab($moduleId,$title,$selected = false){
	global $_out;
	$tabs = $_out->query('/page/tabs')->item(0);
	if(!$tabs) $tabs = $_out->de()->appendChild($_out->createElement('tabs'));
	$tab = $tabs->appendChild($_out->createElement('tab',array('id'=>$moduleId,'title'=>$title)));
	if($selected) $tab->setAttribute('selected','selected');
	return $tab;
}
static function getUrl($params = null){
	global $_sec;
	$ar = array('id'=>$_sec->getId(),'md'=>ap::getCurrentModule()->getId());
	if(is_array($params)) $ar = array_merge($ar,$params);
	$url = array();
	foreach($ar as $name => $value) $url[] = urlencode($name).'='.urlencode($value);
	return '?'.implode('&',$url);
}
static function addMessage($val){
	global $_out;
	if($val && ($e = $_out->query('/page/section')->item(0)))
		$e->appendChild($_out->createElement('message',null,$val));
}
static function ajaxResponse($val,$message = null){
	if($val instanceof xml) $xml = $val;
	elseif($val instanceof DOMElement){
		$xml = new xml();
		$xml->appendChild($xml->importNode($val));
	}else{
		$xml = new xml(null,'response',false);
		$xml->de()->appendChild($xml->createElement('value',null,$val));
		if($message)
			$xml->de()->appendChild($xml->createElement('message',null,$message));
	}
	if($xml){
		header('Content-Type: text/xml; charset=utf-8');
		echo $xml;
	}
	die;
}
static function getClientStructure($cache = true){
	return new structure('../xml/structure.xml',null,null,$cache);
}
static function id($id){
	if(preg_match('/_s_(.+)/',$id,$m)) $id = $m[1];
	return $id;
}
static function getClientSection($id){
	$struct = ap::getClientStructure();
	return $struct->getSection(ap::id($id));
}
static function getFileInfo($path){
	if(file_exists($path)){
		$ar = pathinfo($path);
		$tmp = filesize($path);
		$units = array('Б','КБ','МБ');
		foreach($units as $i => $u){
			$ar['size'] = number_format($tmp,$i,',',' ').' '.$u;
			if($tmp > 1024) $tmp = $tmp/1024;
			else break;
		}
		$ar['date'] = date('d.m.Y H:i',filemtime($path));
		return $ar;
	}
}
function onSectionReady($params = null){
	global $_sec,$_out;
	$modules = $_sec->getModules();
	if(!$modules->getNum() && $_sec->isChildOf('apData')){
		if(apModuleManager::addModule($_sec->getId(),'apContent',$_sec->getTitle())){
			header('Location: '.$_SERVER['REQUEST_URI']);
			die;
		}else throw new Exception('apContent install error',EXCEPTION_XML);
	}
		
	if($_sec->getId() == 'apStruct'){
		if(!$modules->hasModule('apSectionAdd')) $modules->add('apSectionAdd','Добавить раздел');
	}
	if($_sec->isChildOf('apStruct')){
		if(!$modules->hasModule('apSectionEdit')) $modules->add('apSectionEdit','Свойства');
		if(!$modules->hasModule('apModuleManager')) $modules->add('apModuleManager','Модули');
		if(!$modules->hasModule('apTemplateManager')) $modules->add('apTemplateManager','Шаблоны');
		if(!$modules->hasModule('apSectionAdd')) $modules->add('apSectionAdd','Добавить раздел');
	}
	foreach($modules as $id => $m){
		$title = $m->getTitle();
		if(!$title) continue;
		ap::addTab($m->getId(),$title,ap::isCurrentModule($m->getId()));
	}
	if($e = $_out->query('/page/section')->item(0))
		$e->setAttribute('module',ap::getCurrentModule()->getId());
}
function onPageReady($param = null){
	global $_out,$_struct,$_sec;
	
	//Страница авторизации
	$users = new users();
	if(!$users->getUser()){
		$_out->de()->setAttribute('url',$_SERVER['REQUEST_URI']);
		$_tpl =  new template($_struct->getTemplatePath().'auth.xsl');
		echo $_tpl->transform($_out);
		die;
	}
	
	//Автоматическое подключение шаблонов
	if($_out->evaluate('count(/page/section//form)')){
		$_sec->getTemplate()->addTemplate('form.xsl');
	}
	if($_out->evaluate('count(/page/section//rowlist)')){
		$_sec->getTemplate()->addTemplate('rowlist.xsl');
	}
}
static function translate($xml,$xpath,$path){
	return $xml;
}
}
?>