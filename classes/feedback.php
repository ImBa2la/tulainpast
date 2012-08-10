<?
/** Доработки:
 * 
 * @todo integrated with form class - create formActions class extend from form and make methods cheks and send form data
 * v1.2.1
 * fixed bugs
 * extend for registration
 * 
 * v1.2
 * Реорганизация кода (один и тот же код часто используется во многих обработчиках форм)
 * OOP structure class
 * Отправка почтового сообщения пользователю
 * Настройка отправки почтовых сообщений (админу всегда, пользователю опционально)
 * data base saving data
 * relation database settings with saving db data
 * 
 * v1.1
 * update
 *	Оформление в полноценный модуль
 *	Предоставление настройки выбора почтовых шаблонов
 *  модуль связан с классом форм и добавлена возможность редактирования полей форм
 *  добавлены кастомные поля сообщений удачной/неудачной отправки формы
 * 
 */
class feedback extends module{
protected $mess = array();

function err($mess){
	$this->mess[] = $mess;
}

function hasErrors(){
	return count($this->mess);
}
function hasCaptcha($form){
	$xml = new xml($form);
	return $xml->evaluate('count(.//field[@type="captcha"]/@show)',$form);
}
function isSent($form){
	$xml = new xml($form);
	return param('action')==$xml->evaluate('string(//param[@name = "action"]/@value)',$form);
}
function check($form){
	$xml = new xml($form);
	$res = $xml->query('.//field[@check]',$form);
	$pswd = null;
	foreach($res as $field){
		$val = param($field->getAttribute('name'));
		switch($field->getAttribute('type')){
			case 'password':
				if(!$pswd && ($field->getAttribute('name') == 'password')) $pswd = $val;
				if(isset($pswd) && ($field->getAttribute('name') == 'password-check') && ($pswd != $val)) $this->err('Введенные пароли не совпадают');
				if(strstr($field->getAttribute('check'),'empty') && !$val)
					$this->err('Поле "'.$field->getAttribute('label').'" не заполнено');
				break;
			case 'checkbox':
			case 'radio':
				if(!$val) $this->err('Поле "'.$field->getAttribute('label').'" не отмечено');
				break;
			default:
				$mysql = new mysql();
				if($field->getAttribute('login') &&
					($res = $mysql->query("SELECT `login` FROM `".$mysql->getTableName($form->getAttribute('dbTable'))."` WHERE `login`='".($val ? $val : null)."'", true))){
					$this->err('Пользователь с таким логином '.$val.' уже существует.');
				}
				if(strstr($field->getAttribute('check'),'email')){
					if($val && !mymail::isEmail($val))
						$this->err('Адрес электронной почты в поле "'.$field->getAttribute('label').'" введен неверно');
				}
				if($field->getAttribute('type') != 'captcha'){
					if(strstr($field->getAttribute('check'),'empty') && !$val)
						$this->err('Поле "'.$field->getAttribute('label').'" не заполнено');
				}else{
					if($field->getAttribute('show') && strstr($field->getAttribute('check'),'empty') && !$val)
						$this->err('Поле "'.$field->getAttribute('label').'" не заполнено');
				}
		}
	}
	if($this->hasCaptcha($form)){
		$captcha = new captcha();
		$captcha->setParamName('captcha');
		if(!$captcha->check())
			$this->err('Результат выражения с картинки введен неверно');
	}
	return $this->hasErrors();
}
function getSentData($form){	
	global $_site;
	$xml = new xml($form);
	$mysql = new mysql();
	$res = $xml->query('.//field',$form);
	$arRes = array('xml'=>null,'mysql'=>null);
	$arRes['xml'] = new xml(null,'email',null);
	$arRes['xml']->de()->setAttribute('domain',$_site->getDomain());
	$arRes['xml']->de()->setAttribute('hash',md5($mysql->getNextId($mysql->getTableName($form->getAttribute('dbTable')))));
	
	foreach($res as $field){
		$f = array('name'=>$field->getAttribute('name'),'label'=>$field->getAttribute('label'));
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
		if($field->hasAttribute('mail')){
			$arRes['xml']->de()->appendChild($arRes['xml']->createElement('field',array('name'=>$field->getAttribute('name'),'label'=>$f['label']),($field->getAttribute('type') == 'password')?$val:$f['value']));
		}
		if($field->hasAttribute('uri')){
			$arRes['mysql'][$field->getAttribute('name')] = $f['value'];
		}
	}
	if(!($arRes['xml']->query('//@label')->item(0) instanceof DOMAttr)) unset($arRes['xml']);
	if(!$form->getAttribute('dbSave')) unset($arRes['mysql']);
	return $arRes;
}
function sendEmail($xml,$form){
	global $_out,$_site;
	// отправляем почту админу и дублируем пользователю, если есть почтовые поля
	$e = $_out->createElement('final',array('id'=>'email'));
	if($xml->query('//field')->item(0)
		&& $xml->de()->setAttribute('domain',$_site->de()->getAttribute('domain'))
		&& $xml->de()->setAttribute('name',$_site->de()->getAttribute('name'))
		//формируем почтовое сообщение для администратора
		&& ($tpl = new template(
			file_exists($this->getSection()->getTemplatePath().$form->getAttribute('emailTpl').'.xsl')?
				$this->getSection()->getTemplatePath().$form->getAttribute('emailTpl').'.xsl':
				$this->getSection()->getTemplatePath().'email_feedback.xsl' //default template, installed with module install.
		))
		&& ($content = $tpl->transform($xml))
		&& ($domain = $_site->de()->getAttribute('domain')) //site domain
		&& ($email = $form->getAttribute('email') ? //admin email
				$form->getAttribute('email') : 
				($_site->de()->getAttribute('email') 
				.($_site->de()->getAttribute('email2') ? ','.$_site->de()->getAttribute('email2') : null) //доп. мыло
				.($_site->de()->getAttribute('email3') ? ','.$_site->de()->getAttribute('email3') : null)) ) //доп. мыло
		&& ($subject = $form->getAttribute('theme')?$form->getAttribute('theme'):'Новое сообщение от пользователя с сайта - '.$domain)
		&& ($mail = new mymail('no-reply@'.$domain,$email,$subject,$content)) //объект для отправки письма		
		&& @$mail->send() //send email to admin
		//формируем почтовое сообщение для пользователя
		&& ($form->getAttribute('sendUser') ? $this->sendEmailUser($xml,$form):true)
	)xml::setElementText($e,xml::getElementText ($this->getSection()->getXML()->query('good',$form)->item(0)));
	else xml::setElementText($e,xml::getElementText ($this->getSection()->getXML()->query('fail',$form)->item(0)));
	
	$_out->addSectionContent($e);//финал в контент
}
function sendEmailUser($xml,$form){
	if(($tpl = new template(
			file_exists($this->getSection()->getTemplatePath().$form->getAttribute('emailTplUser').'.xsl')?
				$this->getSection()->getTemplatePath().$form->getAttribute('emailTplUser').'.xsl':
				$this->getSection()->getTemplatePath().'email_feedback_user.xsl' //default template, installed with module install.
		))
		&& ($content = $tpl->transform($xml))
		&& ($email = param('email')/* $xml->evaluate('string(//field[@name="email"]/text())')*/) //мыло пользователя
		&& ($subject = $form->getAttribute('theme')?$form->getAttribute('themeUser'):'Вами было отправлено сообщение с сайта - '.$domain)
		&& ($mail = new mymail('no-reply@'.$domain,$email,$subject,$content)) //объект для отправки письма		
		&& @$mail->send() //отправляем письмо
	)return true;
	else return false;
}
function fillForm($form){
	$res = $this->query('.//field',$form);
	foreach($res as $field){
		switch($field->getAttribute('type')){
			case 'radio':
				$opts = $this->query('option',$field);
				foreach($opts as $opt){
					$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->evaluate('string(text())',$opt);
					if($val==stripslashes(param($field->getAttribute('name')))){
						$opt->setAttribute('checked','checked');
						break;
					};
				}
				break;
			case 'checkboxgroup':
				$opts = $this->query('option',$field);
				foreach($opts as $j => $opt){
					$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->evaluate('string(text())',$opt);
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
	$this->formMessage(implode('<br/>',$this->mess),$form);
}
/*
 * @todo optimize for different table, for cross-platform.
 */
function insertDB($data,$e){ //@todo udaptate with tables fields
	if($form = new form($e)){
		$form->replaceURI(array('CONNECT'=>$e->getAttribute('dbConnect'),'TABLE'=>$e->getAttribute('dbTable')));
		$params = array();
		$paramsXml = $this->getXML()->query('param[@uri]',$e);
		foreach($paramsXml as $param){
			if(!$data[$param->getAttribute('name')])
				$params[$param->getAttribute('name')] = ($param->getAttribute('name') == 'sort')?$this->getNextSortIndex($e):$param->getAttribute('value');
		}
		$data = array_merge($data,$params);
		/*$data = array_merge($data,array(
			 'section'	=> $this->getName()
			,'module'	=> $this->getId()
			,'active'	=> 1
			,'sort'		=> $this->getNextSortIndex()
		));*/
		$form->save($data);
	}else $this->err('Form not found');
}
function getNextSortIndex($form){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName($form->getAttribute('dbTable')).'`
		where `section`="'.$this->getName().'" AND `module`="'.$this->getId().'"');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function getXML(){
	return new xml($this->getRootElement()->ownerDocument);
}
function run(){
	global $_out;
	
	$captcha = new captcha();
	$captcha->setParamName('captcha');
	
	if($form = $this->query('form')->item(0)){//нашли форму
		if(!$form->getAttribute('action')) $form->setAttribute('action',$_SERVER['REQUEST_URI']);
				
		if($this->isSent($form)){ //форму отправили
			if(!$this->check($form) && ($res = $this->getSentData($form))){
				if(count($res['mysql']) > 0) 
					$this->insertDB($res['mysql'],$form);
				if($res['xml']) 
					$this->sendEmail($res['xml'],$form);
			}else{ // Ошибка - заполняем форму
				$this->fillForm($form);
			}
		}	
		$_out->addSectionContent($form);

		$captcha->setLanguage('ru');
		$captcha->create('userfiles/cptch.jpg');
	}	
}
function formMessage($str,$form){
	return $form->appendChild($this->getSection()->getXML()->createElement('message',null,$str));
}
}
?>