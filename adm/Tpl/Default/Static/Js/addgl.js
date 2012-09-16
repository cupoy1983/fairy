$(document).ready(function(){
	$("select[name='cates']").bind("dblclick",function(){
		add_cate('cates');
	});
        
                    $("select[name='cates']").bind("click",function(){
                            add_select_lv2();
	});
});
function add_select_lv2(){
    var select_option = $("select[name='cates']").find("option:selected");
    var select_value ;
    for(i=0;i<select_option.length;i++)
    {
                        select_value =$(select_option[i]).attr("value");
    }
    if(select_value !=''){
         var query = new Object();
	query.id = select_value;
                    query.lv = 'lv2';
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=' + CURR_MODULE + '&' + VAR_ACTION + '=getSelect',
		type:"POST",
		cache: false,
		data:query,
		dataType:"text",
		success: function(result){
			$('#lv2').html(result);
                                                            $("select[name='lv2']").bind("click",function(){
                                                                add_select_lv3();
                                                            });
                                                            $("select[name='lv2']").bind("dblclick",function(){
                                                                add_cate('lv2');
                                                            });
		}
	});
    }
}
function add_select_lv3(){
    var select_option = $("select[name='lv2']").find("option:selected");
    var select_value ;
    for(i=0;i<select_option.length;i++)
    {
                        select_value =$(select_option[i]).attr("value");
    }
    if(select_value !=''){
         var query = new Object();
	query.id = select_value;
                    query.lv = 'lv3';
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=' + CURR_MODULE + '&' + VAR_ACTION + '=getSelect',
		type:"POST",
		cache: false,
		data:query,
		dataType:"text",
		success: function(result){
			$('#lv3').html(result);
                                                            $("select[name='lv3']").bind("dblclick",function(){
                                                                add_cate('lv3');
                                                            });
		}
	});
    }
}
function add_catebtn(obj){
        $html = '<div id="btn_'+obj.id+'" onclick="remove_cate(this)"><input type="button" value="'+obj.name+' X " style="margin-right:10px;margin-bottom: 10px;"/><input type="hidden" name="fcatelist[]" value="'+obj.id+'" /></div>';
        $("#faddcate_btn").append($html);
}
function add_cate(lv)
{
	var select_option = $("select[name='"+lv+"']").find("option:selected");
	var obj = new Array();
                    var obj = new Object() ;
	for(i=0;i<select_option.length;i++)
	{
                        obj.name = $(select_option[i]).text();
                        obj.id =   $(select_option[i]).attr("value");
	}
        add_catebtn(obj);
}

function remove_cate(obj)
{
        var div_id = obj.id;
        $("#"+div_id).remove()
}

