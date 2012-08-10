<?
class apFiles extends module{
function getRow(){
	if(is_array($v = param('file')))
		return @$v['id'];
	elseif($v = param('row')) return $v;
}
function getMessage(){
	switch(param('mess')){
		case 'delete_ok':
			return 'Файл удален';
		case 'delete_fail':
			return 'Ошибка, файл не удален';
		case 'update_ok':
			return 'Информация о файле успешно обновлена';
		case 'update_fail':
			return 'Ошибка обновления информации';
		case 'add_ok':
			return 'Новый файл добавлен';
		case 'add_fail':
			return 'При добавлении файла произошла ошибка';
	}
}
function redirect($mess = null){
	$param = array();
	
	if(($action = param('action')))switch($action){
		case 'apply_update':
		case 'apply_add':
			$param['action'] = 'edit';
			$param['row'] = $this->getRow();
			break;
	}
	if($mess) $param['mess'] = $mess;
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
	return $form_element ? new form($form_element) : null;
}
function getTagList(){
	if($form = $this->getForm('edit'))
		$form->replaceURI(array('MD' => $this->getId(),'SECTION' => $this->getSection()->getId()));
	if($form
		&& ($ff = array_pop($form->getFields('@type="file"')))
		&& ($uri = form::getBaseURI($ff->getRootElement()))
	){
		//получаем тэг со списком файлов
		$scheme = new xmlScheme();
		if(!($n = $scheme->getNode($uri))){
			$scheme->add($uri.'/@name',$ff->getName());
			$scheme->save();
			$n = $scheme->getNode($uri);
		}
		if($n instanceof DOMElement)
			return new taglist($n,'file');
		
	}
}
function getList($files){
	if($files
		&& ($list_element = $this->query('rowlist')->item(0))
	){
		$rl = new rowlist($list_element,$files->getNum(),param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		foreach($files  as $i => $file){
			if($i<$s) continue;
			elseif($i>$f) break;
			if($fi = $this->getFileInfo(PATH_ROOT.$file->getAttribute('path'))){
				$rl->addRow($file->getAttribute('id'),array(
					'sort'=>$i+1,
					'title'=>$file->getAttribute('title'),
					'file'=>'<a href="'.PATH_ROOT.$file->getAttribute('path').'">'.$fi['basename'].'</a>',
					'size'=>$fi['size'],
					'date'=>$fi['date'],
					'active'=>$file->hasAttribute('disabled')
				));
			}
		}
		return $rl;
	}
}
static function getFileInfo($path){
	if(file_exists($path)){
		$ar = pathinfo($path);
		$tmp = filesize($path);
		$units = array('Б','КБ','МБ');
		foreach($units as $i => $u){
			$ar['size'] = number_format($tmp,$i,',',' ').' '.$u;
			if($tmp > 1024) $tmp = $tmp/1024;
			else break;
		}
		$ar['date'] = date('d.m.Y H:i',filemtime($path));
		return $ar;
	}
}
static function normalizePath($path){
	$i = 0;
	while(0===strncmp($path,$_SERVER['PHP_SELF'],++$i));
	return substr($path,$i-1);
}
static function getAbsPath($path){
	$path_site = str_replace('\\','/',realpath(pathinfo($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'],PATHINFO_DIRNAME).'/'.PATH_ROOT));
	$path_root = str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']);
	$i = 0;
	while(0===strncmp($path_site,$path_root,++$i));
	return substr($path_site,$i-1).'/'.$path;
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		$files = $this->getTagList();
		$form = $this->getForm($action = param('action'));
		$row = $this->getRow();
		switch($action){
			case 'fileinfo':
				if(($path = urldecode(param('path')))
					&& ($f = $this->getFileInfo($_SERVER['DOCUMENT_ROOT'].$path))
				){
					$f['path'] = $path;
					$xml = new xml(null,'file',false);
					foreach($f as $tagName => $value)
						$xml->de()->appendChild($xml->createElement($tagName,null,$value));
					ap::ajaxResponse($xml);
				}
				vdump('Error file not found '.$path);
				break;
			case 'active':
				if($row && ($file = $files->getById($row))){
					if(param('active')=='on')
						$file->setAttribute('disabled','disabled');
					elseif($file->hasAttribute('disabled'))
						$file->removeAttribute('disabled');
					$files->getXML()->save();
					if(param('ajax'))
						ap::ajaxResponse($file->hasAttribute('disabled') ? 'off' : 'on');
					else $this->redirect('active_ok');
				}
				break;
			case 'move':
				if($row
					&& ($e = $files->getById($row))
					&& ($pos = param('pos'))>0
				){
					$files->move($e,$pos);
					$files->getXML()->save();
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'delete':
				if(!is_array($row)) $row = array($row);
				$counter = 0;
				foreach($row as $id){
					if($id
						&& ($e = $files->getById($id))
					){
						$counter++;
						$files->remove($e);
					}
				}
				if($counter && $counter==count($row)){
					$files->getXML()->save();
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				$_REQUEST['path'] = $this->normalizePath($_REQUEST['path']);
				if($row
					&& $files->getById($row)
					&& file_exists(PATH_ROOT.$_REQUEST['path'])
				){
					$form->replaceURI(array('SECTION'=>$this->getSection()->getId(),'MD'=>$this->getId(),'ID'=>$row));
					$form->save($_REQUEST);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if(is_array($v = $_REQUEST['file'])){
					$v['id'] = $files->generateId('f');
					$v['path'] = $this->normalizePath($v['path']);
					if(file_exists(PATH_ROOT.$v['path']) && $v['id']){
						$_REQUEST['file'] = $v;
						$form->replaceURI(array('SECTION'=>$this->getSection()->getId(),'MD'=>$this->getId()));
						$form->save($_REQUEST);
						$this->redirect('add_ok');
					}else $this->redirect('add_fail');
				}
				break;
			case 'edit':
				$pos = $files->getPos($files->getById($row))+1;
				$form->replaceURI(array('SECTION'=>$this->getSection()->getId(),'MD'=>$this->getId(),'ID'=>$row));
				$form->load();
				if($ff = $form->getField('path')){
					$ff->setValue($this->getAbsPath($ff->getValue()));
				}
			case 'new':
				$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				break;
			default:
				if($rl = $this->getList($files))
					$_out->elementIncludeTo($rl->getRootElement(),'/page/section');
		}
	}
}
function install(){
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('form_edit','form_add','list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('files',$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
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
	if($e = $xml->getElementById('form_settings')){
		$form = new form($e);
		$form->replaceURI(array(
			'MD'=>$this->getId(),
			'ID'=>$this->getSection()->getId()
		));
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		$_out->addSectionContent($form->getRootElement());
	}
}
}
?>