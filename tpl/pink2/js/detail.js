jQuery(function($){
//主题列表
$(".del_topic").click(function(){
   if(confirm('删除主题并且会删除主题相关回复。')){
        var query = new Object();
        query.tid = $("#topic_tid").val();
        $.ajax({
            type:"POST",
            url:SITE_PATH+"services/service.php?m=club&a=deltopic",
            data:query,
            cache:false,
            dataType:"json",
            success:function(result){
                window.location.href =SITE_URL+'club.php?action=index';
            }
        });
    }
});
});
