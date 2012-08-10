<?
class jpgScheme{
private $values = array();
private $cache = array();
const VALUE_DELETE = 'delete';
function add($uri,$value){
	$this->values[$uri] = $value;
}
function save(){
	$str = '';
	foreach($this->values as $uri => $value){
		if(($v = $this->parseURI($uri))
			&& $this->checkPath($v['path'])
		){
			$str.= $uri.' ::: '.$value.'<br>';
			if($value==jpgScheme::VALUE_DELETE){
				if(file_exists($v['path'])) unlink($v['path']);
			}elseif($value) $this->saveImage($value,$v['path'],$v['params']);
			
		}
	}
	//throw new Exception($str.'<pre>'.print_r($_REQUEST,1).'</pre>');
}
function get($uri){
	if(($v = $this->parseURI($uri))
		&& $v['url']
		&& file_exists($v['path'])
	){
		return $v['path'];
	}
}
function delete($uri){
	if(($v = $this->parseURI($uri))
		&& $v['path']
		&& file_exists($v['path'])
	) return unlink($v['path']);
}
static function parseURI($uri){
	if(!preg_match('/%([A-Z0-9_]+)%/',$uri,$matches)
		&& ($url = parse_url($uri))
		&& isset($url['path'])
	){
		$params = array();
		if(isset($url['query']) && $url['query']){
			$tmp1 = explode('&',$url['query']);
			foreach($tmp1 as $pair){
				$tmp3 = explode('=',$pair);
				$params[$tmp3[0]] = $tmp3[1];
			}
		}
		return array(
			'url' => $url['path'],
			'path' => PATH_ROOT.trim($url['path'],'/'),
			'params' => $params
		);
	}
}
/**
* При необходимости создает директории и/или изменяет права доступа
* Возвращет правду если по заданому пути можно сохранить файл
*/
function checkPath($src){
	$path = explode('/',pathinfo($src,PATHINFO_DIRNAME));
	$dirToCreate = array();
	while($dir = implode('/',$path)){
		if(is_dir($dir)){
			break;
		}elseif(($dirName = array_pop($path))
			&& $dirName!='..'
			&& $dirName!='.'
		) array_unshift($dirToCreate,$dirName);
		else return false;
	}
	foreach($dirToCreate as $dirName){
		if(!mkdir($dir = $dir.'/'.$dirName,0755)) return false;
	}
	$path = file_exists($src) ? $src : pathinfo($src,PATHINFO_DIRNAME);
	if(!is_writable($path)) chmod($path,0777);
	return is_writable($path);
}
/**
* Изменяет и сохраняет изображение
*/
function saveImage($src,$dst,$param = null){
	if(!$src || !file_exists($src = $_SERVER['DOCUMENT_ROOT'].$src)) return;
	if(!is_array($param)) $param = array();
	
	//проверяем расширение файла и выясняем поддерживает ли PHP формат изображения
	$image_type = 0x0;
	switch($ext = strtolower(pathinfo($src,PATHINFO_EXTENSION))){
		case 'gif': $image_type |= IMG_GIF; break;
		case 'png': $image_type |= IMG_PNG; break;
		case 'jpg':
		case 'jpeg': $image_type |= IMG_JPG; break;
		default: throw new Exception('Wrong image format '.$src);
	}
	if(!(imagetypes() & $image_type)) throw new Exception('Unsupported image type '.$src);
	
	//выравнивание при обрезании картинки
	$hAlign = isset($param['ha']) ? $param['ha'] : 'center';
	$vAlign = isset($param['va']) ? $param['va'] : 'middle';
	
	//получаем новые размеры картинки
	$w = isset($param['w']) ? intval($param['w']) : null;
	$h = isset($param['h']) ? intval($param['h']) : null;
	list($width,$height) = getimagesize($src);
	
	$x_source = $y_source = 0;
	if($w && $h){ //масштабируем и обрезаем по заданным пропорциям
		if($height*$w/$width < $h){
			$temp_width=($height*$w)/$h;
			switch($hAlign){
				case 'left': $x_source = 0; break;
				case 'right': $x_source = $width-$temp_width; break;
				default: $x_source = ($width-$temp_width)/2;
			}
			$width = $temp_width;
		}elseif($width*$h/$height < $w){
			$temp_height = ($width*$h)/$w;
			switch($vAlign){
				case 'top': $y_source = 0; break;
				case 'bottom': $y_source = $height-$temp_height; break;
				default: $y_source = ($height-$temp_height)/2;
			}
			$height = $temp_height;
		}
	}elseif($w && $w<$width){ //вычисляем высоту по заданной ширине
		$h = $w*$height/$width;
	}elseif($h && $h<$height){ //вычисляем ширину по заданной высоте
		$w = $h*$width/$height;
	}else{ //оставляем как есть
		$w = $width;
		$h = $height;
	}
	
	if(isset($param['max'])){
		if($w >= $h && $w > $param['max']){
			$w = $param['max'];
			$h = $w*$height/$width;
		}elseif($h > $w && $h > $param['max']){
			$h = $param['max'];
			$w = $h*$width/$height;
		}
	}
	
	//Изменяем и выводим картинку
	$image_p = imagecreatetruecolor($w,$h);
	$image = null;
	switch($image_type){
		case IMG_GIF: $image = imagecreatefromgif($src); break;
		case IMG_PNG: $image = imagecreatefrompng($src); break;
		case IMG_JPG: $image = imagecreatefromjpeg($src); break;
	}
	imagecopyresampled($image_p, $image, 0, 0, $x_source, $y_source, $w, $h, $width, $height);
	$res = imagejpeg($image_p,$dst,90);
	imageDestroy($image_p);
	imageDestroy($image);
	return $res;
}
}
?>