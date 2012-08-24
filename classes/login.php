<?
class login extends module{
function run(){
	global $_out,$_params;
	$xml = new xml($this->getRootElement());
	
	//редирект, если авторизованы
	if(customer::getCustomerElement()){
		$this->backRedirect();
	}
	
	//форму отправили
	elseif($action = param('action')){
		
		//залогиниваемся
		if(($form = $this->getForm('login_form'))
			&& $form->sent()
		){
			if(customer::login(param('login'),param('pass')))
				$this->backRedirect();
			else $this->message('Вы ввели неверный логин или пароль.');
		}
		
		//письмо для замены пароля
		elseif(($form = $this->getForm('recover_account_form'))
			&& $form->sent()
		){
			if($form->getCaptcha()->check()){
				if($customer = customer::getByEmail(param('email'))){
					if($customer['active']){
						$this->sendRecoverEmail($customer);
						$form->getCaptcha()->reset();
						$this->message('На указанный электронный почтовый ящик выслано письмо с инструкциями по восстановлению пароля.');
					}else $this->message('Ваш аккаунт не был активирован, проверьте почтовый ящик. При регистрации вам было выслано электронное письмо с регистрационными данными и инструкциями по активации аккаунта.');
				}else $this->message('Пользователь с таким адресом электронной почты не найден');
			}else $this->message('Ошибка! Результат выражения с картинки введен неверно. Попробовать <a href="'.$this->getSection()->getId().'/recover/">еще раз</a>.');
			$form->getCaptcha()->reset();
		}
		
		//замена пароля
		elseif(($form = $this->getForm('change_password_form'))
			&& $form->sent()
		){
			if(($hash = param('hash'))
				&& ($pass = param('pass'))
				&& customer::changePassword($hash,$pass)
			) $this->message('Пароль успешно изменен!');
			else $this->message('Ошибка, пользователь не найден.');
		}
	}
	
	//форма замены пароля
	elseif($_params->exists('passchange') && ($form = $this->getForm('change_password_form'))){
		if($e = $form->getField('hash'))
			$e->setAttribute('value',param('hash'));
		$_out->addSectionContent($form->getElement());
	}
	
	//форма восстановления пароля
	elseif($_params->exists('recover') && ($form = $this->getForm('recover_account_form'))){
		$form->getCaptcha()->create('userfiles/cptch.jpg');
		$_out->addSectionContent($form->getElement());
	}
	
	//форма авторизации (по умолчанию)
	elseif($form = $this->getForm('login_form')){
		if(($backurl = param('backurl')) && ($e = $form->getField('backurl')))
			$e->setAttribute('value',$backurl);
		$_out->addSectionContent($form->getElement());
	}
}
function getForm($id){
	if($e = $this->query('.//form[@id="'.$id.'"]')->item(0))
		return new xmlform($e);
}
function message($v){
	global $_out;
	$e = $_out->query('/page/section/html')->item(0);
	if(!$e) $e = $_out->addSectionContent($_out->createElement('html'));
	xml::setElementText($e,$v);
}
function backRedirect(){
	if(!($backurl = param('backurl')))
		$backurl = BASE_URL.'personal/';
	header('Location: '.$backurl);
	die;
}
function sendRecoverEmail($customer){
	global $_site;
	if(is_array($customer)){
		$xml = new xml(null,'page',false);
		$xml->de()->appendChild($xml->createElement('user',array(
			'id'=>$customer['id']
			,'name'=>$customer['name']
			,'login'=>$customer['login']
			,'password'=>$pass
			,'passChangeURL'=>'http://'.$_SERVER['SERVER_NAME'].BASE_URL.$this->getSection()->getId().'/passchange/?hash='.urlencode($customer['pass'])
		)));
		$xml->de()->appendChild($xml->importNode($_site->de()));
		$tpl = new template($this->getSection()->getTemplatePath().'email_recover.xsl');
		if($content = $tpl->transform($xml)){
			$mail = new mymail('no-reply@'.$_site->getDomain()
				,$customer['email']
				,$_site->getDomain().' - Восстановление пароля'
				,$content);
			return @$mail->send();
		}
	}
}
}
?>