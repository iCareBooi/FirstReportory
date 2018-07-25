<?php
namespace app\index\logic;

use think\Controller;
class Goods extends Controller{
    //逻辑类，通常是控制器类的一个扩展（将一部分控制器的代码，单独放到逻辑类）
    public static function save($data)
    {
        //参数检测
        //数据的处理（调用service封装的方法，service里面再调用模型的方法处理数据）
        \app\index\service\Goods::save($data);
    }
}