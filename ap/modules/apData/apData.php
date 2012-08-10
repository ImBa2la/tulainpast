<?
class apData extends module{
function run(){
	global $_struct;

	header('Location: '.ap::getUrl(array('id' => $_struct->getDefaultSectionId())));
	die;
}
}
?>