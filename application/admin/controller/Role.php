<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Role extends Base
{
    /**
     * 角色列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询所有的角色信息
        $list = \app\admin\model\Role::select();
        return view('index', ['list' => $list]);
    }

    //给角色分派权限 页面展示
    public function setauth($id)
    {
        //查询角色信息
        $role = \app\admin\model\Role::find($id);
        //查询所有的权限 （一级权限、二级权限）
        $top_all = \app\admin\model\Auth::where('pid', 0)->select();
        $second_all = \app\admin\model\Auth::where('pid', '>', 0)->select();
        return view('setauth', ['role' => $role, 'top_all' => $top_all, 'second_all' => $second_all]);
    }

    //为角色分配权限 表单提交
    public function saveauth()
    {
        //接收数据
        $role_id = request()->param('role_id');
        $auth_id = request()->param('id/a');  //变量名后的 /a 表示变量修饰符，接收的是数组
        //将数据修改到角色表
        $role_auth_ids = implode(',', $auth_id);
        \app\admin\model\Role::update(['role_auth_ids' => $role_auth_ids], ['id' => $role_id]);
        //页面跳转 列表页
        $this->success('操作成功', 'index');
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
