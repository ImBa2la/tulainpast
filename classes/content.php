<?
class content extends module{
function run(){
	global $_out;
	$xml = $this->getSection()->getXML();
	$res = $xml->query('./*',$this->getRootElement());
	$_out->addSectionContent($res);
}
}
?>