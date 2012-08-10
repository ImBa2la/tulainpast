todo.onload(function(){
	var content = document.getElementById('content')
		,divs = content.getElementsByTagName('div')
		,tpl = '<div class="img-custom %FLOAT%"><div class="img-wrapper">%CONTENT%<div class="tl"></div><div class="t"></div><div class="tr"></div><div class="l"></div><div class="r"></div><div class="bl"></div><div class="b"></div><div class="br"></div></div>%DESC%</div>'
		,divsFixed = []; //fix for dinamic append elements div
	for(var k = 0; k < divs.length; k++) divsFixed.push(divs[k]);

	for(var k = 0; k < divsFixed.length;k++){
		if(!divsFixed[k].className || (divsFixed[k].className != 'new')){continue;}
		var article = divsFixed[k]
				 ,a = article.getElementsByTagName('a')
			  ,imgs = article.getElementsByTagName('img')
		for(var i=0;i<imgs.length;i++){
			if(imgs[i].className == 'notTouch') continue;
			var e = (imgs[i].parentNode.tagName.toLowerCase() == 'a')?imgs[i].parentNode:imgs[i]
				,align = imgs[i].className=='right'? 'right':'left'
				,title = (!imgs[i].title)? ((!e.title)?(!imgs[i].alt?false:imgs[i].alt):e.title):imgs[i].title
				,desc = (!title)?'':'<div class="img-desc" style="text-align:'+align+';width:'+(imgs[i].width)+'px">'+title+'</div>';
			if((e.parentNode.tagName.toLowerCase() == 'p') && (e.parentNode.children.length == 1)) e.parentNode.style.marginBottom = '0px';
			e.outerHTML = tpl.replace('%CONTENT%',e.outerHTML).replace('%FLOAT%',align).replace('%DESC%',desc);		
			todo.setClass(imgs[i],align);
		}
		for(var i=0;i<a.length;i++){
			if(!a[i].title && a[i].children.lenth){//title for img into gallery box
				for(var j=0;j<a[i].children.length;j++){
					if((a[i].children[j].tagName.toLowerCase() == 'img') && (a[i].children[j].title || a[i].children[j].alt)){
						a[i].title = a[i].children[j].title ? a[i].children[j].title : a[i].children[j].alt;
						break;
					}
				}
			}
			if(/(?:.jpe?g)|(?:.gif)/i.test(a[i].href) && !a[i].rel)
				a[i].rel='gallery[0]';
		}	
	}
	todo.gallery('gallery');
	
});