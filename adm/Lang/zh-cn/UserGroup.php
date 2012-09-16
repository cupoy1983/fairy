<?php
return array(
	'USERGROUP'	=>	'会员组',
	'USERGROUP_INDEX'	=>	'会员组列表',
	'USERGROUP_ADD'=>'添加会员组',
	'USERGROUP_EDIT'=>'编辑会员组',
	'NAME'=>'名称',
	'TYPE'=>'类型',
	'TYPE_SYSTEM'=>'系统',
	'TYPE_USER'=>'会员',
	'IS_SPECIAL'=>'特殊会员组',
	'IS_SPECIAL_TIP'=>'特殊会员组不进行积分等级和佣金返现',
	'ACCESS'=>'前台权限设置',
	'CREDITS_RANGE'=>'积分范围',
	'COMMISSION_RATE'=>'佣金返现比率',
	'BUY_RATE'=>'购买返现比率',
	'IS_ADMIN'=>'网站工作人员组',
	'ICON'=>'会员组小图标',
	'ICON_TIP'=>'填写格式：直接输入图片名称即可，如 taobao.gif<br/>存放位置：public/icons目录',
	'RESET_GROUP_ACCESS'=>'覆盖当前会员组下所有会员的权限（注：如果当前会员组下会员太多，执行此覆盖操作可能会引起超时等错误发生）',
	'CREDITS_RANGE_TIP'=>'当积分达到此范围，将自动升级为此会员组等级',
	'COMMISSION_RATE_TIP'=>'当会员发布的淘宝推广商品，产生佣金时，返现给发布会员的佣金百分比',
	'BUY_RATE_TIP'=>'当会员点击淘宝推广商品，成功购买，并产生佣金时，返现给购买会员的佣金百分比；<br/>当发布商品的会员和购买商品的会员为同一个会员时，只会产生<span style="color:#f00;">佣金返现</span>，而不会产生<span style="color:#f00;">购买返现</span>。',

	'TAB_1'=>'组信息',
	'TAB_2'=>'前台权限设置',
	
	'NAME_REQUIRE'=>'会员组名称不能为空',
	'NAME_UNIQUE'=>'会员组名称已经存在',
	
	'GROUP_EXIST_USER'=>'会员组下存在会员,不能进行删除',
	'GROUP_SYSTEY_DEL'=>'系统内置会员组,不能进行删除',
);
return $array;
?>