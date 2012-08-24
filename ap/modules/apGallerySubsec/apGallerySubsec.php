<?
class apGallerySubsec extends apArticles{
function onEdit($action){
	global  $_struct;
	$form = $this->getForm($action);
	$ff = $form->getField('sec');
	$subsecXML = $_struct->query('./sec',$_struct->getElementById($this->getSection()->getId()));
	foreach($subsecXML as $sec)
		$ff->addOption($sec->getAttribute('id'),$sec->getAttribute('title'));
	parent::onEdit($action);
	
}
function onNew($action){
	global $_struct;
	$form = $this->getForm($action);
	$ff = $form->getField('sec');
	$subsecXML = $_struct->query('./sec',$_struct->getElementById($this->getSection()->getId()));
	foreach($subsecXML as $sec)
		$ff->addOption($sec->getAttribute('id'),$sec->getAttribute('title'));
	parent::onNew($action);
}
function onUpdate($action){
	if($row = $this->getRow()){
		$mysql = new mysql();
		$res = $mysql->query('select * from `'.$mysql->getTableName('secgallery_relation').'` where `aid`='.(int)$row,true);
		if(count($res) == 0)
			$mysql->insert('secgallery_relation',array('aid'=>$row));
		
		return parent::onUpdate($action);
	}
}
function onDelete($action){
	if(($row = $this->getRow())
		&& parent::onDelete($row)
	){
		if(!is_array($row)) $row = array($row);
		$mysql = new mysql();
		$mysql->query('delete from `'.$mysql->getTableName('secgallery_relation').'` where `aid` in('.implode(',',$row).')');
		return true;
	}
}
}
?>