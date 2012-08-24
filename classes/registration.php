<?php
/**
 * Description of registration
 *
 * @author dev-kirill
 */
class registration extends feedback{
function run(){
	global $_out;
	if($hash = param('active'))
		$this->activate($hash);
	else{
		$captcha = new captcha();
		$captcha->setParamName('captcha');
		if($form = $this->query('form',$this)->item(0)){//нашли форму
			if(!$form->getAttribute('action')) $form->setAttribute('action',$_SERVER['REQUEST_URI']);
			if($this->isSent($form)){ //форму отправили
				if(!$this->check($form) && ($res = $this->getSentData($form))){
					if($res['xml']) 
						$this->sendEmail($res['xml'],$form);
					if(count($res['mysql']) > 0) 
						$this->insertDB($res['mysql'],$form);
				}else{ // Ошибка - заполняем форму
					$this->fillForm($form);
				}
			}	
		}
	}
	
	if(authorization::getUserElement()){
		if($backurl = param('backurl')){
			header('Location: '.$backurl);
			die;
		}
		$_out->query('//section')->item(0)->appendChild($_out->createElement('finish',null,"Вы уже авторизованы."));
	}elseif($this->isSent($form) && !$this->mess){
		if(($backurl = param('backurl')) && (authorization::login(param('login')))){
			header('Location: '.$backurl);
			die;
		}
	}else{
		$_out->addSectionContent($form);

		$captcha->setLanguage('ru');
		$captcha->create('userfiles/cptch.jpg');
	}
}	
function insertDB($data,$form){ 
	$mysql = new mysql();
	$data['date'] = date('Y-m-d H:i:s');
	$data['active_hash'] = md5($mysql->getNextId($mysql->getTableName($form->getAttribute('dbTable'))));
	parent::insertDB($data,$form);
}
function activate($hash){
	global $_out;
	$mysql = new mysql();
	if($hash && $res = $mysql->query("SELECT `id`,`name`, `email` FROM `".$mysql->getTableName('users')."` WHERE `active` = 0 AND `active_hash`= '".$hash."'", true)){	
		$fields_value_mysql = array('active'=>1);
		$mysql->update('users',$fields_value_mysql,'active_hash="'.$hash.'"');
		$_out->query('//section')->item(0)->appendChild($_out->createElement('html',null,"Благодарим за регистрацию, ".$res['name'].". Ваша учетная запись с ID ".$res['id']." активирована."));
	}else throw new Exception("Page not Found",EXCEPTION_404);
}
}

?>
