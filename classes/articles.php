<?
class articles extends module{
	protected $table = 'articles';
	protected $tableImg = 'articles_images';
	protected $dirImg = 'articles';
	function run(){
	global $_out;
	if($row = intval(param('row'))){
		if(($xml = $this->getDetailXML($this->getListProp('tagNameText'),$row))
			&& ($e = $_out->xmlIncludeTo($xml,'/page/section'))
			&& ($title = $_out->evaluate('string(title)',$e))
		) $this->setDetailMetaTitle($title);
	}elseif($xml = $this->getListXML($this->getListProp('tagNameList'))){
		$_out->xmlIncludeTo($xml,'/page/section');
		$this->setListMetaTitle($this->getSection()->getTitle());
	}
}
function getTable(){
	$tb = new mysqlToXml($this->table);
	$tb->addDateFormat('date','d.m.Y');
	$tb->setCustomFieldList($this->f('id').'
,'.$this->f('section').'
,'.$this->f('module').'
,'.$this->f('date').'
,'.$this->f('title').'
,'.$this->f('announce').'
,IF(ISNULL('.$this->f('article').') OR '.$this->f('article').'="",'.$this->f('announce').','.$this->f('article').') AS `article`
,'.$this->f('active').'
,'.$this->f('sort'));
	return $tb;
}
function getTableAlias(){
	return null;
}
function f($name){
	$alias = $this->getTableAlias();
	return '`'.($alias ? $alias.'`.`' : null).$name.'`';
}

/**
* Список статей
*/
function getListProp($name){
	$v = $this->evaluate('string(list/@'.$name.')');
	switch($name){
		case 'tagNameText': if(!$v) return 'articlesRow';
		case 'tagNameList': if(!$v) return 'articles';
	}
	return $v;
}
function setListMetaTitle($v){
	global $_out;
	$_out->setMeta('title',$v);
}
function getListTable(){
	$tb = $this->getTable();
	$tb->setAttrFields(array('id','date'));
	$tb->setQueryFields(array('date','title','article'));
	
	if($v = $this->getListProp('coll'))
		$tb->setRowSize($v);
	if($v = $this->getListProp('pageParam'))
		$tb->setPageParamName($v);
	$tb->setPageSize($this->getListProp('pageSize'));
	
	$listQueryFields = array('date','title','announce');
	if($this->getListProp('includeContent'))
		$listQueryFields[] = 'article';
	$tb->setQueryFields($listQueryFields);
	
	if(!$tb->getPageSize()) $tb->setPageSize(10);
	return $tb;
}
function getListCondition(){
	return $this->f('active').'=1 and '
			.$this->f('section').'="'.$this->getSection()->getId().'" and '
			.$this->f('module').'="'.$this->getId().'"';
}
function getListXML($tagName){
	$v = $this->getListProp('sort');
	if($xml = $this->getListTable()->listToXML($tagName,$this->getListCondition(),'sort '.($v ? $v : 'asc'))){
		$id = array();
		$res = $xml->query('//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true);
		foreach($res as $row) $this->setImages($row,$img);
		return $xml;
	}
}

/**
* Отдельная статья
*/
function setDetailMetaTitle($v){
	global $_out;
	$_out->setMeta('title',$v);
}
function getDetailTable(){
	$tb = $this->getTable();
	$tb->setAttrFields(array('id','date'));
	$tb->setQueryFields(array('date','title','article'));
	return $tb;
}
function getDetailCondition($row){
	return $this->f('active').'=1 and '
			.$this->f('section').'="'.$this->getSection()->getId().'" and '
			.$this->f('module').'="'.$this->getId().'" and '
			.$this->f('id').'= "'.$row.'"';
}
function getDetailXML($tagName,$row){
	if($xml = $this->getDetailTable()->rowToXML($tagName,$this->getDetailCondition($row),$val)){
		$this->setImages($xml->de(),$this->getImages($val['id'],true));
		return $xml;
	}
}

/**
* Анонсы
*/
function getAnnounceTable(){
	$tb = $this->getTable();
	$tb->setPageParamName('xxx');
	$tb->setPageSize(3);
	$tb->setAttrFields(array('id','date'));
	$tb->setQueryFields(array('date','title','announce'));
	return $tb;
}
function announce($tagname,$sort = null,$size = null){
	global $_out;
	$tb = $this->getAnnounceTable();
	if($size) $tb->setPageSize($size);
	if(($xml = $tb->listToXML($tagname
				,$this->f('active').'=1 and '
					.$this->f('section').'="'.$this->getSection()->getId().'" and '
					.$this->f('module').'="'.$this->getId().'"'
				,$sort
			))
		&& ($e = $_out->xmlInclude($xml))
	){
		$id = array();
		$res = $_out->query('.//row[@id]',$e);
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true);
		foreach($res as $row) $this->setImages($row,$img,true);
	}
}
function onPageReady($params = null){
	if(is_array($params) && isset($params['tagname']))
		$this->announce($params['tagname'],$params['sort'],$params['size']);
}

/**
* Картинки
*/
function getImages($id,$preview = false){
	global $_out;
	if(!is_array($id)) $id = array($id);
	if(!count($id)) return;
	$res = array();
	$mysql = new mysql();
	if($rs = $mysql->query('SELECT img.*,art.section,art.module
FROM `'.$mysql->getTableName($this->tableImg).'` AS img
LEFT JOIN `'.$mysql->getTableName($this->table).'` AS art ON art.id=img.id_article
WHERE `id_article` IN ('.implode(',',$id).') AND img.`active`=1
ORDER BY `id_article`,`sort`')){
		while($r = mysql_fetch_assoc($rs)){
			$v = array();
			if(file_exists($path = 'userfiles/'.$this->dirImg.'/'.$r['section'].'/'.$r['id'].'.jpg')){
				list($width, $height) = getimagesize($path);
				$v['img'] = $_out->createElement('img',array(
					'name' => $r['field_name'],
					'src' => $path,
					'width' => $width,
					'height' => $height
				));
				if($r['title']) $v['img']->setAttribute('alt',$r['title']);
			}

			if($preview && file_exists($path = 'userfiles/'.$this->dirImg.'/'.$r['section'].'/'.$r['id'].($preview ? '_preview' : null).'.jpg')){
				list($width, $height) = getimagesize($path);
				$v['prv'] = $_out->createElement('preview',array(
					'name' => $r['field_name'],
					'src' => $path,
					'width' => $width,
					'height' => $height
				));
				if($r['title']) $v['prv']->setAttribute('alt',$r['title']);
			}

			if(count($v)) $res[$r['id_article']][] = $v;
		}
	}
	return $res;
}
function setImages($e,$img,$singleOnly = false){
	if($e && is_array($img)
		&& ($id = $e->getAttribute('id'))
		&& isset($img[$id])
		&& is_array($img[$id])
	){
		$xml = new xml($e);
		foreach($img[$id] as $i){
			if(isset($i['img']))$pic = $e->appendChild($xml->importNode($i['img'],true));
			if(isset($i['prv']))$pic->appendChild($xml->importNode($i['prv'],true));
			if($singleOnly) break;
		}
	}
}
}
?>