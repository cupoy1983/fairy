function joinUserGroup(fid,type,func)
{
	if(!$.Check_Login())
		return false;
	
	var query = new Object();
    query.fid = fid;
	query.type = type;
	
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=join",
		data:query,
		cache:false,
		dataType:"json",
		success: function(result){
			func.call(null,result);
		},
		error:function(){
			func.call(null,false);
		}
	});
}

function removeUserGroup(fid,uid,func)
{
	if(!$.Check_Login())
		return false;
	
	var query = new Object();
    query.fid = fid;
	query.uid = uid;
	
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=removeuser",
		data:query,
		cache:false,
		dataType:"json",
		success: function(result){
			func.call(null,uid,result);
		},
		error:function(){
			func.call(null,uid,false);
		}
	});
}

function applyUserGroup(fid,uid,type,func)
{
	var query = new Object();
    query.fid = fid;
	query.uid = uid;
	query.type = type;
	
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=apply",
		data:query,
		cache:false,
		dataType:"json",
		success: function(result){
			func.call(null,uid,result);
		},
		error:function(){
			func.call(null,uid,false);
		}
	});
}

function adminUserGroup(fid,uid,type,func)
{
	if(!$.Check_Login())
		return false;
	
	var query = new Object();
    query.fid = fid;
	query.uid = uid;
	query.type = type;
	
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=admin",
		data:query,
		cache:false,
		dataType:"json",
		success: function(result){
			func.call(null,uid,result);
		},
		error:function(){
			func.call(null,uid,false);
		}
	});
}

function getUserGroups(page,func)
{
	if(USER_ID == 0)
		return;
	
	var query = new Object();
    query.page = page;
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=megroup",
		data:query,
		cache:true,
		dataType:"json",
		success: function(result){
			func.call(null,result);
		},
		error:function(){
			func.call(null,false);
		}
	});
}

function getCateGroups(cid,page,func)
{
	var query = new Object();
	query.cid = cid;
    query.page = page;
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=group&a=categroup",
		data:query,
		cache:true,
		dataType:"json",
		success: function(result){
			func.call(null,cid,result);
		},
		error:function(){
			func.call(null,cid,false);
		}
	});
}