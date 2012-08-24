<?
class cart extends catalog{
function run(){
	global $_out,$_struct,$_params;
	switch($_params->pop()){
		case 'order':
			if($form = $this->getOrderForm($xml)){
				if(!count($this->getAr())){
					header('Location: '.BASE_URL.$this->getSection()->getId().'/');
					die;
				}elseif($form->sent()){
					if(count($errors = $form->check())){
						$form->fill();
						$form->orderMessage(implode('<br/>',$errors));
						$this->addOrderContent($form);
					}elseif($order = $this->addOrder()){
						header('Location: '.BASE_URL.$this->getSection()->getId().'/confirm/?order='.$order->getId());
						die;
					}else{
						$this->orderMessage('Ошибка, заказ не создан!');
					}
				}else{
					$this->addOrderContent($form);
				}
			}
			break;
		case 'confirm':
			if($order = orders::get(param('order'))){
				$xml = $order->getXML();
				if(!$xml || $xml->de()->hasAttribute('confirmed')){
					header('Location: '.BASE_URL.$this->getSection()->getId().'/error/?order='.urlencode($order->getId()));
					die;
				}
				$_out->addSectionContent($xml);
			}
			break;
		case 'success':
			if($order = orders::get(param('order'))){
				if($order->confirm()){
					orders::sendEmail($order);
					$this->clear();
					header('Location: '.BASE_URL.$this->getSection()->getId().'/success/');
					die;
				}else{
					header('Location: '.BASE_URL.$this->getSection()->getId().'/error/?order='.urlencode($order->getId()));
					die;
				}
			}else{
				$this->orderMessage('Спасибо за заказ! Наш сотрудник свяжется с вами в ближайшее время.');
				if($e = $_out->query('/page/section/order')->item(0))
					$e->setAttribute('success','success');
			}
			break;
		case 'error':
			$this->orderMessage('Ошибка, заказ уже обработан.');
			break;
		default:
			if($id = param('cancel')) orders::remove($id);
	}
}
function onSectionReady(){
	global $_out,$_sec;
	if(!session_id()) session_start();
	$todie = false;
	if($_sec->getId()==$this->getSection()->getId() && ($action = param('action'))){
		switch($action){
			case 'add':
				cart::add(param('row'),param('quantity'));
				$todie = true;
				break;
			case 'remove':
				cart::remove(param('row'));
				$todie = true;
				break;
		}
	}
	if($xml = $this->getListXML('cart')){
		$res = $xml->query('//row/price');
		$ar = cart::getAr();
		$total = 0;
		foreach($res as $e){
			if(($id = $e->parentNode->getAttribute('id'))
				&& ($q = isset($ar[$id]) ? $ar[$id]['quantity'] : 0)
				&& ($p = floatval(str_replace(array(' ',','),array('','.'),xml::getElementText($e))))
			){
				$sum = $q*$p;
				$total+= $sum;
				$e->parentNode->appendChild($xml->createElement('quantity',null,number_format($q,0,',',' ')));
				$e->parentNode->appendChild($xml->createElement('sum',null,number_format($sum,0,',',' ')));
			}
		}
		$xml->de()->setAttribute('total',number_format($total,0,',',' '));
		$_out->xmlInclude($xml);
	}
	if($todie){
		if(param('ajax')){
			if($e = $_out->query('/page/cart')->item(0)){
				header ("content-type: text/xml");
				echo $_out->dd()->saveXML($e);
			}
		}else{
			header('Location: '.BASE_URL.$this->getSection()->getId().'/');
		}
		die;
	}
}

/**
* заказ
*/
function getOrderForm(){
	$xml = new xml();
	if($form = $this->query('form')->item(0)){
		$form = $xml->dd()->appendChild($xml->importNode($form));
		//заполняем форму, если пользователь авторизован
		if($e = customer::getCustomerElement()){
			$cxml = new xml($e);
			$ar = array('name'=>'name','phone'=>'phone','email'=>'email','address'=>'address');
			foreach($ar as $attr => $field){
				if((($v = $e->getAttribute($attr)) || ($v = $cxml->evaluate('string('.$attr.'/text())',$e)))
					&& ($f = $xml->query('//field[@name="'.$field.'"]')->item(0))
				) xml::setElementText($f,$v);
			}
		}
	}
	$form = new xmlform($xml->de());
	$form->setAction($this->getSection()->getId().'/order/');
	return $form;
}
function addOrder(){
	if(count($ar = $this->getCartProducts())
		&& ($order = orders::add(array(
			'status'		=> mysql::str(param('client_status'))
			,'name'			=> mysql::str(param('name'))
			,'phone'		=> mysql::str(param('phone'))
			,'email'		=> mysql::str(param('email'))
			,'region'		=> mysql::str(param('delivery_region'))
			,'address'		=> mysql::str(param('address'))
			,'porch'		=> mysql::str(param('porch'))
			,'porch_access'	=> mysql::str(param('porch_access'))
			,'floor'		=> mysql::str(param('floor'))
			,'comment'		=> mysql::str(param('comment'))
		)))
	){
		foreach($ar as $r)
			$order->addItem(array(
				'id_catalog'=> $r['id']
				,'title'	=> mysql::str($r['title'])
				,'quantity'	=> $r['quantity']
				,'price'	=> $r['price']
			));
		return $order;
	}
}
function addOrderContent($v){
	global $_out;
	if(!$_out->query('/page/section/order')->item(0))
		$_out->addSectionContent($_out->createElement('order'));
	if(is_object($v)){
		if($v instanceof DOMElement)
			$_out->elementIncludeTo($v,'/page/section/order');
		elseif($v instanceof xml)
			$_out->xmlIncludeTo($v,'/page/section/order');
		elseif($v instanceof xmlform)
			$_out->elementIncludeTo($v->getElement(),'/page/section/order');
	}
}
function orderMessage($v){
	global $_out;
	$this->addOrderContent($_out->createElement('html',null,$v));
}
function getCartProducts(){
	$res = array();
	if(count($ar = cart::getAr())){
		$tb = $this->getTable();
		if($tb->getRow($rs,$this->f('id').' in ('.implode(',',array_keys($ar)).')')){
			while($r = mysql_fetch_assoc($rs))
				$res[$r['id']] = array_merge($r,$ar[$r['id']]);
		}
	}
	return $res;
}

/**
* методы для работы с данными хранящимися в сессии
*/
static function add($id,$quantity){
	if(($id = intval($id))){
		if(!session_id()) session_start();
		if(!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
		if(!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = array('quantity' => 0);
		if($quantity = intval($quantity))
			$_SESSION['cart'][$id]['quantity'] = $quantity > 0 ? $quantity : 1;
		else $_SESSION['cart'][$id]['quantity']+= 1;
		return $_SESSION['cart'][$id]['quantity'];
	}
}
static function remove($id){
	if(($id = intval($id))){
		if(!session_id()) session_start();
		if(!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
		if(isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
	}
}
static function getAr(){
	if(!session_id()) session_start();
	return isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
}
static function clear(){
	if(!session_id()) session_start();
	if(isset($_SESSION['cart'])) unset($_SESSION['cart']);
}


/**
* переопределения для articles
*/
function getListCondition(){
	if(count($ar = cart::getAr()))
		return $this->f('active').'=1 and '.$this->f('id').' in ('.implode(',',array_keys($ar)).')';
	else return 'false';
}
function getListTable(){
	$tb = parent::getListTable();
	$tb->setQueryFields(array('title','properties_short','price'));
	$tb->setPageSize(100);
	return $tb;
}
function getImages($id,$preview = false){
	global $_out;
	if(is_array($res = parent::getImages($id,$preview))){
		foreach($res as $itm){
			foreach($itm as $v){
				if(isset($v['prv'])){
					list($w,$h) = jpgScheme::limitSize(intval($v['prv']->getAttribute('width')),intval($v['prv']->getAttribute('height')),65);
					$v['prv']->setAttribute('width',$w);
					$v['prv']->setAttribute('height',$h);
					$v['prv']->setAttribute('src','image.php?src='.urlencode($v['prv']->getAttribute('src')).'&w='.$w.'&h='.$h);
				}
			}
		}
	}
	return $res;
}
}
?>