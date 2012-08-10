<?
class mysqlToXml extends mysqlTable{
	var $pageParamName;
	var $skipEmptyFields;
	var $pagingUrl;
	var $images = array();
	var $fieldFloat = array();
	var $fieldCond = array();
	var $fieldDate = array();
	var $currentSection;
function __construct($table,$section_control = true,$sort_control = true){
	global $_page;
	parent::__construct($table,$section_control,$sort_control);
	$this->image = null;
	$this->setIdField('id');
	$this->sort_type = 'asc';
	$this->setPageParamName('x-x-x');
	$this->setRowSize(null);
	$this->setPageSize(10);
	$this->setQueryFields();
	$this->setAttrFields(array('id'));
	$this->skipEmptyFields = false;
	if(is_object($_page) && get_class($_page)=='DOMElement'){
		$this->setCurrentSection($_page->getAttribute('id'));
	}
}
function setCurrentSection($val){$this->currentSection = $val;}
function getCurrentSection(){return $this->currentSection;}
function setPageParamName($val){$this->pageParamName = $val;}
function setRowSize($num){$this->listRowSize = $num;}
function setGroupingByField($fieldName){$this->groupingField = $fieldName;}
function setPageSize($value){$this->pageSize = $value;}
function getPageSize(){return $this->pageSize;}
function setQueryFields($arr = null){$this->queryFields = $arr;}
function setAttrFields($arr = null){$this->attrFields = $arr;}
function setIdField($val){$this->id_field = $val;}
function addDateFormat($fieldName,$formatStr){
	$this->fieldDate[$fieldName] = $formatStr;
}
function addFloatFormat($fieldName,$decimals = 2,$dec_point = ',',$thousands_sep = ' '){
	$this->fieldFloat[$fieldName] = array('decimals'=>$decimals,'dec_point'=>$dec_point,'thousands_sep'=>$thousands_sep);
}
function addImg($params){
	$this->images[] = $params;
}
function setFieldCond($name,$cond){
	$this->fieldCond[$name] = $cond;
}
function __call($name,$params){
	switch($name){
		case 'addImage':
			if(count($params)==1 && is_array($params[0])){
				$this->addImg($params[0]);
			}elseif(!is_array($params[0])){
				$p = array(
					'width'			=>	$params[0],
					'height'		=>	$params[1],
					'path'			=>	$params[2],
					'prefix'		=>	$params[3],
					'fileFieldName'	=>	$params[4]
				);
				if(isset($params[5]))$p['altFieldName'] = $params[5];
				if(isset($params[6]))$p['postfix'] 		= $params[6];
				$this->addImg($p);
			}
		break;
	}
}
function addNl2Br($fieldName){
	$this->fieldNl2Br[$fieldName] = true;
}
function setPagingUrl($value){
	$this->pagingUrl = $value;
}
function listToXML($outTagname,$condition = null,$sort = null,$query = null,$lim = true){
	$limit = $this->getLimit(param($this->pageParamName),$this->pageSize,$condition);
	$out = new xml(null,null,false);
	$outTag = $out->dd()->appendChild($out->createElement($outTagname,array(
		'rows'=>$limit['num_rows'],
		'pages'=>$limit['page_num'],
		'pagesize'=>$limit['page_size'],
		'page'=>$limit['page_current'],
		'pageParam'=>$this->pageParamName)));
	if($this->pagingUrl) $outTag->setAttribute('pagingUrl',$this->pagingUrl);
	if(isset($this->groupingField)){
		if($sort) $sort = $this->groupingField.','.$sort;
		elseif($this->sort_control) $sort = $this->groupingField.', sort'.($this->sort_type!='' ? ' '.$this->sort_type : '');
		else $sort = $this->groupingField;
	}
	if($lim){
		if(!$query){
			$this->getRow($row_set,$condition,$limit['limit_string'],$sort);
		}else{
			$row_set = $this->query($query.' LIMIT '.($lim!==true?$lim:$limit['limit_string']));
		}
	}else{
		$row_set = $query;
	}
	$root = $outTag;
	$counter = 0;
	while($row = mysql_fetch_assoc($row_set)){
		//группируем записи, если надо
		if(isset($this->groupingField)){
			if(!(isset($row[$this->groupingField]))) $root = $outTag;
			elseif($root->getAttribute('title')!=$row[$this->groupingField])
				$root = $outTag->appendChild($out->createElement('group',array('title'=>$row[$this->groupingField])));
		}elseif($this->listRowSize && !($counter++%$this->listRowSize)){
			$root = $outTag->appendChild($out->createElement('tr'));
		}
		//делаем запись
		$rec = $root->appendChild($out->createElement('row'));
		$this->addValuesToRecord($rec,$row,$out);
	}
	return $out;
}
function rowToXML($outTagname,$condition,&$row){
	$this->getRow($row_set,$condition);
	if($row = mysql_fetch_assoc($row_set)){
		$page = 1;
		if($this->sort_control){
			$query = 'active=1 and `sort`<='.$row['sort'];
			if($this->section_control){
				$query.= ' and `'.$this->section_field.'`="'.$this->getCurrentSection().'"';
			}
			$page = ceil($this->getNumRows($query)/$this->getPageSize());
		}elseif($this->pageParamName)
			$page = intval(param($this->pageParamName)) ? intval(param($this->pageParamName)) : 1;
		$out = new xml('new_doc');
		$rec = $out->dd()->appendChild($out->createElement($outTagname,array('page'=>$page)));
		$this->addValuesToRecord($rec,$row,$out);
		return $out;
	}
	return null;
}
protected function addValuesToRecord(&$rec,&$row,&$out){
	$fields = $this->queryFields;
	//добавляем атрибуты в запись, если заданы
	if(is_array($this->attrFields)) foreach($this->attrFields as $fieldName){
		if($this->skipEmptyFields && !$row[$fieldName]) continue;
		if(isset($this->fieldFloat[$fieldName]))
			$row[$fieldName] = number_format($row[$fieldName],$this->fieldFloat[$fieldName]['decimals'],$this->fieldFloat[$fieldName]['dec_point'],$this->fieldFloat[$fieldName]['thousands_sep']);
		if(isset($this->fieldDate[$fieldName]))
			$row[$fieldName] = date($this->fieldDate[$fieldName],strtotime($row[$fieldName]));
		if(isset($row[$fieldName])) $rec->setAttribute($fieldName,$row[$fieldName]);
	}
	//берем все поля записи, если не заданы
	if(!is_array($fields)){
		$fields = array_keys($row);
		if(is_array($this->attrFields)) $fields = array_diff($fields,$this->attrFields);
	}
	//создаем поля в записи
	foreach($fields as $fieldName){
		$v = $row[$fieldName];
		if($this->skipEmptyFields && !$v) continue;
		if(isset($this->fieldNl2Br[$fieldName]))
			$v = nl2br($row[$fieldName]);
		if(isset($this->fieldFloat[$fieldName]))
			$v = number_format($row[$fieldName],$this->fieldFloat[$fieldName]['decimals'],$this->fieldFloat[$fieldName]['dec_point'],$this->fieldFloat[$fieldName]['thousands_sep']);
		if($isDate = isset($this->fieldDate[$fieldName]))
			$v = date($this->fieldDate[$fieldName],strtotime($row[$fieldName]));
		$e = $rec->appendChild($out->createElement($fieldName,null,$v));
		if($isDate) $e->setAttribute('value',date('Y-m-d\TH:i:s\Z'));
		
	}
	//добавляем картинки]
	foreach($this->images as $key => $image){
		$file_name = null;
		if(is_array($image['fileFieldName'])){
			$ar = array();
			foreach($image['fileFieldName'] as $fname) $ar[] = $row[$fname];
			$file_name = implode('_',$ar);
		}else $file_name = $row[$image['fileFieldName']];
		$filepath = $image['path'].$image['prefix'].$file_name.$image['postfix'].'.jpg';
		if(file_exists($filepath)){
			list($width, $height) = getimagesize($filepath);
			if($image['width'] && !$image['height']){
				$image['height'] = ceil(($image['width'] * $height)/$width);
			}elseif(!$image['width'] && $image['height']){
				$image['width'] = ceil(($image['height'] * $width)/$height);
			}
			if(!$image['width'] && !$image['height']){
				$image['width'] = $width;
				$image['height'] = $height;
			}
			$params = array();
			if(isset($image['name'])){
				$params['name'] = $image['name'];
			}else{
				$params['name'] = $key;;
			}
			$params['src'] 		= $filepath;
			$params['alt']		= $image['altFieldName'] ? $row[$image['altFieldName']] : '';
			$params['width']	= $image['width'];
			$params['height']	= $image['height'];
			$img = $rec->appendChild($out->createElement('img',$params));
		}
	}
}
}
?>