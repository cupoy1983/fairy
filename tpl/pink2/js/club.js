jQuery(function($){
	$(".topic_list li").hover(function(){
		$(this).addClass('h');
        $(this).find('.del_topic').addClass('del_css');
	},function(){
		$(this).removeClass('h');
        $(this).find('.del_topic').removeClass('del_css');
	});

	$(".topic_list .tl_c .pic").hover(function(){
		var li = $(this).parent().parent();
		var html = $('.show_big_img',li).html();
		if(html.length > 10)
		{
			html = html.replace(/timgsrc/g,'src');
			html = '<div class="tl_pic_float">'+ html +'<i></i></div>';
			var offset = $(this).offset();
			var left = offset.left;
			var top = offset.top;
			$("body").append(html);
			$(".tl_pic_float").css({"top":top-128,"left":left-42});
		}

	},function(){
		$(".tl_pic_float").remove();
	});
        $(".join_btn").click(function(){
    if(!$.Check_Login())
	return false;
    var query = new Object();
    query.fid = $(".gr_intro_bar").attr("fid");
    $.ajax({
		type:"POST",
		url: SITE_PATH+"services/service.php?m=club&a=join",
		data:query,
		cache:false,
		dataType:"json",
		success: function(result){
			if(result.is_add_forum >0){
                                                                window.location.href =SITE_URL+'club.php?action='+ACTION_NAME+'&fid='+result.fid;
                                                             }else{
                                                                 alert('已经发出加入小组申请，等待管理员审核');
                                                             }
		}
	});
});
$(".forumout").click(function(){
    $r = confirm('确定退出小组？');
    if($r){
        var query = new Object();
    query.fid = $(".gr_intro_bar").attr("fid");

    $.ajax({
        type:"POST",
        url: SITE_PATH+"services/service.php?m=club&a=forumout",
        data:query,
        cache:false,
        dataType:"json",
        success:function(result){
            if(result.is_out>0){
                    window.location.href =SITE_URL+'club.php?action='+ACTION_NAME+'&fid='+result.fid;
              }
        }
    });
    }

});

$(".all_group_nav li a").click(function(){
   $(".all_group_nav li a").each(function(i){
           $("#catenav_"+i).removeClass("c");
    });
    $(this).addClass("c");
    var nav_id = $(this).attr('id');

    if(nav_id == 'catenav_0'){
        $(".all_group_sort").each(function(i){
            i++;
            $("#cate_"+i).show(); 
        });
    }else{
        $(".all_group_sort").each(function(i){
            i++;
            $("#cate_"+i).hide();
        });
         $("#cate_"+nav_id.substring(nav_id.lastIndexOf("_")+1)).show(); 
    }
});

$.Clubuser_Delete = function(fid,uid){
    if(confirm('确定删除该成员？')){
        var query = new Object();
        query.fid = fid;
        query.uid = uid;
        $.ajax({
            type:"POST",
            url: SITE_PATH+"services/service.php?m=club&a=deluser",
            data:query,
            cache:false,
            dataType:"json",
            success:function(result){
                if(result.status){
                        var club_user = $("#CLUB_USER_"+uid);
                        club_user.slideUp("slow");
                }
            }
        });
    }

}
$.Set_Admin = function(fid,uid){
    if(confirm('设置管理员')){
        var query = new Object();
        query.fid = fid;
        query.uid = uid;
        $.ajax({
            type:"POST",
            url:SITE_PATH+"services/service.php?m=club&a=setadmin",
            data:query,
            cache:false,
            dataType:"json",
            success:function(result){
                if(result.status){
                        window.location.href =SITE_URL+'club.php?action='+ACTION_NAME+'&fid='+fid;
                }
            }
        });
    }
}
$.Out_Admin = function(fid,uid){
    if(confirm('取消管理员')){
        var query = new Object();
        query.fid = fid;
        query.uid = uid;
        $.ajax({
            type:"POST",
            url:SITE_PATH+"services/service.php?m=club&a=outadmin",
            data:query,
            cache:false,
            dataType:"json",
            success:function(result){
                if(result.status){
                        window.location.href =SITE_URL+'club.php?action='+ACTION_NAME+'&fid='+fid;
                }
            }
        });
    }
}

//主题列表
$.Remove_Topic = function(tid){
    if(confirm('删除主题并且会删除主题相关回复。')){
        var query = new Object();
        query.tid = tid;
        $.ajax({
            type:"POST",
            url:SITE_PATH+"services/service.php?m=club&a=deltopic",
            data:query,
            cache:false,
            dataType:"json",
            success:function(result){
                $("#TOPIC_ID_"+tid).slideUp("slow");
            }
        });
    }
}
});
