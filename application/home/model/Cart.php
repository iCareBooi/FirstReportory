<?php

namespace app\home\model;

use think\Model;

class Cart extends Model
{
    //封装加入购物车的方法
    public static function addCart($goods_id, $number, $goods_attr_ids)
    {
        //控制器addcart调用此方法添加购物车数据
        //根据登录状态判断， 已登录 添加到数据表；未登录，添加到cookie
        if(session('?user')){
            //已登录 添加到数据表
            //先判断，是否存在相同的购物记录--user_id  goods_id  goods_attr_ids一起作为条件
            $user_id = session('user.id');
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            //查询
            $info = self::where($where)->find();
            if($info){
                //存在相同记录，累加购买数量
                $info->number += $number;
                $info->save();
            }else{
                //不存在相同记录，添加新记录
//                $data = $where;
                $where['number'] = $number;
                self::create($where);
            }
            return true;
        }else{
            //未登录 添加到cookie
            $cart = cookie('cart') ? unserialize(cookie('cart')) : [];
            //dump($cart);
            //拼接要添加的记录 的下标
            $key = $goods_id . '-' . $goods_attr_ids;
            //判断 是否存在相同记录
            if(isset($cart[$key])){
                $cart[$key] += $number;
            }else{
                $cart[$key] = $number;
            }
            //将新的数组 重新转化为字符串，存储到cookie
            cookie('cart', serialize($cart), 86400 * 7);
            //dump($cart);
            /*E:\WAMP64\www\tpshop\thinkphp\library\think\Debug.php:193:
            array (size=2)
              '41-6,8' => string '1' (length=1)
              '41-6,7' => string '5' (length=1)*/
            return true;
        }
    }

    //封装获取所有购物车数据的方法
    public static function getAllCart()
    {
        //判断登录状态  已登录，查询数据表；未登录，查询cookie
        if(session('?user')){
            //已登录，查询数据表
            $user_id = session('user.id');
            //根据user_id为条件查询购物车表 [obj, obj]
            $data = self::where('user_id', $user_id)->select();
            //将结果下的数据对象，转化为数组
            foreach($data as &$v){
                $v = $v->toArray();
            }
            /* dump($data);
                3 =>
                 array (size=8)
                   'id' => int 4
                   'user_id' => int 4
                   'goods_id' => int 41
                   'goods_attr_ids' => string '6,7' (length=3)
                   'number' => int 5*/
            return $data;
        }else{
            //未登录，查询cookie
            $cart = cookie('cart') ? unserialize(cookie('cart')) : [];
            $data = [];
            //遍历数组，将一维数组 转化为二维数组格式 （和数据表取出的数据结构统一）
            foreach($cart as $k=>$v){
                //$k  goods_id - goods_attr_ids ; $v  number
//                ['goods_id'=>'', 'goods_attr_ids'=>'', 'number'=>'']
                //将$k 用 - 分割为数组
                $temp = explode('-', $k);
                $data[] = [
                    'id' => '',
                    'goods_id' => $temp[0],
                    'goods_attr_ids' => $temp[1],
                    'number' => $v
                ];
            }
            //最终的二维数组结构 $data   [[],[],[]]
            /*dump($data);
            array (size=2)
                  0 =>
                    array (size=4)
                      'id' => string '' (length=0)
                      'goods_id' => string '41' (length=2)
                      'goods_attr_ids' => string '6,8' (length=3)
                      'number' => string '1' (length=1)
                  1 =>
                    array (size=4)
                      'id' => string '' (length=0)
                      'goods_id' => string '41' (length=2)
                      'goods_attr_ids' => string '6,7' (length=3)
                      'number' => string '5' (length=1)*/
            return $data;
        }
    }

    //迁移cookie中的购物车数据到数据表
    public static function cookieTodb()
    {
        //从cookie中取出所有数据
        $data = cookie('cart') ? unserialize(cookie('cart')) : [];
//        if(empty($data)) return;
        //将cookie每一条数据添加到数据表
        //$data 中数据的   key => value   key：goods_id - goods_attr_ids   value: number
        foreach($data as $k => $v){
            //从$k 中获取到goods_id 和 goods_attr_ids
            $temp = explode('-', $k);
            $goods_id = $temp[0];
            $goods_attr_ids = $temp[1];
            $number = $v;
            //接下来，其实就是登录情况下，加入购物车的过程，可以调用addCart方法
            self::addCart($goods_id, $number, $goods_attr_ids);
        }
        //清除cookie中的购物车数据
        cookie('cart', null);
        return true;
    }

    //修改指定的购物记录的购买数量
    public static function changeNum($goods_id, $goods_attr_ids, $number)
    {
        //判断登录状态  已登录：修改数据表；未登录：修改cookie
        if(session('?user')){
            //已登录：修改数据表；
            //获取用户id
            $user_id = session('user.id');
            //修改条件
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            //修改数据到数据表
            $data = ['number' => $number];
            //调用update方法修改数据
            self::update($data, $where);//这是调用模型类的update方法
//            self::where($where)->update($data);//这是调用Query类的update方法
            return true;
        }else{
            //未登录：修改cookie
            $data = cookie('cart') ? unserialize(cookie('cart')) : [];
            //拼接下标 key
            $key = $goods_id . '-' . $goods_attr_ids;
            //修改购买数量
            $data[$key] = $number;
            //将修改后的数据 重新保存到cookie
            cookie('cart', serialize($data), 86400 * 7);
            return true;
        }
    }

    //删除指定购物记录的方法
    public static function delCart($goods_id, $goods_attr_ids)
    {
        //判断登录状态， 已登录：删除数据表中的记录； 未登录：删除cookie中的记录
        if(session('?user')){
            //已登录：删除数据表中的记录；
            //获取用户id
            $user_id = session('user.id');
            //删除条件
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            //删除记录 delete  destroy方法
            self::where($where)->delete();
            return true;
        }else{
            //未登录：删除cookie中的记录
            $data = cookie('cart') ? unserialize(cookie('cart')) : [];
            //拼接要删除记录的下标
            $key = $goods_id . '-' . $goods_attr_ids;
            //从数组中删除指定的键值对
            unset($data[$key]);
            //将新的数组 重新保存到cookie
            cookie('cart', serialize($data), 86400 * 7);
            return true;
        }
    }
    
    //添加购物车方法
    public function addCart2($goods_id,$goods_attr_ids,$number)
    {
        //判断登陆状态如果登陆了存数据库 未登录存cookie
        if (session('?user')){
            //获取用户id
            $user_id = session('user.id');
            //根据用户id 商品id 权限id查询购物车数据表
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            //查询数据表
            $info = self::where($where)->find();
            if ($info) {
                //如果找到 只修改购买数量 在原来的记录基础上加上新的数量
                $info->number += $number;
                $info->save();
            }else{
                //如果未找到则添加新纪录
                $where['number'] = $number;
                self::create($where);
            }
            return true;
        }else{
            //如果未登录将cookie中保存的数据做相应的添加或者修改
            $cart = cookie('cart') ? unserialize(cookie('cart')) : [];
            //拼接要添加的记录下标
            $key = $goods_id . '-' . $goods_attr_ids;
            //判断cookie中是否有相同记录
            if (isset($cart[$key])){
                $cart[$key] += $number;
            }else{
                $cart[$key] = $number;
            }
            //将新的数组添加到cookie中
            cookie('cart',serialize($cart),86400*7);
            return true;
        }
    }

    //获取购物车数据也要判断登陆未登录
    public function getAllCart2()
    {
        //判断登陆状态
        if (session('?user')){
            $user_id = session('user.id');
            //根据user_id获取数据表数据
            $data = self::where('user_id',$user_id)->select();
            //将结果对象转化为数组
            foreach ($data as &$v){
                $v = $v->toArray();
            }
            return $data;
        }else{
            //未登录取cookie数据
            $cart = cookie('cart') ? unserialize(cookie('cart')) : [];
            $data = [];
            foreach ($cart as $k => $v){
                $temp = explode('-',$k);
                $data[] = [
                    'id' => '',
                    'goods_id' => $temp[0],
                    'goods_attr_ids' => $temp[1],
                    'number' => $v
                ];
            }
            return $data; //最终二维数组结构 $data [[],[],[]]
        }
    }

    //登陆后迁移购物车数据
    public function cookieToDb2()
    {
        //获取cookie中的数据
        $cart = cookie('cart') ? unserialize(cookie('cart')) : [];
        foreach ($cart as $k => $v){
            $temp = explode('-',$k);
            $goods_id = $temp[0];
            $goods_attr_ids = $temp[1];
            $number = $v;
            //登陆过程中加入购物车的操作
            self::addCart($goods_id,$goods_attr_ids,$number);
        }
        //清除cookie
        cookie('cart',null);
        return true;
    }

    //修改指定购买指定购物数量
    public function changeNum2($goods_id,$goods_attr_ids,$number)
    {
        //判断登陆状态 登陆修改数据表 未登录修改到 cookie
        if (session('?user')){
            $user_id = session('user.id');
            //数据更新到数据表
            //组装where条件
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            //要修改数量
            $data = ['number' => $number];
            self::update($data,$where);
        }else{
            //未登录状态 获取cookie中的数据
            $cart = cookie('cart') ? unserialize(cookie('cart')) : [] ;
            //拼接下标
            $key = $goods_id . '-' . $goods_attr_ids;
            //修改cookie中的购买数量
            $cart[$key] = $number;
            //cookie保存新的值
            cookie('cart',serialize($cart),86400*7);
            return true;
        }
    }

    //删除购物车数据
    public function delCart2($goods_id,$goods_attr_ids)
    {
        //判断登陆状态
        if (session('?user')){
            //登陆状态下 删除数据表数据
            $user_id = session('user.id');
            $where = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'goods_attr_ids' => $goods_attr_ids
            ];
            self::where($where)->delete();
            return true;
        }else{
            //未登录状态获取cookie数据
            $cart = cookie('cart') ? unserialize('cart') : [] ;
            $key = $goods_id . '-' . $goods_attr_ids;
            //cookie($cart[$key],null);
            //数组中删除对应的键值对用unset函数
            unset($cart[$key]);
            //保存新的数据到cookie
            cookie('cart',serialize($cart),86400*7);
            return true;
        }
    }
}
