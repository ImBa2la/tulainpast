<?
function autoload($class){
	if(file_exists($path = '../ap/classes/'.$class.'.php') 
		|| file_exists($path = $class.'.php') 
		|| file_exists($path = '../ap/modules/'.$class.'/'.$class.'.php')
	){
		require_once $path;
	}
}
spl_autoload_register('autoload');
require_once '../ap/lib/default.php';
define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_SITE','../xml/site.xml');
define('PATH_STRUCT','../xml/structure.xml');
define('PATH_STRUCT_AP','../ap/xml/structure.xml');
define('PATH_DATA','../xml/data/');
define('PATH_DATA_AP','../ap/xml/data/');
define('PATH_TPL','../xml/templates/');
define('PATH_TPL_AP','../ap/xml/templates/');
try{
	if(!session_id() && !headers_sent()) session_start();
	$_site = new site(PATH_SITE); #for mysql class
	$_struct = new structure(PATH_STRUCT_AP,PATH_DATA_AP,PATH_TPL_AP);
	$ajax = new ajax();
	/*data manipulation here.*/
	echo $ajax->callMethod($_REQUEST,new xml(null,'responce'),new mysql());
	die;
}catch(Exception $e){
	echo 'Exception: '.$e->getMessage();
}
class ajax{
function callMethod($params,$xml,$mysql){
	if(($c = $this->getCallable($params))
		&& method_exists($c, 'ajax')
	){
		return $c->ajax($params,$xml,$mysql);
	}
	return false;
}
function getCallable($params){
	global $_struct;
	if(($sId = $params['section']) && ($mId = $params['md'])){//actions for module class
		$xml = ap::getClientStructure();
		if($root = $_struct->query('/structure/sec[@id="apData"]')->item(0)){
			$res = $xml->query('/structure/*');
			foreach($res as $node) $root->appendChild($_struct->importNode($node));
		}
		if(($sec = $_struct->getSection($sId))
			&& ($c = $sec->getModules()->getById($mId))
		){
			return $c;
		}
	}elseif($params['action']){
		return new $params['action']();	
	}
	return false;
	
}
}	
?>
