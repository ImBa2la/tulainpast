<?
/** Доработки:
 * 
 * @todo integrated with form class - create formActions class extend from form and make methods cheks and send form data
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
private $mess = array();
private $form;

function err($mess){
	$this->mess[] = $mess;
}

function hasErrors(){
	return count($this->mess);
}
function hasCaptcha(){
	$xml = new xml($this->form);
	return $xml->evaluate('count(.//field[@type="captcha"]/@show)',$this->form);
}
function isSent(){
	$xml = new xml($this->form);
	return param('action')==$xml->evaluate('string(//param[@name = "action"]/@value)',$this->form);
}
function check(){
	$xml = new xml($this->form);
	$res = $xml->query('.//field[@check]',$this->form);
	foreach($res as $field){
		$val = param($field->getAttribute('name'));
		switch($field->getAttribute('type')){
			case 'radio':
				if(!$val) $this->err('Поле "'.$field->getAttribute('label').'" не отмечено');
				break;
			default:
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
	if($this->hasCaptcha()){
		$captcha = new captcha();
		$captcha->setParamName('captcha');
		if(!$captcha->check())
			$this->err('Результат выражения с картинки введен неверно');
	}
	return $this->hasErrors();
}
function getSentData(){	
	global $_site;
	$xml = new xml($this->form);
	$res = $xml->query('.//field',$this->form);
	$arRes['xml'] = new xml(null,'email',null);
	$arRes['xml']->de()->setAttribute('domain',$_site->getDomain());
	
	foreach($res as $field){
		$f = array('name'=>$field->getAttribute('name'),'label'=>$field->getAttribute('label'));
		$val = param($field->getAttribute('name'));
		switch($field->getAttribute('type')){
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
			$arRes['xml']->de()->appendChild($arRes['xml']->createElement('field',array('name'=>$field->getAttribute('name'),'label'=>$f['label']),$f['value']));
		}
		if($field->hasAttribute('uri')){
			$arRes['mysql'][$field->getAttribute('name')] = $f['value'];
		}
	}
	if(!($arRes['xml']->query('//@label')->item(0) instanceof DOMAttr)) unset($arRes['xml']);
	if(!$this->form->getAttribute('dbSave')) unset($arRes['mysql']);
	return $arRes;
}
function sendEmail($xml){
	global $_out,$_site;
	// отправляем почту админу и дублируем пользователю, если есть почтовые поля
	$e = $_out->createElement('final',array('id'=>'email'));
	if($xml->query('//field')->item(0)
		&& $xml->de()->setAttribute('domain',$_site->de()->getAttribute('domain'))
		&& $xml->de()->setAttribute('name',$_site->de()->getAttribute('name'))
		//формируем почтовое сообщение для администратора
		&& ($tpl = new template(
			file_exists($this->getSection()->getTemplatePath().$this->form->getAttribute('emailTpl').'.xsl')?
				$this->getSection()->getTemplatePath().$this->form->getAttribute('emailTpl').'.xsl':
				$this->getSection()->getTemplatePath().'email_feedback.xsl' //default template, installed with module install.
		))
		&& ($content = $tpl->transform($xml))
		&& ($domain = $_site->de()->getAttribute('domain')) //site domain
		&& ($email = $this->form->getAttribute('email') ? //admin email
				$this->form->getAttribute('email') : 
				($_site->de()->getAttribute('email') 
				.($_site->de()->getAttribute('email2') ? ','.$_site->de()->getAttribute('email2') : null) //доп. мыло
				.($_site->de()->getAttribute('email3') ? ','.$_site->de()->getAttribute('email3') : null)) ) //доп. мыло
		&& ($subject = $this->form->getAttribute('theme')?$this->form->getAttribute('theme'):_('Новое сообщение от пользователя с сайта - ').$domain)
		&& ($mail = new mymail('no-reply@'.$domain,$email,$subject,$content)) //объект для отправки письма		
		&& @$mail->send() //send email to admin
		//формируем почтовое сообщение для пользователя
		&& ($this->form->getAttribute('sendUser') ? $this->sendEmailUser($xml):true)
	)xml::setElementText($e,xml::getElementText ($this->getSection()->getXML()->query('good',$this->form)->item(0)));
	else xml::setElementText($e,xml::getElementText ($this->getSection()->getXML()->query('fail',$this->form)->item(0)));
	
	$_out->addSectionContent($e);//финал в контент
}
function sendEmailUser($xml){
	if(($tpl = new template(
			file_exists($this->getSection()->getTemplatePath().$this->form->getAttribute('emailTplUser').'.xsl'?
				$this->getSection()->getTemplatePath().$this->form->getAttribute('emailTplUser').'.xsl':
				$this->getSection()->getTemplatePath().'email_feedback_user.xsl' //default template, installed with module install.
		)))
		&& ($content = $tpl->transform($xml))
		&& ($email = $xml->evaluate('string(//field[@name="email"]/@value)')) //мыло пользователя
		&& ($subject = $this->form->getAttribute('theme')?$this->form->getAttribute('themeUser'):_('Вами было отправлено сообщение с сайта - ').$domain)
		&& ($mail = new mymail('no-reply@'.$domain,$email,$subject,$content)) //объект для отправки письма		
		&& @$mail->send() //отправляем письмо
	)return true;
	else return false;
}
function fillForm(){
	$res = $this->query('.//field',$this->form);
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
	$this->formMessage(implode('<br/>',$this->mess));
}
function insertDB($data){ //@todo udaptate with tables fields
	if($form = new form($this->form)){
		$form->replaceURI(array('CONNECT'=>$this->form->getAttribute('dbConnect'),'TABLE'=>$this->form->getAttribute('dbTable')));
		$data = array_merge($data,array(
			 'section'	=> $this->getName()
			,'module'	=> $this->getId()
			,'active'	=> 1
			,'sort'		=> $this->getNextSortIndex()
		));
		$form->save($data);
	}else $this->err(_('Form not found'));
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName($this->form->getAttribute('dbTable')).'`
		where `section`="'.$this->getName().'" AND `module`="'.$this->getId().'"');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function run(){
	global $_out;
	
	$captcha = new captcha();
	$captcha->setParamName('captcha');
	
	if($this->form = $this->query('form')->item(0)){//нашли форму
		if(!$this->form->getAttribute('action')) $this->form->setAttribute('action',$_SERVER['REQUEST_URI']);
				
		if($this->isSent()){ //форму отправили
			if(!$this->check() && ($res = $this->getSentData())){
				if($res['xml']) 
					$this->sendEmail($res['xml']);
				if(count($res['mysql']) > 0) 
					$this->insertDB($res['mysql']);
			}else{ // Ошибка - заполняем форму
				$this->fillForm();
			}
		}	
		$_out->addSectionContent($this->form);

		$captcha->setLanguage('ru');
		$captcha->create('userfiles/cptch.jpg');
	}	
}
function formMessage($str){
	return $this->form->appendChild($this->getSection()->getXML()->createElement('message',null,$str));
}
}
?>