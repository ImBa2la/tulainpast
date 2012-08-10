<?
class apModuleManager extends module{
function run(){
	global $_out,$_struct,$_sec;
	if(ap::isCurrentModule($this)){
		$action = param('action');
		// управление текущими модулями
		$modules = $this->getModules();
		// все модули доступные для подключения
		$all_modules = $this->getModuleList();
		
		$form = $this->getForm($action);
		switch($action){
			case 'move':
				if(($row = $_REQUEST['row']) && ($pos = param('pos'))){
					$modules->move($row,$pos);
					$modules->getXML()->save();
				}
				$this->redirect($action);
				break;
			case 'delete':
				if($row = param('row')){
					apModuleManager::removeModule($this->getIdSection(),$row);
				}
				$this->redirect($action);
				break;
			case 'add':
			case 'apply_add':
				if($m = apModuleManager::addModule($this->getIdSection(),$_REQUEST['name'],$_REQUEST['title'])){
					$this->redirect($action,$m->getId());
				}else{
					throw new Exception('Error add module "'.$_REQUEST['name'].'"');
				}
				break;
			case 'update':
			case 'apply_update':
			case 'edit':
				if(($row = param('row'))
					&& ($sec = $this->getDataSection())
					&& ($modules = $sec->getModules())
					&& ($module = $modules->getById($row))
					&& method_exists($module,'settings')
				){
					$module->settings($action);
				}
				$this->redirect($action,$row);
				break;
			case 'new':
				if($action == 'new'){
					$m = $modules->add('tests_module');
					$form->replaceURI(array('ID' => $this->getIdSection(),'MODULEID' => $m->getId()));
				}
				$select = $form->getField('name');
				foreach($all_modules as $key => $module){
					$select->addOption($key,$module['name']);
				}
				$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				break;
			default:
			if($res = $this->getList())
				$_out->elementIncludeTo($res,'/page/section');
		}
	}
}
static function getModuleList(){
	$ar = array();
	if($dir = scandir(PATH_MODULE)){
		foreach($dir as $entry){
			if($entry != "."
				&& $entry != ".."
				&& is_dir($path = PATH_MODULE.$entry)
				&& file_exists($path.= '/info.xml')
			){
				$ar[$entry] = array();
				$xml = new xml($path);
				$res = $xml->query('/*/*');
				foreach($res as $e)
					if($e instanceof DOMElement)
						$ar[$entry][$e->tagName] = xml::getElementText($e);
			}
		}
	}
	return $ar;
}
static function getModuleInfo($name){
	$ml = apModuleManager::getModuleList();
	foreach($ml as $i => $info)if($i == $name) return $info;
}
static function addModule($sec_id,$module = null,$title = ""){
	global $_struct;
	if(!$module) $module = 'apContent';
	$sec_id = ap::id($sec_id);
	if(($info = apModuleManager::getModuleInfo($module))
		&& ($sec = $_struct->getSection($sec_id))
		&& ($modules = $sec->getModules())
		&& ($m = $modules->add($module,($title ? $title : @$info['name'])))
	){
		if($module_obj = $modules->getById($m->getId())){
			if(method_exists($module_obj,'install')){
				if(!$module_obj->install())
					apModuleManager::removeModule($sec_id,$m->getId());
			}
			$modules->getXML()->save();
			return $module_obj;
		}
	}
	return false;
}
static function removeModule($sec_id,$row){
	global $_struct;
	if(($sec = $_struct->getSection($sec_id)) && ($modules = $sec->getModules())){
		if(!is_array($row)) $row = array($row);
		foreach($row as $v){
			if(is_object($module_obj = $modules->getById($v))){
				if(method_exists($module_obj,'uninstall')){
					if(!$module_obj->uninstall()){
						throw new Exception('Error uninstall module "'.$row.'"');
					};
				}
				$modules->remove(htmlspecialchars($v));
			}
		}
		$modules->getXML()->save();
	}
}
function redirect($action,$id = null){
	$param = array();
	switch($action){
		case 'edit':return;
		case 'add':break;
		case 'apply_add':
		case 'apply_update':
			if($id){
				$param['action'] = 'edit';
				$param['row'] = $id;
			}
			break;
	}
	//echo 'Location: '.ap::getUrl($param);
	header('Location: '.ap::getUrl($param));
	die;
}


function getIdSection(){
	return ap::id($this->getSection()->getId());
}
function getDataSection(){
	global $_struct;
	return $_struct->getSection($this->getIdSection());
}

function getForm($action){
	$form_element = null;
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$xml = new xml(PATH_MODULE.__CLASS__.'/form/edit.xml');
			if($xml->de()){
				$form_element = $xml->de();
			}
			break;
		case 'new':
		case 'add':
		case 'apply_add':
			$xml = new xml(PATH_MODULE.__CLASS__.'/form/add.xml');
			if($xml->de()){
				$form_element = $xml->de();
			}
			break;
	}
	return $form_element ? new form($form_element) : null;
}

function getList(){
	$xml = new xml(PATH_MODULE.__CLASS__.'/form/rowlist.xml');
	$modules['section']	= $this->getModules();
	$modules['all']		= $this->getModuleList();
	
	if($le = $xml->de()){
		$rl = new rowlist($le,$modules['section']->getNum(),param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		foreach($modules['section'] as $m){
			if($modules['section']->getPos($m->getRootElement())<$s) continue;
			elseif($modules['section']->getPos($m->getRootElement())>$f) break;
			$rl->addRow($m->getId(),array(
				'title'			=> $m->getTitle(),
				'name'			=> $modules['all'][$m->getName()]['name'],
				'version'		=> $modules['all'][$m->getName()]['version'],
				'description'	=> $modules['all'][$m->getName()]['description'],
				'data'			=> $modules['all'][$m->getName()]['data']
			));
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl->getRootElement();
	}
}
function getModules(){
	global $_struct;
	$sec = $_struct->getSection($this->getIdSection());
	return $sec->getModules();
}

}
?>