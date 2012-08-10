<?
class auth extends module{
	private $xml;
	private $form;
function run(){
	global $_out;
	$this->xml = $this->getSection()->getXML();
	if(authorization::getUserElement()){
		$_out->addSectionContent(
				$_out->createElement(
					'finish',
					null,
					"Вы уже авторизованы."
				)
		);
	}elseif($this->form = $this->query('.//form')->item(0)){
		$backurl = param('backurl') ? param('backurl') : $_SERVER['REQUEST_URI'];
		$this->form->setAttribute('action',$_SERVER['REQUEST_URI']);
		$formAction = $this->xml->evaluate('string(.//param[@name = "action"]/@value)',$this->form);
		$message = array();
		
		if((param('auth') == 'error') || preg_match('/auth/',$backurl,$match))
			$message['auth'] = "Введенной пары Логин/Пароль не существует";
		//if(param('action')){echo param('action'); die;}
		/*if($formAction && (param('action')==$formAction)){
			$res = $this->xml->query('.//field[@check]',$this->form);
			foreach($res as $field){
				if(strstr($field->getAttribute('check'),'empty') && !param($field->getAttribute('name')))
					$message[$field->getAttribute('name')] = 'Поле "'.$field->getAttribute('label').'" не заполнено';
			}	
			if(authorization::login(param('login'), param('password'))){
				unset($message['auth']);
			}
			if(!count($message)){
				if($backurl && (authorization::login(param('login'), param('password')))){
					header('Location: '.$backurl);
					die;
				}else{
					if('/'==($basepath = pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME))) $basepath = '';
					header('Location: '.$basepath.'/');
					die;
				}
			}
		}*/
		$this->formMessage(implode('<br/>',$message));
		$_out->addSectionContent($this->form);
	}
}
function formMessage($str){
	return $this->form->appendChild($this->xml->createElement('message',null,$str));
}
}
?>
