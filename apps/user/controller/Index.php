<?php
namespace app\user\controller;

use app\user\model\User as UserModel;
use think\Controller;
use think\Request;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function login(Request $request)
    {
        $user = UserModel::get(['user_name'=>$request->post('user_name')]);
        if (!$user) {
            $user = UserModel::get(['user_email'=>$request->post('user_name')]);
        }
        if (!$user) {
            $this->error('用户不存在！');
        } else {
            if ($request->post('pwd')==$user->user_pwd)
            {
                user_login($user->user_id,360000);
                $this->redirect('dashboard/index/index');
            } else {
                $this->error('密码错误！');
            }
        }
    }

    public function logout()
    {
        user_logout();
        $this->success('已登出！','user/index/index');
    }
}
