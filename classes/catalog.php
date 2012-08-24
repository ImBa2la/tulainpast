<?
class catalog extends articles{
protected $table = 'catalog';
protected $tableImg = 'catalog_images';
protected $dirImg = 'catalog';
function getTable(){
	$tb = parent::getTable();
	$tb->setJoins('
left join `'.$tb->getTableName('currency').'` AS `crr` ON crr.id=ctl.id_currency'
		,'ctl'
		,'`ctl`.`id`'
	);
	$tb->setCustomFieldList('`ctl`.*,`ctl`.price*`crr`.rate as `price`,`crr`.title as `currency`');
	$tb->addFloatFormat('price',2,'.');
	return $tb;
}
function getTableAlias(){
	return 'ctl';
}
function setListMetaTitle($v){
	if($this->getSection()->getParent() && $this->getSection()->getParent()->getId()!='catalog')
		$v = $this->getSection()->getParent()->getTitle().' '.$v;
	parent::setListMetaTitle($v);
}
function getListTable(){
	$tb = parent::getListTable();
	$tb->setAttrFields(array('id'));
	$tb->setQueryFields(array('title','announce','price'));
	$tb->addNl2Br('announce');
	//$tb->setGroupingByField('section');
	return $tb;
}
function getDetailTable(){
	$tb = parent::getDetailTable();
	$tb->setAttrFields(array('id'));
	$tb->setQueryFields(array('title','article','price'));
	return $tb;
}
function getDetailCondition($row){
	return $this->f('active').'=1 and '.$this->f('id').'= '.intval($row);
}
function getListCondition(){
	$ar = array($this->getSection()->getId());
	if($chn = $this->getSection()->getChildren())
		foreach($chn as  $sec) $ar[] = $sec->getId();
	return $this->f('active').'=1 and '
			.$this->f('section').' in ("'.implode('","',$ar).'")';
}
function announce($tagname,$flag,$title){
	global $_out;
	$tb = $this->getAnnounceTable();
	$tb->setPageSize(10);
	$tb->setAttrFields(array('id','section'));
	$tb->setQueryFields(array('title','announce'));
	if(($xml = $tb->listToXML($tagname
				,$this->f('active').'=1 and `ctl`.`'.$flag.'`=1'
				,$sort
			))
		&& ($e = $_out->xmlInclude($xml))
	){
		$e->setAttribute('title',$title);
		$id = array();
		$res = $_out->query('.//row[@id]',$e);
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true);
		foreach($res as $row) $this->setImages($row,$img,true);
	}
}
function onPageReady($params = null){
	$this->announce('catalog','isNew','Новинки');
	$this->announce('catalog','isSpecial','Эксклюзив');
	$this->announce('catalog','isSeller','Ходовые товары');
}
}
?>