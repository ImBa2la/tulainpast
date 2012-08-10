$(document).ready(function(){
	$('.debug span.close').click(function(){
		$(this).parents('.debug').remove();
	});
	$('.debug span.hide').click(function(){
		$(this).siblings('pre').hide();
		$(this).siblings('span.view').css('visibility','visible');
		$(this).hide();
		
	});
	$('.debug span.view').click(function(){
		$(this).siblings('pre').show();
		$(this).siblings('span.hide').show();
		$(this).css('visibility','hidden');
	});
	$('.debug').mousedown(function(e){
		
	var test = e;
  alert('Нажата кнопка мыши. '+
        'В обработчик этого события переданы данные: ' + e.data);
	});
	var test1 = test;
});