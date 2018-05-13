<?php
use app\dashboard\model\Task as TaskModel;
use app\dashboard\model\Taskdata as TaskdataModel;
use app\dashboard\model\Result as ResultModel;
use app\user\model\User as UserModel;
use app\dashboard\model\Usertask as UsertaskModel;
use think\Db;
use think\Cookie;

function kappa($tid)
{
    $final_list = [];
    $final_kappa=[];
    $task = TaskModel::get($tid);
    $ulist = UsertaskModel::all(['tid'=>$tid]);
    foreach ($ulist as $u1) {
        foreach ($ulist as $u2) {
            $final_list[$u1->uid][$u2->uid] = [];
        }
    }
    $td_li = TaskdataModel::all(['tid'=>$tid]);
    foreach ($td_li as $td) {
        $re_li = ResultModel::all(['td_id'=>$td->id]);
        foreach ($re_li as $re1) {
            foreach ($re_li as $re2) {
                if(!isset($final_list[$re1->uid][$re2->uid][$re1->result_1][$re2->result_1]))
                  {$final_list[$re1->uid][$re2->uid][$re1->result_1][$re2->result_1]=0;}
                $final_list[$re1->uid][$re2->uid][$re1->result_1][$re2->result_1]+=1;
            }
        }
    }
    foreach ($ulist as $u1) {
        foreach ($ulist as $u2) {
            $kpa=kappa_cac($final_list[$u1->uid][$u2->uid]);
            if(!($kpa=='无结果')) {$final_kappa[$u1->uid][$u2->uid]=$kpa;}

        }
    }
    return $final_kappa;
}

function kappa_cac($va_list)
{
    $kpa = 0;
    $sum = 0;
    $same_sum = 0;
    $va_li1 = [];
    $va_li2 = [];
    foreach ($va_list as $key1 => $va1) {
       foreach ($va1 as $key2 => $value) {
          if(!isset($va_li1[$key1])) {$va_li1[$key1]=0;}
          if(!isset($va_li2[$key2])) {$va_li2[$key2]=0;}
          $va_li1[$key1] += $value;
          $va_li2[$key2] += $value;
          $sum += $value;
          if($key1==$key2){
              $same_sum += $value;
          }
       }
    }
    if($sum==0) {
        return '无结果';
    } else {
        $ks=0;
        foreach ($va_li1 as $k1 => $value) {
            if(isset($va_li2[$k1])) {
                $ks += $value*$va_li2[$k1];
            }
        }
        $p0 = $same_sum/$sum;
        $pe = $ks/($sum*$sum);
        $kpa = ($p0-$pe)/(1-$pe);
        return $kpa;
    }
}
