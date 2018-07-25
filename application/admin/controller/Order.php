<?php

namespace app\admin\controller;

use app\admin\model\GoodsAttr;
use app\admin\model\OrderGoods;
use think\Controller;
use think\Request;
use app\admin\model\Order as OrderModel;
class Order extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function detail($id)
    {
        //查询订单表数据
        $order = OrderModel::find($id);
        //查询订单商品表的数据
        $order_goods = OrderGoods::where('order_id',$id)->select();
        //查询每个订单商品的属性值属性名称数据(一个表中的某个字段有多个值并且对应另外一个表的多条数据,一般使用连表查询)
       /* foreach ($order as $v){
            $goodsattr = GoodsAttr::alias('t1')
                        ->field('t1.*,t2.attr_name')
        }*/
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
