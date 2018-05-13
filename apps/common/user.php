<?php
use app\user\model\User as UserModel;
use think\Request;
use think\Cookie;
use think\Db;

function user_login($user_id,$time)
{
    $user = UserModel::get($user_id);
    $cookie_pwd = md5($user->user_pwd);
    cookie('name',$user->user_name,$time);
    cookie('uid',$user_id,$time);
    cookie('pwd',$cookie_pwd,$time);
}

function check_user_status()
{
    if ((Cookie::get('uid'))AND(Cookie::get('name'))AND(Cookie::get('pwd'))){
      $user = UserModel::get(Cookie::get('uid'));
      if (Cookie::get('name')==$user->user_name) {
        if (Cookie::get('pwd')==md5($user->user_pwd)) {
            return True;
        } else{
          return False;
        }
      } else{
        return False;
      }
    } else{
      return False;
    }
}

function get_user_msg_count()
{
    $uid = Cookie::get('uid');
    $msg_count = Db::name('msg')->where('to_id',$uid)->count();
    return $msg_count;
}

function nickname_in_cookie()
{
    $user = UserModel::get(Cookie::get('uid'));
    return $user->user_nickname;
}

function nickname($uid)
{
    $user = UserModel::get($uid);
    return $user->user_nickname;
}
function user_logout(){
    Cookie::delete('name');
    Cookie::delete('pwd');
    Cookie::delete('uid');
}
