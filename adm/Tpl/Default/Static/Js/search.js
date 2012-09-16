function searchShop(sele,keyword)
{
	var keywords = $(keyword).val();
	var sele = $(sele);
	
	sele.empty();
	option = new Option('加载数据中...','');
	sele.get(0).options.add(option);
	
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=Shop&' + VAR_ACTION + '=getShop',
		cache: false,
		data:{"key":keywords},
		dataType:"json",
		success:function(data)
		{
			sele.empty();
			if(data && data.length > 0)
			{	
				for(var i=0;i<data.length;i++)
				{
					option = new Option(data[i].shop_name, data[i].shop_id);
					sele.get(0).options.add(option);
				}
			}
			else
			{
				option = new Option('无相关数据','');
				sele.get(0).options.add(option);
			}
		}
	});	
}

function searchGroup(sele,keyword)
{
	var keywords = $(keyword).val();
	var sele = $(sele);
	
	sele.empty();
	option = new Option('加载数据中...','');
	sele.get(0).options.add(option);
	
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=Forum&' + VAR_ACTION + '=search',
		cache: false,
		data:{"key":keywords},
		dataType:"json",
		success:function(data)
		{
			sele.empty();
			if(data && data.length > 0)
			{	
				for(var i=0;i<data.length;i++)
				{
					option = new Option(data[i].name, data[i].fid);
					sele.get(0).options.add(option);
				}
			}
			else
			{
				option = new Option('无相关数据','');
				sele.get(0).options.add(option);
			}
		}
	});	
}

function searchCateGroup(sele,cid)
{
	var cid = $(cid).val();
	var sele = $(sele);
	
	sele.empty();
	option = new Option('加载数据中...','');
	sele.get(0).options.add(option);
	
	$.ajax({
		url: APP + '?' + VAR_MODULE + '=IndexCateGroup&' + VAR_ACTION + '=getGroups',
		cache: false,
		data:{"cid":cid},
		dataType:"json",
		success:function(data)
		{
			sele.empty();
			if(data && data.length > 0)
			{	
				option = new Option('不分组','0');
				sele.get(0).options.add(option);
				
				for(var i=0;i<data.length;i++)
				{
					option = new Option(data[i].name, data[i].id);
					sele.get(0).options.add(option);
				}
			}
			else
			{
				option = new Option('分类暂无相关分组','');
				sele.get(0).options.add(option);
			}
		}
	});	
}