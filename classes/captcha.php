<?
class captcha{
function __construct($iWidth=200,$iHeight=25){
	$this->captcha($iWidth,$iHeight);
}
function captcha($iWidth=200,$iHeight=25){
	$this->iWidth = $iWidth;
	$this->iHeight = $iHeight;
	$this->oImage = imagecreate($iWidth, $iHeight);
	$this->font = 'font.ttf';
	$this->fontSize = 13;
	$this->setParamName('captcha');
	$this->sessionName = 'captcha';
	$this->setLanguage();
	imagecolorallocate($this->oImage, 255, 255, 255);
	if(session_id()=='')session_start();
}
function setParamName($value){$this->paramName = $value;}
function getParamName(){return $this->paramName;}
function setLanguage($lang = 'de'){
	switch($lang){
	case 'en':
		$this->digits = array(1=>'one', 2=>'two', 3=>'three', 4=>'four', 5=>'five', 6=>'six', 7=>'seven', 8=>'eigth', 9=>'nine');
		break;
	case 'de':
		$this->digits = array(1=>'ein',2=>'zwei',3=>'drei',4=>'vier',5=>'funf',6=>'sechs',7=>'sieben',8=>'acht',9=>'neun');
		break;
	case 'es':
		$this->digits = array(1=>'uno',2=>'dos',3=>'tres',4=>'cuatro',5=>'cinco',6=>'seis',7=>'ciete',8=>'ocho',9=>'nueve');
		break;
	default:
		$this->digits = array(1=>'один',2=>'два',3=>'три',4=>'четыре',5=>'пять',6=>'шесть',7=>'семь',8=>'восемь',9=>'девять');
	}
}
function drawLines(){
	 for($i = 0; $i < $this->iHeight; $i+=(int)rand(4,8)){
		$rand = rand(80,210);
	 	$iLineColour = imagecolorallocate($this->oImage,$rand,$rand,$rand);
		$rand = rand(-3,3);
		imageline($this->oImage, 0, $i-$rand, $this->iWidth, $i+$rand, $iLineColour);
	 }
	 for($i = 0; $i < $this->iWidth; $i+=(int)rand(3,12)){
		$rand = rand(80,210);
	 	$iLineColour = imagecolorallocate($this->oImage,$rand,$rand,$rand);
		$rand = rand(-2,2);
		imageline($this->oImage, $i, 0, $i-$rand, $this->iHeight, $iLineColour);
	 }
}
function win2uni($s)
  {
    $s = convert_cyr_string($s,'w','i'); // преобразование win1251 -> iso8859-5
    // преобразование iso8859-5 -> unicode:
    for ($result='', $i=0; $i<strlen($s); $i++) {
      $charcode = ord($s[$i]);
      $result .= ($charcode>175)?"&#".(1040+($charcode-176)).";":$s[$i];
    }
    return $result;
  }
function drawCharacters(){
	$firstOperand = rand(1,count($this->digits));
	$secondOperand = rand(1,count($this->digits));
	$_SESSION[$this->sessionName] = (int)$firstOperand + (int)$secondOperand;
	$str = $this->digits[$firstOperand].'+'.$this->digits[$secondOperand];
	//$strlen = mb_strlen($str,'utf-8');
	$strlen = strlen($str);
	$xSpacing = 5;
	$iSpacing = intval($this->iWidth / $strlen);
	for($i = 0; $i<$strlen; $i++){
		//$letter = mb_substr($str,$i,1,'utf-8');
		$letter = substr($str,$i,1);
		$iRandColour = rand(0,150);
		$iTextColour = imagecolorallocate($this->oImage, $iRandColour, $iRandColour, $iRandColour);
		$iRandDegree = rand(10,-10);
		$ySpacing = rand($this->fontSize, $this->iHeight);
		//imagettftext($this->oImage, $this->fontSize, $iRandDegree, $xSpacing, $ySpacing, $iTextColour, $this->font, iconv('windows-1251','utf-8',$letter));
		imagettftext($this->oImage, $this->fontSize, $iRandDegree, $xSpacing, $ySpacing, $iTextColour, $this->font, $this->win2uni($letter));
		$xSpacing+= $iSpacing;
	}
}
function create($sFilename = ''){
	if(!function_exists('imagejpeg')) return false;
	$this->drawLines();
	$this->drawCharacters();
	if($sFilename != ''){
		if(!file_exists($sFilename)){
			$fd = fopen($sFilename,'w');
			if($fd) fclose($fd);
			else return false;
		}
		if(!imagejpeg($this->oImage, $sFilename)) echo 'Ошибка вывода капчи';
	}else{
		header('Content-type: image/jpeg');
		imagejpeg($this->oImage);
	}
	imagedestroy($this->oImage);
	return true;
}
function check(){
	if(!isset($_POST[$this->paramName]) || !isset($_SESSION[$this->sessionName])) return false;
	return ($_POST[$this->paramName]==$_SESSION[$this->sessionName]);
}
}
?>
