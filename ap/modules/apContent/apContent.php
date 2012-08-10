<?
class apContent extends module{
function __construct(DOMElement $e,structure $struct){
	parent::__construct($e,$struct);
}
function getForm(){
	if($e = $this->getSection()->getXML()->query('form',$this->getRootElement())->item(0))
		return new form($e);
}
function redirect($mess = null){
	header('Location: '.ap::getUrl($mess ? array('mess' => $mess) : null));
	die;
}
function getMessage(){
	switch(param('mess')){
		case 'save_ok':
			return 'Данные успешно сохранены';
	}
}
function run(){
	global $_out,$_sec;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		if($form = $this->getForm()){
			$form->replaceURI(array(
				'ID' => $_sec->getId(),
				'MD' => $this->getId()
			));
			switch($action = param('action')){
				case 'save':
					$values = $this->initImages($form,true);
					$values = array_merge($_REQUEST,$values);
					$form->save($values);
					$this->updateImagesSize($form);
					$this->redirect('save_ok');
					break;
				case 'fileinfo':
					if(($path = urldecode(param('path')))
						&& ($f = ap::getFileInfo($_SERVER['DOCUMENT_ROOT'].$path))
					){
						$f['path'] = $path;
						$xml = new xml(null,'file',false);
						foreach($f as $tagName => $value)
							$xml->de()->appendChild($xml->createElement($tagName,null,$value));
						ap::ajaxResponse($xml);
					}
					vdump('Error file not found '.$path);
					break;
			}
			$this->initImages($form,false);
			$form->load();
			$_out->elementIncludeTo($form->getRootElement(),'/page/section');
		}else throw new Exception('Form not found',EXCEPTION_XML);
	}
}
function install(){
	if(!$this->getForm()){
		$data_xml = new xml(PATH_MODULE.__CLASS__.'/data.xml');
		$this->getSection()->getXML()->elementIncludeTo($data_xml->getElementById('content_form'),'//modules/module[@id="'.$this->getId().'"]');
		if($form = $this->getForm())
			$form->getXML()->save();
	}
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('content',$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
	if($form = $this->getForm()){
		//удаляем галерею
		$formFields = $form->getFields('@type="image"');
		foreach($formFields as $ff){
			$formats = array();
			$res = $ff->query('param');
			foreach($res as $param) $formats[] = $param->parentNode->removeChild($param);
			
			$scheme = new xmlScheme();
			if(($n = $scheme->getNode($ff->getURI()))
				&& $n instanceof DOMElement
			){
				$tl = new taglist($n,'img');
				foreach($tl as $img){
					$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
					foreach($formats as $param){
						$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
						$e->setAttribute('name',$fieldName);
						$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getGalleryImageName($img->getAttribute('id')),$e->getAttribute('uri')));
					}
				}
				$ff->removeImageFiles();
				$n->parentNode->removeChild($n);
				$tl->getXML()->save();
			}
		}
	}
	
	
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
	if($e = $xml->getElementById('content_form_settings')){
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

//Галерея
function getGalleryImageName($id){
	return implode('_',array($this->getSection()->getId(),$this->getId(),$id));
}
function initImages($form,$isUpdate){
	$formFields = $form->getFields('@type="image"');
	$values = array();
	foreach($formFields as $ff){
		//форматы картинок
		$formats = array();
		$res = $ff->query('param');
		foreach($res as $param) $formats[] = $param->parentNode->removeChild($param);
		//получаем тэг со списком картинок, создаем объект списка
		$scheme = new xmlScheme();
		$scheme->add($ff->getURI().'/@name',$ff->getName());
		$scheme->save();
		if(($n = $scheme->getNode($ff->getURI()))
			&& $n instanceof DOMElement
		){
			$tl = new taglist($n,'img');
			
			//добавляем новые картинки
			$arNewImages = array();
			if($isUpdate && isset($_REQUEST[$fieldName = $ff->getName().'___new']) && is_array($_REQUEST[$fieldName])){
				$fieldNameTitle = 'title_'.$fieldName;
				foreach($_REQUEST[$fieldName] as $j => $src){
					if(file_exists($path = $_SERVER['DOCUMENT_ROOT'].$src)){
						//делаем id
						$i = 1;
						while($tl->getById($id = "i$i"))$i++;
						
						$arImg = array();
						$arPrev = array();
						//добавляем поля в форму
						$values[$name = $ff->getName().'_IMAGE_ID_'.$id] = $src;
						foreach($formats as $param){
							$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
							$e->setAttribute('name',$name);
							$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getGalleryImageName($id),$e->getAttribute('uri')));
							if($param->hasAttribute('preview')) $arPrev[] = $e;
							else $arImg[] = $e;
						}
						
						if(count($arImg)
							&& ($src_rel = $ff->getImagePath(form::getURI($arImg[0])))
						){//добавляем в XML список картинок
							if(strpos($src_rel,PATH_ROOT)===0)
								$src_rel = substr($src_rel,strlen(PATH_ROOT));
							$arNewImages[$id] = $tl->append(array(
									'id'=>$id
									,'src'=>$src_rel
								));
							//превью
							if(count($arPrev)
								&& ($src_rel = $ff->getImagePath(form::getURI($arPrev[0])))
							){
								if(strpos($src_rel,PATH_ROOT)===0)
									$src_rel = substr($src_rel,strlen(PATH_ROOT));
								$arNewImages[$id]->appendChild($tl->getXML()->createElement('preview',array(
										'src'=>$src_rel
									)));
							}
							
							if(isset($_REQUEST[$fieldNameTitle])
								&& isset($_REQUEST[$fieldNameTitle][$j])
								&& $_REQUEST[$fieldNameTitle][$j]
							){
								$arNewImages[$id]->setAttribute('title',mb_substr($_REQUEST[$fieldNameTitle][$j],0,127));
							}
						}
					}
				}
			}
			
			//заполняем форму текущими картинками
			$rowsToDelete = array();
			foreach($tl as $img){
				$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
				if($isUpdate
					&& !isset($_REQUEST[$ff->getName()][$fieldName])
					&& !isset($arNewImages[$img->getAttribute('id')]))
				{ //определяем картинки для удаления
					$values[$fieldName] = jpgScheme::VALUE_DELETE;
					$rowsToDelete[] = $img;
				}
				foreach($formats as $param){
					if(!$isUpdate && !$param->hasAttribute('preview')) continue;
					$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
					$e->setAttribute('name',$fieldName);
					$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getGalleryImageName($img->getAttribute('id')),$e->getAttribute('uri')));
					if(!$isUpdate && $img->hasAttribute('title'))
						$e->setAttribute('title',$img->getAttribute('title'));
				}
			}
			//обновляем данные
			if($isUpdate){
				//удаляем
				foreach($rowsToDelete as $e) $e->parentNode->removeChild($e);
				//обновляем тайтлы
				if(isset($_REQUEST[$fieldNameTitle = 'title_'.$ff->getName()])
					&& is_array($_REQUEST[$fieldNameTitle])
				) foreach($_REQUEST[$fieldNameTitle] as $str => $title){
					if(preg_match('/'.$ff->getName().'_IMAGE_ID_(i[0-9]+)/',$str,$m)
						&& ($e = $tl->getById($m[1]))
					){
						if($title) $e->setAttribute('title',$title);
						else $e->removeAttribute('title');
					}
				}
				//пересортировываем
				$sortOrder = isset($_REQUEST[$ff->getName().'_sort_order']) ? explode(',',$_REQUEST[$ff->getName().'_sort_order']) : array();
				foreach($sortOrder as $i => $str){
					if(preg_match('/id(i[0-9]+)/',$str,$m)
						&& ($e = $tl->getById($m[1]))
					) $tl->move($e,$i+1);
					elseif(preg_match('/new[0-9]+/',$str)
						&& ($e = array_shift($arNewImages))
					) $tl->move($e,$i+1);
				}
			}
			$tl->getXML()->save();
		}
		$ff->getRootElement()->setAttribute('target',$ff->getURI());
		$ff->getRootElement()->removeAttribute('uri');
	}
	return $values;
}
function updateImagesSize($form){
	$formFields = $form->getFields('@type="image"');
	foreach($formFields as $ff){
		$scheme = new xmlScheme();
		if(($n = $scheme->getNode($ff->getRootElement()->getAttribute('target')))
			&& $n instanceof DOMElement
		){
			$tl = new taglist($n,'img');
			foreach($tl as $img){
				if($img->hasAttribute('width') && $img->hasAttribute('height')) continue;
				$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
				if(($res = $ff->query('param[@name="'.htmlspecialchars($fieldName).'"]'))
					&& ($e = $res->item(0))
					&& ($src_rel = $ff->getImagePath(form::getURI($e)))
				){
					list($w,$h) = getimagesize($src_rel);
					if($w && $h){
						$img->setAttribute('width',$w);
						$img->setAttribute('height',$h);
					}
					if(($prv = $tl->getXML()->query('preview',$img)->item(0))
						&& ($e = $res->item(1))
						&& ($src_rel = $ff->getImagePath(form::getURI($e)))
					){
						list($w,$h) = getimagesize($src_rel);
						if($w && $h){
							$prv->setAttribute('width',$w);
							$prv->setAttribute('height',$h);
						}
					}
				}
			}
			$tl->getXML()->save();
		}
	}
}
}
?>