<?php
class guestbookList extends articles{
function getListXML($tagName){
	$v = $this->getListProp('sort');
	if($xml = $this->getListTable()->listToXML($tagName,$this->getListCondition(),'sort '.($v ? $v : 'asc'))){
		$id = array();
		$res = $xml->query('//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$authors = $this->getAuthor($id,true);
		foreach($res as $row) $this->setAuthor($row,$authors,$xml);			
		
		return $xml;
	}
}
function setAuthor($e,$a,$xml){
	if($e && is_array($a)
		&& ($id = $e->getAttribute('id'))
		&& isset($a[$id])
		&& is_array($a[$id])
	){
		$e->appendChild($xml->createElement('author',array('uid'=>$a[$id]['id']),$a[$id]['name'].' '.$a[$id]['surname']));
	}
	
}
function getAuthor($aid){
	if(!is_array($aid)) $aid = array($aid);
	if(!count($aid)) return;
	$res = array();
	$mysql = new mysql();
	$q = 'select `gb`.`aid`,`users`.`id`, `users`.`name`,`users`.`surname`,`users`.`login` from `'.$mysql->getTableName('articles_guestbook').'` as `gb` left join `'.$mysql->getTableName('users').'` AS `users` ON `gb`.`uid` = `users`.`id`   where `gb`.`aid` in ('.implode(',',$aid).')';
	if($rs = $mysql->query($q)){
		while($r = mysql_fetch_assoc($rs))
			$res[$r['aid']] = $r;
	}
	return $res;
}
}
?>