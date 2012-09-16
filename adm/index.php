<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//定义项目名称和路径
define('ADMIN_PATH', str_replace('\\', '/',getcwd()));
define('APP_NAME', basename(ADMIN_PATH));
define('APP_PATH', './');
define('FANWE_ROOT', str_replace('\\', '/',substr(ADMIN_PATH, 0, -(strlen(APP_NAME) + 1))).'/');
@ini_set('memory_limit', '128M');
require(ADMIN_PATH."/ThinkPHP/ThinkPHP.php");
?>