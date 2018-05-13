<?php
namespace app\user\model;

use think\Model;

class Usertask extends Model
{
    protected $tabel = 'tdts_usertask';
    public function task()
    {
        return $this->belongsTo('Task');
    }
    public function user()
    {
        return $this->belongsTo('User');
    }
}
