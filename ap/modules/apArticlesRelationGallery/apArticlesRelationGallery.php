<?
class apArticlesRelationGallery extends apArticles{
function fillSelect($form){
	global $_struct;
	$ff = $form->getField('relation');
	$sections = $_struct->query('.//sec[@class="photo" or @class="photo_ext"]',$_struct->getElementById('apData'));
	$cond = array();
	foreach($sections as $section)
		$cond[] = $section->getAttribute('id');
	
	if($ff && (count($cond) > 0)){
		$mysql = new mysql();
		$q = 'select `id`,`title` from `'.$mysql->getTableName('articles').'` where `section` IN ("'.implode('","',$cond).'")';
		$rs = $mysql->query($q);
		while($r = mysql_fetch_assoc($rs))
			$ff->addOption($r['id'],$r['title']);
	}
}
function onEdit($action){
	$this->fillSelect($this->getForm($action));
	parent::onEdit($action);
	
}
function onNew($action){
	$this->fillSelect($this->getForm($action));
	parent::onNew($action);
}
function onAdd($action){
	$mysql = new mysql();
	$_REQUEST['aids'] = $mysql->getNextId('articles');
	if(parent::onAdd($action)) 
		return true;
}
function onDelete($action){
	if(($row = $this->getRow())
		&& parent::onDelete($row)
	){
		if(!is_array($row)) $row = array($row);
		$mysql = new mysql();
		$mysql->query('delete from `'.$mysql->getTableName('articles_relations').'` where `aidStory` in ('.implode(',',$row).')');
		return true;
	}
}
function install(){
	if(parent::install()){
		$mysql = new mysql();
		$table = 'articles_relations';
		if(!$mysql->hasTable($table)){
			$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
			`aidStory` int(10) NOT NULL,
			`aidGallery` int(10) default NULL,
			PRIMARY KEY  (`aidStory`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8');	
		}
		return true;
	}
}
}
?>