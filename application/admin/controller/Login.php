<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;

class Login extends Controller
{
    /**
     * 登录页面
     *
     * @return \think\Response
     */
    public function login()
    {
        //一个方法处理两个业务逻辑：表单展示、表单提交
        if(request()->isGet()){
            //get请求，页面表单展示
            //临时关闭模板布局
            $this->view->engine->layout(false);
            return view();
        }
        //post请求  表单提交
        //接收参数
        $data = request()->param();
//        dump($data);die;
        //验证码的校验
        if(!captcha_check($data['code'])){
            $this->error('验证码错误');
        }
        //查询用户表
        $where = [
            'username' => $data['username'],
            'password' => encrypt_password($data['password'])
        ];
        $info = \app\admin\model\Manager::where($where)->find();
        //判断
        if($info){
            //登录成功 设置登录标识到session
            session('user_info', $info->toArray());
            //页面跳转到后台首页
//            $this->success('登录成功', 'admin/index/index');
            $this->redirect('admin/index/index');
        }else{
            //登录失败
            $this->error('用户名或者密码错误');
        }
    }

    //登出
    public function logout()
    {
        //删除所有的session
        session(null);
        //跳转到登录页面
        $this->redirect('admin/login/login');
    }
}
