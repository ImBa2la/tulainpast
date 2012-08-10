<?php
class guestbook extends feedback{
function run(){
	global $_out;
	if(!authorization::getUserElement()){
		$_out->query('//section')->item(0)->appendChild($_out->createElement('finish',null,'<p>Только <a href="/registration/">зарегистрированные</a> и авторизованные пользователи могут задавать вопросы.</p>'));
	}else{
		parent::run();
	}
}
function insertDB($data,$form){
	$mysql = new mysql();
	$data['aid'] = $mysql->getNextId('articles');;
	$data['uid'] = authorization::getUserElement()->getAttribute('id');
	$data['date'] = date('Y-m-d H:i:s');
	$data['section'] = $this->getSection()->getId();
	$data['module'] = $this->getXML()->evaluate('string(//module[@name="articles"]/@id)');
	parent::insertDB($data,$form);
}
}
?>