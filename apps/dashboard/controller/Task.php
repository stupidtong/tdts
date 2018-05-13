<?php
namespace app\dashboard\controller;
use think\Controller;
use app\dashboard\model\User as UserModel;
use app\dashboard\model\Task as TaskModel;
use app\dashboard\model\Taskdata as TaskdataModel;
use app\dashboard\model\Result as ResultModel;
use app\dashboard\model\Usertask as UsertaskModel;
use app\dashboard\model\Msg as MsgModel;
use think\Request;
use think\Cookie;
use think\Db;
use think\Csv;

class Task extends Controller
{
    public function index($tid)
      {
          if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
          if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
          $task = TaskModel::get($tid);
          $this->assign('task', $task);
          $list = TaskdataModel::where(['tid'=>$tid])->paginate(10);
          $this->assign('list', $list);
          return $this->fetch();
    }
    public function create()
    {
      if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
      return $this->fetch();
    }
    public function add(Request $request)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = new TaskModel;
        $task->stime = date("Y-m-d H:i:s");
        $task->uid = Cookie::get('uid');
        if ($request->post('checkbox')==1) {
            $task->multi_result = 1;
        } else {
            $task->multi_result = 0;
        }
        if ($task->allowField(true)->save(input('post.'))) {
            $usertask = new UsertaskModel;
            $usertask->uid = $task->uid;
            $usertask->tid = $task->id;
            $usertask->jointime = $task->stime;
            $usertask->master = 1;
            $usertask->allowField(true)->save();
            $this->redirect('dashboard/task/add_data',['tid' =>$task->id]);
        }
    }
    public function add_data($tid)
    {
        if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($tid);
        $this->assign('task', $task);
        return $this->fetch();
    }
    public function add_data_action($tid)
    {
        if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($tid);
        $file = request()->file('files');
        if (empty($file)) {
            $this->error('请选择上传文件');
        }
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
          if ($info->getExtension()=='csv'){
            $file_csv = fopen(ROOT_PATH . 'public' . DS . 'uploads'.DS .$info->getSaveName(),'r');
            $a = 0;
            while ($data = fgetcsv($file_csv)) {
                $taskdata = new TaskdataModel;
                $taskdata->tid = $tid;
                $taskdata->data_1 = $data[0];
                if ($task->data_num >1) {
                    $taskdata->data_2 = $data[1];
                }
                if ($task->data_num >2) {
                    $taskdata->data_3 = $data[2];
                }
                if ($taskdata->allowField(true)->save()) {
                    $a += 1;
                }
            }
            $this->success('成功添加了'.$a.'条数据。',url('dashboard/task/index', ['tid'=>$tid]));
          } else {
            $this->error('不支持的格式！');
          }
        } else {
            $this->error($file->getError());
        }
    }
    public function delete($tid)
    {
        if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($tid);
        $tname = $task->name;
        if ($task) {
            $taskdata = TaskdataModel::all(['tid' => $tid]);
            UsertaskModel::destroy(['tid' => $tid]);
            MsgModel::destroy(['tid' => $tid]);
            foreach ($taskdata as $td) {
                ResultModel::destroy(['td_id' => $td->id]);
                $td->delete();
            }
            $task->delete();
            $this->success('成功删除了'.$tname,'dashboard/index/index');
        } else {
          $this->error('删除失败！');
        }
    }
    public function delete_data($tdid)
    {

        $taskdata = TaskdataModel::get($tdid);
        $tid = $taskdata->tid;
        if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        if ($taskdata) {
           ResultModel::destroy(['td_id' => $tid]);
           $taskdata->delete();
           $this->success('删除成功！');
        } else {
          $this->error('删除失败！');
        }
    }
    public function tasklist()
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $user = UserModel::get(Cookie::get('uid'));
        $tasklist = TaskModel::all(['uid' => $user->user_id]);
        $this->assign('ownlist', $tasklist);
        $join_task_list = $user->usertask()->select();
        $joinlist = [];
        foreach ($join_task_list as $usertask) {
            $joinlist[] =  TaskModel::get($usertask->tid);
        }
        $this->assign('joinlist', $joinlist);
        return $this->fetch();
    }
    public function invite($tid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $this->assign('tid', $tid);
        return $this->fetch();
    }
    public function invite_msg(Request $request)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $this->assign('tid', $request->post('tid'));
        $q_nickname = $request->post('nickname');
        $result = Db::name('user')
          ->where('user_nickname', 'LIKE', '%'.$q_nickname.'%')
          ->select();
        $this->assign('userlist',$result);
        return $this->fetch();
    }
    public function invite_action(Request $request)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $tid = (int)$request->post('tid');
        $msg = new MsgModel;
        $msg->from_id = Cookie::get('uid');
        $msg->msgtime =  date("Y-m-d H:i:s");
        if ($msg->allowField(true)->save(input('post.'))) {
          $this->success('成功邀请了用户。',url('dashboard/task/index', ['tid'=>$tid]));
        } else {
            $this->error('邀请失败！');
        }
    }
    public function fill($tid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($tid);
        $this->assign('task',$task);
        $taskdata_list = TaskdataModel::all(['tid'=>$tid]);
        $found_data = FALSE;
        $min_user = 99999;
        foreach ($taskdata_list as $taskdata) {
            $result_list = Db::name('result')->where('td_id',$taskdata->id)->select();
            $user_list = [];
            foreach ($result_list as $result) {
                if (!in_array($result['uid'],$user_list)) {$user_list[]=$result['uid'];}
            }
            if ((count($user_list)<$min_user)&&(!in_array(Cookie::get('uid'),$user_list))){
                $needed_t_data = $taskdata;
                $found_data = TRUE;
                $min_user = count($user_list);
            }
        }

        if ($found_data){
            $print_data = [$task->data_1 => $needed_t_data->data_1];
            if (!$needed_t_data->data_2==NULL){
                $print_data[$task->data_2] = $needed_t_data->data_2;
            }
            if (!$needed_t_data->data_3==NULL){
                $print_data[$task->data_3] = $needed_t_data->data_3;
            }
            $this->assign('taskdata',$needed_t_data);
            $this->assign('print_list',$print_data);
            return $this->fetch();
        } else {
            $this->success('您已经完成了所有的标记任务！','dashboard/index/index');
        }
    }
    public function fill_action(Request $request)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($request->post('tid'));
        $result = new ResultModel;
        $result->uid = Cookie::get('uid');
        $result->td_id = $request->post('td_id');
        $result->result_1 = $request->post('result_1');
        if ($task->result_num>1) {
            $result->result_2 = $request->post('result_2');
        }
        if ($request->post('result_1')=='') {$this->error('填写结果不能为空！');}
        $result_had = ResultModel::get(['td_id'=>$result->td_id,'uid'=>$result->uid]);
        if (($task->multi_result==0)&&($result_had)){$this->error('该任务不允许多结果，请勿重复提交！');}
        if ($result->allowField(true)->save()) {
            $this->redirect('dashboard/task/fill',['tid'=>$task->id]);
        } else {
            $this->error('填写不合法！');
        }

    }
    public function fill_multi(Request $request)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($request->post('tid'));
        $result = new ResultModel;
        $result->uid = Cookie::get('uid');
        $result->td_id = $request->post('td_id');
        $result->result_1 = $request->post('result_1');
        if ($task->result_num>1) {
            $result->result_2 = $request->post('result_2');
        }
        if ($result->allowField(true)->save()) {
            $taskdata = TaskdataModel::get($request->post('td_id'));
            $print_data = [$task->data_1 => $taskdata->data_1];
            if (!$taskdata->data_2==NULL){
                $print_data[$task->data_2] = $taskdata->data_2;
            }
            if (!$taskdata->data_3==NULL){
                $print_data[$task->data_3] = $taskdata->data_3;
            }
            $this->assign('task',$task);
            $this->assign('taskdata',$taskdata);
            $this->assign('print_list',$print_data);
            $result_list = ResultModel::all(['td_id'=>$taskdata->id,'uid'=>Cookie::get('uid')]);
            $this->assign('result_list',$result_list);
            return $this->fetch();
        } else {
            $this->error('填写不合法！');
        }
    }
    public function delete_result($rid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $result = ResultModel::get($rid);
        $result->delete();
        $this->success('删除成功');
    }
    public function manage($tid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $task = TaskModel::get($tid);
        $this->assign('task', $task);
        $list = TaskdataModel::where(['tid'=>$tid])->paginate(10);
        $this->assign('list', $list);
        return $this->fetch();
    }
    public function td_result($tdid){
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $taskdata = TaskdataModel::get($tdid);
        $task = TaskModel::get($taskdata->tid);
        $print_data = [$task->data_1 => $taskdata->data_1];
        if (!$taskdata->data_2==NULL){
            $print_data[$task->data_2] = $taskdata->data_2;
        }
        if (!$taskdata->data_3==NULL){
            $print_data[$task->data_3] = $taskdata->data_3;
        }
        $this->assign('task',$task);
        $this->assign('taskdata',$taskdata);
        $this->assign('print_list',$print_data);
        $result_list = ResultModel::all(['td_id'=>$taskdata->id,'uid'=>Cookie::get('uid')]);
        $this->assign('result_list',$result_list);
        return $this->fetch();
    }
    public function info($tid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        if (!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        $task = TaskModel::get($tid);
        $this->assign('task',$task);
        $ulist = UsertaskModel::all(['tid'=>$tid]);
        $this->assign('ulist',$ulist);

        $taskdata_list = TaskdataModel::all(['tid'=>$tid]);
        $min_used = 99999;
        $sum_result = 0;
        foreach ($taskdata_list as $taskdata) {
            $result_list = Db::name('result')->where('td_id',$taskdata->id)->select();
            $user_list = [];
            foreach ($result_list as $result) {
                if (!in_array($result['uid'],$user_list)) {$user_list[]=$result['uid'];}
            }
            if (count($user_list)<$min_used){
                $min_used = count($user_list);
            }
            $sum_result += count($result_list);
        }
        $this->assign('td_num',count($taskdata_list));
        $this->assign('re_num',$sum_result);
        $this->assign('min',$min_used);
        return $this->fetch();
    }
    public function csv_out($tid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        if(!(task_owner($tid)==Cookie::get('uid'))) {$this->error('您无权操作本任务，请检查权限！', 'dashboard/index/index');}
        $td_list = TaskdataModel::all(['tid'=>$tid]);
        $final_csv_result = [];
        $task = TaskModel::get($tid);
        $csv_head = task_head($task);
        $final_csv_result[] = $csv_head;
        foreach ($td_list as $taskdata) {
            $final_csv_result[] = taskdata_out($taskdata);
        }
        $str = "";
        foreach ($final_csv_result as $line) {
            foreach ($line as $l_key => $l_value) {
                if ($l_key==(count($line)-1)){
                    $str.= iconv('utf-8', 'utf-8', $l_value)."\r\n";
                } else {
                    $str.= iconv('utf-8', 'utf-8', $l_value).",";
                }
            }
        }
        $file_name="CSV".date("mdHis",time()).".csv";
        $this->export_filename($file_name,$str);

    }
    public function export_filename($filename,$data)
    {
      header("Content-type:text/csv");
      header("Content-Disposition:attachment;filename=".$filename);
      header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
      header('Expires:0');
      header('Pragma:public');
        echo $data;
    }
}
