<?
function autoload($class){
	if(file_exists($path = '../ap/classes/'.$class.'.php') || file_exists($path = $class.'.php'))
		require_once $path;
}
spl_autoload_register('autoload');
require_once '../ap/lib/default.php';
define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_SITE','../xml/site.xml');
define('PATH_STRUCT','../xml/structure.xml');
define('PATH_DATA','../xml/data/');
define('PATH_TPL','../xml/templates/');

try{
	if(!session_id() && !headers_sent()) session_start();
	$_site = new site(PATH_SITE);
	$ajax = new ajax();
	/*data manipulation here.*/
	echo $ajax->callMethod($_REQUEST,new xml(null,'responce'),new mysql());
	die;
}catch(Exception $e){
	echo 'Exception: '.$e->getMessage();
}
class ajax{
function callMethod($params,$xml,$mysql){
	if($params['action']
		&& ($c = new $params['action']())
		&& method_exists($c, 'ajax')
	){
		return $c->ajax($params,$xml,$mysql);
	}
	return false;
}
	
}	
?>
