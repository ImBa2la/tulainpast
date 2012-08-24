<?
class apCatalog extends apArticles{
protected $table = 'catalog';
protected $tableImages = 'catalog_images';
function getForm($action){
	if($form = parent::getForm($action)){
		if($ff = $form->getField('id_currency')){
			$mysql = new mysql();
			if($rs = $mysql->query('select * from `'.$mysql->getTableName('currency').'` where `active`=1')){
				while($r = mysql_fetch_assoc($rs)) $ff->addOption($r['id'],$r['title']);
			}
		}
	}
	return $form;
}
function getList(){
	$rl = parent::getList();
	$rl->addFloatFormat('price');
	$cond['str'] = '';
	$cond['arr'] = array();
	if(is_array($c = param('cond'))){
		$xml = $this->getSection()->getXML();
		$le = $this->query('rowlist[@id="article_list"]')->item(0);
		$filterFields = $xml->query('filter/field',$le);
		$cond['logic'] = $xml->evaluate('string(filter/@logic)',$le);
		foreach($filterFields as $f){
			switch(strtolower($f->getAttribute('operator'))){
				case 'like':	$op = ' LIKE "%{VALUE}%"'; break;
				case 'lt':		$op = ' < "{VALUE}"'; break;
				case 'gt':		$op = ' > "{VALUE}"'; break;
				case 'lte':		$op = ' <= "{VALUE}"'; break;
				case 'gte':		$op = ' >= "{VALUE}"'; break;
				default:		$op = ' = "{VALUE}"'; #equal
			}
			if($c[$f->getAttribute('name')] && $c[$f->getAttribute('name')] != $f->getAttribute('label'))
				$cond['arr'][] = '`'.$f->getAttribute('name').'`'.str_replace('{VALUE}', $c[$f->getAttribute('name')], $op);
		}
		$cond['str'] = implode(
				' '.(strtolower($cond['logic']) == 'or'?'OR':'AND').' '
				,$cond['arr']
			);
	}
			
	$order = array('col' => 'sort','order' => 'desc');
	if(is_array($o = param('order'))){
		$order = array(
			'col' => $o[0],
			'order' => $o[1]
		);
	}	
	$rl->setOrder($order['col'],$order['order']);
	$params = $rl->setQueryParams(array());
	if($cond['str']) $params['cond'] .= ' AND '.$cond['str'];
	$params['sortcontrol'] = false;
	$params['order'] = '`'.$order['col'].'` '.$order['order'];
	$params['agregate'] = 'count(*) as count';
	$rl->setQueryParams($params);
	
	return $rl;
}
function showList(){
	global $_out;
	if($rl = $this->getList()){
		$rl->build();
		if(($numRows = $rl->getNumPages()) < param('page')) #condition for overflow pages
			header('Location: '.str_replace('page='.param('page'),'page='.$numRows ,$_SERVER['REQUEST_URI']) );
		$urlParams = array();
		if(is_array($c = param('cond')))
			foreach($c as $k=>$v)
				$urlParams['cond['.$k.']'] = $v;
		$rl->setFilter($c);
		$rl->getRootElement()->setAttribute('sortUri',ap::getUrl($urlParams));
		$_out->addSectionContent($rl->getRootElement());
	}
}

function install(){
	$mysql = new mysql();
	if(!$mysql->hasTable($this->table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName('catalog').'` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`section` varchar(63) DEFAULT NULL,
		`module` varchar(15) DEFAULT NULL,
		`date` datetime DEFAULT NULL,
		`title` varchar(55) NOT NULL,
		`announce` text,
		`article` text,
		`price` double(10,2) unsigned NOT NULL DEFAULT "0.00",
		`id_currency` int(10) unsigned NOT NULL DEFAULT "1",
		`active` tinyint(1) unsigned DEFAULT NULL,
		`sort` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8');	
	}
	if(parent::install()){
		return true;
	}
}
}
?>