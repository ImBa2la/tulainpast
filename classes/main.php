<?
class main extends module{
function run(){
	global $_out,$_struct,$_events,$_struct,$_site,$_youtube;
	$_events->addListener('PageReady',$this);
	
	/*add side menu for fotogallery section*/
	if(($id = param('id'))// && preg_match('#(?:.*)\/[a-z_]+([0-9]+)[\/]?#xi',$_SERVER["REQUEST_URI"],$match) 
		&& ($sec = $_struct->getSection($id))
		&& (($sec->getClass() == 'photo') || ($sec->getClass() == 'photo_ext'))
	){	
		if(preg_match('#(?:.*)\/[a-z_]+([0-9]+)[\/]?#xi',$_SERVER["REQUEST_URI"],$match)){//
			$mysql = new mysql();
			$q = 'SELECT * FROM `'.$mysql->getTableName('secgallery_relation').'` WHERE `aid` = '.$match[1];
			$res = $mysql->query($q,true);
			if((count($res) > 0) && ($res['section'] != '')) $id = $res['section']; 
		}
		
		$p = $_struct->query('./ancestor-or-self::sec[@class="photo" or @class="photo_ext"] | ./sec[@class="photo" or @class="photo_ext"]',$_struct->getElementById($id));
		foreach($p as $data){
			$res = $this->getArticlesWithImage(array($data->getAttribute('id')));
			foreach($res as $r){
				$values = array(
					"id"		=>$r['subsection']?$r['subsection']:$r['section'].'/row'.$r['id'].'/',
					"title"		=>$r['title'],
					"class"		=>"photo_ext",
					"row"		=>$r['id']
				);
				if(preg_match('#'.$r['section'].'\/[a-z_]+([0-9]+)[\/]?#xi',$_SERVER["REQUEST_URI"],$match)&&($r['id'] == $match[1])) $values['selected'] = 'selected';
				if($r['prv'] && $r['img']){
					$values['img'] = $r['img'];
					$values['prv'] = $r['prv'];
					$values['url'] =$r['section'].'/row'.$r['id'].'/';
				}
				if($r['subsection']){
					if(!$match[1])$values['row'] = $r['id'];
					$e = $_struct->getElementById($r['subsection']);
					foreach($values as $k=>$v)$e->setAttribute($k,$v);
				}else{
					$e = $_struct->elementIncludeTo(
						 $_struct->createElement('sec',$values)
						,$data
					);
				}
			}
		}
	}
	if(($id = param('id'))=='map'){
		$p = $_struct->query('./sec',$_struct->getElementById($id));
		foreach($p as $data){
			$sec = $_struct->getSection($data->getAttribute('id'));
			if(($sec = $_struct->getSection($data->getAttribute('id')))
				&& ($src['img'] = $sec->getXML()->evaluate('string(//img[1]/@src)'))
				&& ($src['prv'] = $sec->getXML()->evaluate('string(//img[1]/preview[1]/@src)'))
			) 
				foreach($src as $k=>$v) $data->setAttribute($k,$v);
		}
		
	}
	
	$articles	= array('images'=>'gallery'/*,'story'=>'story'*/);
	foreach($articles as $article=>$alias){
		if(($parent = $_struct->getElementById($article))
			&& ($childs['xml'] = $_struct->query('./sec',$parent))	
		){
			$childs['arr']	= array();
			foreach($childs['xml'] as $child){
				$childs['arr'][] = $child->getAttribute('id');			
			}
			$arr = array();
			if(count($childs['arr']) > 0){
				if(count($res = $this->getArticlesWithImage($childs['arr'])) > 0){
					foreach($res as $r){
						if((count($r) > 0) && file_exists($r['prv']) && file_exists($r['img'])){
							$arr[] = $r;
						}
					}
				}
			}
			$xml = new xml();
			$xml->dd()->appendChild($xml->createElement($alias, $arr[mt_rand(0,count($arr)-1)]));
			$_out->xmlIncludeTo($xml,'/page');
		}
	}
	
	if(((param('id') == null) || (param('id') =='home'))
		&& ($sec = $_struct->getSection('news')) 
		&& ($modules = $sec->getModules())
		&& ($m = $modules->getById('m1'))
	){
		$_events->addListener('PageReady',$m,array('tagname' => 'news','sort' => 'desc','size' => 3));	
		
	}
	
	if($user = $_site->evaluate('string(/site/youtube/@login)')){
		$_youtube = new youtube($user);
	}
}
function getArticlesWithImage($sections = array()){
	if(count($sections) > 0){
		$mysql = new mysql();
		$q = 'SELECT `a`.`id`, `sgr`.`section` as `subsection`, `a`.`section`,`a`.`title`,`a`.`announce`'.
			' FROM `'.$mysql->getTableName('articles').'` AS `a`'.
			' LEFT JOIN `'.$mysql->getTableName('secgallery_relation').'` AS `sgr` ON `a`.`id` = `sgr`.`aid`'.
			' WHERE `a`.`section` IN ("'.@implode('","',$sections).'")  AND `a`.`active` = "1"'.
			' ORDER by '.((count($sections) > 1)? "`a`.`date` DESC": "`a`.`sort` ASC");
		
		if($rs = $mysql->query($q)){
			$result = array();
			while($r = mysql_fetch_assoc($rs)){
				$q = 'SELECT `id` FROM `'.$mysql->getTableName('articles_images').'` WHERE `id_article` = '.$r['id'].' ORDER BY RAND() LIMIT 1';
				if(($img = $mysql->query($q,true)) && (count($img) > 0)){
					$r['img'] = "userfiles/articles/".$r['section']."/".$img['id']."_preview.jpg";
					$r['prv'] = "userfiles/articles/".$r['section']."/".$img['id'].".jpg";
				}
				$result[] = $r;
			}
			return $result;
		}
	}
	return false;
}
function onPageReady(){
	global $_sec,$_out;
	if(
		$_sec->getTemplate()->getId() == 'default.xsl' && (
			$tempList = $_sec->getTemplateList() && 
			!$tempList->getNum()
		) || !$tempList
	){
		$_sec->getTemplate()->addTemplate('def.xsl');
	}
}
}
?>