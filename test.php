<?php
function autoload($class){
	if(file_exists($path = 'ap/classes/'.$class.'.php') || file_exists($path = 'classes/'.$class.'.php'))
		require_once $path;
}
spl_autoload_register('autoload');
require 'ap/lib/default.php';

define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_SITE','xml/site.xml');
define('PATH_STRUCT','xml/structure.xml');
define('PATH_DATA','xml/data/');
define('PATH_TPL','xml/templates/');

$_site = new site('xml/site.xml');

/*******************************************************************************
 * content restore
 */
$sql = new mysql();
$restore = array(
	#9 =>array('section'=>'whatthreat','module'=>'m1')
	#,7 =>array('section'=>'news','module'=>'m1')
	#,10=>array('section'=>'otkonkidotroll','module'=>'m1')
	#,11=>array('section'=>'tulahistoric','module'=>'m1')
	#,14=>array('section'=>'mempries','module'=>'m1')
	#,15=>array('section'=>'tulastreets','module'=>'m1')
);

foreach($restore as $sid=>$data){
	echo 'Working with: '.$data['section'].' ...<br/>';
	$counter = 1;
	$aidArray = array();
	$q = 'DELETE from '.$sql->getTableName('articles').' where `section` = '.mysql::str($data['section']);
	$sql->query($q);
	$q = 'SELECT * FROM `jos_content` WHERE `sectionid` = '.$sid.' ORDER BY `created` ASC';
	#$q = 'SELECT * FROM `tulainpast_db`.'.$sql->getTableName('articles').' WHERE `section` = '.mysql::str($data['section']);
	$rs = $sql->query($q);
	while($res = mysql_fetch_assoc($rs)){
		$aidArray[] = $res['id'];
		$values = array(
			'id'=>$res['id'] + 200 #adding 200 (over that 179 rows photogallery) for excluding collision from photogallery it`s magic number
			,'title'=>mysql::str($res['title'])
			,'announce'=>mysql::str(preg_replace(
					array(
						'#src=([\"|\']{1})images#i'
						,'#<img(.*?)align(=[\"|\'](?:.*?)[\"|\'])(?:.*?)>#i'
					)
					,array(
						'src=$1/userfiles/image'
						,'<img$1class$2/>'
					)
					,$res['introtext']))
			,'article'=>mysql::str(preg_replace(
					array(
						'#src=([\"|\']{1})images#i'
						,'#<img(.*?)align(=[\"|\'](?:.*?)[\"|\'])(?:.*?)>#i'
					)
					,array(
						'src=$1/userfiles/image'
						,'<img$1class$2/>'
					)
					,$res['fulltext']))
			,'active'=>mysql::str(($res['state'] == 1)?1:0)
			,'date'=>mysql::str($res['created'])
			,'sort'=>mysql::str($counter++)
			,'module'=>mysql::str($data['module'])
			,'section'=>mysql::str($data['section'])
		);
		$sql->insert($sql->getTableName('articles'),$values); #add to main table
		if($data['section'] != 'news'){#extend articles
			$q = 'DELETE FROM '.$sql->getTableName('articles_relations').' where `aidStory`='.($res['id'] + 200);
			$sql->query($q);
			$sql->insert($sql->getTableName('articles_relations'),array('aidStory'=>($res['id'] + 200))); #add to relation table
		}
		#vdump(array($res,$values),false);
			
	}
}

/*******************************************************************************
 * Reqursive Tree Catalog and make watermark for imges
 * /
require 'classes/images.php';

$dir = 'userfiles/articles/babusalbom/babus3/'; #start directory
function read_folder_directory($dir = "root_dir/dir"){ //make collection
	$listDir = array(); 
	if($handler = opendir($dir)) { 
		while (($sub = readdir($handler)) !== FALSE) { 
			if ($sub != "." && $sub != ".." && $sub != "Thumb.db" && $sub != "Thumbs.db") { 
				if(is_file($dir."/".$sub)) { 
					$listDir[] = $sub; 
				}elseif(is_dir($dir."/".$sub)){ 
					$listDir[$sub] = read_folder_directory($dir."/".$sub); 
				} 
			} 
		} 
		closedir($handler); 
	} 
	return $listDir; 
} 

$files['userfiles']['articles']['babusalbom']['babus3'] = read_folder_directory ($dir); 


function read_tree($tree,$path='',$offset = ''){
	echo '<pre>';
	foreach($tree as $name=>$brunch){
		$water = 'images/__watermark.png';
		$waterL	= new images($water,65,65,false,null,null,null,true);//large watermark
		$waterS	= new images($water,18,18,true,null,null,null,true);//prev watermark
		if(is_array($brunch)){
			echo '=== dir : '.$name.', path: '.$path.'/'.$name.' ===<br/>';
			read_tree($brunch,$path.'/'.$name, $offset.'&nbsp;&nbsp;&nbsp;&nbsp;');
		}else{
			$src = $path.'/'.$brunch;
			if(($ext = strtolower(pathinfo($src,PATHINFO_EXTENSION)))
				&& ($ext== 'jpg') || ($ext == 'jpeg')
			){
				#if(!is_dir($_SERVER['DOCUMENT_ROOT'].$path.'/temp')) mkdir($_SERVER['DOCUMENT_ROOT'].$path.'/temp');
				#if(!strpos($brunch,'_preview')){
				$img	= new images($src,null,null,false,null,null,null,false);
				if(strpos($brunch,'_preview')){
					//set_time_limit (30);
					$img->addWatermark($waterS,3,4,80,'_right','_bottom');//prev watermark
					#$img->save($_SERVER['DOCUMENT_ROOT'].$path.'/temp/'.$brunch);
					//$img->__destroy();
				}else{
					$img->addWatermark($waterL,9,14,80,'_right','_bottom');//large watermark
				}
				$img->save($_SERVER['DOCUMENT_ROOT'].$path.'/'.$brunch);
			}
			//echo $offset.$src.'<br/>';
		}
		//$water->__destruct();
	}
	echo '</pre>';
}
#&amp;waterMark=/images/__watermark.png&amp;waterW=65&amp;waterH=65&amp;waterAlpha=1&amp;waterOffsetX=9&amp;waterOffsetY=14&amp;waterOpacity=80"/>
#&amp;waterMark=/images/__watermark.png&amp;waterW=18&amp;waterH=18&amp;waterAlpha=1&amp;waterOffsetX=3&amp;waterOffsetY=4&amp;waterOpacity=80"
set_time_limit (120);
#foreach ($files as $file)
	#echo '<pre>'.print_r(array($file,  pathinfo($dir)),true)."</pre>";
#read_tree($files);

/*******************************************************************************
 *  test images lib (watermark, text)
 * /
$i = new images('/test_b.jpg',null,null,false,null,null,null,false);
#$i = new images('/test.jpg',null,null,false,null,null,null,false);#for preview
$w = new images('/images/__watermark.png',65,65,false,null,null,null,true);
#$w = new images('/images/__watermark.png',18,18,false,null,null,null,true);#for preview
$i->addWatermark($w,9,14,80,'_right','_bottom');
$i->save('test_w.jpg');

#$water	= new images($water,50,50,true,null,null,0xFF0000,false);
#$img	= new images($src,500,200,true,null,null,null,false);
#$img->addText("I wrote about you!",'font.ttf', 14, 0, 0, 'right_', 'bottom',null,"#FFffff",0);

#$img->save('test.jpg');
#$water->imageStream();
#$img->imageStream();
# */


/*******************************************************************************
 *  save urls
 * /
================ function f1() ====================== 
DELIMITER $$

USE `tulainpast`$$

DROP FUNCTION IF EXISTS `f1`$$

CREATE DEFINER=`root`@`localhost` FUNCTION `f1`(nid INT(11)) RETURNS INT(11)
    DETERMINISTIC
    READS SQL DATA   
    BEGIN
	DECLARE aid INT; 
	DECLARE pid INT DEFAULT NULL; 
	
	cicle : LOOP
		SELECT @pid := `jp`.`parent_id` INTO pid
		FROM `jos_phocagallery_categories` AS `jp`
		WHERE `id` = nid
		LIMIT 1; 
		
		SELECT `id` INTO aid
		FROM `jos_phocagallery_categories` 
		WHERE `id` = nid
		LIMIT 1; 
		
		IF pid = 0 
			THEN 
				LEAVE cicle ;
		END IF ;
		SET nid = pid ;
  
	END LOOP cicle ;
	RETURN nid;
    END$$

DELIMITER ;
DELIMITER $$
================ query ======================
USE `tulainpast`;#`tulainpast_db`

TRUNCATE TABLE `tulainpast_rewrite_url`; 

INSERT INTO `tulainpast_rewrite_url` (`aid`,`oldurl`,`newurl`,`type`)
(SELECT 
	`a`.`id` AS `aid`
	,CONCAT(
		'/site/index.php/'
		,IF(
			(`m`.`parent` > 0)
			,CONCAT((SELECT `helpM`.`alias` FROM `jos_menu` AS `helpM` WHERE `helpM`.`id` = `m`.`parent`),'/')
			,'')
		,`m`.`alias`
		,'/category/'
		,CONCAT(`a`.`id`,'-',`a`.`alias`)
	) AS `oldurl`
	,NULL AS `newurl`
	,'gallery' AS `type`
	# working info
	#,`a`.`alias` AS `aAlias`
	#,`m`.`id` AS `mid`	
	#,`m`.`alias` AS `mAlias`
	#,`m`.`parent`
	#,SUBSTRING(`m`.`link`,LOCATE('=com_',`m`.`link`)+5,LOCATE('&view=',`m`.`link`)-LOCATE('=com_',`m`.`link`)-5) AS `component`
	#,SUBSTRING(`m`.`link`,LOCATE('&view=',`m`.`link`)+6,LOCATE('&layout=',`m`.`link`)-LOCATE('&view=',`m`.`link`)-6) AS `location`
FROM `jos_phocagallery_categories` AS `a`
	LEFT JOIN `jos_menu` AS `m` ON SUBSTRING(`m`.`link`,LOCATE('&id=',`m`.`link`)+4) = f1(`a`.`id`)
		AND `m`.`published` = 1 
		AND (`m`.`componentid` = 34)
) UNION (
SELECT 
	`a`.`id` AS `aid`
	,CONCAT(
		'/site/index.php/'
		,IF(
			(`m`.`parent` > 0)
			,CONCAT((SELECT `helpM`.`alias` FROM `jos_menu` AS `helpM` WHERE `helpM`.`id` = `m`.`parent`),'/')
			,'')
		,`m`.`alias`
		,'/'
		,IF(
			NOT(SUBSTRING(`m`.`link`,LOCATE('&view=',`m`.`link`)+6,LOCATE('&layout=',`m`.`link`)-LOCATE('&view=',`m`.`link`)-6) = 'category')
			,CONCAT(`c`.`id`,'-',`c`.`alias`,'/')
			,'')
		,CONCAT(`a`.`id`,'-',`a`.`alias`)
		
		
	) AS `oldurl`
	,NULL AS `newurl`
	,'article' AS `type`
	# working info
	#,`a`.`alias` AS `aAlias`
	#,`m`.`id` AS `mid`	
	#,`m`.`alias` AS `mAlias`
	#,`a`.`sectionid` AS `sid`
	#,`s`.`alias` AS `sAlias`
	#,`a`.`catid` AS `cid`
	#,`c`.`alias` AS `cAlias`
	#,`m`.`parent`
	#,SUBSTRING(`m`.`link`,LOCATE('=com_',`m`.`link`)+5,LOCATE('&view=',`m`.`link`)-LOCATE('=com_',`m`.`link`)-5) AS `component`
	#,SUBSTRING(`m`.`link`,LOCATE('&view=',`m`.`link`)+6,LOCATE('&layout=',`m`.`link`)-LOCATE('&view=',`m`.`link`)-6) AS `location`
FROM `jos_content` AS `a`
	LEFT JOIN `jos_sections` AS `s` ON `a`.`sectionid` = `s`.`id`
	LEFT JOIN `jos_categories` AS `c` ON `a`.`catid` = `c`.`id`
	LEFT JOIN `jos_menu` AS `m` ON IF(
		(SUBSTRING(`m`.`link`,LOCATE('&view=',`m`.`link`)+6,LOCATE('&layout=',`m`.`link`)-LOCATE('&view=',`m`.`link`)-6) = 'section')
		,`a`.`sectionid`
		,`a`.`catid`) = SUBSTRING(`m`.`link`,LOCATE('&id=',`m`.`link`)+4) 
		AND `m`.`published` = 1 
		AND (`m`.`componentid` = 20) 
		AND NOT(`m`.`alias` = 'about')
WHERE NOT(`a`.`id` = 7 OR `a`.`id` = 24) #exclude about and topografy
);
 */


/*******************************************************************************
 * Joomla password generate
 * /

require 'helper.php';
$juh = new JUserHelper();
$array['password'] = 'makaka';
$salt = $juh->genRandomPassword(32);
$crypt = $juh->getCryptedPassword($array['password'], $salt);
$array['password'] = $crypt.':'.$salt;
echo $array['password'];
 * 
 */
?>
