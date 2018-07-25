<?php

namespace app\home\controller;

use app\home\model\Goods;
use app\home\model\GoodsAttr;
use think\Controller;
use think\Request;
use app\home\model\Cart as CartModel;
class Cart extends Base
{
    /**
     * 购物车列表
     *
     * @return \think\Response
     */
    public function index()
    {
  /*      dump($list);die;
        array (size=4)
  0 =>
    array (size=8)
      'id' => int 1
      'user_id' => int 4
      'goods_id' => int 41
      'goods_attr_ids' => string '1,2' (length=3)
      'number' => int 17
      'create_time' => string '2018-06-23 20:15:13' (length=19)
      'update_time' => string '2018-06-23 21:55:26' (length=19)
      'delete_time' => null
  1 =>
    array (size=8)
      'id' => int 2
      'user_id' => int 4
      'goods_id' => int 41
      'goods_attr_ids' => string '5,7' (length=3)
      'number' => int 5
      'create_time' => string '2018-06-23 20:18:00' (length=19)
      'update_time' => string '2018-06-23 20:18:00' (length=19)
      'delete_time' => null
  2 =>
    array (size=8)
      'id' => int 3
      'user_id' => int 4
      'goods_id' => int 41
      'goods_attr_ids' => string '6,8' (length=3)
      'number' => int 1
      'create_time' => string '2018-06-23 21:30:40' (length=19)
      'update_time' => string '2018-06-23 21:30:40' (length=19)
      'delete_time' => null
  3 =>
    array (size=8)
      'id' => int 4
      'user_id' => int 4
      'goods_id' => int 41
      'goods_attr_ids' => string '6,7' (length=3)
      'number' => int 5
      'create_time' => string '2018-06-23 21:30:40' (length=19)
      'update_time' => string '2018-06-23 21:30:40' (length=19)
      'delete_time' => null*/
        //查询购物车数据 调用Cart模型getAllCart方法
        $list = \app\home\model\Cart::getAllCart();
        //查询每一条购物记录中的商品信息
        foreach($list as $k => &$v){
            //$v['goods_id']
            //查询商品表基本信息
            $v['goods'] = \app\home\model\Goods::find($v['goods_id'])->toArray();
            //查询商品属性值 属性名称信息
//            SELECT t1.*,t2.attr_name FROM `tpshop_goods_attr` t1 left join tpshop_attribute t2 on t1.attr_id = t2.id where t1.id in (8,11);
            $v['goodsattr'] = \app\home\model\GoodsAttr::alias('t1')
                ->field('t1.*, t2.attr_name')
                ->join('tpshop_attribute t2', 't1.attr_id = t2.id', 'left')
                ->where('t1.id', 'in', $v['goods_attr_ids'])
                ->select();
        }
        return view('index', ['list' => $list]);
        /*dump($list);die;
        array (size=4)
  0 =>
    array (size=10)
      'id' => int 1
      'user_id' => int 4
      'goods_id' => int 41
      'goods_attr_ids' => string '1,2' (length=3)
      'number' => int 17
      'create_time' => string '2018-06-23 20:15:13' (length=19)
      'update_time' => string '2018-06-23 21:55:26' (length=19)
      'delete_time' => null
      'goods' =>
        array (size=11)
          'id' => int 41
          'goods_name' => string 'iphone  8 plus' (length=14)
          'goods_price' => string '6899.00' (length=7)
          'goods_number' => int 1000
          'goods_introduce' => string '<p>test</p>' (length=11)
          'goods_logo' => string '\uploads\20180623\9f1b4f2892fd9cc7f27989ecc8b345d2.png' (length=54)
          'create_time' => string '2018-06-23 19:24:18' (length=19)
          'update_time' => string '2018-06-23 19:24:18' (length=19)
          'delete_time' => null
          'type_id' => int 1
          'cate_id' => int 187
      'goodsattr' =>
        array (size=2)
          0 =>
            object(app\home\model\GoodsAttr)[1069]
              ...
          1 =>
            object(app\home\model\GoodsAttr)[1070]
              ...*/
    }

    //购物车列表 优化写法
    /*public function index1()
    {
        //查询购物车数据 调用Cart模型getAllCart方法
        $list = \app\home\model\Cart::getAllCart();
        //将所有购物记录中的商品id 取出来
        $goods_ids = [];
        foreach($list as $v){
            $goods_ids[$v['goods_id']] = $v['goods_id'];
//            $goods_ids[] = $v['goods_id']; // 可以foreach外面使用array_unique函数去重
        }
        //查询所有的商品信息
        $goods = \app\home\model\Goods::where('id', 'in', $goods_ids)->select();
//        dump($goods);die;
        //直观的写法
//        foreach($list as &$v){
//            //$v['goods_id'] 49
//            foreach($goods as $value){
//                if($value['id'] == $v['goods_id']){
//                    $v['goods'] = $value;
//                }
//            }
//        }
        //更好的写法
        //[obj,obj]   [49=>obj, 50=>obj]
        $new_goods = []; //新的商品数组中，以商品id为下标
        foreach($goods as $v){
            $new_goods[$v['id']] = $v;
        }
        unset($v);
        //遍历购物车数据$list
        foreach($list as &$v){
            //$v['goods_id']  根据商品id为下标，从new_goods中取出对应商品信息
            $v['goods'] = $new_goods[$v['goods_id']];
        }
        unset($v);
//        dump($list);die
        //查询商品属性值 属性名称信息 优化
        //将所有的属性值对应的主键id放到一个数组
        $goods_attr_ids = [];
        foreach($list as $v){
            $temp = explode(',', $v['goods_attr_ids']);
            //数组合并  arr1 + arr2 ;   数组函数 array_merge()
            $goods_attr_ids = array_merge($goods_attr_ids, $temp);
        }
        unset($v);
        //数组去重
        $goods_attr_ids = array_unique($goods_attr_ids);
//        dump($goods_attr_ids);die;
        //查询所有的属性值、属性名称
        $goodsattr = \app\home\model\GoodsAttr::alias('t1')
            ->field('t1.*, t2.attr_name')
            ->join('tpshop_attribute t2', 't1.attr_id = t2.id', 'left')
            ->where('t1.id', 'in', $goods_attr_ids)
            ->select();
        //将属性值数组中，主键id作为下标
        $new_goodsattr = [];
        foreach($goodsattr as $v){
            $new_goodsattr[$v['id']] = $v;
        }
        unset($v);
        //将购物车数组$list 和 属性值 $new_goodsattr 整合到一起
        foreach($list as &$v){
            //$v['goods_attr_ids'] // 8,11
            //在每条购物车数据中，对goods_attr_ids中的每一个值，从$new_goodsattr取出属性数据
            $temp = explode(',', $v['goods_attr_ids']);
            foreach($temp as $id){
                $v['goodsattr'][] = $new_goodsattr[$id];
            }
        }
        unset($v);
//        dump($list);die;
        return view('index', ['list' => $list]);
    }*/
    /**
     * 添加购物车.
     *
     * @return \think\Response
     */
    public function addcart()
    {
        //商品详情页 加入购物车按钮隐藏表单提交
        if(request()->isGet()){
            //如果以get方式请求，直接跳转到首页去
            $this->redirect('home/index/index');
        }
        //接收参数
        $data = request()->param();
        //参数检测 略
        //处理数据 调用模型的addCart方法
        \app\home\model\Cart::addCart($data['goods_id'], $data['number'], $data['goods_attr_ids']);
        //显示成功页面
        //查询商品信息， logo 和名称等信息
        $goods = \app\home\model\Goods::find($data['goods_id']);
        return view('addcart', ['goods' => $goods]);
    }

    //ajax请求 修改购买数量
    public function changenum()
    {
        //接收参数
        $data = request()->param();
        //参数检测 略 1,2,3格式的检测  可以用正则，也可以分割为数组，对每个值进行判断
        //处理数据
        \app\home\model\Cart::changeNum($data['goods_id'], $data['goods_attr_ids'], $data['number']);
        //返回数据
        return json(['code' => 10000, 'msg' => 'success']);
    }

    //ajax请求 删除购物记录
    public function delcart()
    {
        //接收数据
        $data = request()->param();
        //参数检测 略
        //处理数据
        \app\home\model\Cart::delCart($data['goods_id'], $data['goods_attr_ids']);
        //返回数据
        return json(['code' => 10000, 'msg' => 'success']);
    }
    
    //购物车页面第二回
    public function index2()
    {
        $list = CartModel::getAllCart();
        foreach ($list as &$v){
            $v['goods'] = Goods::find('id',$v['goods_id'])->toArray();
            //SELECT * FROM tpshop_goods_attr t1 LEFT JOIN tpshop_attribute t2 ON t1.attr_id = t2.id
            $v['goodsattr'] = GoodsAttr::alias('t1')
                            ->field('t1.*,t2.attr_name')
                            ->join('tpshop_attribute t2','t1.attr_id = t2.id','left')
                            ->where('t1.id','in',$v['goods_attr_ids'])
                            ->select();
        }
        return view('index2',['list' => $list]);
    }
}