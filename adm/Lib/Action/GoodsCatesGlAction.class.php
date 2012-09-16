<?php

/*
 * 分类关联管理
 * jobin.lin(jobin.lin@gmail.com)
 */
class GoodsCatesGlAction extends CommonAction
{
    function index()
	{
        $cate_id = $_REQUEST['cate_id'];
        $cate_name = $_REQUEST['cate_name'];
        
        $model = D("GoodsCatesGl");
        $types = D("GoodsCates")->field('type')->group('type')->select();
        
        $count = $model->where('cate_id = '.$cate_id)->count('cate_id');
        $sql = 'SELECT gcl.*,gc.name as cate_name FROM '.C("DB_PREFIX").'goods_cates_gl gcl  left join '.C('DB_PREFIX').'goods_cates gc on gc.id = gcl.f_cate_id WHERE cate_id='.$cate_id;
   
        $this->_sqlList($model,$sql,$count,true);
        
        $this->assign('types',$types);
        $this->assign('cate_id',$cate_id);
        $this->assign('cate_name',$cate_name);
        $this->display ();
        return;
    }
	
    function setting()
	{
        $cate_id = $_REQUEST['cate_id'];
        $cate_info =  D('GoodsCategory')->where("cate_id=".$cate_id)->find();
        $type = $_REQUEST['type'];
        $cate_list = D('GoodsCates')->where("type='$type' and pid = ''")->field('id,type,name')->order('sort asc')->select();
        $this->assign('cate_info',$cate_info);
        $this->assign('type',$type);
        $this->assign('cate_list',$cate_list);
        $this->display();
    }
	
    function insert()
	{
        $fcate_arr =  $_REQUEST['fcatelist'];
        $fcate_arr = array_unique($fcate_arr);
        $model = D('GoodsCatesGl');
        $res = true;
        foreach($fcate_arr as $k=>$v)
		{
            $data  = array();
            $data['cate_id'] = $_REQUEST['cate_id'];
            $data['f_cate_id'] = $v;
            $data['type'] = $_REQUEST['type'];
            if($model->where($data)->count('cate_id'))
                continue;
            $data['sort'] = 100;

            if(false===$model->data($data)->add()){
                $res = false;
                break;
            }
        }
        if($res){
            $this->assign('jumpUrl', U('GoodsCatesGl/index', array('cate_id'=>$_REQUEST['cate_id'])) );
            $this->success (L('EDIT_SUCCESS'));
        }else{
            //错误提示
            $this->saveLog(0,$f_cate_id);
            $this->error (L('EDIT_ERROR'));
        }
        
    }
    public function remove()
    {
            //删除指定记录
            $result = array('isErr'=>0,'content'=>'');
            $gl_id = $_REQUEST['id'];
            if(!empty($gl_id))
            {
                    $model = D("GoodsCatesGl");
                    $condition = array('gl_id' => array('in',explode (',',$gl_id)));
                    $model->where ( $condition )->delete();
                   if(false !== $model->where ( $condition )->delete())
                            {
                                    $this->saveLog(1,$gl_id);
                            }
                            else
                            {
                                    $result['isErr'] = 1;
                                    $result['content'] = L('REMOVE_ERROR');
                            }
            }
            else
            {
                    $result['isErr'] = 1;
                    $result['content'] = L('ACCESS_DENIED');
            }

            die(json_encode($result));
    }
	
    public function getSelect()
	{
        $id = $_REQUEST['id'];
        $lv = $_REQUEST['lv'];
        $data = D("GoodsCates")->where("pid='$id'")->order('sort asc')->select();
        $data_html ='';
        if($data){
             $data_html ='<select multiple="multiple" class="selectmul" name="'.$lv.'" id="'.$lv.'" style="height:400px;">';
            foreach($data as $k=>$v){
                $temp_html = '<option parentID="'.$v['pid'].'" value="'.$v['id'].'"> '.$v['name'].'</option>';
                $data_html.=$temp_html;
            }
            $data_html.='</select>';
        }
       echo $data_html;exit;
    }

}
?>
