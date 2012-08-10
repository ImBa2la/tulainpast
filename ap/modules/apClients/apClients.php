<?
class apClients extends module{
private $rl;
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
			$mess = 'Клиент удален'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Запись добавлена'; break;
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
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$form_element = $xml->getElementById('form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('form_add');
			break;
	}
	if($form_element){
		$form = new form($form_element);
		return $form;
	}
}
function getList(){
	if(!$this->rl){
		$xml = $this->getSection()->getXML();
		if($list_element = $xml->query('rowlist[@id="list"]',$this->getRootElement())->item(0)){
			$order = array('col' => 'date','order' => 'desc');
			if(is_array($o = param('order'))){
				$order = array(
					'col' => $o[0],
					'order' => $o[1]
				);
			}
			$this->rl = new mysqllist($list_element,array(
				'table' => 'users',
				'cond' => '',
				'sortcontrol' => false,
				'order' => '`'.$order['col'].'` '.$order['order'],
				'page' => param('page')
			));
			$this->rl->setOrder($order['col'],$order['order']);
			$this->rl->addDateFormat('date','d.m.Y H:i');
			$this->rl->build();
		}
	}
	return $this->rl;
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		$action = param('action');
		$form = $this->getForm($action);
		$row = $this->getRow();
		$mysql = new mysql();
		switch($action){
			case 'active':
				if($row){
					$state = !(param('active')=='on');
					$res = $mysql->updateRow('users',array(
						'active' => $state ? '1' : '0'
					),'`id`='.$row);
					if(!$res) $state = !$state;
					if(param('ajax'))
						ap::ajaxResponse($state ? 'on' : 'off');
					else $this->redirect('active_'.($res ? 'ok' : 'fail'));
				}
				break;
			case 'delete':
				if($row){
					$this->deleteItem($row);
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('users'),
						'SECTION'=>$this->getSection()->getId()
					));
					$form->save($_REQUEST,$row);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				$this->setRow($row = $mysql->getNextId('users'));
				$values = array_merge($_REQUEST,array(
					'date' => date('Y-m-d H:i:s')
				));
				$form->replaceURI(array(
					'ID'=>$row,
					'TABLE'=>$mysql->getTableName('users'),
					'SECTION'=>$this->getSection()->getId()
				));
				$form->save($values,$row);
				$this->redirect('add_ok');
				break;
			case 'edit':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('users'),
						'SECTION'=>$this->getSection()->getId()
					));
					$form->load($row);
					if($ff = $form->getField('date'))
						$ff->setValue($this->dateToStr($ff->getValue()));
				}
				$_out->addSectionContent($form->getRootElement());
				break;
			case 'new':
				if($ff = $form->getField('date'))
					$ff->setValue(date('d.m.Y'));
				$_out->addSectionContent($form->getRootElement());
				break;
			default:
				if($rl = $this->getList()){
					$rl->getRootElement()->setAttribute('sortUri',ap::getUrl());
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function deleteItem($row){
	if($row && ($rl = $this->getList())){
		if(!is_array($row)) $row = array($row);
		foreach($row as $id) $rl->deleteRow($id);
	}
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName('users').'`
		where `section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function install(){
	$mysql = new mysql();
	$table = 'users';
	if(!$mysql->hasTable($table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `comment` text,
  `date` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT "0",
  `active_hash` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `login` varchar(50) NOT NULL,
  PRIMARY KEY (`id`))');
	}
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('form_edit','form_add','list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	return true;
}
function uninstall(){
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
static function dateToStr($val){
	return date('d.m.Y',strtotime($val));
}
static function strToDate($val){
	if(preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/',str_replace(' ','',trim($val)),$res)){
		return $res[3].'-'.$res[2].'-'.$res[1];
	}
	return date('Y-m-d');
}
}
?>