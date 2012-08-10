<?
class apCommon extends module{
function run(){
	global $_out,$_sec;
	if(ap::isCurrentModule($this)){
		if($e = $_sec->getXML()->query('//form[@id="common_form"]')->item(0)){
			$form = new form($e);
			if(param('action')=='save'){
				$form->save($_REQUEST);
			}
			$form->load();
			$_out->elementIncludeTo($form->getRootElement(),'/page/section');
		}
	}
}
}
?>