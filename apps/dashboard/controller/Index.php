<?php
namespace app\dashboard\controller;
use think\Controller;
use app\user\model\User as UserModel;
use app\dashboard\model\Task as TaskModel;
use think\Request;
use think\Cookie;
class Index extends Controller
{
    public function index()
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $user = UserModel::get(Cookie::get('uid'));
        $tasklist = TaskModel::all(['uid' => $user->user_id]);
        $this->assign('ownlist', $tasklist);
        $join_task_list = $user->usertask()->select();
        $sum_count = 0;
        $joinlist = [];
        foreach ($join_task_list as $usertask) {
            $task = TaskModel::get($usertask->tid);
            $sum_count += $task->taskdata()->count();
            $joinlist[] = $task;
        }
        $this->assign('sum_count', $sum_count);
        $this->assign('joinlist', $joinlist);
        return $this->fetch();
    }
}
