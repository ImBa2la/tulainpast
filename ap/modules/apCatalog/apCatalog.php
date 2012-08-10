<?
class apCatalog extends apArticles{
function getList(){
	$mysql = new mysql();
	$rl = parent::getList();
	$rl->setQueryParams(array(
		'cols' => 'art.*,ctl.*'
		,'alias' => 'art'
		,'join' => 'left join `'.$mysql->getTableName('calalog').'` AS `ctl` ON art.id=ctl.id_article'
	));
	return $rl;
}
function onNew($action){
	parent::onNew($action);
	$this->getSection()->getTemplate()->addTemplate('../../modules/'.__CLASS__.'/tpl.xsl');
}
function onEdit($action){
	parent::onEdit($action);
	$this->getSection()->getTemplate()->addTemplate('../../modules/'.__CLASS__.'/tpl.xsl');
}
function setRow($v){
	setParam('id_article',$v);
	parent::setRow($v);
}
function onDelete($action){
	if(($row = $this->getRow())
		&& parent::onDelete($row)
	){
		if(!is_array($row)) $row = array($row);
		$mysql->query('delete from `'.$mysql->getTableName('catalog').'` where `id_article` in('.implode(',',$row).')');
		return true;
	}
}
}
?>