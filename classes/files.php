<?php
class files extends module{
private $module_xml;
private $xml;
function run(){
	//vdump($_SERVER,false);
	global $_out;
	
	$this->xml = $this->getSection()->getXML();
	$this->module_xml = $this->xml->query('/section/modules/module[@id="'.$this->getId().'"]')->item(0);
	
	if($files = $this->xml->query('//file[not(@disabled)]',$this->module_xml)){
		$xml = new xml(null, 'files', null);
		foreach($files as $file){
			if(($f['path'] = $file->getAttribute("path"))
				&& file_exists($f['path'])
				&& ($f['ext'] = strtolower(pathinfo($f['path'], PATHINFO_EXTENSION)))
				&& ($f['size'] = file_size(filesize($f['path'])))){
				//vdump($f,false);
				$xml->de()->appendChild($xml->createElement(
					'file',
					array(
						'id'=>$file->getAttribute("id"),
						'title'=>$file->getAttribute("title"),
						'path'=>$f['path'],
						'size'=>$f['size'],
						'ext'=>$f['ext']
					),
					null));
			}
		}
		$_out->addSectionContent($xml);
	}
}
}
function file_size($size){
	$filesizename = array(" Б", " Кб", " Мб", " Гб", " TB", " PB", " EB", " ZB", " YB");
	return $size ? number_format(round($size/pow(1024, ($i = floor(log($size, 1024)))), 2), 2, ',', ' ') . $filesizename[$i] : '0 Bytes';
}
?>
