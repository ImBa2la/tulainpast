<?
class faq extends module{
function run(){
	global $_out,$_site;
	$tb_art = new mysqlToXml('faq',false);
	$tb_art->setAttrFields(array('id'));
	if(!$tb_art->getPageSize()) $tb_art->setPageSize(10);
	$tagNameList = 'faq';
	$tagNameText = 'faqItem';
	$listQueryFields = array('date','name','question');
	$tb_art->addDateFormat('date','d.m.Y');
	
	if($list = $this->query('list')->item(0)){
		$tb_art->setPageSize($list->getAttribute('pageSize'));
		if($v = $list->getAttribute('sort'))
			$tb_art->sort_type = $v;
		if($v = $list->getAttribute('pageParam'))
			$tb_art->setPageParamName($v);
		if($list->hasAttribute('includeContent'))
			$listQueryFields[] = 'answer';
	}
	
	if($row = intval(param('row'))){
		$tb_art->setQueryFields(array('date', 'name','question','answer'));
		if($xml = $tb_art->rowToXML($tagNameText,'`active`=1 and id='.$row,$val)){
			$_out->xmlIncludeTo($xml,'/page/section');
		}
	}else{
		$tb_art->setQueryFields($listQueryFields);
		if($xml = $tb_art->listToXML($tagNameList,'`active`=1'.(in_array('answer', $listQueryFields) ? ' and `answer` is not null and `answer` != ""' : null))){
			$_out->xmlIncludeTo($xml,'/page/section');
		}
	}
	
	$this->xml = $this->getSection()->getXML();
	$this->module_xml = $this->xml->query('/section/modules/module[@id="'.$this->getId().'"]')->item(0);
	$captcha = new captcha();
	$captcha->setParamName('captcha');
	
	
	if($this->form_xml = $this->xml->query('//form',$this->module_xml)->item(0)){//нашли форму
		if(!$this->form_xml->getAttribute('action'))
			$this->form_xml->setAttribute('action',$_SERVER['REQUEST_URI']);
		$formAction = $this->xml->evaluate('string(//param[@name = "action"]/@value)',$this->form_xml);
		$isCaptcha = $this->xml->evaluate('count(//field[@type = "captcha"])',$this->form_xml);
		
		if($formAction && param('action')==$formAction){ //форму отправили
			$message = array();
			/**
			* Проверка полей
			*/
			$res = $this->xml->query('//field[@check]',$this->form_xml);
			foreach($res as $field){
				$name = $field->getAttribute('name');
				$val = param($name);
				switch($field->getAttribute('type')){
					case 'radio':
						if(!$val) $message[$name] = 'Поле "'.$field->getAttribute('label').'" не отмечено';
						break;
					default:
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
				$res = $this->xml->query('//field',$this->form_xml);
				$feilds_value_mysql = array();// массив для mysql
				//$feilds_value_mass = array();// массив для почты
				$xml = new xml(null,'email',null);
				foreach($res as $field){
					print $field->xml;
					$f = array(
						'name'	=>	$field->getAttribute('name'),
						'label'	=>	$field->getAttribute('label'),
					);
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
						$e = $xml->createElement(
								'field'
								,array('name'=>$f['name'],'value'=>$f['value'],'label'=>$f['label'])
								,null);
						$xml->de()->appendChild($e);
					}
					
					if($field->hasAttribute('mysql')){
						$feilds_value_mysql[$field->getAttribute('mysql')] = '"'.$f['value'].'"';
					}
				}
				
				// сохраняем в базу
				$mysql = new mysql();
				$feilds_value_mysql['date'] = '"'.date("Y-m-d H:m:s").'"';
				$feilds_value_mysql['sort'] = $this->getNextSortIndex();
				$feilds_value_mysql['active'] = 0;
				$mysql->insert($mysql->getTableName('faq'),$feilds_value_mysql);
				$this->formMessage('<strong>Ваш вопрос успешно добавлен. В ближайшее время на него ответят.</strong>');
				
				// отправляем почту админу, если есть почтовые поля
				
				$e = $_out->createElement('final');
				if($xml->query('//field')->item(0)
					&& ($faqId = $mysql->getInsertId())
					&& $xml->de()->setAttribute('id',$faqId)
					&& $xml->de()->setAttribute('domain',$_site->de()->getAttribute('domain'))
					&& $xml->de()->setAttribute('name',$_site->de()->getAttribute('name'))
					&& ($tpl = new template($this->getSection()->getTemplatePath().'email_faq.xsl'))
					&& ($content = $tpl->transform($xml))
					&& ($domain = $_site->de()->getAttribute('domain')) //домен сайта
					&& ($email = $_site->de()->getAttribute('email') //мыло админа
							.($_site->de()->getAttribute('email2') ? ','.$_site->de()->getAttribute('email2') : null) //доп. мыло
							.($_site->de()->getAttribute('email3') ? ','.$_site->de()->getAttribute('email3') : null)) //доп. мыло
					&& ($mail = new mymail('no-reply@'.$domain,$email,$domain.' - Вопрос с сайта №'.$faqId,$content)) //объект для отправки письма пивоварне			
					&& @$mail->send() //отправляем письмо
				)xml::setElementText($e,'<p><strong>Ваш вопрос успешно отправлен!</strong><br />В ближайшее время на него ответит наш специалист.</p>');
				else xml::setElementText($e,'<p>Произошла ошибка отправки почтового сообщения.<br />Попробуйте отправить ваш вопрос еще раз, при повторе ошибки, пожалуйста, свяжитесь с администратором сайта</p>');
			
				$_out->addSectionContent($e);	
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
		
		$_out->addSectionContent($this->form_xml);
		$captcha->setLanguage('ru');
		$captcha->create('userfiles/cptch.jpg');
	}
}
function formMessage($str){
	return $this->form_xml->appendChild($this->xml->createElement('message',null,$str));
}
function getNextSortIndex(){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName('faq').'`');
	if($rs && ($row = mysql_fetch_assoc($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
}
?>