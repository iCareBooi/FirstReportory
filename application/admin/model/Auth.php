<?php

namespace app\admin\model;

use think\Model;

class Auth extends Model
{
    //设置获取器， 对is_nav字段的值进行自动转化
    public function getIsNavAttr($value)
    {
        // 1 是； 0 否
        return $value ? '是' : '否';
    }
}
