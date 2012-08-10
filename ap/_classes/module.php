<?
class module{
private $e;
private $struct;
function __construct(DOMElement $e,structure $struct){
	$this->e = $e;
	$this->setStructure($struct);
}
function getRootElement(){
	return $this->e;
}
function setStructure(structure $struct){
	$this->struct = $struct;
}
function run(){
}
function getId(){
	return $this->e->getAttribute('id');
}
function getTitle(){
	return $this->e->getAttribute('title');
}
function getName(){
	return $this->e->getAttribute('name');
}
function getSection(){
	$xml = new xml($this->e);
	if($pi = pathinfo($xml->documentURI(),PATHINFO_FILENAME)){
		return $this->struct->getSection($pi);
	}
}
function query($query){
	$xml = $this->getSection()->getXML();
	return $xml->query($query,$this->getRootElement());
}
function evaluate($query){
	$xml = $this->getSection()->getXML();
	return $xml->evaluate($query,$this->getRootElement());
}
}
?>