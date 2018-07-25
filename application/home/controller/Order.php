<?php

namespace app\home\controller;

use app\home\model\Address;
use app\home\model\Cart;
use app\home\model\Goods;
use app\home\model\OrderGoods;
use app\home\model\Order as OrderModel;
use app\home\model\GoodsAttr;
use think\Controller;
use think\Exception;
use think\Request;
use sphinx\Sphinxclient;
class Order extends Base
{

    /**
     * 结算页面.
     *
     * @return \think\Response
     */
    public function create()
    {
        //登录判断
        if(!session('?user')){
            //没有登录，跳转到登录页
            //指定登录成功后，调回的地址  比如重新进入购物车列表
            $back_url = 'home/cart/index';
            session('back_url', $back_url);
            $this->redirect('home/login/login');
        }
        //接收参数
        $cart_ids = request()->param('cart_ids');
        /*dump($cart_ids);die;
        E:\WAMP64\www\tpshop\thinkphp\library\think\Debug.php:193:string '1,2,3,4' (length=7)*/
        //参数检测 略
        //查询当前用户的收货地址信息 tpshop_address表
        $user_id = session('user.id');
        $address = \app\home\model\Address::where('user_id', $user_id)->select();
//        dump($address);die;
        //获取支付方式
        $paytype = config('paytype');
        //查询选中的购物记录信息
        //SELECT t1.*, t2.goods_name, t2.goods_logo, t2.goods_price, t2.goods_number FROM `tpshop_cart` t1 left join tpshop_goods t2 on t1.goods_id = t2.id where t1.id in (5,6);
        $data = \app\home\model\Cart::alias('t1')
            ->field('t1.*, t2.goods_name, t2.goods_logo, t2.goods_price, t2.goods_number')
            ->join('tpshop_goods t2', 't1.goods_id = t2.id', 'left')
            ->where('t1.id', 'in', $cart_ids)
            ->select();
        //计算总金额 和总数量
        $total_price = 0;
        $total_number = 0;
        foreach($data as $v){
            $total_price += $v['number'] * $v['goods_price'];
            $total_number += $v['number'];
        }
//        dump($total_price);die;
        return view('create', ['address' => $address, 'paytype' => $paytype, 'data' => $data, 'total_price' => $total_price, 'total_number' => $total_number]);
    }

    //提交订单
    public function save()
    {
        //接收参数
        $data = request()->param();
        //参数检测 略
        //订单编号  唯一
        $order_sn = mt_rand(100000, 999999) . time();
        //用户id
        $user_id = session('user.id');
        //收货地址信息
        $address = \app\home\model\Address::find($data['address_id']);
        //订单总金额  根据cart_ids 查询 购物表和商品表，累加计算
        //SELECT t1.*, t2.goods_name, t2.goods_logo, t2.goods_price, t2.goods_number FROM `tpshop_cart` t1 left join tpshop_goods t2 on t1.goods_id = t2.id where t1.id in (5,6);
        $cart_data = \app\home\model\Cart::alias('t1')
            ->field('t1.*, t2.goods_name, t2.goods_logo, t2.goods_price, t2.goods_number')
            ->join('tpshop_goods t2', 't1.goods_id=t2.id', 'left')
            ->where('t1.id', 'in', $data['cart_ids'])
            ->select();
        //计算订单总金额
        $order_amount = 0;
        foreach($cart_data as $v){
            $order_amount += $v['goods_price'] * $v['number'];
        }
        //组装一条订单表数据
        $row = [
            'order_sn' => $order_sn,
            'order_amount' => $order_amount,
            'user_id' => $user_id,
            'consignee_name' => $address->consignee,
            'consignee_phone' => $address->phone,
            'consignee_address' => $address->address,
            'shipping_type' => 'yuantong',
            'pay_type' => $data['pay_type']
        ];
        //开启事务：
        \think\Db::startTrans();
        try{
            //将数据添加到订单表
            $order = \app\home\model\Order::create($row);
            //向订单商品表 添加多条数据
            $ordergoods_data = [];
            foreach($cart_data as $v){
                //组装一条 订单商品表的数据
                $row_goods = [
                    'order_id' => $order->id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_price' => $v['goods_price'],
                    'goods_logo' => $v['goods_logo'],
                    'number' => $v['number'],
                    'goods_attr_ids' => $v['goods_attr_ids']
                ];
                //放到结果数组，用于后续批量添加
                $ordergoods_data[] = $row_goods;

//            //减库存操作
//            //查询商品库存
//            $goods = \app\home\model\Goods::find($v['goods_id']);
//            $store = $goods['goods_number'] - $v['number'];
//            if($store < 0){
//                //库存不够
//                $this->error('创建订单失败，库存不够');
//            }
//            \app\home\model\Goods::update(['goods_number' => $store], ['id' => $v['goods_id']]);
            }
            //批量添加数据到订单商品表
            $ordergoods = new \app\home\model\OrderGoods();
            $ordergoods->saveAll($ordergoods_data);
            //减库存
            // 将购物记录中  同一个商品的 购买数量进行累加  哪个商品一共购买多少个
            $new_data = [];
            foreach($cart_data as $v){
                //商品id$v['goods_id']  购买数量$v['number']  原始库存$v['goods_number']
                if(!isset($new_data[$v['goods_id']])){
                    $new_data[$v['goods_id']] = $v['number'];
                }else{
                    $new_data[$v['goods_id']] += $v['number'];
                }
            }
            //针对每一个商品，进行减库存
            foreach($new_data as $k => $v){
                //$k  goods_id;  $v  购买数量
                $goods = \app\home\model\Goods::find($k);
                //计算新的库存
                $store = $goods['goods_number'] - $v;
                if($store < 0){
//                    $this->error('库存不足');
                    //抛出异常
                    throw new \Exception('库存不足' , 10001);
                }
                //修改商品表的库存
                \app\home\model\Goods::update(['goods_number' => $store], ['id' => $k]);
            }
            //将购物车表中的对应记录删除  cart_ids   5,6
            \app\home\model\Cart::destroy($data['cart_ids']);
//        \app\home\model\Cart::where('id', 'in', $data['cart_ids'])->delete();
            //接下来去支付
            switch ($data['pay_type']){
                case 'alipay':
                    //支付宝支付
                    break;
                case 'wechat':
                    //微信支付
                    break;
                case 'card':
                    //银联
                    break;
                case 'cash':
                    //货到付款
                    break;
            }
            //提交事务
            \think\Db::commit();
        }catch(\Exception $e){
            //回滚事务
            \think\Db::rollback();
            //获取异常信息 错误码 $e->getCode();
//            $code = $e->getCode();

            $error = $e->getMessage();
            //进行报错
            $this->error($error);
        }
    }
    
    //支付宝异步通知地址
    public function notify()
    {
        //接收参数
        $data = request()->param();
        //验证签名

    }

    //支付宝同步通知地址
    public function callback()
    {
        //接收参数
        $data = request()->param();
        //验证签名
        $alipaySevice = new AlipayTradeService($config);
        $result = $alipaySevice->check($data);
        if ($result){
            //验证成功
            //echo '验签成功';die;
            //展示成功支付的页面
            return view('paysuccess',['total_amount' => $data['total_amount'],]);
        }else{
            //验签失败
            //echo '验签失败';die;
            return view('payfail');
        }
    }

    //购物车页面跳转结算页面
    public function create2()
    {
        //判断登陆状态 未登录状态不允许结算直接跳转至登陆界面
        if (!session('?user')){
            //设置跳回地址
            $back_url = 'home/cart/index';
            session('back_url',$back_url);
            $this->redirect('home/login/login');
        }
        //正常登陆状态下 接收参数
        $cart_ids = request()->param('cart_ids');
        //根据登陆用户的user_id查询收货人姓名 地址 电话信息
        $user_id = session('user.id');
        $address = Address::where('user_id',$user_id)->select();
        //页面需要展示支付方式
        $paytype = config('paytype');
        //提交订单页面需要展示购物车中勾选的数据信息 通过cart_ids链表查询
        //select * form tpshop_cart t1 left join tpshop_goods t2 on t1.goods_id = t2.id where t1.id in (1,2,3,4);
        $data = Cart::alias('t1')
                ->field('t1.*,t2.goods_name,t2.goods_logo,t2.goods_price,t2.goods_number')
                ->join('tpshop_goods t2','t1.goods_id = t2.id','left')
                ->where('t1.id','in',$cart_ids)
                ->select();
        //页面需要展示订单应付款 和 数量
        $total_price = 0;
        $total_number = 0;
        foreach ($data as $v){
            $total_price += $v['number'] * $v['goods_price'];
            $total_number += $v['number'];
        }
        //渲染模板
        return view('create',[
            'address' => $address,
            'paytype' => $paytype,
            'data' => $data,
            'total_number' => $total_number,
            'total_price' => $total_price
        ]);
    }

    //create 页面表单提交
    public function save2()
    {
        //接收参数
        $data = request()->param();
        //参数检测
        //用户id
        $user_id = session('user.id');
        //订单编号随机生成
        $order_sn = mt_rand(100000,999999).time();
        //根据user信息查询地址表
        $address = Address::find($data['address']);
        //根据订单表字段 判断需要链表查询购物车表和商品表
        //select * from tpshop_cart t1 left join tpshop_goods t2 on t1.goods_id = t2.id where t1.id in cart_ids
        $cart_data = Cart::alias('t1')
                    ->field('t1.*,t2.goods_name,t2.goods_price,t2.goods_logo,t2.goods_number')
                    ->join('tpshop_goods t2','t1.goods_id = t2.id','left')
                    ->where('t1.id','in',$data['cart_ids']);
        //计算订单总金额
        $order_amount = 0;
        foreach ($cart_data as $v){
            $order_amount += $v['goods_price'] * $v['number'];
        }
        //组装一条订单数据
        $row = [
            'order_sn' => $order_sn,
            'order_amount' => $order_amount,
            'user_id' => $user_id,
            'consignee_name' => $address->consignee,
            'consignee_phone' => $address->phone,
            'consignee_address' => $address->address,
            'shipping_type' => 'yuantong',
            'paytype' => $data['paytype']
        ];
        //开启事务
        \think\Db::startTrans();
        try{
            //向数据表中添加数据
            $order = \app\home\model\Order::create($row,true);
            $order_data = [];
            foreach ($cart_data as $v){
                $row_goods = [
                    'order_id' => $order->id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_price' => $v['goods_price'],
                    'goods_logo' => $v['goods_logo'],
                    'goods_number' => $v['number'],
                    'goods_attr_ids' => $v['goods_attr_ids']
                ];
                $order_data[] = $row_goods;
            }
            //向数据表中添加数据
            $order_goods = new OrderGoods();
            $order_goods->saveAll($order_data);
            //减库存操作
            $new_goods = [];
            //得知道买的这个商品的数量
            foreach ($cart_data as $v){
                if (!isset($new_goods[$v['goods_id']])){
                    $new_goods[$v['goods_id']] = $v['number'];
                }else{
                    $new_goods[$v['goods_id']] += $v['number'];
                }
            }
            //数据表中要减去这个商品数量
            foreach ($new_goods as $k => $v){
                $goods = Goods::find($k)->toArray();
                //计算新的库存
                $store = $goods['goods_number'] - $v;
                if ($store < 0){
                    throw new \Exception('库存不足',10001);
            }
                //将数据修改到数据表
                Goods::update(['goods_number' => $store],['id' => $k]);
            }
            //将购物车对应记录删除
            Cart::destroy($data['cart_ids']);
            //支付程序
            //接下来去支付
            switch ($data['pay_type']){
                case 'alipay':
                    //支付宝支付
                    break;
                case 'wechat':
                    //微信支付
                    break;
                case 'card':
                    //银联
                    break;
                case 'cash':
                    //货到付款
                    break;
            }
            //提交事务
            \think\Db::commit();
        } catch (Exception $e){
            //回滚事务
            \think\Db::rollback();
            $error = $e->getMessage();
            $this->error($error);
        }
    }


    //待付款 0 待发货 1 待收货 2 已发货 3
    public function pay(){
        //判断用户登录 获取user_id
        /* if (!session('?user')) {
            $this->success('请登录','home/login/login');
        } */
        $order_goods_info = cache('order_goods_info');
        if ($order_goods_info === false) {
            //echo 'mysql';
            //获取用户id
            $user_id = session('user.id');
            //查询订单表 支付状态为0
            $data = OrderModel::where(['pay_status'=>'0','user_id'=>$user_id])->field('id,order_sn,order_amount,create_time')->select()->toArray();
            //dump($data);
            $order_id = [];
            foreach ($data as $v) {
                $order_id[]= $v['id'];
            }
            // dump($order_id);die;
            //遍历得到每个待付款的订单主键id,where in 查询order_goods表中对应商品信息
            $order_goods = OrderGoods::where('order_id','in',$order_id)->field('goods_name,goods_price,goods_logo,number,goods_attr_ids')->select()->toArray();
            //dump($order_goods);
            //以下是将两个二维数组合并为一个二维数组供页面遍历使用
            
            foreach ($order_goods as $key => $v) {
                $order_goods_info[] = array_merge($data[$key],$v);
            }
            //dump($order_goods_info);
            //同时获取每个订单商品的属性值 根据goods_attr_ids查询goods_attr表
            $order_attr = [];
            foreach ($order_goods as $v) {
                $order_attr[] = explode(',',$v['goods_attr_ids']);
            }
            //dump($order_attr);
            //得到订单中每个商品属性值 根据属性值id查询属性名称
            foreach ($order_attr as $v) {
                $order_attr_name[] = GoodsAttr::where('id','in',$v)->field('attr_value')->select()->toArray();
            }
            //dump($order_attr_name);
            array_walk($order_goods_info,function(&$v,$k,$order_attr_name){$v['goods_attr_id'] = $order_attr_name[$k];},$order_attr_name);
                // array_walk($data,function(&$v, $k){$v['create_time'] = strtotime($v['create_time']);$v['limit'] = 2;});
            // //组装一个最终页面遍历需要的结果二维数组
            // foreach ($order_goods_info as $key => $v) {
            //     $order_goods_info['goods_attr_ids'][] = $order_attr_name[$key];
                
            // }
            
            //$total_price = [];
            foreach ($order_goods_info as $k => $v) {
                $order_goods_info[$k]['total_price'] = $v['goods_price'] * $v['order_amount'];
            }
            //dump($total_price);
            //$this->fetch('pay',['order_goods_info' => $order_goods_info]);
            cache('order_goods_info',$order_goods_info);
            //dump($order_goods_info);
        }
        //$order_goods_info = json_decode($order_goods_info);
        //dump($order_goods_info);die;
        return view('pay',['order_goods_info' => $order_goods_info]);
    }

	//待付款 0 待发货 1 待收货 2 已发货 3
    public function send(){
        
        $user_id = session('user.id');
        //查询订单表 支付状态为
        $data = OrderModel::where('pay_status','in',[1,3])
                ->where('user_id',5)
                ->field('id,order_sn,pay_status,create_time')->select()->toArray();
        dump($data);

        $order_id = [];
            foreach ($data as $v) {
                $order_id[]= $v['id'];
            }
            // dump($order_id);die;
            //遍历得到每个待付款的订单主键id,where in 查询order_goods表中对应商品信息
            $order_goods = OrderGoods::where('order_id','in',$order_id)->field('goods_name,goods_price,goods_logo,number,goods_attr_ids')->select()->toArray();
            dump($order_goods);

            //以下是将两个二维数组合并为一个二维数组供页面遍历使用
            
            /* foreach ($order_goods as $key => $v) {
                $order_goods_info[] = array_merge($data[$key],$v);
            }
            dump($order_goods_info); */
            array_walk(
                $data,
                function(&$v,$k,$order_goods)
                {
                    $v = array_merge($v,$order_goods[$k]); 
                },
                $order_goods
            );
            // dump($data);
            //计算实付款
            // $total_price = 'total_price';
            array_walk(
                $data,
                function(&$v,$k){
                    $v['total_price'] = $v['goods_price'] * $v['number']; 
                }
                // $total_price
            );
            dump($data);

            //同时获取每个订单商品的属性值 根据goods_attr_ids查询goods_attr表
            // $order_attr = [];
            foreach ($order_goods as $v) {
                $order_attr[] = explode(',',$v['goods_attr_ids']);
            }
            dump($order_attr);
            //dump($order_attr);
            //得到订单中每个商品属性值 根据属性值id查询属性名称
            foreach ($order_attr as $v) {
                $order_attr_name[] = GoodsAttr::where('id','in',$v)->field('attr_value')->select()->toArray();
            }
            dump($order_attr_name);
            array_walk(
                $data,
                function(&$v,$k,$order_attr_name){
                    $v['attr_values'] = $order_attr_name[$k];
                },
                $order_attr_name
            );
            dump($data);
        return view('send',['data' => $data]);
	}
    /* public function test1()
    {

    } */
    public function test(){
        $user_id = session('user.id');
        //查询订单表 支付状态为
        $data = OrderModel::where('pay_status','in',[1,3])
                ->where('user_id',5)
                ->field('id,order_sn,pay_status,create_time')->select()->toArray();
        $order_id = [];
            foreach ($data as $v) {
                $order_id[]= $v['id'];
            }
            // dump($order_id);die;
            //遍历得到每个待付款的订单主键id,where in 查询order_goods表中对应商品信息
            $order_goods = OrderGoods::where('order_id','in',$order_id)->field('goods_name,goods_price,goods_logo,number,goods_attr_ids')->select()->toArray();
            // dump($order_goods);

            //以下是将两个二维数组合并为一个二维数组供页面遍历使用
            
            /* foreach ($order_goods as $key => $v) {
                $order_goods_info[] = array_merge($data[$key],$v);
            }
            dump($order_goods_info); */
            array_walk(
                $data,
                function(&$v,$k,$order_goods)
                {
                    $v = array_merge($v,$order_goods[$k]); 
                },
                $order_goods
            );
            // dump($data);
            //计算实付款
            // $total_price = 'total_price';
            array_walk(
                $data,
                function(&$v,$k){
                    $v['total_price'] = $v['goods_price'] * $v['number']; 
                }
                // $total_price
            );
            // dump($data);

            //同时获取每个订单商品的属性值 根据goods_attr_ids查询goods_attr表
            // $order_attr = [];
            foreach ($order_goods as $v) {
                $order_attr[] = explode(',',$v['goods_attr_ids']);
            }
            // dump($order_attr);
            //dump($order_attr);
            //得到订单中每个商品属性值 根据属性值id查询属性名称
            foreach ($order_attr as $v) {
                $order_attr_name[] = GoodsAttr::where('id','in',$v)->field('attr_value')->select()->toArray();
            }
            // dump($order_attr_name);
            array_walk(
                $data,
                function(&$v,$k,$order_attr_name){
                    $v['attr_values'] = $order_attr_name[$k];
                },
                $order_attr_name
            );
            //dump($data);
            $order = [];
            foreach ($data as $k => $v) {
                $order[$v['order_sn']][] = $v;
            }
            foreach ($order as $key => $value) {
                echo $key;
            }
            dump($order);
        return view('test',['order' => $order]);
    }

    public function receive(){
        return view();
    }
	//此方法页面数据遍历效果不对
    /* public function pay1(){
        //$user_id = session('user.id');
            //查询订单表 支付状态为0
            $data = OrderModel::where(['pay_status'=>'0','user_id'=>4])->field('id,order_sn,order_amount,create_time')->select()->toArray();
            dump($data);

            $order_id = [];
            foreach ($data as $v) {
                $order_id[]= $v['id'];
            }
            $order_goods = OrderGoods::where('order_id','in',$order_id)->select()->toArray();
            dump($order_goods);
            
            $order_attr = [];
            foreach ($order_goods as $v) {
                $order_attr[] = explode(',',$v['goods_attr_ids']);
            }

            foreach ($order_attr as $v) {
                $order_attr_name[] = GoodsAttr::where('id','in',$v)->field('attr_value')->select()->toArray();
            }
            
            
            //合并order_goods 和 order_attr_name
            array_walk(
				$order_goods,
				function(&$v,$k,$order_attr_name){
					$v['goods_attr_id']=$order_attr_name[$k];
				},
				$order_attr_name
			);
			dump($order_goods);
			dump($order_attr_name);
			foreach ($order_goods as $k => $v) {
                $order_goods[$k]['total_price'] = $v['goods_price'] * $v['number'];
            }
            return view('test',['data' => $data,'order_goods' => $order_goods]);
    } */
    public function sp(){
        //实例化
        $sp = new Sphinxclient();
        //连接
        $sp->SetServer('127.0.0.1',9312);
        $sp->SetArrayResult(true);
        $sp->SetMatchMode(SPH_MATCH_ANY);
        //var_dump($sp);
        //关键字
        $keywords = '曹操';
        $indexwords = 'tpshop_order';
        $rs = $sp->query($keywords,$indexwords);
        var_dump($rs);
        //$order_goods_info = $this->pay();
        //return view('pay',['rs' => $rs,'order_goods_info' => $order_goods_info]);
    }
}
