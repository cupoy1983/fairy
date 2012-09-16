//用户登录/注册弹出框
//$name 商城名，$discount 返利比例或价格
//type 1、直接跳走到普通登录 2、跳出弹出框
var ye_dialog_window = ye_dialog;
function logreg_window(type){
    if(type<=0){
        location.href = siteurl+"/user.php?action=register&url="+document.URL;
    }else{
        var com_shop_jumpurl = "";
        if($("#com_shop_jumpurl").val()){//跳转链接
            com_shop_jumpurl = $("#com_shop_jumpurl").val();
        }
        //if (!$("#usersid").val()) {//判断是否登录
            var com_shop_name = "";
            var com_shop_dis = "";
            var top_title = "";
            if($("#com_shop_name").val()){//商城名称
                com_shop_name = $("#com_shop_name").val();
            }
            if($("#com_shop_dis").val()){//返利
                com_shop_dis = $("#com_shop_dis").val();
            }
            
            if(com_shop_dis && com_shop_dis){
                top_title = '，去“'+com_shop_name+'”购物无法获得'+com_shop_dis;
            }
            var fanlihtml = "";
            if(com_shop_jumpurl){
                fanlihtml = '<p class="pop_tit6"><a href="javascript:void(0);" id="gojumpid" onclick="gojump(\''+com_shop_jumpurl+'\',this.id);" target="_blank">不要返利先去看看>></a></p>';
            }
           
            var alertreghtml = '<div class="weipop_hoad"><p class="pop_tit1 fonp">您未登录迅购网'+top_title+'<a class="weipop_hoadpop" id="ye_dialog_close_win" href="javascript:void(0);" onclick="close_win();"></a></p><div class="clear10"></div><div class="weipop_hoad1 l"><p class="pop_tit2 fonp">快速注册</p><form method="post" name="reg_form" id="regform" style="margin:0px;padding:0px;" action="/LoginReg/creatuser_window" target="_blank" onsubmit="return checkreg_win();"><input type="hidden" name="reg_referer" value="'+com_shop_jumpurl+'" /><input type="hidden" name="reg_win" value="reg_win" /><p class="pop_tit3"><span class="tt1 l">账号：</span><input id="username_win" name="username_win" type="text" class="l" onblur="checkusername_win(1);"/></p><p class="pop_tit30" id="usernamemsg_win"></p><p class="pop_tit3"><span class="tt1 l">电子邮箱：</span><input name="email_win" id="email_win" type="text" class="l" onblur="checkemail_win(1);"/></p><p class="pop_tit30" id="mailmsg_win"></p><p class="pop_tit3"><span class="tt1 l">密码：</span><input name="passw_win" id="passw_win" type="password" class="l" onblur="checkpass_win(1);" /></p><p class="pop_tit30" id="pass1msg_win"></p><p class="pop_tit3"><span class="tt1 l">确认密码：</span><input name="passw2_win" id="passw2_win" type="password" class="l" onblur="checkpass_win(2);" onkeydown="if(event.keyCode==13){checkreg_win(\''+com_shop_jumpurl+'\');}" /></p><p class="pop_tit30" id="pass2msg_win"></p><div class="weipop_hoad2"><input name="regsubmit" type="submit" class="input" value="快速注册"/></div></form></div>';
            
            var alertloghtml = '<div class="weipop_hoad3 r"><p class="pop_tit2 fonp">快速登录</p><form method="post" name="login" id="loginform" style="margin:0px;padding:0px;" action="/LoginReg/wb_login_window" target="_blank" onsubmit= "return checklogin_win();"><input type="hidden" name="referer" value="'+com_shop_jumpurl+'" /><input type="hidden" name="log_win" value="log_win" /><p class="pop_tit3"><span class="tt1 l">账号：</span><input name="log_username_win" id="log_username_win" type="text" class="l"/></p><p class="pop_tit30" id="log_usernamemsg_win"></p><p class="pop_tit3"><span class="tt1 l">密码：</span><input type="password" name="log_passw_win" id="log_passw_win" class="l" onkeydown="if(event.keyCode==13){checklogin_win();}" /></p><p class="pop_tit30" id="log_passwmsg_win"></p><p class="pop_tit4"><input checked="checked" name="is_remember" id="is_remember_win" type="checkbox" value="1" class="title l" /><span class="l">记住我的登录状态</span><a href="'+siteurl+'/otherlogin/reset/" class="l">忘记密码</a></p><p class="pop_tit5"><input name="sub" type="submit" class="input1" value="快速登录" /></p></form>'+fanlihtml+'</div><div class="clear10"></div><div class="weipop_hoad4 fonp">成为迅购网会员，去400家商城购物，都可获得返利，最高返利可达50%</div></div>';
            ye_dialog_window.openHtml(alertreghtml+alertloghtml, '登录迅购网', '600', 'auto');
        //}
    }
}

//用户登录
function checklogin_win(){
    //清空登录框错误提示
    $("#log_usernamemsg_win").html("");
    $("#log_passwmsg_win").html("");
    
    //清空注册框错误提示
    $("#mailmsg_win").html("");
    $("#usernamemsg_win").html("");
    $("#pass1msg_win").html("");
    $("#pass2msg_win").html("");
    
    var uname = $("#log_username_win").val();//用户名
    if("" == uname){
        if($("#log_usernamemsg_win")){
            $("#log_usernamemsg_win").html('<font style="color:#C31212;">账号不能为空!</font>');
            //$("#login_user").focus();
            return false;
        }
    }else{
        var pass = $("#log_passw_win").val();//密码
        if("" == pass){
            if($("#log_passwmsg_win")){
                $("#log_passwmsg_win").html('<font style="color:#C31212;">密码不能为空!</font>');
                //$("#login_passw").focus();
                return false;
            }
        }
    }
    
    //下次是否直接登录
    if($("#is_remember_win").attr("checked")==true){
        var is_remember = 1;
    }else{
        var is_remember = 0;
    }  
    
    $.post(siteurl+"/LoginReg/wb_login", {name:uname,pwd:pass,is_remember:is_remember},function(msg){
        var results = jQuery.parseJSON(msg);
        if(results.ret == 'success'){
            location.reload();//刷新本页
        }else{
            var tipmsg = results.tip;
            var input_i = results.input_i;//用于判断焦点锁定在哪个文本框上
            if(input_i == '1'){
                $("#log_usernamemsg_win").html('<font style="color:#C31212;">'+tipmsg+'</font>');
            }else if(input_i == '2'){
                $("#log_passwmsg_win").html('<font style="color:#C31212;">'+tipmsg+'</font>');
            }else{
                ye_msg.open(tipmsg,3,2);
            }
            return false;
        }
    });
}

//注册
function checkreg_win(){
    //清空登录框错误提示
    $("#log_usernamemsg_win").html("");
    $("#log_passwmsg_win").html("");
    
    //清空注册框错误提示
    /*$("#mailmsg_win").html("");
    $("#usernamemsg_win").html("");
    $("#pass1msg_win").html("");
    $("#pass2msg_win").html("");*/
    
    var u = checkusername_win(2);
    if(u==false || $("#usernamemsg_win").html()!='账号未被注册!'){
        return false;
    }
    var e = checkemail_win(2);
    if(e==false || $("#mailmsg_win").html()!='邮箱可用!'){
        return false;
    }
    var p = checkpass_win(3);
    if(p==false){
        return false;
    }

    /*var username = $("#username_win").val();
    var email = $("#email_win").val();
    var password = $("#passw_win").val();
    var password2 = $("#passw2_win").val();
    var userObj=new Object();
	userObj.username=username;
    userObj.password=password;
    userObj.password2=password2;
    userObj.email=email;
    userObj.code="";
    userObj.isinvite="";
    
   $.post(siteurl+"/LoginReg/creatuser", {userreg_arr:userObj},function(msg){
       var results = jQuery.parseJSON(msg);
       if(results.ret == 'success'){
            location.reload();//刷新本页
       }else{
            ye_msg.open(results.tip,3,2);
       }
                    
   });*/
   
    $("#ye_dialog_window").hide();
    $("#ye_dialog_overlay").hide();
    return true;
}

//邮箱
function checkemail_win(type){
    var reg_email = $.trim($("#email_win").val());
    if(reg_email!=""){
        var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!filter.test(reg_email)){
            $("#mailmsg_win").html('<font color="red">邮箱格式不正确!</font>');
            return false;
        }else{
            if(type==1){
                $.get("/Public/servlet/regcheck.php?do=submit&send=2&em="+encodeURIComponent(reg_email)+"&timestamp=" + GetRandomNum(1,999999),
                function(msg){
                    var results = jQuery.parseJSON(msg);
                    if(results == 0){
                	    $("#mailmsg_win").html('<font color="red">邮箱已被注册!</font>');
                        return false;
                	} else {
                	    $("#mailmsg_win").html('邮箱可用!');
                        //$("#mailimg_win").html('<img src="/Public/images/uc/weibo_pinctfimg1.gif" />');
                        return true;
                	}
                });
            }
            return true;
        }
    }else{
        $("#mailmsg_win").html('<font color="red">输入邮箱，用作登录和找回密码!</font>');
        return false;
    }
}

//检测用户名
function checkusername_win(type){
    var username = $.trim($("#username_win").val());
    if(username!=''){
        var reallen = username.replace(/[^\x00-\xff]/g, "**").length;
        if(reallen<3 || reallen>20){
            $("#usernamemsg_win").html('<font color="red">3-20字符，一个汉字代表两个字符。</font>');
            return false;
        }else{
            if(type==1){
                $.get("/Public/servlet/regcheck.php?do=submit&send=1&uname="+encodeURIComponent(username)+"&timestamp=" + GetRandomNum(1,999999),
                function(msg){
                    var results = jQuery.parseJSON(msg);
                    if(results == 0){
                		$("#usernamemsg_win").html('<font color="red">此账号已被注册!</font>');
                        return false;
                	} else if(results == 3){
                	    $("#usernamemsg_win").html('<font color="red">账号包含特殊字符，请重新设置!</font>');
                        return false;
                	}else {
                	   $("#usernamemsg_win").html('账号未被注册!');
                        //$("#usernameimg_win").html('<img src="/Public/images/uc/weibo_pinctfimg1.gif" />');
                        return true;
                	}          
                });
            }
            if(type==2){
                return true;
            }
        }
    }else{
        $("#usernamemsg_win").html('<font color="red">账号不能为空!</font>');
        return false;
    }	
}

//检测密码
function checkpass_win(type){
    var passw1 = $.trim($("#passw_win").val());
    if(passw1!=""){
        if(type==1){   
            var passlen = passw1.length;
            if(passlen<6 || passlen>16){
                $("#pass1msg_win").html('<font color="red">6-16个字符。</font>');
                return false;
            }else{
                //$("#pass1img_win").html('<img src="/Public/images/uc/weibo_pinctfimg1.gif" />');
                $("#pass1msg_win").html('密码已设置!');
                return true;
            }
        }else if(type==2 || type==3){
            var passw2 = $.trim($("#passw2_win").val());
            if(passw2!=''){
                if(passw2!=passw1){
                    $("#pass2msg_win").html('<font color="red">两次密码输入不一致!</font>');
                    return false;
                }else{
                    //$("#pass2img_win").html('<img src="/Public/images/uc/weibo_pinctfimg1.gif" />');
                    $("#pass2msg_win").html('两次密码一致!');
                    return true;
                }
            }else{
                $("#pass2msg_win").html('<font color="red">请再次输入密码!</font>');
                return false;
            }
        }
    }else{
        $("#pass1msg_win").html('<font color="red">请输入密码!</font>');
        return false;
    }
    
}

//直接跳走
function gojump(jumpurl,id){
    $("#"+id).attr("target", "_blank"); 
    $("#"+id).attr("href",jumpurl);
    
    //关闭弹出框
    ye_dialog_window.close();
}

function close_win(){
    //关闭返利弹出框--start
    var com_shop_jumpurl = $("#com_shop_jumpurl").val();
    if(com_shop_jumpurl!=""){//跳转链接
        gojump(com_shop_jumpurl,"ye_dialog_close_win");
        /*document.getElementById("ye_dialog_close_win").target = "_blank";
        document.getElementById("ye_dialog_close_win").href = com_shop_jumpurl;
        //$("#ye_dialog_close_win").attr("target", "_blank"); 
        //$("#ye_dialog_close_win").attr("href",com_shop_jumpurl);*/   
    }else{
         ye_dialog_window.close();
    }
}
