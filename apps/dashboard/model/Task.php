<?php
namespace app\dashboard\model;

use think\Model;

class Task extends Model
{
    protected $tabel = 'tdts_task';
    public function taskdata()
    {
        return $this->hasMany('Taskdata','tid');
    }
    public function usertask()
    {
        return $this->hasMany('Usertask','tid');
    }
}
