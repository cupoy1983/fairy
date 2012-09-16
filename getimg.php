<?php
define('FANWE_ROOT', str_replace('getimg.php', '', str_replace('\\', '/', __FILE__)));

if(!include(FANWE_ROOT.'./core/function/global.func.php'))
	exit;

@include FANWE_ROOT.'./public/config.global.php';

@include(FANWE_ROOT.'./public/data/caches/system/setting.cache.php');
define('IMAGE_CREATE_QUALITY',(int)$data['setting']['image_create_quality']);

$php_self = htmlspecialchars(getPhpSelf());
if($php_self === false)
	exit;

$site_path = substr($php_self, 0, strrpos($php_self, '/'));
$site_url = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$site_path.'/');

$authkey = md5($config['security']['authkey']);
$args = unserialize(authcode(base64_decode($_REQUEST['args']),'DECODE',$authkey));

$src = $args['src'];
if(empty($src))
	exit;

$width = $args['width'];
$height = $args['height'];
$gen = $args['gen'];

$img_url_arr[0] = substr($src,0,-4);
$img_url_arr[1] = substr($src,-3,3);
$img_url = $img_url_arr[0]."_".$width."x".$height.".".$img_url_arr[1];

if(!file_exists(FANWE_ROOT.$src))
	exit;

if(file_exists(FANWE_ROOT.$img_url))
	@header("location: ".$site_url.$img_url,true);

if(function_exists('ini_get'))
{
    $memory_limit = @ini_get('memory_limit');
    if($memory_limit && getBytes($memory_limit) < 33554432 && function_exists('ini_set'))
    {
        ini_set('memory_limit', '128M');
    }
}

include_once fimport('class/image');
$image = new Image();
$image->max_size = 8192;
$img = $image->thumb(FANWE_ROOT.$src,$width,$height,$gen);
if($img === false)
    exit;

@header("location: ".$site_url.$img_url,true);
?>