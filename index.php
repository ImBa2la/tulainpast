<?
function autoload($class){
	if(file_exists($path = 'ap/classes/'.$class.'.php') || file_exists($path = 'classes/'.$class.'.php'))
		require_once $path;
}
spl_autoload_register('autoload');
require 'ap/lib/default.php';

define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_SITE','xml/site.xml');
define('PATH_STRUCT','xml/structure.xml');
define('PATH_DATA','xml/data/');
define('PATH_TPL','xml/templates/');
function parseParam(){
	global $_params;
	if(!is_array($_params)) return;
	$args = func_get_args();
	$num = count($_params);
	foreach($args as $prefix){
		if(!$num) break;
		foreach($_params as $i => $v)
			if(preg_match('/^'.$prefix.'([[0-9a-z_\-]+)$/',$v,$res)){
				setParam(trim($prefix,'_'),$res[1]);
				unset($_params[$i]);
				$num = count($_params);
				break;
			}
	}
	$_params = array_values($_params);
}
try{
	$pos = strrpos($_SERVER['PHP_SELF'],'/')+1;
	define('BASE_URL',substr($_SERVER['PHP_SELF'],0,$pos));
	$url = substr($_SERVER['REQUEST_URI'],$pos);
	$_params = explode('/',trim(substr($_SERVER['REQUEST_URI'],$pos),'/'));
	if(count($_params)) param('id',array_shift($_params));
	parseParam('page','row','param');
	foreach($_params as $i => $val) $_params[$i] = urldecode($val);
	
	$_site = new site('xml/site.xml');
	$_struct = new structure('xml/structure.xml');
	$_out = new out();
	$_events = new events('xml/events.xml');
	$_events->addEvent('PageReady');
	
	$_site->setModules(new modules($_site,'modules'));
	
	$modules = $_site->getModules();
	if(!$modules->hasModule('main'))
		$modules->move($modules->add('main'),1);
	$modules->add('authorization');
	$modules->run();
	
	$_sec = $_struct->getCurrentSection();
	$_sec->getModules()->run();
	$_out->xmlInclude($_struct);
	$_out->xmlInclude($_site);
	$_events->happen('PageReady');
	
	$_tpl = $_sec->getTemplate();
	$_out->save('temp.xml');
	echo $_tpl->transform($_out);
}catch(Exception $e){
	switch($e->getCode()){
		case EXCEPTION_404:
			header("HTTP/1.0 404 Not Found");
			$_site = new site('xml/site.xml');
			$_struct = new structure('xml/structure.xml');
			$_out = new out();
			
			param('id',$_struct->getDefaultSectionId());
			$_sec = $_struct->getCurrentSection();
			
			$_out->xmlInclude($_struct);
			$_out->xmlInclude($_site);
			$_out->de()->setAttribute('status','404');
			$_out->de()->appendChild($_out->createElement(
					'meta',
					array('name'=>'title'),
					'Ошибка 404. Запрашиваемая страница не найдена')
			);
			
			$_tpl =  new template($_struct->getTemplatePath().'default.xsl');
			$_tpl->addTemplate($_struct->getTemplatePath().'404.xsl');
			echo $_tpl->transform($_out);
			break;
		default:
			echo 'Exception: '.$e->getMessage();
	}	
}
die;
?>