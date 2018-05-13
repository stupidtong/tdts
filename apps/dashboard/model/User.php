<?php
namespace app\dashboard\model;

use think\Model;

class User extends Model
{
    protected $tabel = 'tdts_user';
    public function usertask()
    {
        return $this->hasMany('Usertask','uid');
    }
}
