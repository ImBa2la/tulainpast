<?
class section{
private $e;
private $tpl;
private $xmlPath;
private $tplPath;
function __construct(DOMElement $sec,$data_path,$template_path){
	$this->xmlPath = $data_path;
	$this->tplPath = $template_path;
	$this->e = $sec;
}
function getId(){
	return $this->e->getAttribute('id');
}
function getTitle(){
	return $this->e->getAttribute('title');
}
function getAlias(){
	return $this->e->getAttribute('alias');
}
function getClass(){
	return $this->e->getAttribute('class');
}
function getXML(){
	$path = $this->xmlPath.$this->e->getAttribute('id').'.xml';
	$xml = new xml($path,'section');
	return $xml;
}
function getElement(){
	return $this->e;
}
function query($query){
	$xml = new xml($this->getElement());
	return $xml->query($query,$this->getElement());
}
function isCurrent(){
	global $_sec;
	return $_sec->getId() == $this->getId();
}
function isChildOf($id){
	$xml = new xml($this->e->ownerDocument);
	return $xml->evaluate('count(ancestor::sec[@id="'.htmlspecialchars($id).'"])',$this->e);
}
function getModules(){
	$modules = new modules($this->getXML());
	$modules->setStructure(new structure($this->e->ownerDocument));
	return $modules;
}
function getTemplatePath(){
	return $this->tplPath;
}
function getTemplate(){
	if(!isset($this->tpl)){
		$xml = $this->getXML();
		if($e = $xml->query('/section/template')->item(0)){
			$this->tpl = new template($this->tplPath.$e->getAttribute('id').'.xsl');
			$res = $xml->query('template',$e);
			foreach($res as $e)
				$this->tpl->addTemplate($e->getAttribute('id').'.xsl');
		}else $this->tpl = new template($this->tplPath.'default.xsl');
	}
	return $this->tpl;
}
function getTemplateList(){
	$e = $this->getXML()->query('/section/template')->item(0);
	if(!$e) $e = $this->getXML()->de()->appendChild($this->getXML()->createElement('template',array('id'=>'default')));
	if($e) return new tagList($e,'template');
	return false;
}
function getParent(){
	if($this->e->parentNode->tagName == 'sec'){
		return new section($this->e->parentNode,$this->xmlPath,$this->tplPath);
	}
}
function getChildren(){
	$ar = array();
	$xml = new xml($this->getElement());
	$res = $xml->query('sec',$this->getElement());
	foreach($res as $e) $ar[] = new section($e,$this->xmlPath,$this->tplPath);
	return count($ar) ? $ar : null;
}
function setAlias($v){
	if(preg_match('/^[a-z]{1}[a-z0-9_-]{1,62}$/',$v)){
		return $this->e->setAttribute('alias',$v);
	}elseif(!$v){
		$this->e->removeAttribute('alias');
	}
}
function setTemplatePath($path){
	$this->tplPath = $path;
}
function setSelected($val){
	if($val) $this->e->setAttribute('selected','selected');
	elseif($this->e->hasAttribute('selected')) $this->e->removeAttribute('selected');
}
function setTitle($val){
	$this->e->setAttribute('title',$val);
}
function setClass($val){
	$this->e->setAttribute('class',$val);
}
function remove($removeFile = true){
	$path = $this->getXML()->documentURI();
	if($tmp = parse_url($this->getXML()->documentURI(),PHP_URL_PATH)) $path = $tmp;
	if($removeFile && file_exists($path)) unlink($path);
	return $this->getElement()->parentNode->removeChild($this->getElement());
}
}
?>