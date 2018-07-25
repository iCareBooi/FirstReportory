<?php
//声明命名空间
namespace app\index\controller;
//引入父类（基类）控制器（可选）
//use think\Controller;
//定义当前控制器类
class Index extends \think\Controller
{
    //定义一些方法
    public function index()
    {
        return view();
    }

    //这里假设是商品详情页
    public function read($id)
    {
        $info = \app\index\service\Goods::getGoodsById($id);
        dump($info);die;
        return view('read', ['info' => $info]);
    }

    //假设这里是商品添加的表单提交
    public function save(){
        //接收数据
        $data = request()->param();
        //处理数据
        \app\index\logic\Goods::save($data);
        //页面展示 页面跳转
        $this->success('操作成功', 'index');
    }
}
