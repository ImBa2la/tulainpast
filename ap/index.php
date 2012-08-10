<?
function autoload($class){
	if(substr($class,0,2)=='ap' && file_exists($path = 'modules/'.$class.'/'.$class.'.php')){
		require_once $path;
		return;
	}
	if(file_exists($path = 'classes/'.$class.'.php') || file_exists($path = '../classes/'.$class.'.php'))
		require_once $path;
}
spl_autoload_register('autoload');
require 'lib/default.php';

define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_ROOT','../');
define('PATH_DATA','xml/data/');
define('PATH_TPL','xml/templates/');
define('PATH_TPL_CLIENT',PATH_ROOT.'xml/templates/');
define('PATH_MODULE','modules/');

try{
	$_site = new site('../xml/site.xml');
	$_struct = new structure('xml/structure.xml','xml/data','xml/templates');
	$_out = new out();
	$_events = new events('xml/events.xml');
	$_events->addEvent('SectionReady');
	$_events->addEvent('PageReady');
	
	$_site->setModules(new modules($_site,'apModules'));
	$modules = $_site->getModules();
	if(!$modules->hasModule('ap'))
		$modules->move($modules->add('ap'),1);
	$modules->run();
	
	$_sec = $_struct->getCurrentSection();
	$_events->happen('SectionReady');
	
	$_sec->getModules()->run();
	$_out->xmlInclude($_struct);
	$_out->xmlInclude($_site);
	$_events->happen('PageReady');
	$_tpl = $_sec->getTemplate();
	$_out->save('temp.xml');
	echo $_tpl->transform($_out);
}catch(Exception $e){
	$_out = new out();
	$_out->addSectionContent('Exception: '.$e->getMessage());
	$_tpl = new template('xml/templates/error.xsl');
	echo $_tpl->transform($_out);
}
die;
?>