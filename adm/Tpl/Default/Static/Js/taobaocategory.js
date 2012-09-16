function removeData(obj,id,pk)
{
	var ids =  new Array();
	
	if(isNaN(id))
	{
		$("#" + id + " input:checked[name='key']").each(function(){
			ids.push(this.value);
		});
	}
	else
	{
		ids.push(id);
		var parent = $(obj).parent().parent();
		id = parent.parent().parent().attr('id');
	}
	
	ids = ids.join(',');
	if(ids == '')
		return false;
	
	if(!window.confirm(CONFIRM_DELETE))
		return false;
	
	var query = new Object();
	query.id = ids;
	
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=' + CURR_MODULE + '&' + VAR_ACTION + '=remove',
		type:"POST",
		cache: false,
		data:query,
		dataType:"json",
		success: function(result){
			if(result.isErr == 0)
			{
				$("#" + id + " tbody tr input[name='key']").each(function(){
					if((',' + ids + ',').indexOf(',' + this.value + ',') != -1)
					{
						parent = $(this).parent().parent();
						this.checked = false;
						$('td span,td a',parent).each(function(){
							if (typeof(this.onclick) == 'function' && this.onclick.toString() != '')
							{
								if(this.onclick.toString().indexOf('toggleStatus') != -1)
								{
									var img = $('img',this).get(0);
									img.src = img.src.replace('status','disabled');
								}
								
								this.onclick = '';
							}
						});
						
						parent.attr({"disabled":true,"title":ALREADY_REMOVE});
						$("td",parent).attr({"disabled":true}).addClass('disabled');
						$("td *",parent).attr({"disabled":true}).addClass('disabled');
					}
					
					if($("#" + id + " tbody tr:not([disabled])").length == 0)
						location.reload(true);
				});
			}
			else
				$.ajaxError(result.content);
		}
	});
}
function getClildCategory(obj,id,pk)
{
	var ids =  new Array();
	
	if(isNaN(id))
	{
		$("#" + id + " input:checked[name='key']").each(function(){
			ids.push(this.value);
		});
	}else{
            ids.push(id) ;
        }
	
	ids = ids.join(',');
	if(ids == '')
		return false;
	
	if(!window.confirm(CONFIRM_GET_CATEGORY))
		return false;
	
	var query = new Object();
	query.cids = ids;
	
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=' + CURR_MODULE + '&' + VAR_ACTION + '=getItemCats',
		type:"POST",
		cache: false,
		data:query,
		dataType:"json",
		success: function(result){
			if(result.isErr == 0)
			{
				location.reload(true);
			}
			else
				$.ajaxError(result.content);
		}
	});
}