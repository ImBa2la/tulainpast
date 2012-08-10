<?
class apGuestBook extends apArticles{
function fillSelect($form){
	if($ff = $form->getField('uid')){
		$mysql = new mysql();
		$q = 'select `id`,`name`,`surname` from `'.$mysql->getTableName('users').'` ';
		$rs = $mysql->query($q);
		while($r = mysql_fetch_assoc($rs))
			$ff->addOption($r['id'],$r['name'].' '.$r['surname']);
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
	$_REQUEST['aid'] = $mysql->getNextId('articles');
	if(parent::onAdd($action)) 
		return true;
}
function onDelete($action){
	if(($row = $this->getRow())
		&& parent::onDelete($row)
	){
		if(!is_array($row)) $row = array($row);
		$mysql = new mysql();
		$mysql->query('delete from `'.$mysql->getTableName('articles_guestbook').'` where `aid` in ('.implode(',',$row).')');
		return true;
	}
}
function install(){
	if(parent::install()){
		$mysql = new mysql();
		$table = 'articles_guestbook';
		if(!$mysql->hasTable($table)){
			$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
			  `id` int(9) unsigned NOT NULL AUTO_INCREMENT,
			  `aid` int(9) unsigned NOT NULL,
			  `uid` int(9) unsigned NOT NULL,
			  PRIMARY KEY (`id`,`aid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8');	
		}
		return true;
	}
}
}
?>