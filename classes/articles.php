<?
class articles extends module{
function run(){
	global $_sec,$_out;
	$tb_art = new mysqlToXml('articles');
	$tb_art->customFieldList = '`id`
,`section`
,`module`
,`date`
,`title`
,`announce`
,IF(iSNULL(`article`) OR `article`="",`announce`,`article`) AS `article`
,`active`
,`sort`';
	if($this->getRootElement()->hasAttribute('coll')){
		$tb_art->setRowSize($this->getRootElement()->getAttribute('coll'));
	}
	$tagNameList = 'articles';
	$tagNameText = 'articlesRow';
	$listQueryFields = array('date','title','announce');
	$tb_art->addDateFormat('date','d.m.Y');
	
	if($list = $this->query('list')->item(0)){
		$tb_art->setPageSize($list->getAttribute('pageSize'));
		if($v = $list->getAttribute('sort'))
			$tb_art->sort_type = $v;
		if($v = $list->getAttribute('tagNameList'))
			$tagNameList = $v;
		if($v = $list->getAttribute('tagNameText'))
			$tagNameText = $v;
		if($v = $list->getAttribute('pageParam'))
			$tb_art->setPageParamName($v);
		if($list->hasAttribute('includeContent'))
			$listQueryFields[] = 'article';
	}
	if(!$tb_art->getPageSize()) $tb_art->setPageSize(10);
	$tb_art->setAttrFields(array('id','date'));
	if($row = (int)param('row')){
		$tb_art->setQueryFields(array('date','title','article'));//if(ISNULL(article),announce,article) as 
		$xml = $tb_art->rowToXML($tagNameText,'active=1 and section="'.$this->getSection()->getId().'" and module = "'.$this->getId().'" and id = "'.$row.'"',$val);
		if($xml){
			$_out->setMeta('title',$val['title']);
			$_out->xmlIncludeTo($xml,'/page/section');
			if($e = $_out->query('/page/section/'.$tagNameText)->item(0))
				$this->setImages($e,$this->getImages($val['id'],true));
		}
	}else{
		$this->getList($tb_art, $tagNameList, $listQueryFields);
	}
}
function getList($tb, $tagNameList, $listQueryFields){
	global $_out;
	$tb->setQueryFields($listQueryFields);
	if($xml = $tb->listToXML($tagNameList,'active=1 and section="'.$this->getSection()->getId().'" and module = "'.$this->getId().'"')){
		$_out->xmlIncludeTo($xml,'/page/section');
		$id = array();
		$res = $_out->query('/page/section/'.$tagNameList.'//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true);
		foreach($res as $row) $this->setImages($row,$img);
	}
}
function setImages($e,$img,$singleOnly = false){
	if($e && is_array($img)
		&& ($id = $e->getAttribute('id'))
		&& isset($img[$id])
		&& is_array($img[$id])
	)foreach($img[$id] as $i){
		if(isset($i['img']))$pic = $e->appendChild($i['img']);
		if(isset($i['prv']))$pic->appendChild($i['prv']);
		if($singleOnly) break;
	}
}
function getImages($id,$preview = false){
	global $_out;
	if(!is_array($id)) $id = array($id);
	if(!count($id)) return;
	$res = array();
	$mysql = new mysql();
	if($rs = $mysql->query('select * from `'.$mysql->getTableName('articles_images').'` where `id_article` in ('.implode(',',$id).') and `active`=1 order by `id_article`,`sort`')){
		while($r = mysql_fetch_assoc($rs)){
			$v = array();
			if(file_exists($path = 'userfiles/articles/'.$this->getSection()->getId().'/'.$r['id'].'.jpg')){
				list($width, $height) = getimagesize($path);
				$v['img'] = $_out->createElement('img',array(
					'src' => $path,
					'width' => $width,
					'height' => $height
				));
				if($r['title']) $v['img']->setAttribute('alt',$r['title']);
			}
			if($preview && file_exists($path = 'userfiles/articles/'.$this->getSection()->getId().'/'.$r['id'].($preview ? '_preview' : null).'.jpg')){
				list($width, $height) = getimagesize($path);
				$v['prv'] = $_out->createElement('preview',array(
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
function announce($tagname,$sort = null,$size = null){
	global $_out;
	$tb_art = new mysqlToXml('articles');
	if($sort) $tb_art->sort_type = $sort;
	$tb_art->setPageParamName('xxx');
	$tb_art->setPageSize($size ? $size : 3);
	$tb_art->addDateFormat('date','d.m.Y');
	$tb_art->setAttrFields(array('id','date'));
	$tb_art->setQueryFields(array('date','title','announce'));
	if($xml = $tb_art->listToXML($tagname,'active=1 and section="'.$this->getSection()->getId().'" and module = "'.$this->getId().'"')){
		$_out->xmlInclude($xml);
		$id = array();
		$res = $_out->query('/page/'.$tagname.'//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true);
		foreach($res as $row) $this->setImages($row,$img,true);
	}
}
function onPageReady($params = null){
	if(is_array($params) && isset($params['tagname']))
		$this->announce($params['tagname'],$params['sort'],$params['size']);
}
}
?>