<?
require_once 'formGallery.php';
class apArticles extends module{
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
			$mess = 'Статья удалена'; break;
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
			$form_element = $xml->getElementById('article_form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('article_form_add');
			break;
	}
	if($form_element){
		$form = new formGallery($form_element);
		return $form;
	}
}
function getList(){
	if(!$this->rl){
		$xml = $this->getSection()->getXML();
		if($list_element = $xml->query('rowlist[@id="article_list"]',$this->getRootElement())->item(0)){
			$this->rl = new mysqllist($list_element,array(
				'table' => 'articles',
				'cond' => '`section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"',
				'page' => param('page')
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
			case 'active':
				if($row){
					$state = !(param('active')=='on');
					$res = $mysql->updateRow('articles',array(
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
				if($row){
					$this->deleteArticle($row);
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('articles'),
						'SECTION'=>$this->getSection()->getId()
					));
					$values = array_merge($_REQUEST,array(
						'date' => $this->strToDate($_REQUEST['date']),
					));
					$form->save($values,$row);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				$this->setRow($row = $mysql->getNextId('articles'));
				$values = array_merge($_REQUEST,array(
					'section' => $this->getSection()->getId(),
					'module' => $this->getId(),
					'date' => $this->strToDate($_REQUEST['date']),
					'sort' => $this->getNextSortIndex()
				));
				$form->replaceURI(array(
					'ID'=>$row,
					'TABLE'=>$mysql->getTableName('articles'),
					'SECTION'=>$this->getSection()->getId()
				));
				$form->save($values,$row);
				$this->redirect('add_ok');
				break;
			case 'edit':
				if($row){
					$form->replaceURI(array(
						'ID'=>$row,
						'TABLE'=>$mysql->getTableName('articles'),
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
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function deleteArticle($row){
	if($row && ($rl = $this->getList())){
		if(!is_array($row)) $row = array($row);
		$form = $this->getForm('edit');
		foreach($row as $id){
			$xml = new xml();
			$f = new formGallery($xml->appendChild($xml->importNode($form->getRootElement()->cloneNode(true))));
			$f->replaceURI(array(
				'ID'=>$id,
				'TABLE'=>$rl->getTableName(),
				'SECTION'=>$this->getSection()->getId()
			));
			$f->deleteImages($id);
			$rl->deleteRow($id);
		}
	}
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName('articles').'`
		where `section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function install(){
	$mysql = new mysql();
	$table = 'articles';
	if(!$mysql->hasTable($table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`section` varchar(63) DEFAULT NULL,
`module` varchar(15) DEFAULT NULL,
`date` datetime DEFAULT NULL,
`title` varchar(255) DEFAULT NULL,
`announce` text,
`article` text,
`active` tinyint(1) unsigned DEFAULT NULL,
`sort` int(10) unsigned NOT NULL DEFAULT "1",
PRIMARY KEY (`id`),
KEY `SectionIndex` (`section`)
)');
	}
	$table = 'articles_images';
	if(!$mysql->hasTable($table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`id_article` int(10) unsigned DEFAULT NULL,
`field_name` varchar(31) DEFAULT NULL,
`title` varchar(255) DEFAULT NULL,
`sort` int(10) unsigned DEFAULT NULL,
`active` tinyint(1) unsigned NOT NULL DEFAULT "1",
PRIMARY KEY (`id`)
)');
	}
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('article_form_edit','article_form_add','article_list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('articles',$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
	$mysql = new mysql();
	$table = 'articles';
	if($rs = $mysql->query('select * from `'.$mysql->getTableName($table).'` where `section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"'))
		while($r = mysql_fetch_array($rs)) $this->deleteArticle($r['id']);
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
	if($e = $xml->getElementById('article_form_settings')){
		$form = new form($e);
		$form->replaceURI(array(
			'MODULE'=>$this->getId(),
			'SECTION'=>$this->getSection()->getId()
		));
		//размер превью
		if(($ffh = $form->getField('previewSizeH'))
			&& ($ffv = $form->getField('previewSizeV'))
			&& ($ffMaxPrev = $form->getField('previewSizeMax'))
			&& ($ffMaxImg = $form->getField('imgSizeMax'))
		){
			if(($res = $this->query('form//field[@name="image"]/param[@preview]'))
				&& ($e1 = $res->item(0))
				&& ($e2 = $res->item(1))
			){
				$ffi1 = new formImageField($e1);
				$ffi2 = new formImageField($e2);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi1->setPreviewSize(intval(param('previewSizeH')),intval(param('previewSizeV')),intval(param('previewSizeMax')));
						$ffi2->setPreviewSize(intval(param('previewSizeH')),intval(param('previewSizeV')),intval(param('previewSizeMax')));
						$ffi1->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi1->getPreviewSize())){
							$ffh->setValue($s['width']);
							$ffv->setValue($s['height']);
							$ffMaxPrev->setValue($s['max']);
						}
						break;
				}
			}
			if(($res = $this->query('form//field[@name="image"]/param[not(@preview)]'))
				&& ($e1 = $res->item(0))
				&& ($e2 = $res->item(1))
			){
				$ffi1 = new formImageField($e1);
				$ffi2 = new formImageField($e2);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi1->setPreviewSize(null,null,intval(param('imgSizeMax')));
						$ffi2->setPreviewSize(null,null,intval(param('imgSizeMax')));
						$ffi1->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi1->getPreviewSize())){
							$ffMaxImg->setValue($s['max']);
						}
						break;
				}
			}
		}
		//поля формы
		$isImageField = $this->evaluate('count(./form//field[@name="image"])')==2;
		$isUpdate = $action=='update' || $action=='apply_update';
		$arFields = array('date','announce','article','image');
		$arForms = array('article_form_edit','article_form_add');
		$v = param('dataFields');
		if(!is_array($v)) $v = array();
		switch($action){
			case 'update':
			case 'apply_update':
				$xmlData = $this->getSection()->getXML();
				foreach($arFields as $fieldName){
					foreach($arForms as $formId){
						$e = $this->query('form[@id="'.$formId.'"]//field[@name="'.$fieldName.'"]')->item(0);
						if(in_array($fieldName,$v)){
							if(!$e
								&& ($e = $xml->query('/data/form[@id="'.$formId.'"]//field[@name="'.$fieldName.'"]')->item(0))
								&& ($ePlace = $this->query('./form[@id="'.$formId.'"]/place[@for="'.$fieldName.'"]')->item(0))
							){
								$ePlace->parentNode->insertBefore($xmlData->importNode($e),$ePlace);
								$ePlace->parentNode->removeChild($ePlace);
								
								if(($e = $xml->query('/data/rowlist[@id="article_list"]//col[@name="'.$fieldName.'"]')->item(0))
									&& ($ePlace = $this->query('./rowlist[@id="article_list"]//place[@for="'.$fieldName.'"]')->item(0))
								){
									$ePlace->parentNode->insertBefore($xmlData->importNode($e),$ePlace);
									$ePlace->parentNode->removeChild($ePlace);
								}
								
								$xmlData->save();
							}
						}elseif($e){
							$e->parentNode->insertBefore($xmlData->createElement('place',array('for'=>$fieldName)),$e);
							$e->parentNode->removeChild($e);
							
							if($e = $this->query('rowlist[@id="article_list"]//col[@name="'.$fieldName.'"]')->item(0)){
								$e->parentNode->insertBefore($xmlData->createElement('place',array('for'=>$fieldName)),$e);
								$e->parentNode->removeChild($e);
							}
							
							$xmlData->save();
						}
					}
				}
				break;
			default:
				if(($ff = $form->getField('dataFields'))){
					$res = $ff->query('option[@value]');
					foreach($res as $e)
						if($this->query('form[@id="'.$arForms[0].'"]//field[@name="'.$e->getAttribute('value').'"]')->item(0))
							$e->setAttribute('checked','checked');
				}
		}
		
		$isImageField = $this->evaluate('count(form//field[@name="image"])')==2 && !(!$isImageField && $isUpdate);
		if(!$isImageField){
			while($ff = $form->getField('imgNum')) $ff->remove();
			if($ff = $form->getField('previewSizeH')) $ff->remove();
			if($ff = $form->getField('previewSizeV')) $ff->remove();
			if($ff = $form->getField('previewSizeMax')) $ff->remove();
			if($ff = $form->getField('imgSizeMax')) $ff->remove();
		}
		
		$isAnnounceField = $this->evaluate('count(form//field[@name="announce"])')==2;
		if(!$isAnnounceField){
			while($ff = $form->getField('announceType')) $ff->remove();
		}
		
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		if(($ff = $form->getField('pageParam')) && !$ff->getValue())
			$ff->setValue('page');
		if(($ff = $form->getField('includeContent')) && !$ff->getValue())
			$ff->setValue(0);
		if(($ff = $form->getField('imgNum')) && !$ff->getValue())
			$ff->setValue(1);
		if(($ff = $form->getField('listPageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageParam')) && !$ff->getValue())
			$ff->setValue('page');
		if(($ff = $form->getField('tplNameList')) && !$ff->getValue())
			$ff->setValue('articles');
		if(($ff = $form->getField('tplNameText')) && !$ff->getValue())
			$ff->setValue('articlesRow');
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
	return date('Y-m-d H:i:s');
}
}
?>