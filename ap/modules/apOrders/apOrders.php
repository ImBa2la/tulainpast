<?
class apOrders extends module{
private $rl;
private $table = 'order';
private $tableItem = 'order_item';
function getRow(){
	if($row = param('row')){
		if(is_array($row)) foreach($row as $i => $r) $row[$i] = intval($r);
		else $row = intval($row);
	}
	return $row;
}
function setRow($v){
	setParam('row',$v);
}
function getMessSessionName(){
	return $this->getSection()->getId().'_'.$this->getId();
}
function setMessage($mess){
	if($mess){
		if(!session_id() && !headers_sent()) session_start();
		$_SESSION['apMess'][$this->getMessSessionName()] = $mess;
	}
}
function getMessage(){
	if(!session_id() && !headers_sent()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'delete_ok':
			$mess = 'Заказ удален'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null){
	$param = array();
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function getList(){
	if(!$this->rl){
		$xml = $this->getSection()->getXML();
		if($list_element = $xml->query('rowlist[@id="list"]',$this->getRootElement())->item(0)){
			$mysql = new mysql();
			$this->rl = new mysqllist($list_element,array(
				'table' => $this->table,
				'alias' => 'o',
				'cols' => 'o.*,SUM(i.price*i.quantity) AS `sum`',
				'join' => 'LEFT JOIN `'.$mysql->getTableName($this->tableItem).'` AS i ON o.id=i.id_order',
				'group' => 'o.id',
				'sortcontrol' => false,
				'order' => 'o.`date` desc',
				'page' => param('page')
			));
			$this->rl->addDateFormat('date','d.m.y');
			$this->rl->addFloatFormat('sum');
			$this->rl->build();
		}
	}
	return $this->rl;
}
function getOrderXML($id){
	$mysql = new mysql();
	if(($id = intval($id))
		&& ($rs = $mysql->query('select o.*, u.id as `uid` from `'.$mysql->getTableName($this->table).'` as o left join `'.$mysql->getTableName('customers').'` as u on o.id_customer = u.id  where o.id='.$id))
		&& ($o = mysql_fetch_assoc($rs))
		&& ($rs = $mysql->query('select *,`quantity`*`price` as `sum` from `'.$mysql->getTableName($this->tableItem).'` where id_order='.$id))
	){
		$xml = new xml();
		$eOrder = $xml->appendChild($xml->createElement('order',array(
				'id'=>$o['id']
				,'date'=>date('d.m.y',strtotime($o['date']))
				,'user'=>$o['name']
				,'address'=>$o['address']
			)));
		if($o['uid']) $eOrder->setAttribute('uid',$o['uid']);
		if($o['desc']) $eOrder->appendChild($xml->createElement('desc',null,$o['desc']));
		$sum = 0;
		while($r = mysql_fetch_assoc($rs)){
			$eOrder->appendChild($xml->createElement('beer',array(
					'title'=>$r['title']
					,'price'=>number_format($r['price'],2,',',' ')
					,'quantity'=>$r['quantity']
					,'sum'=>number_format($r['sum'],2,',',' ')
				)));
			$sum+= $r['sum'];
		}
		$eOrder->setAttribute('sum',number_format($sum,2,',',' '));
		return $xml;
	}
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		$action = param('action');
		$row = $this->getRow();
		$mysql = new mysql();
		switch($action){
			case 'delete':
				if($row){
					$this->deleteItem($row);
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'edit':
				if($row
					&& ($xml = $this->getOrderXML($row))
				){
					$_out->addSectionContent($xml);
					$this->getSection()->getTemplate()->addTemplate('../../modules/'.__CLASS__.'/tpl.xsl');
				}
				break;
			default:
				if($rl = $this->getList()){
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function deleteItem($row){
	if(!is_array($row)) $row = array($row);
	$ar = array();
	foreach($row as $id) if($id = intval($id)) $ar[] = $id;
	if(count($ar)){
		$mysql = new mysql();
		$mysql->query('DELETE FROM `'.$mysql->getTableName($this->table).'` WHERE id in ('.implode(',',$ar).')');
		$mysql->query('DELETE FROM `'.$mysql->getTableName($this->tableItem).'` WHERE id_order in ('.implode(',',$ar).')');
	}
}
function install(){
	$mysql = new mysql();
	if(!$mysql->hasTable($this->table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`id_customer` int(10) unsigned DEFAULT NULL,
`date` datetime DEFAULT NULL,
`status` varchar(127) DEFAULT NULL,
`name` varchar(255) DEFAULT NULL,
`phone` varchar(255) DEFAULT NULL,
`email` varchar(127) DEFAULT NULL,
`region` varchar(127) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`porch` varchar(31) DEFAULT NULL,
`porch_access` varchar(31) DEFAULT NULL,
`floor` varchar(31) DEFAULT NULL,
`comment` text,
`isConfirm` tinyint(1) unsigned NOT NULL DEFAULT "0",
PRIMARY KEY (`id`),
KEY `customer` (`id_customer`)
)');
	}
	if(!$mysql->hasTable($this->tableItem)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`id_order` int(10) unsigned NOT NULL,
`id_catalog` int(10) unsigned NOT NULL,
`title` varchar(255) DEFAULT NULL,
`quantity` tinyint(4) DEFAULT NULL,
`price` float(9,2) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `order` (`id_order`)
)');
	}
	$xml_data = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('list');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$xml_sec->elementIncludeTo($e,$this->getRootElement());
	}
	$xml_sec->save();
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$moduleName = $this->getName();
			if(preg_match('/ap([A-Z].*)/',$moduleName,$m))
				$moduleName = strtolower($m[1]);
			$modules->add($moduleName,$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function settings($action){

}
}
?>