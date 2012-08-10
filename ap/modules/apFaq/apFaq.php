<?
class apFaq extends module{
private $rl;
function getRow(){
	if($row = param('row')){
		if(is_array($row)) foreach($row as $i => $r) $row[$i] = intval($r);
		else $row = intval($row);
	}
	return $row;
}
function setRow($v){
	setParam('row',$v);
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
			$mess = 'Запись удалена'; break;
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
			$form_element = $xml->getElementById('faq_form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('faq_form_add');
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
		if($list_element = $xml->query('rowlist[@id="faq_list"]',$this->getRootElement())->item(0)){
			$this->rl = new mysqllist($list_element,array(
				'table' => 'faq',
				//'cond' => '`section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"',
				'page' => param('page')
			));
			$this->rl->addDateFormat('date','d.m.Y');
			$this->rl->addNl2Br('question');
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
					$res = $mysql->updateRow('faq',array(
						'active' => $state ? '1' : '0'
					),'`id`='.$row);
					if(!$res) $state = !$state;
					if(param('ajax'))
						ap::ajaxResponse($state ? 'on' : 'off');
					else $this->redirect('active_'.($res ? 'ok' : 'fail'));
				}
				break;
			case 'move':
				if($row
					&& ($pos = param('pos'))
					&& ($rl = $this->getList())
					&& $rl->moveRow($row,$pos)
				){
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'delete':
				if($row
					&& ($rl = $this->getList())
					&& $rl->deleteRow($row)
				){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$rl->getTableName()
					));
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('faq'),
					));
					$values = array_merge($_REQUEST,array(
						'date' => $this->strToDate($_REQUEST['date']),
					));
					$form->save($values);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				$this->setRow($row = $mysql->getNextId('faq'));
				$values = array_merge($_REQUEST,array(
					'date' => $this->strToDate($_REQUEST['date']),
					'sort' => $this->getNextSortIndex()
				));
				$form->replaceURI(array(
					'ID'=>$row,
					'TABLE'=>$mysql->getTableName('faq')
				));
				$form->save($values);
				$this->redirect('add_ok');
				break;
			case 'edit':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('faq')
					));
					$form->load();
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
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function deleteFaq($row){
	if($row && ($rl = $this->getList())){
		if(!is_array($row)) $row = array($row);
		$form = $this->getForm('edit');
		$form->replaceURI(array(
			'ID'=>$row,
			'TABLE'=>$rl->getTableName()
		));
		foreach($row as $id)
			$rl->deleteRow($row);
	}
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName('faq').'`');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function install(){
	$mysql = new mysql();
	$table = 'faq';
	if(!$mysql->hasTable($table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
  `id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `question` text,
  `answer` text,
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT "1",
  `sort` int(9) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)');
	}
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('faq_form_edit','faq_form_add','faq_list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('faq',$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
	$mysql = new mysql();
	$table = 'faq';
	if($rs = $mysql->query('select * from `'.$mysql->getTableName($table).'`'))
		while($r = mysql_fetch_array($rs)) $this->deleteFaq($r['id']);
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function settings($action){
	global $_out;
	 
	$xml = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	if($e = $xml->getElementById('faq_form_settings')){
		$form = new form($e);
		$form->replaceURI(array(
			'MODULE'=>$this->getId(),
			'SECTION'=>$this->getSection()->getId()
		));
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		//echo '<pre>'; print_r(array($ff,$_REQUEST)); echo '</pre>';
		if(($ff = $form->getField('pageParam')) && !$ff->getValue())
			$ff->setValue('page');
		if(($ff = $form->getField('includeContent')) && !$ff->getValue())
			$ff->setValue(0);
		if(($ff = $form->getField('listPageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageParam')) && !$ff->getValue())
			$ff->setValue('page');
		if(($ff = $form->getField('tplNameList')) && !$ff->getValue())
			$ff->setValue('faq');
		if(($ff = $form->getField('tplNameText')) && !$ff->getValue())
			$ff->setValue('faqRow');
		$_out->addSectionContent($form->getRootElement());
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