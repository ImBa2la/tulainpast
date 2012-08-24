<?
class meta extends module{
function run(){
	global $_out;
	if($v = $this->evaluate('string(title)')) $_out->setMeta('title',$v);
	if($v = $this->evaluate('string(keywords)')) $_out->setMeta('keywords',$v);
	if($v = $this->evaluate('string(description)')) $_out->setMeta('description',$v);
	$_out->addSectionContent($res);
}
}
?>