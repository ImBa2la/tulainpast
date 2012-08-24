<?
class search extends catalog{
function searchRequest(){
	if($s = param('request')){
		$s = preg_replace('/[\s]+/xms',' ',trim($s));
		$ar = explode(' ',$s);
		$arRes = array();
		foreach($ar as $str){
			if(mb_strlen($str) > 1) $arRes[] = $str;
			if(count($arRes)==4)break;
		}
		return implode(' ',$arRes);
	}
}
function getListCondition(){
	if($search = $this->searchRequest())
		return $this->f('active').'=1 and '.$this->f('title').' like "%'.str_replace(' ','%',addslashes($search)).'%"';
	return 'false';
}
function run(){
	if($this->searchRequest()){
		param('row',null);
		parent::run();
	}else{
		throw new Exception('page not found',EXCEPTION_404);
	}
}
function getListTable(){
	if($tb = parent::getListTable()){
		$tb->setPageParamName('page');
		$tb->setAttrFields(array('id','section'));
		$tb->setPageSize(10);
		return $tb;
	}
}
function getListXML($tagName){
	if($xml = parent::getListXML('search')){
		$xml->de()->setAttribute('request',param('request'));
		return $xml;
	}
}
}
?>