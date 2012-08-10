<?
class video extends module{
function run(){
	global $_out,$_youtube,$_params;
	if(isset($_youtube)){
		if(count($_params) && ($id = $_params[0])){
			if($xml = $_youtube->videoItem($id,'videoItem'))
				$_out->addSectionContent($xml);
		}elseif($xml = $_youtube->videoList(param('page'),9,'videos'))
			$_out->addSectionContent($xml);
	}
}
}
?>