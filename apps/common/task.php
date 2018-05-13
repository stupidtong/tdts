<?php
use app\dashboard\model\Task as TaskModel;
use app\dashboard\model\Taskdata as TaskdataModel;
use app\dashboard\model\Result as ResultModel;
use think\Db;
use think\Cookie;

function task_owner($tid)
{
    $task = TaskModel::get($tid);
    return $task->uid;
}
function task_name($tid)
{
    $task = TaskModel::get($tid);
    if ($task) {
        return $task->name;
    } else {
        return "任务已删除";
    }

}
function result_count_raw($td_id)
{
    $db_result = Db::name('result')->where('td_id',$td_id)->where('uid',Cookie::get('uid'))->count();
    return $db_result;
}
function result_count($td_id)
{
    $db_result = Db::name('result')->where('td_id',$td_id)->where('uid',Cookie::get('uid'))->count();
    if ($db_result==0) {
        $db_result = '尚未标注';
    }
    if ($db_result==1) {
        $db_re = Db::name('result')->where('td_id',$td_id)->where('uid',Cookie::get('uid'))->find();
        $db_result = $db_re['result_1'];
    }

    return $db_result;
}
function result_user_num($td_id)
{
    $result_list = Db::name('result')->where('td_id',$td_id)->select();
    $user_list = [];
    foreach ($result_list as $result) {
        if (!in_array($result['uid'],$user_list)) {$user_list[]=$result['uid'];}
    }
    return count($user_list);
}
function result_filled($tid)
{
    $taskdata = TaskdataModel::all(['tid'=>$tid]);
    $r_num = 0;
    foreach ($taskdata as $td) {
        if (result_count_raw($td->id)>0) {$r_num+=1;}
    }
    return $r_num;
}
function result_filled_by_uid($tid,$uid)
{
    $taskdata = TaskdataModel::all(['tid'=>$tid]);
    $r_num = 0;
    foreach ($taskdata as $td) {
        $db_result = Db::name('result')->where('td_id',$td->id)->where('uid',$uid)->count();
        if ($db_result>0) {$r_num+=1;}
    }
    return $r_num;
}
function task_head(TaskModel $task)
{
    $final_return = [];
    $final_return[] = 'id';
    $final_return[] = $task->data_1;
    if ($task->data_num > 1) {
        $final_return[] = $task->data_2;
    }
    if ($task->data_num > 2) {
        $final_return[] = $task->data_3;
    }
    $final_return[] = '结果数量';
    $final_return[] = $task->result_1;
    if ($task->result_num > 1) {
        $final_return[] = $task->result_2;
    }
    $final_return[] = '准确率';
    return $final_return;
}
function result_conclusion($td_id)
{
    $result_list = ResultModel::all(['td_id'=>$td_id]);
    $conclusion_list = [];
    foreach ($result_list as $result) {
        $founded = FALSE;
        foreach ($conclusion_list as $re_key => $re_value) {
            if (($re_value[0]==$result->result_1)&&($re_value[1]==$result->result_2)) {
                $founded = TRUE;
                $conclusion_list[$re_key][2] += 1;
            }
        }
        if (!$founded) {
             $conclusion_list[]=[$result->result_1,$result->result_2,1];
        }
    }
    for ($i=0; $i < count($conclusion_list); $i++) {
        for ($j=0; $j < $i; $j++) {
            if (($conclusion_list[$j][2])<($conclusion_list[$j+1][2])) {
                $temp = $conclusion_list[$j][2];
                $conclusion_list[$j][2] = $conclusion_list[$j+1][2];
                $conclusion_list[$j+1][2] = $temp;
            }
        }
    }
    return $conclusion_list;
}
function result_to_list($td_id)
{
    $unum = result_user_num($td_id);
    $result_multi_li = result_conclusion($td_id);
    $result_final = [$unum];
    foreach ($result_multi_li as $re_li) {
        $result_final[] = $re_li[0];
        if (!$re_li[1]==NULL) {$result_final[] = $re_li[1];}
        $result_final[] = $re_li[2]/$unum;
    }
    return $result_final;
}
function taskdata_out(TaskdataModel $td)
{
    $final_return = [];
    $task = TaskModel::get($td->tid);
    $final_return[] = $td->id;
    $final_return[] = $td->data_1;
    if ($task->data_num > 1) {
        $final_return[] = $td->data_2;
    }
    if ($task->data_num > 2) {
        $final_return[] = $td->data_3;
    }
    $final_return = array_merge($final_return,result_to_list($td->id));
    return $final_return;
}
