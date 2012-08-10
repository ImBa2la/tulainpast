function sendAjax(data,a){
	$.ajax({
		type:'POST',
		url:'classes/ajax.php',
		data: data,
		dataType:'json',
		success:function(data){
			if(a && data.result == 'successful'){
				a.innerHTML = (a.rel == 1)?'Отписаться от новостей':'Подписка на новости';
				a.rel = (a.rel == 1)?0:1;
			}
			$("div.subnews form")
				.find('div.mess').css('display','block').text(data.message)
				.parent().find('div.form').css('display','none');
		}
	});
}
$(document).ready(function() {
	var marg_wind = $(window).width()/2;
	
	$("a.subnews").click(function(){
		if(this.rel) sendAjax('action=subscribe&subs[act]='+this.rel,this);
		$(".mapswf").fadeOut(200);
		$("div.mod-window-bg").fadeIn(250);
		setTimeout(function() {
			$("div.subnews").css('display','block');
			$("div.subnews").animate({left: marg_wind},500);
		}, 250);
		$(window).resize(function(){
			var marg_wind2 = $(window).width()/2;
			if ($("div.mod-window-bg").css('display') == 'block') {
				$("div.subnews").animate({left: marg_wind2},100);
			}
		});
	});
	$("div.subnews form input[type='button']").click(function(){
		try{/*validation*/
			var p=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
			if(!this.form.name.value){alert('Поле "Имя" должно быть заполнено');return false;};
			if(!p.test(this.form.email.value)){alert('Поле "E-mail" заполнено не корректно');return false;};			
		}catch(er){alert(er.message);}
		sendAjax('action=subscribe&subs[name]='+this.form.name.value+'&subs[email]='+this.form.email.value+'&subs[act]=1');
	});
	$("div.authoriz form input[type='button']").click(function(){
		try{/*validation*/
			if(!this.form.login.value){alert('Поле "Логин" должно быть заполнено');return false;};
			if(!this.form.password.value){alert('Поле "Пароль" должно быть заполнено');return false;};			
		}catch(er){alert(er.message);}
		this.form.submit();
	});
	$("div.log a.login").click(function(){
		$(".mapswf").fadeOut(200);
		$("div.mod-window-bg").fadeIn(250);
		setTimeout(function() {
			$("div.authoriz").css('display','block');
			$("div.authoriz").animate({left: marg_wind},500);
		}, 250);
		$(window).resize(function(){
			var marg_wind2 = $(window).width()/2;
			if ($("div.mod-window-bg").css('display') == 'block') {
				$("div.authoriz").animate({left: marg_wind2},100);
			}
		});
	});
	$("div.mod-window-bg").click(function(){
		$("div.subnews").animate({left: "-210px"},500);
		setTimeout(function() {
			$("div.subnews").css('display','none')
				.find('div.mess').css('display','none')
				.parent().find('div.form').css('display','block');
			$("div.mod-window-bg").fadeOut(250);
		}, 500);
		$("div.authoriz").animate({left: "-210px"},500);
		setTimeout(function() {
			$("div.authoriz").css('display','none');
			$("div.mod-window-bg").fadeOut(250);
			$(".mapswf").fadeIn(200);
		}, 500);
	});
	
	if(/^\#unSubscribe=/.test(location.hash)){
		var email = location.hash.substr(13);
		sendAjax('action=subscribe&subs[email]='+email+'&subs[act]=0');
		$("a.subnews").click();
	}
});