<?php
// +----------------------------------------------------------------------
// | 方维购物分享网站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: awfigq <awfigq@qq.com>
// +----------------------------------------------------------------------
/**
 +------------------------------------------------------------------------------
 * 后台权限节点
 +------------------------------------------------------------------------------
 */
class RoleNodeAction extends CommonAction
{
	public function add()
	{
		$nav_list = D("RoleNav")->getField('id,name');
		$this->assign("nav_list",$nav_list);
		$this->display();
	}
	
	public function insert()
	{
		$name=$this->getActionName();
		$model = D($name);
		if(false === $data = $model->create())
		{
			$this->error($model->getError());
		}
		
		if($data['module_name'] == '')
			$data['module_name'] = $data['module'];
		if($_REQUEST['module'] == "" && $_REQUEST['action'] != "")
			$data['auth_type'] = 2;
		elseif($_REQUEST['module'] != "" && $_REQUEST['action'] == "")
			$data['auth_type'] = 1;
		else
			$data['auth_type'] = 0;
			
		if(D("RoleNode")->where("module='".$data['module']."' and action='".$data['action']."'")->count()>0)
			$this->error(L('ROLENODE_UNIQUE'));
		
		//保存当前数据对象
		$list=$model->add($data);
		if ($list !== false)
		{
			$this->saveLog(1,$list);
			$this->success (L('ADD_SUCCESS'));

		}
		else
		{
			$this->saveLog(0,$list);
			$this->error (L('ADD_ERROR'));
		}
	}
	
	public function edit()
	{
		$id = intval($_REQUEST['id']);
		$vo = D("RoleNode")->getById($id);
		$this->assign ( 'vo', $vo );
		
		$nav_list = D("RoleNav")->getField('id,name');
		$this->assign("nav_list",$nav_list);
		$this->display();
	}
	
	public function toggleStatus()
	{
		$id = intval($_REQUEST['id']);
		if($id == 0)
			exit;
		
		$val = intval($_REQUEST['val']) == 0 ? 1 : 0;
			
		$field = trim($_REQUEST['field']);
		if(empty($field))
			exit;
		
		$result = array('isErr'=>0,'content'=>'');
		$name=$this->getActionName();
		$model = D($name);
		$pk = $model->getPk();
		if(false !== $model->where($pk.' = '.$id)->setField($field,$val))
		{
			if($field == 'is_log')
			{
				include ADMIN_PATH.'/Conf/authoritys.php';
				$log_app = array();
				$list = D("RoleNode")->where('log_type = 1 AND is_log = 1')->select();
				foreach($list as $item)
				{
					$module = strtolower($item['module']);
					$action = strtolower($item['action']);
			
					$log_app[$module][] = $action;
					if($action == 'add')
						$log_app[$module][] = 'insert';
						
					if(isset($authoritys['actions'][$module][$action]))
					{
						$authoritys_list = $authoritys['actions'][$module][$action];
						foreach($authoritys_list as $authority_item)
						{
							$log_app[$module][] =  strtolower($authority_item);
						}
					}
				}
				D('SysConf')->where("name = 'LOG_APP'")->setField('val',serialize($log_app));
			}
			
			$this->saveLog(1,$id,$field);
			$result['content'] = $val;
		}
		else
		{
			$this->saveLog(0,$id,$field);
			$result['isErr'] = 1;
		}
		
		die(json_encode($result));
	}
}

function getIsLog($log_type,$node)
{
	if($log_type == 1)
		return '<span class="pointer" module="RoleNode" href="javascript:;" onclick="toggleStatus(this,\''.$node['id'].'\',\'is_log\')"><img status="'.$node['is_log'].'" src="'.__TMPL__.'Static/Images/status-'.$node['is_log'].'.gif" /></span>';
	else
		return '&nbsp;';
}


function getRoleNavName($id)
{
	return D("RoleNav")->where('id = '.$id)->getField('name');
}

function getAuthType($type)
{
	return L('AUTH_TYPE_'.$type);
}
?>