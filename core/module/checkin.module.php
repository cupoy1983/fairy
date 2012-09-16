<?php
class CheckinModule{
	
	public static $qiandao_jifen = 5;   //签到送积分
	public static $lianxux = 10;        //连续签到多少天送勋章
	public static $lianxuxjf = 50;      //连续签到多少天送勋章, 额外送多少积分
	public static $lianxumonth = 28;    //连续签到一个月多少天
	public static $lianxumonthjf = 200;   //连续签到一个月可额外奖励多少积分
	
	public function index(){
		global $_FANWE;
		
		$order_list = FS("Exchange")->getOrderTop();
		
		include_once (FANWE_ROOT . 'fanli.php');
		$timedate = date("Ymd");
		$where = " WHERE times=1 AND FROM_UNIXTIME(time,'%Y%m%d')='{$timedate}'";
		$sql = "SELECT id FROM " . FDB::table("qiandao") . $where;
		$sql = 'SELECT COUNT(id) FROM ' . FDB::table("qiandao") . $where;
		$tdcount = FDB::resultFirst($sql);
		include template('page/u/u_checkin');
		display();
	}
	
	public function checkin_ajax(){
		global $_FANWE;
		$type = $_REQUEST['type'];
		if($type == 2){
			$timedate = date("Ymd");
			$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m%d')='{$timedate}'";
			$sql = "SELECT id FROM " . FDB::table("qiandao") . $where;
			$qiandao = FDB::fetchFirst($sql);
			if($_FANWE['uid'] <= 0){
				echo json_encode(array(
						'ret' => 'nologin',
						'tip' => '您还没有登录，请先登录！！' 
				));
				exit();
			}
			if($qiandao['id'] > 0){
				echo json_encode(array(
						'ret' => 'fail',
						'tip' => '今天你已经签到了！！' 
				));
				exit();
			}else{
				// 更新积分
				FDB::query('UPDATE ' . FDB::table("user") . ' SET credits = credits+' . self::$qiandao_jifen . ' WHERE uid = ' . $_FANWE['uid']);
				$qindao_data = array();
				$qindao_data['time'] = time();
				$qindao_data['uid'] = $_FANWE['uid'];
				$qindao_data['user_name'] = $_FANWE['user_name'];
				$qindao_data['times'] = 1;
				$qindao_data['jifen'] = self::$qiandao_jifen;
				FDB::insert('qiandao', $qindao_data, true);
				
				// 如果连续签到10次 开始
				$timedate = date("Ym");
				$sql = "SELECT id FROM " . FDB::table("user_medal") . " WHERE uid='{$_FANWE['uid']}' AND mid=4";
				$media = FDB::fetchFirst($sql);
				
				$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m')='{$timedate}'";
				$sql = 'SELECT id,time FROM ' . FDB::table("qiandao") . $where;
				$qiandaoAll = FDB::fetchAll($sql);
				$qarr = array();
				foreach($qiandaoAll as $key => $value){
					$qarr[$key] = date('Y-m-d', $value['time']);
				}
				$flag = CheckinModule::iflianxu($qarr);
				if($flag == 9){
					$sql = 'UPDATE ' . FDB::table("user") . ' SET credits = credits+' . self::$lianxuxjf . ' WHERE uid = ' . $_FANWE['uid'];
					FDB::query($sql);
					$timedate = date("Ymd");
					$qdsql = 'UPDATE ' . FDB::table("qiandao") . " SET jifen = jifen+'{self::$lianxuxjf}' WHERE uid = '{$_FANWE['uid']}' AND FROM_UNIXTIME(time,'%Y%m%d')='{$timedate}'";
					FDB::query($qdsql);
				}
				if(empty($media) && $flag >= 9){
					CheckinModule::songxunzhang();
				}
				// 如果连续签到10次 结束
				
				$timedate = date("Ym");
				$where = " WHERE  uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m')='{$timedate}'";
				$sql = 'SELECT COUNT(id)
                    FROM ' . FDB::table("qiandao") . $where;
				$uday = FDB::resultFirst($sql);
				// 统计积分
				$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m')='{$timedate}'";
				$sql = 'SELECT SUM(jifen)
                    FROM ' . FDB::table("qiandao") . $where;
				$upoints = FDB::resultFirst($sql);
				$yday = self::$lianxumonth - $uday;
				// 如果连续签到28次,送积分
				if($yday == 0){
					// 更新积分
					FDB::query('UPDATE ' . FDB::table("user") . ' SET credits = credits+' . self::$lianxumonthjf . ' WHERE uid = ' . $_FANWE['uid']);
					$timedate = date("Ymd");
					$qdsql = 'UPDATE ' . FDB::table("qiandao") . " SET jifen = jifen+'{self::$lianxumonthjf}' WHERE uid = '{$_FANWE['uid']}' AND FROM_UNIXTIME(time,'%Y%m%d')='{$timedate}'";
					FDB::query($qdsql);
					// 统计积分
					$timedate = date("Ym");
					$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m')='{$timedate}'";
					$sql = 'SELECT SUM(jifen)
                        FROM ' . FDB::table("qiandao") . $where;
					$upoints = FDB::resultFirst($sql);
					echo json_encode(array(
							'ret' => 'success',
							'tip' => "你已经连续签到{self::$lianxumonth}天，已获取额外送您的{self::$lianxumonthjf}全勤奖积分啦！",
							'getjifen' => self::$qiandao_jifen,
							'upoints' => $upoints 
					));
				}else{
					echo json_encode(array(
							'ret' => 'success',
							'tip' => "你已经连续签到{$uday}天，还有{$yday}天就可以领取{self::$lianxumonthjf}积分哦！",
							'getjifen' => self::$qiandao_jifen,
							'upoints' => $upoints 
					));
				}
				exit();
			}
		}elseif($type == 1){
			$timedate = date("Ymd");
			$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m%d')='{$timedate}'";
			$sql = "SELECT id FROM " . FDB::table("qiandao") . $where;
			$qiandao = FDB::fetchFirst($sql);
			if($qiandao['id'] > 0){
				echo json_encode(array(
						'ret' => 'fail' 
				));
				exit();
			}else{
				echo json_encode(array(
						'ret' => 'success' 
				));
				exit();
			}
		}
	}
	
	private function iflianxu($arr){
		$flag = 0;
		function compare($x, $y){
			return (strtotime($x) - strtotime($y));
		}
		uasort($arr, 'compare');
		$arr = array_slice($arr, 0);
		for($i = 1;$i < count($arr);$i ++){
			if((strtotime($arr[$i]) - strtotime($arr[0])) == ($i * 24 * 60 * 60)){
				$flag ++;
			}else{
				break;
			}
		}
		return $flag;
	}
	
	// 连续签到10天送勋章
	private function songxunzhang(){
		global $_FANWE;
		$media_data = array();
		$media_data['create_time'] = time();
		$media_data['uid'] = $_FANWE['uid'];
		$media_data['type'] = 0;
		$media_data['mid'] = 4;
		$media_data['deadline'] = 0;
		FDB::insert('user_medal', $media_data, true);
		return true;
	}
	
	public function checkintimes(){
		global $_FANWE;
		include_once (FANWE_ROOT . 'fanli.php');
		$type = $_REQUEST['type'];
		$month = $_REQUEST['month'];
		$year = $_REQUEST['year'];
		$timedate = ($year . $month) == '' ? date("Ym") : $year . $month;
		$where = " WHERE uid='{$_FANWE['uid']}' AND times=1 AND FROM_UNIXTIME(time,'%Y%m')='{$timedate}'";
		$sql = "SELECT id,time FROM " . FDB::table("qiandao") . $where;
		$qiandaoAll = FDB::fetchAll($sql);
		$qdtime = array();
		foreach($qiandaoAll as $key => $value){
			$value['tdsate'] = date('Y-m-d', $value['time']);
			$qdtime[$key]['time'] = date('Y-m-d', $value['time']);
		}
		echo json_encode(array(
				'ret' => 'success',
				'data' => $qdtime,
				'checkintimes' => count($qdtime),
				'lasttimes' => self::$lianxumonth - count($qdtime) 
		));
		exit();
	}
}

?>