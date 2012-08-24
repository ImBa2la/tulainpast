<?
class orders extends module{
const table_order = 'order';
const table_item = 'order_item';
function run(){
	global $_out;
	customer::auth();
	if($xml = $this->getOrdersXML()){
		$_out->addSectionContent($xml);
	}
}
function getOrdersXML(){
	if(($cid = customer::getCustomerId()) && !is_nan($cid)){
		$tb = new mysqlToXml(orders::table_order);
		$tb->setCustomFieldList('o.*,sum(i.`quantity`*i.`price`) as `total`,sum(i.`quantity`) as `quantity`,count(i.`id`) as `num_items`');
		$tb->setJoins('left join `'.$tb->getTableName(orders::table_item).'` as i on i.`id_order`=o.`id`','o','o.`id`');
		$tb->setAttrFields(array('id','date','quantity','num_items','total'));
		$tb->setQueryFields(array('status','name','phone','email','region','address','porch','porch_access','floor','comment'));
		$tb->addDateFormat('date','d.m.Y');
		$tb->addFloatFormat('total',0);
		$tb->addNl2Br('comment');
		if($xml = $tb->listToXML('orders','o.`id_customer`='.$cid.' and `isConfirm`=1','o.`date` desc')){
			$ids = array();
			$res = $xml->query('/orders/row');
			foreach($res as $e) $ids[] = $e->getAttribute('id');
			if(count($ids)
				&& ($rs = $tb->query('select * from `'.$tb->getTableName(orders::table_item).'` where `id_order` in('.implode(',',$ids).') order by `id_order`'))
			){
				$order = null;
				while($r = mysql_fetch_assoc($rs)){
					if(!$order || $order->getAttribute('id') != $r['id_order']){
						$order = $xml->query('/orders/row[@id="'.$r['id_order'].'"]')->item(0);
						if(!$order) continue;
					}
					$order->appendChild($xml->createElement('item',array(
						'id' => $r['id_catalog']
						,'title' => $r['title']
						,'price' => number_format($r['price'],0,',',' ')
						,'quantity' => $r['quantity']
						,'sum' => number_format($r['price']*$r['quantity'],0,',',' ')
					)));
				}
			}
			return $xml;
		}
	}
}
static function add($v){
	$mysql = new mysql();
	$customer = customer::getCustomerElement();
	$data = array('id_customer' => $customer ? $customer->getAttribute('id') : 'NULL','date' => 'NOW()','isConfirm' => '0');
	foreach($v as $name => $value)
		$data[$name] = $value;
	if($mysql->insert(orders::table_order,$data) && ($id = $mysql->getInsertId()))
		return new order($id);
}
static function remove($id){
	if($id = intval($id)){
		$mysql = new mysql();
		$mysql->query('delete from `'.$mysql->getTableName(orders::table_order).'` where `id`='.$id.' and `isConfirm`=0 and `id_customer`'.(customer::getCustomerId() ? '='.customer::getCustomerId() : ' is null'));
		if($mysql->affectedRows()){
			$mysql->query('delete from `'.$mysql->getTableName(orders::table_item).'` where `id_order`='.$id);
			return true;
		}
	}
}
static function get($id){
	if($id = intval($id)) return new order($id);
}
static function sendEmail(order $order){
	global $_site,$_sec;
	if($orderXML = $order->getXML()){
		$xml = new xml(null,'page',false);
		$xml->de()->appendChild($xml->importNode($orderXML->de()));
		$xml->de()->appendChild($xml->importNode($_site->de()));
		$tpl = new template($_sec->getTemplatePath().'email_order.xsl');
		if($content = $tpl->transform($xml)){
			$mail = new mymail('no-reply@'.$_site->getDomain()
				,$_site->getEmail()
				,$_site->getDomain().' - Заказ №'.$order->getId()
				,$content);
			return $mail->send();
		}
	}
}
}

class order{
private $id;
function __construct($id){
	$this->id = intval($id);
}
function getId(){
	return $this->id;
}
function addItem($v){
	if(is_array($v)){
		$mysql = new mysql();
		$v['id_order'] = $this->getId();
		return $mysql->insert(orders::table_item,$v);
	}
}
function confirm(){
	$mysql = new mysql();
	if($mysql->update(orders::table_order,array('isConfirm' => 1),'`id`='.$this->getId().' and `isConfirm`=0'))
		return $mysql->affectedRows();
}
function getXML(){
	$mysql = new mysql();
	if(($rs = $mysql->query('select * from `'.$mysql->getTableName(orders::table_order).'` where `id`='.$this->getId()
			.' and `id_customer`'.(customer::getCustomerId() ? '='.customer::getCustomerId() : ' is null')))
		&& ($order = mysql_fetch_assoc($rs))
	){
		$xml = new xml(null,'order',false);
		$total = 0;
		if($rs = $mysql->query('select * from `'.$mysql->getTableName('order_item').'` where `id_order`='.$order['id'])){
			while($r = mysql_fetch_assoc($rs)){
				$sum = $r['price']*$r['quantity'];
				$total+= $sum;
				$xml->de()->appendChild($xml->createElement('item',array(
					'id' => $r['id']
					,'price' => number_format($r['price'],0,',',' ')
					,'quantity' => $r['quantity']
					,'sum' => number_format($sum,0,',',' ')
				),$r['title']));
			}
		}
		$xml->de()->setAttribute('id',$order['id']);
		$xml->de()->setAttribute('date',date('d.m.Y H:i',strtotime($order['date'])));
		$xml->de()->setAttribute('total',number_format($total,0,',',' '));
		$ar = array(
			'Статус' => 'status'
			,'Ф.И.О.' => 'name'
			,'Телефон' => 'phone'
			,'Электронная почта' => 'email'
			,'Регион' => 'region'
			,'Адрес' => 'address'
			,'Подъезд' => 'porch'
			,'Код' => 'porch_access'
			,'Этаж' => 'floor'
			,'Коментарий' => 'comment');
		foreach($ar as $label => $name)
			$xml->de()->appendChild($xml->createElement($name,array('label' => $label),$order[$name]));
		if($r['isConfirm']) $xml->de()->setAttribute('confirmed','confirmed');
		return $xml;
	}
}
}
?>