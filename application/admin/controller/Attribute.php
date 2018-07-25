<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Attribute extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询所有的属性信息
//        $list = \app\admin\model\Attribute::select();
        // 连表查询，获取商品类型名称，SELECT t1.*, t2.type_name FROM `tpshop_attribute` t1 left join tpshop_type t2 on t1.type_id = t2.id ;
        $list = \app\admin\model\Attribute::alias('t1')
            ->field('t1.*, t2.type_name')
            ->join('tpshop_type t2', 't1.type_id = t2.id', 'left')
            ->select();
        return view('index', ['list' => $list]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //查询所有的商品类型，用于下拉列表展示
        $type = \app\admin\model\Type::select();
        return view('create', ['type' => $type]);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收数据
        $data = $request->param();
        //$this->error('123');
        //参数检测 表单验证  略
        //添加数据 tpshop_attribute表
        \app\admin\model\Attribute::create($data, true);
        //页面跳转
        $this->success('操作成功', 'index');
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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

    //根据商品类型id获取商品属性
    public function getattr($id)
    {
        //查询属性表 获取指定类型下的属性信息
        $data = \app\admin\model\Attribute::where('type_id', $id)->select();
        //dump($data);die;
        //为了页面处理数据方便，将每条数据的可选值attr_values 分割成数组
        foreach($data as &$v){
//            $v = $v->getData(); // 这样是获取原始值，不使用获取器
            //分割成数组 覆盖原来的字符串
            $v['attr_values'] = explode(',', $v['attr_values']);
        }
        unset($v);//前面有引用，可以在foreach结束后，unset $v;
        //返回数据
        $res = [
            'code' => 10000,
            'msg' => 'success',
            'data' => $data
        ];
        return json($res);
    }
}
