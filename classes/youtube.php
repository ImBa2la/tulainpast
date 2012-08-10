<?
class youtube extends xml{
function __construct($user){
	if(!($v = @file_get_contents('http://gdata.youtube.com/feeds/api/users/'.$user.'/uploads')))
		throw new Exception('YouTube user feed "'.$user.'" is not available');
	parent::__construct($v);
	$this->registerNameSpace('atom','http://www.w3.org/2005/Atom');
	$this->registerNameSpace('media','http://search.yahoo.com/mrss/');
}
function parseURL($url){
	if(preg_match('/\/watch\?v\=([a-zA-Z0-9_\-]+)/',$url,$m)){
		return $m[1];
	}
}
function videoItem($id = null,$tagname = 'video'){
	$xml = new xml(null,$tagname,false);
	$query = $id ? '/atom:feed/atom:entry/atom:id[contains(text(),"'.$id.'")]/parent::*' : '/atom:feed/atom:entry[1]';
	if(($e = $this->query($query)->item(0))
		&& ($id = $this->parseURL($this->evaluate('string(atom:link[@rel="alternate"]/@href)',$e)))
	){
		$r = $xml->de();
		$r->setAttribute('id',$id);
		$r->appendChild($xml->createElement('title',null,$this->evaluate('string(atom:title/text())',$e)));
		$r->appendChild($xml->createElement('desc',null,$this->evaluate('string(atom:content/text())',$e)));
	}elseif($id) throw new Exception('Video is not available',EXCEPTION_404);
	return $xml;
}
function videoList($page = 1,$pageSize = null,$tagname = 'videos'){
	$xml = new xml(null,$tagname,false);
	$res = $this->query('/atom:feed/atom:entry');
	if($res->length){
		$page = intval($page) > 0 ? intval($page) : 1;
		$pageSize = $pageSize ? abs(intval($pageSize)) : 7;
		$start = ($page-1)*$pageSize;
		$end = $start + $pageSize;
		$video = $xml->de();
		$video->setAttribute('rows',$res->length);
		$video->setAttribute('pages',ceil($res->length/$pageSize));
		$video->setAttribute('pagesize',$pageSize);
		$video->setAttribute('pageParam','page');
		$video->setAttribute('page',$page);
		foreach($res as $i => $e){
			if($i<$start || $i>=$end) continue;
			$r = $video->appendChild($xml->createElement('row'));
			if($id = $this->parseURL($this->evaluate('string(atom:link[@rel="alternate"]/@href)',$e)))
				$r->setAttribute('id',$id);
			$r->appendChild($xml->createElement('title',null,$this->evaluate('string(atom:title/text())',$e)));
			$r->appendChild($xml->createElement('desc',null,$this->evaluate('string(atom:content/text())',$e)));
			if($img = $this->query('media:group/media:thumbnail[1]',$e)->item(0)){
				$r->appendChild($xml->createElement('img',array(
					'src' => $img->getAttribute('url'),
					'width' => $img->getAttribute('width'),
					'height' => $img->getAttribute('height')
				)));
			}
		}
	}
	return $xml;
}
}
?>