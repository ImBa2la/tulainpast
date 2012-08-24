<?
/*
 * need refactoring:
 *		1. make full OOP model (check, mail, show form)
 *		2. full instalation /ap/modules/apClients, register.php, email_register.xsl
 *		3. unify .xsl mail template
 *		4. add settings for module ap (email admin, messages, apply active - (check in authorization.php line 49), duplicate email)
 */
class register extends module{
private $module_xml;
private $form_xml;
private $xml;
function run(){
	global $_sec,$_out,$_site;
	$mysql = new mysql();
	
	$this->xml = $this->getSection()->getXML();
	//активируем пользователя по ID
	if($hash = param('active'))
		$this->activate($hash);
	else{
		//регистрация нового пользователя с отправкой на почту регистрационных данных
		$this->module_xml = $this->getRootElement();

		$captcha = new captcha();
		$captcha->setParamName('captcha');

		if($this->form_xml = $this->query('.//form')->item(0)){//нашли форму
			$_out->addSectionContent($this->xml->query('.//mess')->item(0));
			$this->form_xml->setAttribute('action',$_SERVER['REQUEST_URI']);
			$formAction = $this->xml->evaluate('string(//param[@name = "action"]/@value)',$this->form_xml);
			$isCaptcha = $this->xml->evaluate('count(//field[@type = "captcha"])',$this->form_xml);
			
			if($formAction && param('action')==$formAction){ //форму отправили
				$message = array();
				
				/**
				* Проверка полей
				*/
				$res = $this->xml->query('.//field[@check]',$this->form_xml);
				$pswd = null;

				foreach($res as $field){
					$name = $field->getAttribute('name');
					$val = param($name);
					switch($field->getAttribute('type')){
						case 'password':
							if(!$pswd && ($field->getAttribute('name') == 'password')) $pswd = $val;
							if(isset($pswd) && ($field->getAttribute('name') == 'password-check') && ($pswd != $val)) $message[$name] = 'Введенные пароли не совпадают';
							if(strstr($field->getAttribute('check'),'empty') && !$val)
								$message[$name] = 'Поле "'.$field->getAttribute('label').'" не заполнено';
							break;
						case 'checkbox':
							if(!$val) $message[$name] = 'Поле "'.$field->getAttribute('label').'" не отмечено';
							break;
						case 'radio':
							if(!$val) $message[$name] = 'Поле "'.$field->getAttribute('label').'" не отмечено';
							break;
						default:
							if($field->getAttribute('login') &&
							   $res = $mysql->query("SELECT `login` FROM `".$mysql->getTableName('users')."` WHERE `login`='".($val ? $val : null)."'", true)){
								$message[$name] = 'Пользователь с таким логином '.$val.' уже существует.';
							}
							if(strstr($field->getAttribute('check'),'email')){
								if($val && !mymail::isEmail($val))
									$message[$name] = 'Адрес электронной почты в поле "'.$field->getAttribute('label').'" введен неверно';
							}
							if(strstr($field->getAttribute('check'),'empty') && !$val)
								$message[$name] = 'Поле "'.$field->getAttribute('label').'" не заполнено';
					}
				}
				if($isCaptcha && !$captcha->check())
					$message[$captcha->getParamName()] = 'Результат выражения с картинки введен неверно';


				/**
				* Содержание
				*/
				if(!count($message)){ //Ошибок не произошло
					
					$message = false;
					$email = $_site->de()->getAttribute('email');
					//$email = 'kirill@forumedia.ru';
					$domain = $_site->de()->getAttribute('domain');
					$title = $this->form_xml->hasAttribute('title') ? $this->form_xml->getAttribute('title') : $domain;

					// сохраняем в базу
					
					$fields_value_mysql = array();// массив для mysql
					$res = $this->xml->query('//field',$this->form_xml);
					foreach($res as $field){
						$f = array(
							'name'	=>	$field->getAttribute('name'),
							'label'	=>	$field->getAttribute('label'),
						);
						$val = param($field->getAttribute('name'));
						switch($field->getAttribute('type')){
							case 'password':
								$f['value'] = md5($val);
								break;
							case 'checkbox':
								$f['value'] = isset($_REQUEST[$field->getAttribute('name')]) ? "1" : "0";
								break;
							case 'textarea':
								$f['value'] = nl2br(strip_tags($val));
								break;
							default:
								$f['value'] = strip_tags($val);
						}

						if($field->hasAttribute('mysql')){
							$fields_value_mysql[$field->getAttribute('mysql')] = '"'.addslashes($f['value']).'"';
						}
					}
					$fields_value_mysql['date'] = '"'.date("Y-m-d H:m:s").'"';
					$fields_value_mysql['active'] = 0;
					$fields_value_mysql['active_hash'] = '"'.md5($mysql->getNextId($mysql->getTableName('users'))).'"';
					$mysql->insert($mysql->getTableName('users'),$fields_value_mysql);
					$uid = $mysql->getInsertId();
					
					$e = $_out->createElement('finish');
					
					if(($xml = new xml(null,'register',false)) //делаем XML для почтового шаблона
						&& $xml->de()->appendChild($xml->createElement('user',array(
							'id'=>$uid,
							'name'=>param('name'),
							'surname'=>param('first-name'),
							'login'=>param('login'),
							'password'=>param('password'),
							'hash'=>md5($uid)
						))) //втыкаем пользователя с данными
						&& ($tpl = new template($this->getSection()->getTemplatePath().'email_register.xsl')) //достаем шаблон письма
						&& ($content = $tpl->transform($xml)) //делаем письмо
						&& ($domain = $_site->de()->getAttribute('domain')) //домен сайта
						&& ($email = param('email')) //мыло админа
						&& ($mail = new mymail('no-reply@'.$domain,$email,$domain.' - Регистрация пользователя',$content)) //объект для отправки письма
						&& @$mail->send() //отправляем письмо
					) $e->appendChild($_out->createElement('html',null,'<p><strong>Спасибо за регистрацию! На указанный вами электронный адрес выслано письмо с инструкцией по активации вашего аккаунта</strong></p>'));
					
				}else{
					/**
					* Ошибка - заполняем форму
					*/
					$res = $this->xml->query('//field',$this->form_xml);
					foreach($res as $field){
						switch($field->getAttribute('type')){
							case 'radio':
								$opts = $this->xml->query('option',$field);
								foreach($opts as $opt){
									$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->xml->evaluate('string(text())',$opt);
									if($val==stripslashes(param($field->getAttribute('name')))){
										$opt->setAttribute('checked','checked');
										break;
									};
								}
								break;
							case 'checkboxgroup':
								$opts = $this->xml->query('option',$field);
								foreach($opts as $j => $opt){
									$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->xml->evaluate('string(text())',$opt);
									if($val==param($field->getAttribute('name').$j))
										$opt->setAttribute('checked','checked');
								}
								break;
							case 'select':
								$field->setAttribute('value',param($field->getAttribute('name')));
								break;
							default:
								$field->appendChild($field->ownerDocument->createTextNode(param($field->getAttribute('name'))));
						}
					}
					$this->formMessage(implode('<br/>',$message));
				}
			}
		}
		
		if(authorization::getUserElement()){
			if($backurl = param('backurl')){
				header('Location: '.$backurl);
				die;
			}
			$_out->query('//section')->item(0)->appendChild(
				$_out->createElement(
					'finish',
					null,
					"Вы уже авторизованы."
				)
			);
		}elseif(param('action') === "register" && !$message){
			if(($backurl = param('backurl')) && (authorization::login(param('login')))){
				echo $_SESSION['login'];
				header('Location: '.$backurl);
				die;
			}
			$_out->query('//section')->item(0)->appendChild(
				$_out->createElement(
					'finish',
					null,
					"Вы успешно зарегистрированы!<br /> 
					На указанный Вами электронный адрес отправлено письмо с инструкцией по активации аккаунта."
				)
			);
		}else{
			$_out->addSectionContent($this->form_xml);
			$captcha->setLanguage('ru');
			$captcha->create('userfiles/cptch.jpg');
		}
	}
}
function formMessage($str){
	global $_out;
	return $this->form_xml->appendChild($this->xml->createElement('message',null,$str));
}
function activate($hash){
	global $_out;
	$mysql = new mysql();
	if($hash && $res = $mysql->query("SELECT `id`,`name`, `email` FROM `".$mysql->getTableName('users')."` WHERE `active` = 0 AND `active_hash`= '".$hash."'", true)){	
		$fields_value_mysql['active'] = 1;
		$mysql->update('users',$fields_value_mysql,'active_hash="'.$hash.'"');
		$_out->query('//section')->item(0)->appendChild($_out->createElement('html',null,"Благодарим за регистрацию, ".$res['name'].". Ваша учетная запись с ID ".$res['id']." активирована."));
	}else throw new Exception("Page not Found",EXCEPTION_404);
}
}
?>