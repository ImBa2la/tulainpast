$(document).ready(function() {
	$(window).load(function(){
		/* init params */
		var ul = $("section.gallery ul"),
			li = $("section.gallery li"),
			widthUl = 55/*(ul padding) mul 2 */,
			h = 105,/*base height img*/
			hResize = 120,
			imgArray = [],
			imgBorder = 2;/*2px solid #fff*/
		li.eq(0).addClass('active');
		li.each(function(){
			var i = $(this).index();
			$(this)
				.find('img')
				.each(function(){
					addImage(this);//preload
					this.parentNode.parentNode.w = imgArray[i].width;//save original sizes for resizing to li element
					this.parentNode.parentNode.h = imgArray[i].height;
					/*width*/
					this.width = Math.floor(h/imgArray[i].height*imgArray[i].width);
					this.parentNode.style.width = Math.floor(h/imgArray[i].height*imgArray[i].width)+imgBorder*2+'px';
					this.parentNode.parentNode.style.width = Math.floor(h/imgArray[i].height*imgArray[i].width)+imgBorder*2+'px';
					/*height*/
					this.height = h;
					this.parentNode.style.height = h+imgBorder*2+'px';
					this.parentNode.parentNode.style.height = h+imgBorder*2+'px';
					
					/*this.parentNode.parentNode.w = getImageSize(this).width;//save original sizes for resizing to li element
					this.parentNode.parentNode.h = getImageSize(this).height;
					/*width* /
					this.width = Math.floor(h/this.parentNode.parentNode.h*this.parentNode.parentNode.w);
					this.parentNode.style.width = Math.floor(h/this.parentNode.parentNode.h*this.parentNode.parentNode.w)+imgBorder*2+'px';
					this.parentNode.parentNode.style.width = Math.floor(h/this.parentNode.parentNode.h*this.parentNode.parentNode.w)+imgBorder*2+'px';
					/*height* /
					this.height = h;
					this.parentNode.style.height = h+imgBorder*2+'px';
					this.parentNode.parentNode.style.height = h+imgBorder*2+'px';*/
				});	
			widthUl += $(this).outerWidth(true);
		});
		ul.width(widthUl);
		var  stopRightPoint = widthUl - $("section.gallery").find('div').outerWidth(true)
			,speed = widthUl + 2000;
		
		/* click functional */
		$("section.gallery a" +
			",div.big-img span.right,	div.big-img span.left"+
			",section.gallery span.right,	section.gallery span.left"
		).click(clicked);
		
			
		/* hover functional */
		li.hover(
			function(){if(!(this.className == 'active')){size($(this),0,33)}},
			function(){if(!(this.className == 'active')){size($(this),1,30)}}
		);
		$("section.gallery span.right,section.gallery span.left").hover(
			function(){ul.animate({marginLeft: ($(this).attr('class') == 'right')?-stopRightPoint:0},speed);},
			function(){ul.stop(true);}
		);
		
		/* functions 
		 * */
		function clicked(e){
			var eq = 0,eqLast = $("section.gallery li.active").index();
			ul.stop(true);
			if(e != 'slideshow') slideShow(true);
			else $("div.big-img span.play").click(function(){slideShow(true)});
			
			if(($(this).attr('class') == 'right') || (e == 'slideshow'))
				eq = ($("section.gallery li.active").index() == (li.size() - 1)) ? 
						0 : $("section.gallery li.active").index() + 1;
			if($(this).attr('class') == 'left')
				eq = ($("section.gallery li.active").index() == 0) ? 
						li.size() - 1 : $("section.gallery li.active").index() - 1;
			if(this.href)
				eq = $(this).parent('li').index();
			li.removeAttr('class');
			li.eq(eq).addClass('active');
			gall(eq,eqLast);
			return false;
		}
		function size(li, act, z){
			var  oWidth	= (act == 0)? Math.ceil(hResize/li[0].h*li[0].w):Math.ceil(h/li[0].h*li[0].w)
				,oHeight= (act == 0)? hResize:h
				,oLeft	= (act == 0)? (hResize/li[0].h*li[0].w - h/li[0].h*li[0].w)/(-2) + 'px':'0px' 
				,oTop	= (act == 0)? (hResize-h)/(-2)+'px':'0px';
			li.css('z-index',z)
			.find('a').css({'box-shadow':((act == 0)?'10px 2px 10px #333,-1px 5px 10px #333':'3px 3px 10px #333 inset,-3px -3px 10px #333 inset')}).animate({"top":oTop,'left':oLeft,'width':oWidth+imgBorder*2 + 'px','height':oHeight+imgBorder*2 + 'px'},500)
			.find('img').animate({'width':oWidth + 'px','height':oHeight + 'px','opacity':(act == 0)?1:0.5},500);
				
		}
		function gall(eq,eqLast) {//actions for change image
			var imgCurr = $("img.big-img"),
				imgNext = $("section.gallery li.active a"),
				alt = imgNext.attr('title') ? imgNext.attr('title') : '',
				src = imgNext.attr('href'),
				border = 3,
				offset = 0,
				offsetUl = 0,
				heightWork = (parseInt(imgNext.attr('width')) < 626 ) ? parseInt(imgNext.attr('height')) + border*2 : Math.ceil(626 / parseInt(imgNext.attr('width')) * parseInt(imgNext.attr('height'))) + border*2,
				speed = Math.abs(heightWork - border*2 - imgCurr.height()) * 1.5 + 300;
			//change big image
			$("div.big-img div.text").animate({"opacity":0},speed);
			imgCurr.fadeOut(speed,function(){
				var interval = setInterval(resizeElements,1);
				$(this).parent('div.img')
				//.append(($(this).is('img.loader'))?'<img class="loader" src="images/loader2.gif" />':'')
				.animate({"height":heightWork},speed,false,function(){
					imgCurr.css({"border-width":3});
					imgCurr.attr('alt',alt);
					imgCurr.attr('src',src);
					$("div.big-img div.text").text(alt).animate({"opacity":1},speed,false,function(){
						$('img.loader').detach();
						$('img.big-img').fadeIn(speed);
					});
					clearInterval(interval);
				}).children('img.loader')
				.css({'margin-top':(parseInt($(this).parent('div.img').height()) / 2 - 40)});
			});
			
			//slider scroll
			li.each(function(){
				if($(this).index() < eq)
					offset += parseInt($(this).outerWidth(true));
			});
			offsetUl = (offset < stopRightPoint)?((eq != 0)?offset - li.eq(eq-1).outerWidth(true) + 20:0):stopRightPoint;
			size(li.eq(eqLast),1,30);
			ul.animate({marginLeft: -offsetUl},1000,false,function(){size(li.eq(eq),0,31);});
		}
		function resizeElements(){
			$('div.img img.loader').css({'margin-top':(parseInt($('div.img').height()) / 2 - 40)});
			$("div.big-img span.right").css({height: parseInt($('div.img').height()) + 4});
			$("div.big-img span.left").css({height: parseInt($('div.img').height()) + 4});
			$("div.big-img span.play").css({top: parseInt($('div.img').height())/2 - 30});
		}
		function slideShow(act){
			if(act == true){
				clearInterval(window.timer);
				$("div.big-img span.play").click(slideShow);
			}else{
				clearInterval(window.timer);
				window.timer = setInterval(clicked,8000,'slideshow');
			}
		}
		function addImage(oPic) {
			imgArray[imgArray.length] = new Image();
			imgArray[imgArray.length-1].src = (typeof oPic == 'object')?oPic.src:oPic;//object or string
		}
		li.css('display','block').eq(0).find('a').click();//call first initialize
	});
});