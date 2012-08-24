<?
class rowlist extends taglist{
private $headers = array();
private $totals = array();
private $buttons;
function __construct($val,$num_rows = null,$cur_page = null,$page_size = null){
	$xml = new xml('xml/_rowlist'.microtime().'.tmp.xml','rowlist');
	parent::__construct($xml->de(),'row');
	
	$this->setPageSize($page_size);
	$this->setNumRows($num_rows);
	$this->setCurrentPage($cur_page);
	
	if($val instanceof DOMElement){
		$this->importSettings($val);
	}else{
		$this->setHeaders($val);
	}
}
function setFormAction($val){
	$this->getRootElement()->setAttribute('uri',$val);
}
function importSettings(DOMElement $e){
	$xml = new xml($e);
	
	if($a = $xml->query('actions',$e)->item(0)){
		$this->getXML()->elementIncludeTo($a,$this->getRootElement());
	}
	
	$attrs = array('title','add','delete','nocheckbox');
	foreach($attrs as $name) if($e->hasAttribute($name)){
		$this->getRootElement()->setAttribute($name,$e->getAttribute($name));
	}
	
	if($e->hasAttribute($name = 'pageSize')){
		$this->setPageSize($e->getAttribute($name));
	}
	
	$headers = array();
	$res = $xml->query('col',$e);
	foreach($res as $col){
		$headers[$col->getAttribute('name')] = $col->hasAttribute('sort')
			? array('title' =>$col->getAttribute('header'),'sort'=>$col->getAttribute('sort'))
			: $col->getAttribute('header');
	}
	$this->setHeaders($headers);
        
	if($t = $xml->query('totals',$e)->item(0)){
		$node = $this->getXML()->dd()->importNode($t,true);
		$this->getRootElement()->appendChild($node);    
	}
	if($filter = $xml->query('filter',$e)->item(0)){
		$node = $this->getXML()->dd()->importNode($filter,true);
		$this->getRootElement()->appendChild($node);    
	}
	
	$buttons = array();
	$res = $xml->query('buttons/button',$e);
	foreach($res as $btn){
		if($btn->hasAttribute('action'))
			$buttons[$btn->getAttribute('action')] = $btn->getAttribute('title');
	}
	$this->setButtons($buttons);
}
function getButtons(){return is_array($this->buttons) && count($this->buttons) ? $this->buttons : null;}
function setButtons($val){
	if($val && is_array($val)){
		$this->buttons = $val;
	}
}
function getTotals(){return $this->totals;}
function setTotals($val){
   if($val && is_array($val)){
        $this->totals = array_keys($val);
        $xml = $this->getXML();
        if($e = $xml->query('totals',$this->e)->item(0)){
            foreach($val as $key=>$v){
                if($t = $xml->query('total[@name="'.$key.'"]',$e)->item(0)){
                    $t->setAttribute('value',$v);
                }
            }
        }
   }     
}
function setFilter($val){
if($val && is_array($val)){
	$xml = $this->getXML();
	if($e = $xml->query('filter',$this->e)->item(0)){
		foreach($val as $key=>$v){
			if($f = $xml->query('field[@name="'.$key.'"]',$e)->item(0)){
				$f->setAttribute('value',$v);
			}
		}
	}
}     
}
function setHeaders($val){
	if($val && is_array($val)){
		$this->headers = array_keys($val);
		$xml = $this->getXML();
		if($e = $xml->query('headers',$this->e)->item(0))$e->parentNode->removeChild($e);
		$h = new taglist($this->getRootElement()->appendChild($xml->createElement('headers')),'h');
		foreach($val as $id => $val){
			$attr = array('name'=>$id);
			$header = is_array($val) ? $val['title'] : $val;
			if(is_array($val)){
				if(isset($val['sort'])) $attr['sort'] = $val['sort'];
				if(isset($val['class'])) $attr['class'] = $val['class'];
			}
			$h->append($attr,$header);
		}
	}
}
function getHeaders(){
	return $this->headers;
}
private function init(){
	$this->getRootElement()->setAttribute('numPages',$this->getNumPages());
}
function getPageSize(){return $this->getRootElement()->getAttribute('pageSize');}
function setPageSize($val){
	$val = intval($val);
	$this->getRootElement()->setAttribute('pageSize',$val>0 ? $val : 20);
	$this->init();
}
function getNumRows(){return $this->getRootElement()->getAttribute('numRows');}
function setNumRows($val){
	$val = intval($val);
	$this->getRootElement()->setAttribute('numRows',$val>=0 ? $val : 0);
	$this->init();
}
function getCurrentPage(){return $this->getRootElement()->getAttribute('curPage');}
function setCurrentPage($val){
	$val = intval($val);
	$this->getRootElement()->setAttribute('curPage',$val>0 ? $val : 1);
}
function getNumPages(){
	return $this->getNumRows() ? ceil($this->getNumRows()/$this->getPageSize()) : 1;
}
function getStartIndex(){
	return intval(($this->getCurrentPage()-1)*$this->getPageSize());
}
function getFinishIndex(){
	return intval(($this->getCurrentPage()-1)*$this->getPageSize()+$this->getPageSize()-1);
}
function addRow($id,$values = null,$buttons = null){
	$row = new row($this->append(),$this->headers,array_merge(array(row::IDATTR=>$id,'buttons'=>$buttons),$values));
	if($b = $this->getButtons()) foreach($b as $action => $title) $row->setButton($action,$title);
	return $row;
}
function getRow($id){
	return $id && ($e = parent::getById($id)) ? new row($e,$this->headers) : null;
}
function removeRow($id){
	if($id && ($e = parent::getById($id))) $this->remove($e);
}
function setOrder($col,$order = 'asc'){
	$xml = new xml($this->getRootElement());
	if($col
		&& $order
		&& $xml->evaluate('count(headers/h[@name="'.$col.'"])',$this->getRootElement())
	){
		$res = $xml->query('headers/h[@sort]',$this->getRootElement());
		foreach($res as $e){
			if($col == $e->getAttribute('name')) $e->setAttribute('sort',$order);
			else $e->setAttribute('sort','sort');
		}
		return true;
	}
}

/**
* Iterator
*/
function rewind(){return new row(parent::rewind(),$this->headers);}
function current(){return parent::current() ? new row(parent::current(),$this->headers) : null;}
function next(){return new row(parent::next(),$this->headers);}
function valid(){return $this->current();}
}


class row{
const IDATTR = '__id';
private $e;
private $headers;
function __construct(DOMElement $e,$headers,$values = null){
	$this->e = $e;
	$this->headers = $headers;
	$xml = new xml($this->e);
	
	call_user_func(array($this,'set'.row::IDATTR),@$values[row::IDATTR]);
	foreach($this->headers as $name){
		call_user_func(array($this,'set'.$name),@$values[$name]);
	}
	if(isset($values['buttons']) && is_array($values['buttons']))
		foreach($values['buttons'] as $action => $title)
			$this->setButton($action,$title);
}
function hasColumn($name){
	return in_array($name,$this->headers);
}
function __call($m,$a){
	$xml = new xml($this->e);
	switch($m){
		case 'rewind':
		case 'current':
		case 'key':
		case 'next':
		case 'valid':
		case 'setButton':
		case 'getButtons':
			return call_user_func(array($this,$m),$a);
		default:
			if(preg_match('/^get(\w+)$/',$m,$res)){
				$name = strtolower($res[1]);
				if($name==row::IDATTR)
					return $this->e->getAttribute('id');
				elseif($this->hasColumn($name) && ($e = $xml->query($name,$this->e)->item(0)))
					return xml::getElementText($e);
			}elseif(preg_match('/^set(\w+)$/',$m,$res)){
				switch($name = strtolower($res[1])){
					case row::IDATTR:
						if($val = $a[0]) $this->e->setAttribute('id',$val);
						elseif($this->e->hasAttribute('id')) $this->e->removeAttribute('id');
						break;
					default:
						if($this->hasColumn($name)){
							$e = null;
							if($e = $xml->query('cell[@name="'.htmlspecialchars($name).'"]',$this->e)->item(0));
							else $e = $this->e->appendChild($xml->createElement('cell',array('name'=>$name)));
							if($e) xml::setElementText($e,$a[0]);
						}
				}
			}
	}
}
function getButtons(){
	$xml = new xml($this->e);
	if($e = $xml->query('buttons',$this->e)->item(0));
	else $e = $this->e->appendChild($xml->createElement('buttons'));
	if($e)return new taglist($e,'b');
}
function setButton($action,$title){
	if($b = $this->getButtons()){
		if($e = $b->getById($action,'action'))
			$e->parentNode->removeChild($e);
		$b->append(array('action'=>$action,'title'=>$title));
	}
}
}
?>