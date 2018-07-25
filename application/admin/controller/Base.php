<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Base extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        //进行登录判断
        if(!session('?user_info')){
            //没有登录, 跳转到登录页面
            $this->redirect('admin/login/login');
        }
        //调用checkauth()检测权限
        $this->checkauth();
        //调用getauth方法
        $this->getauth();
    }

    //获取当前登录管理员的菜单权限
    public function getauth()
    {
        //获取当前登录管理员的角色id   读取session
        $role_id = session('user_info.role_id');
        //判断角色id   ==1 表示超级管理员
        if($role_id == 1){
            //超级管理员 直接查询权限表（一级菜单权限、二级菜单权限）
//            $top = \app\admin\model\Auth::where('pid', 0)->where('is_nav', 1)->select();
            $top = \app\admin\model\Auth::where(['pid'=>0, 'is_nav'=>1])->select();
//            $second = \app\admin\model\Auth::where('pid', '>', 0)->where('is_nav', 1)->select();
            $second = \app\admin\model\Auth::where(['pid'=>['>', 0], 'is_nav'=>1])->select();
        }else{
            //其他管理员 先查询角色表，获取拥有的权限ids
            $role_info = \app\admin\model\Role::find($role_id);
            $role_auth_ids = $role_info['role_auth_ids'];
            //再查询权限表（一级菜单权限、二级菜单权限）  where id in
            $top = \app\admin\model\Auth::where([
                'pid'=>0,
                'is_nav'=>1,
                'id' => ['in', $role_auth_ids]
            ])->select();
            $second = \app\admin\model\Auth::where([
                'pid'=>['>', 0],
                'is_nav'=>1,
                'id' => ['in', $role_auth_ids]
            ])->select();
        }
        //模板变量赋值
        $this->assign('top', $top);
        $this->assign('second', $second);
//        dump($top);dump($second);die;
    }

    //检测权限
    public function checkauth()
    {
        //获取到当前登录管理员 角色id  查session
        $role_id = session('user_info.role_id');
        //判断 超级管理员不需要检测 角色id == 1
        if(1 == $role_id){
            return;
        }
        //其他管理员 检测权限
        //特殊页面 比如首页，不需要检测
        //获取当前访问的控制器名称和方法名称
        $controller = request()->controller(); // Goods
        $action = request()->action(); // index
        //首页不需要检测
        if($controller == 'Index' && $action == 'index'){
            return ;
        }
        //需要检测权限的情况
        //当前管理员所拥有的权限
        $role_info = \app\admin\model\Role::find($role_id);
        //将拥有的权限字符串，分割为数组
        $role_auth_ids = explode(',', $role_info['role_auth_ids']);
        //当前访问的页面 对应的权限
        $auth = \app\admin\model\Auth::where(['auth_c' => strtolower($controller), 'auth_a' => $action])->find();
        //判断 $auth['id'] 是否在  $role_info['role_auth_ids'] 范围中
        if(!in_array($auth['id'], $role_auth_ids)){
            //没有权限
            $this->error('没有权限', 'admin/index/index');
        }
        return;
    }
}
