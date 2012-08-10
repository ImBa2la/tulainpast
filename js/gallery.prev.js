$('#wrapper').append('<div class="img-prev"><img src="images/loader2.gif" alt=" " width="86" height="86" class="gallery" /></div>');
$(document).ready(function() {
	var i = $("div.img-prev");
	$('ul.nest-1 > li.photo > ul.nest-2').hover(function(){i.show()},function(){i.hide()});
	$("ul.nest-2 > li a img").parent('a').hover(
		function(){// screenY / clientY / layerY (not IE, opera; bag FF)
			var offset = Math.ceil(offsetFunc(this,this.offsetTop) - i.outerHeight()/2 + this.offsetHeight/2)
				,src = $(this).find('img').attr('src');
				
			i.find('img').animate({"opacity":0},300,false,function(){
				i.animate({top:parseInt(offset)},300,false,function(){
					i.find('img').attr('src',src).animate({"opacity":1},300);
				}).find('img').attr('src',src);				
			});
			i.stop(true);
		},
		function(){i.stop(true);}
	);

});
function offsetFunc(e, offset){
	if(e.offsetParent instanceof HTMLBodyElement) return offset;
	else return offsetFunc(e.offsetParent,offset+e.offsetParent.offsetTop);
}
function editor(id,prop){
	var v={
// General options
mode : "none",
theme : "advanced",
plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",
language : "ru",

// Theme options
theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",
theme_advanced_toolbar_location : "top",
theme_advanced_toolbar_align : "left",
theme_advanced_statusbar_location : "bottom",
relative_urls : false,

theme_advanced_resize_horizontal : false,
theme_advanced_resizing : true,

// Example content CSS (should be your site CSS)
content_css : "css/tinymce.css",

// Drop lists for link/image/media/template dialogs
template_external_list_url : "lists/template_list.js",
external_link_list_url : "lists/link_list.js",
external_image_list_url : "lists/image_list.js",
media_external_list_url : "lists/media_list.js",


// Replace values for the template plugin
template_replace_values : {
	username : "Some User",
	staffid : "991234"
}/*,

file_browser_callback: function(field_name, url, type, win){
		tinyMCE.activeEditor.windowManager.open({
			file: 'uploader.php?opener=tinymce&type='+type,
			title: 'Active Page File Manager',
			width: 635,
			height: 500,
			resizable: "yes",
			inline: true,
			close_previous: "no",
			popup_css: false
		},{
			callback: function(url){
				win.document.getElementById(field_name).value=url;
				if(typeof(win.ImageDialog) != "undefined"){
					if(win.ImageDialog.getImageData)win.ImageDialog.getImageData();
					if(win.ImageDialog.showPreviewImage)win.ImageDialog.showPreviewImage(url);
				};	
			}
		});
		return false;
	}
*/};
	if(typeof(prop)=='object')for(var i in prop)v[i]=prop[i];
	tinyMCE.init(v);
	if(id&&v.mode=='none')tinyMCE.execCommand("mceAddControl",true,id);
};