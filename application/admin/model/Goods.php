<?php
namespace app\admin\model;
use think\Model;
//use traits\model\SoftDelete;
class Goods extends Model
{
    //可以定义一些属性和方法
    //特殊表 可以使用table属性 指定模型对应的真实表名称
//    protected $table = 'tpshop_goods';
    //使用SoftDelete 这个trait
    use \traits\model\SoftDelete;
    protected $deleteTime = 'delete_time';
}