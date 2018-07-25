<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Auth extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询权限表数据
        $list = \app\admin\model\Auth::select();
//        dump($list);
        //使用递归函数，重新排序
        $list = getTree($list);
//        dump($list);die;
        return view('index', ['list' => $list]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //查询所有的一级分类，用于下拉列表展示
        $top_auth = \app\admin\model\Auth::where('pid', 0)->select();
        return view('create', ['top_auth'=>$top_auth]);
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
        //将控制器名称和方法名称统一转化为小写 或者大写 或者 首字母大写
        $data['auth_c'] = strtolower($data['auth_c']);
        $data['auth_a'] = strtolower($data['auth_a']);
//        dump($data);die;
        //参数检测 略
        // 将数据添加到数据表  第二个参数true 表示过滤非数据表字段
        \app\admin\model\Auth::create($data, true);
        //页面跳转 列表页
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
}
