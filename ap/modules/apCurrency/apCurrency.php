<?
class apCurrency extends module{
private $rl;
private $forms;
protected $table = 'currency';
function getRow(){
	if($row = param('row')){
		if(is_array($row)) foreach($row as $i => $r) $row[$i] = intval($r);
		else $row = intval($row);
	}
	return $row;
}
function setRow($v){
	param('row',$v);
}
function getMessSessionName(){
	return $this->getSection()->getId().'_'.$this->getId();
}
function setMessage($mess){
	if($mess){
		if(!session_id() && !headers_sent()) session_start();
		$_SESSION['apMess'][$this->getMessSessionName()] = $mess;
	}
}
function getMessage(){
	if(!session_id() && !headers_sent()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'delete_ok':
			$mess = 'Валюта удалена'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Валюта добавлена'; break;
		case 'add_fail':
			$mess = 'При добавлении записи произошла ошибка'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null){
	$param = array();
	$action = param('action');
	if($action && ($row = $this->getRow())){
		switch($action){
			case 'apply_update':
			case 'apply_add':
				$param['action'] = 'edit';
				$param['row'] = $row;
		}
	}
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function getForm($action){
	$xml = $this->getSection()->getXML();
	$form_element = null;
	if(!is_array($this->forms)) $this->forms = array();
	if(isset($this->forms[$action])) return $this->forms[$action];
	$form_element = null;
	$formxml = new xml(null,null,false);
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			if($e = $this->query('form[@id="form_edit"]')->item(0))
				$form_element = $formxml->appendChild($formxml->importNode($e));
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			if($e = $this->query('form[@id="form_add"]')->item(0))
				$form_element = $formxml->appendChild($formxml->importNode($e));
			break;
	}
	if($form_element){
		$this->forms[$action] = new form($form_element);
		return $this->forms[$action];
	}
}
function getList(){
	if(!$this->rl){
		$xml = $this->getSection()->getXML();
		if($list_element = $xml->query('rowlist[@id="list"]',$this->getRootElement())->item(0)){
			$this->rl = new mysqllist($list_element,array(
				'table' => $this->table,
				'page' => param('page')
			));
			$this->rl->addFloatFormat('rate');
		}
	}
	return $this->rl;
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		switch($action = param('action')){
			case 'active':
				if($row = $this->getRow()){
					$mysql = new mysql();
					$state = !(param('active')=='on');
					$res = $mysql->updateRow($this->table,array(
						'active' => $state ? '1' : '0'
					),'`id`='.$row);
					if(!$res) $state = !$state;
					if(param('ajax'))
						ap::ajaxResponse($state ? 'on' : 'off');
					else $this->redirect('active_'.($res ? 'ok' : 'fail'));
				}
				break;
			case 'move':
				if(($row = $this->getRow())
					&& ($pos = param('pos'))
					&& ($rl = $this->getList())
					&& $rl->moveRow($row,$pos)
				){
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'delete':
				if($this->onDelete($action)){
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($this->onUpdate($action))
					$this->redirect('update_ok');
				else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if($this->onAdd($action))
					$this->redirect('add_ok');
				else $this->redirect('add_fail');
				break;
			case 'edit':
				$this->onEdit($action);
				break;
			case 'new':
				$this->onNew($action);
				break;
			default:
				if($rl = $this->getList()){
					$rl->build();
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function onNew($action){
	global $_out;
	$form = $this->getForm($action);
	$_out->addSectionContent($form->getRootElement());
}
function onEdit($action){
	global $_out;
	if($row = $this->getRow()){
		$mysql = new mysql();
		$form = $this->getForm($action);
		$form->replaceURI(array(
			'ID'=>$row,
			'TABLE'=>$mysql->getTableName($this->table)
		));
		$form->load($row);
		$_out->addSectionContent($form->getRootElement());
	}
}
function onAdd($action){
	$mysql = new mysql();
	$form = $this->getForm($action);
	$this->setRow($row = $mysql->getNextId($this->table));
	$values = array_merge($_REQUEST,array(
		'sort' => $this->getNextSortIndex()
	));
	$form->replaceURI(array(
		'ID'=>$row,
		'TABLE'=>$mysql->getTableName($this->table)
	));
	$form->save($values,$row);
	return true;
}
function onUpdate($action){
	if($row = $this->getRow()){
		$mysql = new mysql();
		$form = $this->getForm($action);
		$form->replaceURI(array(
			'ID'=>$row,
			'TABLE'=>$mysql->getTableName($this->table)
		));
		$form->save($_REQUEST,$row);
	}
	return $row;
}
function onDelete($action){
	if($rl = $this->getList())
		return $rl->deleteRow($id);
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName($this->table).'`');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function install(){
	$mysql = new mysql();
	if(!$mysql->hasTable($this->table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($this->table).'` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`title` varchar(255) DEFAULT NULL,
	`rate` float(6,2) unsigned DEFAULT NULL,
	`sort` int(10) unsigned DEFAULT NULL,
	`active` tinyint(1) unsigned NOT NULL DEFAULT "1",
	PRIMARY KEY (`id`)
)');
	}
	$mysql->insert($this->table, array('title'=>mysql::str('RUR'),'rate'=>mysql::str('1.00'),'sort'=>1,'active'=>1));
	$xml_data = new xml(PATH_MODULE.$this->getName().'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('form_edit','form_add','list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	return true;
	
	/*if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$moduleName = $this->getName();
			if(preg_match('/ap([A-Z].*)/',$moduleName,$m))
				$moduleName = strtolower($m[1]);
			$modules->add($moduleName,$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}*/
}
function uninstall(){
	return true;
}
}
?>