<?
class fFieldGallery{
private $ff;
private $formats;
private $sortOrder;
function __construct(formField $ff){
	$this->ff = $ff;
	if($this->ff->getRootElement()->hasAttribute('_ffg')){//Нельзя создавать больше одного объекта для одного поля
		throw new Exception('Duplicate fFieldGallery object for "'.$this->ff->getName().'" field');
	}
	$this->formats = array();
	$xml = $ff->getXML();
	$res = $xml->query('param',$ff->getRootElement());
	foreach($res as $param) $this->formats[] = $param->parentNode->removeChild($param);
	$this->ff->getRootElement()->setAttribute('_ffg','_ffg');
}
function fieldName($id){
	return $this->ff->getName().'_IMAGE_ID_'.$id;
}
function imageId($fieldName){
	$fieldName = substr($fieldName,strlen($this->ff->getName()));
	if(preg_match('/IMAGE_ID_([0-9]+)$/',$fieldName,$m))
		return intval($m[1]);
}
function deleteImages($id_article){
	$mysql = new mysql();
	if(
		($rs = $mysql->query('select `id` from `'.$mysql->getTableName('articles_images').'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"'))
	)while($r = mysql_fetch_assoc($rs)){
		foreach($this->formats as $param){
			$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
			$e->setAttribute('name',$fieldName);
			$e->setAttribute('uri',str_replace('%IMG_ID%',$r['id'],$e->getAttribute('uri')));
		}
	}
	$this->ff->removeImageFiles();
	$mysql->query('delete from `'.$mysql->getTableName('articles_images').'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"');
}
function prepareEdit($id_article){
	$this->load($id_article,false);
}
function prepareUpdate($id_article,$values){
	$this->sortOrder=isset($values[$this->ff->getName().'_sort_order']) ? explode(',',$values[$this->ff->getName().'_sort_order']) : array();
	$values = array_merge($values,$this->load($id_article,isset($values[$this->ff->getName()]) ? $values[$this->ff->getName()] : array()));
	$values = $this->addNew($id_article,$values);
	return $values;
}
private function load($id_article,$values){
	$mysql = new mysql();
	$isUpdate = is_array($values);
	$arId = $isUpdate ? array_keys($values) : array();
	foreach($arId as $i => $fieldName)
		if($id = $this->imageId($fieldName)) $arId[$i] = $id;
		else unset($arId[$i]);
	if(($rs = $mysql->query('select `id` from `'.$mysql->getTableName('articles_images').'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"'
			.(count($arId) ? ' and `id` not in('.implode(',',$arId).')' : null).' order by `sort`')
		)
		&& mysql_num_rows($rs)
	){
		$rowsToDelete = array();
		while($r = mysql_fetch_assoc($rs)){
			$fieldName = $this->fieldName($r['id']);
			if($isUpdate){
				$values[$fieldName] = jpgScheme::VALUE_DELETE;
				$rowsToDelete[] = $r['id'];
			}
			foreach($this->formats as $param){
				if(!$isUpdate && !$param->hasAttribute('preview')) continue;
				$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
				$e->setAttribute('name',$fieldName);
				$e->setAttribute('uri',str_replace('%IMG_ID%',$r['id'],$e->getAttribute('uri')));
			}
		}
		if($isUpdate && count($rowsToDelete))
			$mysql->query('delete from `'.$mysql->getTableName('articles_images').'` where id in('.implode(',',$rowsToDelete).')');
	}
	if($isUpdate){
		$v = array();
		foreach($this->sortOrder as $i => $str) if(preg_match('/id([0-9]+)/',$str,$m)) $v[] = '('.$m[1].','.($i+1).')';
		if(count($v)){
			$mysql->query('insert into `'.$mysql->getTableName('articles_images').'` (`id`,`sort`) values '.implode(',',$v).' on duplicate key update `sort`=values(`sort`)');
		}
	}
	return $values;
}
private function addNew($id_article,$values){
	if(isset($values[$fieldName = $this->ff->getName().'___new']) && is_array($values[$fieldName])){
		$sortOrder = array();
		foreach($this->sortOrder as $i => $str) if(preg_match('/new[0-9]+/',$str)) $sortOrder[] = $i+1;
		$mysql = new mysql();
		$counter = 0;
		foreach($values[$fieldName] as $src){
			if(file_exists($path = $_SERVER['DOCUMENT_ROOT'].$src)){
				if($mysql->insert('articles_images',array(
					'field_name'=>'"'.$this->ff->getName().'"',
					'id_article'=>$id_article,
					'sort'=>isset($sortOrder[$counter]) ? $sortOrder[$counter] : '0'
				))){
					$img_id = $mysql->getInsertId();
					$name = $this->fieldName($img_id);
					$values[$name] = $src;
					foreach($this->formats as $param){
						$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
						$e->setAttribute('name',$name);
						$e->setAttribute('uri',str_replace('%IMG_ID%',$img_id,$e->getAttribute('uri')));
					}
					$counter++;
				}
			}
		}
	}
	return $values;
}
}
?>