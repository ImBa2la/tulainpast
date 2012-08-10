todo.cancelBubble=function(e){e=e||window.event;if(!e)return;if(e.stop)e.stopPropagation();else e.cancelBubble=true;if(e.preventDefault)e.preventDefault();else e.returnValue=false;};
todo.setEvent=function(name,e,f){if(!e._actionQueue)e._actionQueue={};if(!e._actionQueue[name]){e._actionQueue[name]=e['on'+name]?[e['on'+name]]:[];e['on'+name]=function(event){var e=event||window.event;for(var i=0;i<this._actionQueue[e.type].length;i++)if(this._actionQueue[e.type][i])this._actionQueue[e.type][i].call(this,event);}};var i=e._actionQueue[name].length;e._actionQueue[name][i]=f;return i;};
todo.deleteEvent=function(name,e,i){if(e._actionQueue && e._actionQueue[name] && e._actionQueue[name][i]){delete e._actionQueue[name][i];if(!e._actionQueue[name].length){delete e._actionQueue[name];delete e['on'+name];}}};
todo.bodyScroll=function(){if(window.pageYOffset)return {'top':window.pageYOffset,'left':window.pageXOffset};else if(document.documentElement)return {'top':document.documentElement.scrollTop,'left':document.documentElement.scrollLeft};else return {'top':(document.scrollTop||body.scrollTop),'left':(document.scrollLeft||body.scrollLeft)}};
todo.slide=function(e,x,y,breakOff,callback,delay,step){
	var stopIt=function(e){if(e && e.timer){window.clearInterval(e.timer);e.timer=null;if(e.callback)e.callback.call(e._);}};
	if(e._slide && e._slide.timer){if(breakOff)stopIt(e._slide);else return;};
	if(e.style.position=='absolute'){
		if(!e.style.left)e.style.left=e.offsetLeft+'px';
		if(!e.style.top)e.style.top=e.offsetTop+'px';
	}else{if(!e.style.left)e.style.left='0';if(!e.style.top)e.style.top='0';};
	e._slide={'_':e,
		'delay':delay?delay:50,
		'step':step?step:4,
		'start':{'x':parseInt(e.style.left),'y':parseInt(e.style.top)},
		'finish':{'x':x,'y':y},
		'callback':callback,
		'run':function(){if(!this.timer)this.timer=window.setInterval(function(o){return function(){o._slide.slide();}}(this._),this.delay);},
		'slide':function(){
			var res_x=todo.motion(this._.style.left,this.start.x,this.finish.x,this.step),
				res_y=todo.motion(this._.style.top,this.start.y,this.finish.y,this.step);
			this._.style.left=res_x.res+'px';
			this._.style.top=res_y.res+'px';
			if(res_x.ready && res_y.ready)stopIt(this);
		}
	};
	e._slide.run();
};
todo.resize=function(e,w,h,breakOff,callback,delay,step){
	var stopIt=function(e){if(e && e.timer){window.clearInterval(e.timer);e.timer=null;if(e.callback)e.callback.call(e._);}};
	if(e._resize!=undefined&&e._resize.timer){if(breakOff)stopIt(e._resize);else return;};
	if(w&&!e.style.width)e.style.width=e.offsetWidth+'px';
	if(h&&!e.style.height)e.style.height=e.offsetHeight+'px';
	e._resize={'_':e,
		'isImg':e.tagName.toLowerCase()=='img',
		'delay':delay?delay:50,
		'step':step?step:4,
		'start':{'w':parseInt(e.style.width),'h':parseInt(e.style.height)},
		'finish':{'w':w,'h':h},
		'callback':callback,
		'run':function(){if(!this.timer)this.timer=window.setInterval(function(o){return function(){o._resize.resize();}}(this._),this.delay);},
		'resize':function(){
			var ready=true;
			if(this.finish.w){
				var res_w=todo.motion(this._.style.width,this.start.w,this.finish.w,this.step);
				if(this.isImg)this._.width=res_w.res;
				this._.style.width=res_w.res+'px';
				if(!res_w.ready)ready=false;
			};
			if(this.finish.h){
				var res_h=todo.motion(this._.style.height,this.start.h,this.finish.h,this.step);
				if(this.isImg)this._.height=res_h.res;
				this._.style.height=res_h.res+'px';
				if(!res_h.ready)ready=false;
			};
			if(ready)stopIt(this);
		}
	};
	e._resize.run();
};
todo.opacity=function(e,opacity,delay,breakOff,callback){
	var stopIt=function(e){if(e && e.timer){window.clearInterval(e.timer);e.timer=null;if(e.callback)e.callback.call(e._);}};
	if(e._opacity && e._opacity.timer){if(breakOff)stopIt(e._opacity);else return;};
	e._opacity={'_':e,
		'delay':delay,
		'step':0.07,
		'finish':opacity,
		'callback':callback,
		'run':function(){
			if(!this.timer)this.timer=window.setInterval(
				function(o,stopIt){return function(){
						var v=o.get()+o.step*o.sign;
						if((o.sign>0 && v>=o.finish) || (o.sign<0 && v<=o.finish) || v>1 || v<0){o.set(o.finish);stopIt(o);}else o.set(v);
					}}(this,stopIt),this.delay);
			
		},
		'set':function(v){
			var p=this.prop();
			if(p=='filter'){if(v==1)v='';else v='alpha(opacity='+v*100+')';}
			this._.style[p]=v;
		},
		'get':function(){
			var p=this.prop();
			if(p=='filter'){
				var tmp=this._.style.filter.match(/opacity=([0-9]{1,3})/);
				if(tmp&&tmp[1])return parseInt(tmp[1])/100;
				return 1;
			};
			return this._.style[p]==''?1:parseFloat(this._.style[p]);
		},
		'prop':function(){
			if(typeof document.body.style.opacity=='string')return 'opacity';
			else if(typeof document.body.style.MozOpacity=='string')return 'MozOpacity';
			else if(typeof document.body.style.KhtmlOpacity=='string')return 'KhtmlOpacity';
			else if(document.body.filters&&navigator.appVersion.match(/MSIE ([\d.]+);/)[1]>=5.5)return 'filter';
			return 'opacity';
		}
	};
	if(!delay){e._opacity.set(opacity);return;};
	e._opacity.sign=e._opacity.get()>opacity?-1:1;
	e._opacity.run();
};
todo.dragZIndex=199;
todo.drag=function(e){
	e._drag={'_':e,'x':null,'y':null,'draged':false,'onClick':e.onclick,
		'startDrag':function(event){
			event=event||window.event;
			this.x=event.clientX-parseInt(this._.style.left);
			this.y=event.clientY-parseInt(this._.style.top);
			this.eventIndexMouseMove=todo.setEvent('mousemove',document,function(e){return function(event){return e.drag(event);}}(this));
			this.eventIndexMouseUp=todo.setEvent('mouseup',document,function(e){return function(event){return e.stopDrag(event);}}(this));
			this.draged=false;
			if(this._.style.zIndex<todo.dragZIndex)this._.style.zIndex=++todo.dragZIndex;
			todo.cancelBubble(event);
		},
		'drag':function(event){
			event=event||window.event;
			this._.style.left=(event.clientX-this.x)+'px';
			this._.style.top=(event.clientY-this.y)+'px';
			todo.cancelBubble(event);
			this.draged=true;
		},
		'stopDrag':function(event){
			todo.deleteEvent('mousemove',document,this.eventIndexMouseMove);
			todo.deleteEvent('mouseup',document,this.eventIndexMouseUp);
			return false;
		}
	};
	e.onclick=function(e){return function(event){if(e.draged){e.draged=false;todo.cancelBubble(event);return false;}else if(e.onClick)e.onClick.call(this,event);}}(e._drag);
	e.onmousedown=function(e){this._drag.startDrag(e);};
	e.style.position='absolute';
	e.style.zIndex=++todo.dragZIndex;
	return e;
};
todo.popupZIndex=99999;
todo.popup=function(width,height){
	width=width?width:300;
	height=height?height:300;
	var getCenterPos=function(w,h){
			var size=todo.clientSize();
			return {'left':size.w>w?Math.ceil((size.w-w)/2):17,
				'top':size.h>h?Math.ceil((size.h-h-46)/2):17}
		},
		pos=getCenterPos(width,height),
		e=todo.create('div',{'class':'todo_popup'},null,{'position':'absolute','left':pos.left+'px','top':pos.top+'px','width':width+'px','height':height+'px','zIndex':todo.popupZIndex});
	e._popup={'_':e,'isClose':true,
		'close':function(){
			todo.opacity(this._,0.5,50,false,function(w){return function(){w._=w._.parentNode.removeChild(w._);document.body.removeChild(document.getElementById('todo_wrap'));}}(this));
			this.isClose=true;
		},
		'open':function(){
			if(!this.isClose)return;
			this._.style.zIndex=todo.popupZIndex;
			this.isClose=false;
			todo.opacity(this._,1);
			this._=document.body.appendChild(this._);
			if(this.content)this.content=this.content.parentNode.removeChild(this.content);
			else this.content=todo.create('div',null,null,{'paddingTop':'23px','paddingBottom':'23px'});
			this._.innerHTML='<div class="todo_top"></div><div class="todo_bottom"></div><div class="todo_left"></div><div class="todo_right"></div><div class="todo_corner1"></div><div class="todo_corner2"></div><div class="todo_corner3"></div><div class="todo_corner4"></div>';
			this._.appendChild(this.content);
			this._.appendChild(todo.create('div',{'class':'todo_button_close'})).onclick=function(){this.parentNode._popup.close();};
		},
		'getSize':function(){return {'width':parseInt(this._.style.width),'height':parseInt(this._.style.height)}},
		'getCenterPos':getCenterPos,
		'resize':function(w,h,callback){todo.resize(this._,w,h+46,false,callback);},
		'position':function(left,top){todo.slide(this._,left,top);}
	};
	e._popup.open();
	return e;
};
todo.gallery=function(name,slideshow){
	var a=document.getElementsByTagName('a'),res,g;
	if(!this._galleries)this._galleries={};
	for(var i=0;i<a.length;i++){
		if(!a[i].rel)continue;
		res=a[i].rel.match(new RegExp(name+'\\[([a-zA-Z0-9]+)\\]'));
		if(!res)continue;
		if(this._galleries[res[1]])g=this._galleries[res[1]];
		else{
			g={'name':res[1],'a':[],'slideshow':slideshow,'slideshowTimer':null,
				'startSlideshow':function(){
					if(this.slideshowTimer)return;
					this.slideshowTimer=window.setInterval(function(g){return function(){if(g.getWin()._popup.isClose || !g.next())g.stopSlideshow();}}(this),this.slideshow);
					if(this.current>=this.a.length-1)this.current=-1;
					if(this.slideShowButton)this.slideShowButton._changeState();
				},
				'stopSlideshow':function(){
					window.clearInterval(this.slideshowTimer);
					this.slideshowTimer=null;
					if(this.slideShowButton)this.slideShowButton._changeState();
				},
				'getWin':function(){
					if(!this.win){
						this.win=todo.drag(todo.popup(200,200));
						this.e=this.win._popup.content;
						this.win._popup.close__def=this.win._popup.close;
						this.win._popup.close=function(e){return function(){e.stopSlideshow();this.close__def();}}(this);
					}else if(this.win._popup.isClose)this.win._popup.open();
					return this.win;
				},
				'setBackground':function(){
					if(!(document.getElementById('todo_wrap'))){
						this.bg = document.body.appendChild(todo.create('div',{'class':'todo_wrap','id':'todo_wrap'}));
						this.bg.style.height = document.body.offsetHeight+'px';
					}
				},
				'setLoader':function(){
					var pos=this.getWin()._popup.getSize();
					this.e.innerHTML='';
					this.loader=this.e.appendChild(todo.create('div',{'class':'todo_gallery_loader'}));
					this.loader.style.top=((pos.height-66)/2)+'px';
					this.loader.style.left=((pos.width-66)/2)+'px';
				},
				'setImage':function(){
					var img=this.img,win=this.getWin(),size=todo.clientSize(),w=img.width,h=img.height;
					this.collapse=null;
					if(size.h<h+100){
						w=Math.ceil((size.h-100)*w/h);h=size.h-100;
						this.collapse=true;
					};
					if(size.w<w+100){
						this.collapse={'state':0,'size':{'w':w,'h':h}};
						h=Math.ceil((size.w-100)*h/w);w=size.w-100;
						this.collapse=true;
					};
					if(this.collapse){
						this.collapse={'state':true,'size':{'w':img.width,'h':img.height},'collapse':{'w':w,'h':h}};
						img.width=w;img.height=h;
					};
					var pos=win._popup.getCenterPos(w,h),
						scr=todo.bodyScroll();
					this.e.innerHTML='';
					win._popup.resize(w,h,function(g){return function(){g.img=g.e.appendChild(g.img);}}(this));
					win._popup.position(pos.left+scr.left,pos.top+scr.top);
					this.setTitle();
					this.setNav();
					this.setExpand();
				},
				'set':function(a){
					this.setBackground();
					this.setLoader();
					this.img=todo.create('img',{'src':a.href});
					for(var i=0;i<this.a.length;i++)if(a==this.a[i]){
						this.current=i;
						this.img._gallery=this;
						this.img._timer=window.setInterval(function(g){return function(){if(g.img.complete || g.img._counter>1000){g.setImage();window.clearInterval(g.img._timer)}else g.img._counter++;}}(this),200);
						break;
					}
				},
				'next':function(){if(this.current<this.a.length-1){this.set(this.a[this.current+1]);return true;};return false;},
				'prev':function(){if(this.current>0){this.set(this.a[this.current-1]);return true;};return false;},
				'setTitle':function(){if(this.a[this.current].title){this.e.appendChild(todo.create('div',{'class':'todo_gallery_title'},this.a[this.current].title),{});}},
				'setNav':function(){
					if(this.a.length==1)return;
					var e=this.e.appendChild(todo.create('div',{'class':'todo_gallery_nav'},(this.current+1)+' из '+this.a.length));
					if(this.current>0){
						var left=e.appendChild(todo.create('div',{'class':'todo_gallery_button_left','title':'Предидущая'}));
						left._gallery=this;
						left.onclick=function(){this._gallery.prev();};
					};
					if(this.current<this.a.length-1){
						var right=e.appendChild(todo.create('div',{'class':'todo_gallery_button_right','title':'Следующая'}));
						right._gallery=this;
						right.onclick=function(){this._gallery.next();};
					};
					if(this.slideshow){
						var ss=e.appendChild(todo.create('div',{'class':'todo_gallery_button_pause','title':'Пауза'}));
						ss._gallery=this;
						ss._state=this.slideshowTimer;
						ss._changeState=function(){
							this._state=!this._state;
							if(this._state)this.className='todo_gallery_button_pause';
							else this.className='todo_gallery_button_play';
							return this._state;
						};
						ss.onclick=function(){
							if(this._state)this._gallery.stopSlideshow();
							else this._gallery.startSlideshow();
						};
						this.slideShowButton=ss;
					}
				},
				'setExpand':function(){
					if(!this.collapse)return;
					var e=this.e.appendChild(todo.create('div',{'class':'todo_gallery_button_expand'}));
					e._gallery=this;
					e.onclick=function(){
						var win=this._gallery.getWin(),size,
							state=this._gallery.collapse.state=!this._gallery.collapse.state;
						this.className='todo_gallery_button_'+(state?'expand':'collapse');
						if(state)size=this._gallery.collapse.collapse;
						else size=this._gallery.collapse.size;
						var pos=win._popup.getCenterPos(size.w,size.h),
							scr=todo.bodyScroll();
						win._popup.resize(size.w,size.h);
						todo.resize(this._gallery.img,size.w,size.h);
						win._popup.position(pos.left+scr.left,pos.top+scr.top);
					}
				},
				'add':function(e){
					for(var i=0;i<this.a.length;i++)if(e==this.a[i])return;
					e._gallery=this;
					e.onclick=function(){
						this._gallery.set(this);
						if(this._gallery.slideshow)this._gallery.startSlideshow();
						return false;
					};
					this.a[this.a.length]=e;
				}
			};
			this._galleries[g.name]=g;
		};
		g.add(a[i]);
	}
};
window.onresize = function(){
	if(w = document.getElementById('todo_wrap'))w.style.height = document.body.offsetHeight+'px';
}