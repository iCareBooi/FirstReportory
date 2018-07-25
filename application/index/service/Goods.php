<?php
namespace app\index\service;
class Goods{
    public static function getGoodsById($id)
    {
        $info = cache('goods_'.$id);
        if(empty($info)){
            dump('查询数据库');
            //从数据库查询一个商品
            $info = \app\admin\model\Goods::find($id);
            //将结果放入缓存
            cache('goods_'.$id, $info);
        }
        return $info;
    }

    public static function save($data)
    {
        \app\admin\model\Goods::create($data,true);
    }
}