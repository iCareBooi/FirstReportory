<?php

namespace app\home\controller;

use think\Controller;
use think\Request;

class Goods extends Base
{
    /**
     * 商品列表
     *
     * @return \think\Response
     */
    public function index($cate_id)
    {
        //查询指定分类下的商品
        $list = \app\home\model\Goods::where('cate_id', $cate_id)->order('id desc')->paginate(10);
        return view('index', ['list' => $list]);
    }

    /**
     * 商品详情.
     *
     * @return \think\Response
     */
    public function detail($id)
    {
        //商品基本信息
        $goods = \app\home\model\Goods::find($id);
        //商品相册信息
        $goodspics = \app\home\model\Goodspics::where('goods_id', $id)->select();
        //查询属性名称信息
        $attribute = \app\home\model\Attribute::where('type_id', $goods->type_id)->select();
        //查询当前商品所有的属性值
        $goodsattr = \app\home\model\GoodsAttr::where('goods_id', $id)->select();
        //为了方便页面展示，将所有的属性值 按照属性id 做分组
        $new_goodsattr = [];  //['attr_id' => [obj,obj], 'attr_id'=>[obj,obj]]
        foreach($goodsattr as $k=>$v){
            //$v['attr_id'] 属性id
            $new_goodsattr[$v['attr_id']][] = $v->toArray();
        }
        //dump($new_goodsattr);
        return view('detail', [
            'goods' => $goods,
            'goodspics' => $goodspics,
            'attribute' => $attribute,
            'new_goodsattr' => $new_goodsattr
        ]);
    }
}
