<?
class out extends xml{
function __construct(){
	parent::__construct(null,'page',false);
	$this->de()->appendChild($this->createElement('section'));
}
function setMeta($name,$value){
	$e = $this->query('/*/meta[@name="'.htmlspecialchars($name).'"]')->item(0);
	if(!$e) $e = $this->de()->appendChild($this->createElement('meta',array('name'=>$name)));
	if($e) xml::setElementText($e,$value);
}
function addSectionContent($val){
	if($val instanceof xml){
		$this->xmlIncludeTo($val,'/page/section');
	}elseif($val instanceof DOMElement){
		$this->elementIncludeTo($val,'/page/section');
	}elseif($val instanceof DOMNodeList){
		foreach($val as $e) $this->addSectionContent($e);
	}elseif(is_string($val)){
		if($e = $this->query('/page/section')->item(0)){
			$e->appendChild($this->dd()->createTextNode($val));
		}
	}
}
}
?>