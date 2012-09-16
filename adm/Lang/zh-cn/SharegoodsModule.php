<?php
$array = array(
	'SHAREGOODSMODULE'=>'商品接口管理',
	'SHAREGOODSMODULE_INDEX'=>'接口列表',
	'SHAREGOODSMODULE_EDIT'=>'编辑接口',
	
	'CLASS'=>'接口代码',
	'NAME'=>'接口名称',
	'DOMAIN'=>'域名限制',
	'ICON'=>'图标',
	'LOGO'=>'LOGO',
	'CONTENT'=>'说明',
	'URL'=>'网站链接',
	
	'TAOBAO_TIPS'=>'申请时请将回调地址，设置到 callback 目录下tb.php<br/>例：http://www.uu43.com/callback/tb.php',
	'TAOBAO_APP_KEY'=>'App Key',
	'TAOBAO_APP_SECRET'=>'App Secret',
	'TAOBAO_TK_PID'=>'淘宝客PID',
	'TAOBAO_TK_PID_TIPS'=>'淘宝客PID：例：PID为mm_xxxxxxxx_0_0，填写xxxxxxxx即可',
	'TAOBAO_SESSION_KEY'=>'SessionKey',
	'TAOBAO_SESSION_KEY_TIPS'=>'用于获取淘宝客报表，请先设置App Key、App Secret、淘宝客PID，提交保存后，更新缓存；<br/>请点击 <a href="../login.php?tbsk=true" target="_blank">获取SessionKey</a>，并用淘宝客关联的淘宝帐户登陆；<br/>将页面输出的<span style="color:#f00;">红色字符串</span>复制粘贴到 SessionKey 文本框；<br/>将页面输出的<span style="color:#00f;">蓝色字符串</span>复制粘贴到 SessionKey过期时间 文本框；',
	'TAOBAO_EXPIRES_IN'=>'SessionKey过期时间',
	'TAOBAO_EXPIRES_IN_TIPS'=>'SessionKey过期后，将不能再获取淘宝客报表，过期时间以授权登陆后输出的 时间为准，手动修改为其他时间，并不能生效',
	
	'PAIPAI_UIN'=>'QQ号',
	'PAIPAI_SPID'=>'spid',
	'PAIPAI_TOKEN'=>'token',
	'PAIPAI_SECKEY'=>'seckey',

	'JDBUY_UNIONID'=>'unionId',
	'JDBUY_UNIONID_TIPS'=>'查看配置说明 http://bbs.fanwe.com/forum.php?mod=viewthread&tid=188',

	'VANCL_SOURCE'=>'Source',
	'VANCL_SOURCE_TIPS'=>'查看配置说明 http://bbs.fanwe.com/forum.php?mod=viewthread&tid=188',

	'DANGDANG_FROM'=>'from',
	'DANGDANG_FROM_TIPS'=>'查看配置说明 http://bbs.fanwe.com/forum.php?mod=viewthread&tid=188',
	
	'YIQIFA_SITE_ID'=>'网站ID',
	'YIQIFA_UID'=>'网站主ID',
	'YIQIFA_DATA_SECRET'=>'数据私钥',
	'YIQIFA_UID_TIPS'=>'查看配置说明 http://bbs.fanwe.com/forum.php?mod=viewthread&tid=188',
	'YIQIFA_SITES'=>'亿起发站点列表',
	'YIQIFA_DATA_SECRET_TIPS'=>'获取报表数据的私钥，在获取亿起发数据的PUSH接口中使用，缺少会导致无法取到报表数据，使会员点击亿起发商品获取佣金功能无法使用；<br/>联系亿起发的媒介经理，将接口地址（<span style="color:#f00;">http://网站地址/callback/yiqifa.php</span>）和 <span style="color:#f00;">数据私钥</span> 告诉媒介经理，由媒介经理在亿起发平台进行设置；',
	'YIQIFA_APP_KEY'=>'App Key',
	'YIQIFA_APP_SECRET'=>'App Secret',

	'CATE_UPDATE_TIP'=>'<span style="color:#f00; background:#fff; padding:3px;">更新接口后，可点击左侧【更新缓存】，进行前台的缓存更新</span>',
	'UPDATE_SUCCESS'=>'更新成功',
	'CATE_UPDATE'=>'更新缓存',
	
	'NAME_REQUIRE'=>'接口名称不能为空',
);
return $array;