<?
class apSubscribers extends module{
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
		case 'send_ok':
			$mess = 'Письмо разослано!'; break;
		case 'send_fail':
			$mess = 'Письмо не отправлено, возможно нет активных подписчиков.'; break;
		case 'delete_ok':
			$mess = 'Пользователь удален'; break;
		case 'delete_fail':
			$mess = 'Ошибка, пользователь не удален'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Пользователь добавлен'; break;
		case 'add_fail':
			$mess = 'При добавлении пользователя произошла ошибка'; break;
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
			$form_element = $xml->getElementById('subscribers_form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('subscribers_form_add');
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
		if($list_element = $xml->query('rowlist[@id="subscribers_list"]',$this->getRootElement())->item(0)){
			$this->rl = new mysqllist($list_element,array(
				'table' => 'users',
				'page' => param('page'),
				'activecol' => 'subscribe',
				'order' => 'date desc',
				'sortcontrol' => false
			));
			$this->rl->addDateFormat('date','d.m.Y');
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
			case 'spam':
				$res = $this->spam();
				$this->redirect('send_'.($res ? 'ok' : 'fail'));
				break;
			case 'active':
				if($row){
					$mysql = new mysql();
					$state = !(param('active')=='on');
					$res = $mysql->update('users',array(
						'subscribe' => $state ? '1' : '0'
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
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('users')
					));
					$form->save($_REQUEST);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				$this->setRow($row = $mysql->getNextId('users'));
				$values = array_merge($_REQUEST,array(
					'date' => date("Y-m-d H:i:s"),
				));
				$form->replaceURI(array(
					'ID'=>$row,
					'TABLE'=>$mysql->getTableName('users')
				));
				$form->save($values);
				$this->redirect('add_ok');
				break;
			case 'edit':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('users')
					));
					$form->load();
				}
				$_out->addSectionContent($form->getRootElement());
				break;
			case 'new':
				$_out->addSectionContent($form->getRootElement());
				break;
			default:
				if($rl = $this->getList()){
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function deleteUser($row){
	if($row && ($rl = $this->getList())){
		if(!is_array($row)) $row = array($row);
		$form = $this->getForm('edit');
		$form->replaceURI(array(
			'ID'=>$row,
			'TABLE'=>$rl->getTableName()
		));
		foreach($row as $id){
			$rl->deleteRow($row);
		}
	}
}
function spam(){
	global $_site;
	$counter = 0;
	$mysql = new mysql();
	//ищем модуль с письмом
	$modules = $this->getSection()->getModules();
	$module = null;
	foreach($modules as $m){
		if($m->getName()=='apContent'){
			$module = $m;
			break;
		}
	}
	
	if($module //модуль нашли
		&& ($e = $module->query('form')->item(0)) //ищем элемент формы
	){
		$form = new form($e);
		$form->replaceURI(array(
			'ID' => $module->getSection()->getId(),
			'MD' => $module->getId()
		));
		if(($ff = $form->getField('message')) //ищем поле с ссобщением
			&& ($schema = form::getSchemeObject($ff->getURI())) //получаем схему
			&& ($message = $schema->get($ff->getURI())) //получаем сообщение
			&& ($ff = $form->getField('subject')) //ищем поле с темой письма
			&& ($schema = form::getSchemeObject($ff->getURI())) //получаем схему
			&& ($subject = $schema->get($ff->getURI())) //получаем тему
			&& ($rs = $mysql->query('select * from `'.$mysql->getTableName('users').'` where `subscribe`=1')) //получаем подписчиков
		){
			$mail = new mymail('no-reply@'.$_site->de()->getAttribute('domain'),'info@bezdomena.com',$subject,$message);
			while($r = mysql_fetch_assoc($rs)) if($r['email']){
				$mail->setContent($message.'<p>Если вы не хотите больше получать новости с сайта <a href="http://'.$_site->de()->getAttribute('domain').'/">'.$_site->de()->getAttribute('domain').'</a> проследуйте по этой ссылке: <a href="http://'.$_site->de()->getAttribute('domain').'/#unSubscribe='.  base64_encode($r['email']).'">http://'.$_site->de()->getAttribute('domain').'/#unSubscribe='.  base64_encode($r['email']).'</a></p>');
				$mail->to = $r['email'];
				if(@$mail->send()) $counter++; //отправляем письмо
			}
		}
	}
	return $counter;
}
function install(){
	$mysql = new mysql();
	$table = 'users';
	if(!$mysql->hasTable($table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `city` varchar(63) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(63) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `subscribe` tinyint(1) unsigned NOT NULL DEFAULT "0",
  PRIMARY KEY (`id`)
)');
	}
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('subscribers_form_edit','subscribers_form_add','subscribers_list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	/*if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			if($m = $modules->add('subscribers',$this->getTitle(),$this->getId())){
				$xml = $modules->getXML();
				/*if($e = $xml_data->query('/data/form[@id="register"]')->item(0)){
					$xml->elementIncludeTo($e,$m->getRootElement());
				}* /
				$xml->save();
			}
		}
		return true;
	}*/
	return true;
}
function uninstall(){
	$mysql = new mysql();
	$table = 'users';
	if($mysql->hasTable($table)
		&& ($rs = $mysql->query('select id from `'.$mysql->getTableName($table).'`'))
	) while($r = mysql_fetch_array($rs)) $this->deleteUser($r['id']);
	//$mysql->query('drop table `'.$mysql->getTableName($table).'`');
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
/*
function settings($action){
	global $_out;
	$xml = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	if($e = $xml->getElementById('subscribers_form_settings')){
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
		
		$_out->addSectionContent($form->getRootElement());
	}
}
*/
}
?>