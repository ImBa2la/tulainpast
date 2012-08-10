<?
class catalog extends module{
function run(){
	global $_out,$_params;
	$xml = null;
	if($g_uuid = array_shift($_params))
		$xml = $this->getProducts($g_uuid);
	else
		$xml = $this->getCategories();
	
	if($xml)
		$_out->addSectionContent($xml);
}
function getProducts($g_uuid){
	$mysql = new mysql;
	if($g_uuid
		&& ($rs = $mysql->query('SELECT g.title AS `group`,c.* FROM `'.$mysql->getTableName('catalog').'` AS c
LEFT JOIN `'.$mysql->getTableName('catalog_groups').'` AS g ON c.g_uuid=g.uuid
WHERE c.g_uuid="'.addslashes($g_uuid).'"'))
		&& mysql_num_rows($rs)
	){
		$xml = new xml(null,'products',false);
		$root = $xml->de();
		while($r = mysql_fetch_assoc($rs)){
			if(!$root->hasAttribute('group'))
				$root->setAttribute('group',$r['group']);
			$prd =$root->appendChild($xml->createElement('prd',array('id' => $r['id']
				,'title' => trim($r['title'])
				,'price' => number_format($r['price'],0,',',' ')
			)));
			if(!$r['active']) $prd->setAttribute('notexists','notexists');
		}
		return $xml;
	}
}
function getCategories(){
	global $_sec;
	$mysql = new mysql;
	if($rs = $mysql->query('SELECT p.id AS `pid`,count(c.id) as `num`,g.*
FROM `'.$mysql->getTableName('catalog').'` AS c
LEFT JOIN `'.$mysql->getTableName('catalog_groups').'` AS g ON c.g_uuid=g.uuid
LEFT JOIN `'.$mysql->getTableName('catalog_groups').'` AS p ON g.g_uuid=p.uuid
WHERE (c.section="'.$_sec->getId().'" AND c.active=1)
GROUP BY g.id
ORDER BY g.g_uuid,g.sort')
	){
		$xml = new xml(null,'groups',false);
		while($r = mysql_fetch_assoc($rs)){
			$this->addGroup($r,$xml);
		}
		$this->groups($xml);
		return $xml;
	}
}
function groups($xml){
	$mysql = new mysql;
	do{
		$ar = array();
		$res = $xml->query('/groups/group[@pid]');
		foreach($res as $e) $ar[$e->getAttribute('pid')] = true;
		if(count($ar)
			&& ($rs = $mysql->query('SELECT p.id AS `pid`,g.* FROM `'.$mysql->getTableName('catalog_groups').'` AS g
LEFT JOIN `'.$mysql->getTableName('catalog_groups').'` AS p ON g.g_uuid=p.uuid
WHERE g.id IN ('.implode(',',array_keys($ar)).')
GROUP BY g.id
ORDER BY g.g_uuid,g.sort'))
		)while($r = mysql_fetch_assoc($rs)){
			$this->addGroup($r,$xml);
		}
	}while($res->length);
}
function addGroup($r,$xml){
	$group = $xml->createElement('group',array('id' => $r['id'],'title' => trim($r['title']),'uuid' => $r['uuid']));
	if($r['num']) $group->setAttribute('num',$r['num']);
	if($r['pid']){
		if($e = $xml->query('/groups//group[@id="'.$r['pid'].'"]')->item(0))
			$group = $e->appendChild($group);
		else{
			$group = $xml->de()->appendChild($group);
			$group->setAttribute('pid',$r['pid']);
		}
	}else
		$group = $xml->de()->appendChild($group);
	if($group->parentNode){
		$res = $xml->query('/groups/group[@pid="'.$r['id'].'"]');
		foreach($res as $e){
			$e = $group->appendChild($e);
			$e->removeAttribute('pid');
		}
		return $group;
	}
}
}
?>