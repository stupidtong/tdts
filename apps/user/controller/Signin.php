<?php
namespace app\user\controller;

use app\user\model\User as UserModel;
use think\Controller;
use think\Request;

class Signin extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function add(Request $request)
    {
        $user = new UserModel;
        $user->user_score = 0;
        $user->user_level = 0;
        $this->check('user_name','用户名', $request);
        if ($request->post('user_pwd')=='') {
           $this->error('密码不能为空');
        }
        $this->check('user_nickname','昵称', $request);
        $this->check('user_email','邮箱', $request);
        if ($user->allowField(true)->save(input('post.'))) {
            $this->success('用户[ ' . $user->user_nickname . ':' . $user->user_id . ' ]新增成功', 'Index/index');
        } else {
            $this->error('用户新增失败，请返回重试');
        }
    }

    private function check($name, $msg, $request) {
      if ($request->post($name)=='') {
         $this->error($msg.'不能为空');
      } else {
         $check_user = UserModel::get([$name=>$request->post($name)]);
         if ($check_user) {
           $this->error($msg.'已存在');
         }
      }
    }
}
