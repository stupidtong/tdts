<?php
namespace app\dashboard\controller;
use think\Controller;
use app\dashboard\model\User as UserModel;
use app\dashboard\model\Usertask as UsertaskModel;
use app\dashboard\model\Msg as MsgModel;
use think\Request;
use think\Cookie;
use think\Db;

class Message extends Controller
{
    public function index()
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $msglist = MsgModel::all(['to_id'=>Cookie::get('uid')]);
        $this->assign('msglist',$msglist);
        return $this->fetch();
    }
    public function accept($mid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $msg = MsgModel::get($mid);
        if ($msg->to_id==Cookie::get('uid')){
            $ut_p = UsertaskModel::get(['uid'=>Cookie::get('uid'),'tid'=>$msg->tid]);
            if ($ut_p) {
                $msg->delete();
                $this->success('您已接受过邀请！');
            } else {
              $ut = new UsertaskModel;
              $ut->uid = Cookie::get('uid');
              $ut->tid = $msg->tid;
              $ut->jointime = date("Y-m-d H:i:s");
              if ($ut->allowField(true)->save()) {
                  $msg->delete();
                  $this->success('您已接受邀请！');
              } else {
                  $this->error('接受失败！');
              }
            }

        } else {
            $this->error('只能接受发给自己的信息！');
        }
    }
    public function delete($mid)
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        $msg = MsgModel::get($mid);
        if ($msg->to_id==Cookie::get('uid')){
            $msg->delete();
            $this->success('删除成功!');
        } else {
            $this->error('只能删除发给自己的信息！');
        }
    }
    public function delete_all()
    {
        if(!check_user_status()) {$this->error('您还未登录！', 'user/index/index');}
        MsgModel::destroy(['to_id' => Cookie::get('uid')]);
        $this->success('删除成功!');
    }
}
