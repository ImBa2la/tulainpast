<?php
class guestbookList extends articles{
function getList($tb, $tagNameList, $listQueryFields){
	global $_out;
	$tb->setQueryFields($listQueryFields);
	if($xml = $tb->listToXML($tagNameList,'active=1 and section="'.$this->getSection()->getId().'" and module = "'.$this->getId().'"')){
		$_out->xmlIncludeTo($xml,'/page/section');
		$id = array();
		$res = $_out->query('/page/section/'.$tagNameList.'//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$authors = $this->getAuthor($id);
		//vdump($authors,false);
		foreach($res as $row){
			$this->setAuthor($row,$authors);			
		}
	}
}

function setAuthor($e,$a){
	global $_out;
	if($e && is_array($a)
		&& ($id = $e->getAttribute('id'))
		&& isset($a[$id])
		&& is_array($a[$id])
	){
		$e->appendChild($_out->createElement('author',array('uid'=>$a[$id]['id']),$a[$id]['name'].' '.$a[$id]['surname']));
	}
	
}
function getAuthor($aid){
	if(!is_array($aid)) $aid = array($aid);
	if(!count($aid)) return;
	$res = array();
	$mysql = new mysql();
	if($rs = $mysql->query('select `gb`.`aid`,`users`.`id`, `users`.`name`,`users`.`surname`,`users`.`login` from `'.$mysql->getTableName('articles_guestbook').'` as `gb` left join `'.$mysql->getTableName('users').'` AS `users` ON `gb`.`uid` = `users`.`id`   where `gb`.`aid` in ('.implode(',',$aid).')')){
		while($r = mysql_fetch_assoc($rs))
			$res[$r['aid']] = $r;
	}
	return $res;
}
}
?>