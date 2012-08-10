/**
* todo
* author: Pavel Khoroshkov aka pgood
* http://pgood.ru
*/
function todo(){
	this.action=[];
	this.execute=function(){if(!this.action)return;for(var i in this.action)try{this.action[i]();}catch(er){alert('todo >> "'+er.message+'" in "'+er.fileName+'" on "'+er.lineNumber+'"');};this.action=null;};
	var onLoadAction=function(o){return function(){o.execute();}}(this);
	if(document.addEventListener)document.addEventListener('DOMContentLoaded',onLoadAction,false);
	else if(document.attachEvent)document.attachEvent('onreadystatechange',function(){if(document.readyState==='complete')onLoadAction();});
	else if(window.addEventListener)window.addEventListener('load',onLoadAction,false);
	else if(window.attachEvent)window.attachEvent('onload',onLoadAction);
};
todo.prototype.get=function(id){return document.getElementById(id);};
todo.prototype.create=function(tag,attrs,text,style){var e=document.createElement(tag);if(attrs)for(var i in attrs)switch(i){case 'class': e.className=attrs[i];break;case 'id': e.id=attrs[i];break;default: e.setAttribute(i,attrs[i]);break;};if(text)e.appendChild(document.createTextNode(text));if(style)for(var i in style)e.style[i]=style[i];return e;};
todo.prototype.onload=function(func){this.action[this.action.length]=func;};
todo.prototype.loop=function(e,func,i,step){if(!e||!e.length)return;step=step?Math.abs(step):1;for(var i=i?i:0;i<e.length;i+=step)if(typeof(e[i])=='object')func.call(e[i],i);else func(i,e[i]);};
todo.prototype.stopPropagation=function(e){e=e||window.event;if(!e)return;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true;};
todo.prototype.ajax=function(url,callback,params,method){
	if(XMLHttpRequest==undefined){XMLHttpRequest=function(){try{return new ActiveXObject('Msxml2.XMLHTTP.6.0');}catch(e){};try{return new ActiveXObject('Msxml2.XMLHTTP.3.0');}catch(e){};try{return new ActiveXObject('Msxml2.XMLHTTP');}catch(e){};try{return new ActiveXObject('Microsoft.XMLHTTP');}catch(e){};throw new Error('This browser does not support XMLHttpRequest');}};
	var r=new XMLHttpRequest,data='';
	r._callback=callback;
	r.onreadystatechange=function(){if(this.readyState==4){if(this.status==200&&this._callback)this._callback(this.responseText,this.responseXML);}};
	if(method=='post'){
		if(params)for(var i in params)data+=i+'='+encodeURIComponent(params[i])+'&';
		r.open('POST',url,true);
		r.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		r.setRequestHeader('Content-Length',data.length);
	}else{if(params){
		url+=url.match(/\?/)?'&':'?';
		for(var i in params)url+=i+'='+encodeURIComponent(params[i])+'&';
	};r.open('GET',url,true);};
	r.send(data);
};
todo.prototype.setClass=function(e,name,v){
	if(e.className){
		var c=e.className.split(' ');
		for(var i=0;i<c.length;i++)if(!c[i]||c[i]==name)delete c[i];
		if(v)c.push(name);
		e.className=c.join(' ');
	}
};
todo.prototype.clientSize=function(){if(document.compatMode=='CSS1Compat'){return {'w':document.documentElement.clientWidth,'h':document.documentElement.clientHeight};}else{return {'w':document.body.clientWidth,'h':document.body.clientHeight}}};
todo.prototype.motion=function(v,start,finish,step){v=parseInt(v);var last=Math.abs(Math.abs(finish)-Math.abs(v)),step=Math.ceil((step+last)/step),res,ready;if(finish>=start){res=v+step;ready=res>=finish;}else{res=v-step;ready=res<=finish};return {'res':ready?finish:res,'ready':ready};};
todo=new todo;