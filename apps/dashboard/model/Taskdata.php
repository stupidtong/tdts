<?php
namespace app\dashboard\model;

use think\Model;

class Taskdata extends Model
{
    protected $tabel = 'tdts_taskdata';
    public function task()
    {
        return $this->belongsTo('Task');
    }
}
