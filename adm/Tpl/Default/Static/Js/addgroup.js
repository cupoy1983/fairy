$(document).ready(function(){
	$("select[name='cids']").bind("dblclick",function(){
		add_cate(this);
	});
        $("#cids_select").hide();
        $("#parent_gid").bind("change",function(){chage_parent_gid();});
        
        $("select[name='cids']").bind("click",function(){
            var pid = $("select[name='cids']").val();  //获取Select选择的Value
		getCategory(pid,'cids2');
	});
        
});

function add_cate(obj)
{
    var cid = $(obj).find("option:selected").val();
    var c_name =  $(obj).find("option:selected").text();
    var add_input = $('#add_items').find('input');
    for(i=0;i<add_input.length;i++)
    {
        if(cid ==$(add_input[i]).attr("cid"))
            return;
    }
    $('#add_items').append('<span class="add_item">'+c_name+'<input type="hidden" name="addcids[]" value="'+cid+'-'+c_name+'" cid="'+cid+'"/></span>');
    $(".add_item").bind("dblclick",function(){
		remove_cate(this);
	});
}

function remove_cate(obj)
{
    $(obj).remove();
}
function chage_parent_gid(){
    if($("#parent_gid").val()>0){
          $("#group_name").hide();
          $("#cids_select").show();
    }else{
        $("#group_name").show();
        $("#cids_select").hide();
    }
}
function getCategory(pid,cids_opt){
    if(pid == $("input[name='"+cids_opt+"_pid']").val() )
        return ;
        if(cids_opt == 'cids2'){
            $("#cids3").find("option").remove();
        }
        $("#"+cids_opt).find("option").remove();
        
        $("input[name='"+cids_opt+"_pid']").val(pid);
        var query = new Object();
        $.ajax({
                url: APP+'?'+VAR_MODULE+'='+CURR_MODULE+'&'+VAR_ACTION+'=getCategory&pid='+pid,
                type: "GET",
                data:query,
                dataType: "json",
                success: function(result){
                        if(result){
                            for(i=0;i<result.length;i++)
                            {
                                    $("#"+cids_opt).append("<option value='"+result[i].cid+"'>"+result[i].name+"</option>");
                            }
                            
                            if(cids_opt == 'cids2'){
                                $("#"+cids_opt).bind("dblclick",function(){
                                        add_cate( $("#"+cids_opt));
                                });
                                 $("#"+cids_opt).bind("click",function(){
                                    var pid = $("#"+cids_opt).val();  //获取Select选择的Value
                                        getCategory(pid,'cids3');
                                });
                            }
                            if(cids_opt == 'cids3'){
                                $("#"+cids_opt).bind("dblclick",function(){
                                        add_cate( $("#"+cids_opt));
                                });
                            }
                        }
                }
        });
}
