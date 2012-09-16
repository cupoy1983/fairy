<?php
$authoritys = array();
//不需要认证的模板中的操作
$authoritys['no']['region']['getcitys'] = 1;
$authoritys['no']['goodstags']['search'] = 1;
$authoritys['no']['user']['getuserlist'] = 1;
$authoritys['no']['usergroup']['authoritys'] = 1;
$authoritys['no']['goodscatesgl']['getselect'] = 1;
$authoritys['no']['shop']['getshop'] = 1;

//全局权限
$authoritys['all']['add'] = array('insert');
$authoritys['all']['insert'] = array('add');
$authoritys['all']['edit'] = array('update');
$authoritys['all']['index'] = array('search','show');
$authoritys['all']['update'] = array('edit','editfield','togglestatus','deleteimg','deleteimgbyid');

//广告管理权限
$authoritys['actions']['adv']['index'] = array('getpagelist','getlayoutlist');

//缓存管理权限
$authoritys['actions']['cache']['system'] = array('clear','systemclear');
$authoritys['actions']['cache']['custom'] = array('clear','customclear');

//数据库管理权限
$authoritys['actions']['database']['dump'] = array('dumptable');
$authoritys['actions']['database']['delete'] = array('deletetable');
$authoritys['actions']['database']['restore'] = array('restoretable');

//小组管理权限
$authoritys['actions']['forum']['check'] = array('show','checkok','nocheckok');
$authoritys['actions']['forum']['showupdate'] = array('removeupdate');

//商品管理权限
$authoritys['actions']['goods']['check'] = array('checkok');
$authoritys['actions']['goods']['disables'] = array('disable','removedisables');
$authoritys['actions']['goods']['remove'] = array('removeshare');

//会员勋章
$authoritys['actions']['medal']['user'] = array('send','award','removeaward');
$authoritys['actions']['medal']['check'] = array('checkapply','removedisables');

//订单
$authoritys['actions']['order']['index'] = array('show');

//分享分类管理
$authoritys['actions']['goodscategory']['add'] = array('updatecache');
$authoritys['actions']['goodscategory']['update'] = array('updatecache');
$authoritys['actions']['goodscategory']['remove'] = array('updatecache');

//分享管理权限
$authoritys['actions']['share']['update'] = array('editcomment','updatecomment','toexamineselect','toexamineall','shiftclass','updateshiftclass','getcatetags');
$authoritys['actions']['share']['index'] = array('comments');
$authoritys['actions']['share']['remove'] = array('batchdelete','dobatchdelte','removecomment');

//店铺管理权限
$authoritys['actions']['shop']['check'] = array('checkok');
$authoritys['actions']['shop']['disables'] = array('disable','removedisables');
$authoritys['actions']['shop']['remove'] = array('removeshare');

//淘宝采集
$authoritys['actions']['taobaocollect']['collect'] = array('update','shop','goods','share');

//又拍
$authoritys['actions']['upyun']['update'] = array('test');

//sql管理权限
$authoritys['actions']['tempfile']['index'] = array('clear','fileclear');

//信件管理
$authoritys['actions']['usermsg']['index'] = array('show','delbymlid','delbymiid');
$authoritys['actions']['usermsg']['groupsend'] = array('savesend','updatesend','groupedit');
$authoritys['actions']['usermsg']['grouplist'] = array('togglestatus','remove');

//调用Ckfinder的权限检测
$authoritys['ckfinder'] = array(
	'commissionsetting'=>array('update'),
	'exchangegoods'=>array('add','update'),
	'groupsetting'=>array('update'),
	'mconfig'=>array('update'),
	'sysconf'=>array('update'),
	'usermsg'=>array('savesend'),
	'usersetting'=>array('update'),
);
?>