<?php

namespace app\home\controller;

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
        //临时关闭模板布局
        $this->view->engine->layout(false);
        return view();
    }

    //登录表单提交
    public function dologin()
    {
        //接收数据
        $data = request()->param();
        //参数检测 略
        //根据用户名密码查询用户表
        $password = encrypt_password($data['password']);
        //①先查询用户，再比对密码
//        $user = \app\home\model\User::where('phone', $data['username'])->whereOr('email', $data['username'])->find();
////        //用户存在，密码正确，已激活
//        if($user && $user['password'] == $password && $user['is_check'] == 1){
//            //登录成功，设置登录标识
//            session('user', $user->toArray());
//            //页面跳转
//            $this->success('登录成功', 'home/index/index');
//        }else{
//            //用户名或者密码错误
//            $this->error('用户名或者密码错误');
//        }

        //②直接根据用户名、密码等条件一起查询用户表
        $user = \app\home\model\User::where(function($query)use($data){
            $query->where('phone', $data['username'])->whereOr('email', $data['username']);
        })->where('password', $password)->where('is_check', 1)->find();
        if($user){
            //设置登录标识
            session('user', $user->toArray());
            //迁移cookie购物车数据
            \app\home\model\Cart::cookieTodb();
            //获取session中的跳转地址
            $back_url = session('back_url') ? session('back_url') : 'home/index/index';
            $this->success('登录成功', $back_url);
        }else{
            //用户名或者密码错误
            $this->error('用户名或者密码错误');
        }
    }

    //退出
    public function logout()
    {
        //清空session
        session(null);
        //跳转到登录页面
        $this->redirect('login');
    }

    /**
     * 手机号注册页面
     *
     * @return \think\Response
     */
    public function register()
    {
        //临时关闭模板布局
        $this->view->engine->layout(false);
        return view();
    }

    //手机号注册 提交表单
    public function phone()
    {
        //接收参数
        $data = request()->param();
        //参数检测 表单验证
        //验证规则
        $rule = [
            'phone' => 'require|regex:/^1[3-9]\d{9}$/|unique:user',
            'code' => 'require|regex:/^\d{4}$/',
            'password' => 'require|length:6,16|confirm:repassword',
        ];
        //提示信息
        $msg = [
            'phone.require' => '手机号不能为空',
            'phone.regex' => '手机号格式不正确',
            'phone.unique' => '手机号已被注册',
            'code.require' => '验证码不能为空',
            'code.regex' => '验证码格式不正确',
            'password.require' => '密码不能为空',
            'password.length' => '密码长度必须为6到16个字符',
            'password.confirm' => '两次密码输入必须一致'
        ];
        //实例化验证类
        $validate = new \think\Validate($rule, $msg);
        //执行验证
        if(!$validate->check($data)){
            //验证不通过，报错
            $error = $validate->getError();
            $this->error($error);
        }
        //检测验证码
//        $code = session('register_code_' . $data['phone']);
        $code = cache('register_code_' . $data['phone']);
        if($code != $data['code']){
            $this->error('验证码错误');
        }
        //检测验证码有效期
//        $sendtime = session('register_sendtime_' . $data['phone']);
        $sendtime = cache('register_sendtime_' . $data['phone']);
        if(time() - $sendtime > 300){
            //超过5分钟，过期了
            $this->error('验证码过期');
        }
        //让短信验证码失效，从session删除掉
//        session('register_code_' . $data['phone'], null);
        cache('register_code_' . $data['phone'], null);
        //接下来才是正常的注册， 添加数据
        $data['password'] = encrypt_password($data['password']);
        //用户名设置为手机号，是否激活状态，设置为已激活
        $data['username'] = $data['phone'];
        $data['is_check'] = 1;
        \app\home\model\User::create($data, true);
        //跳转页面
        $this->success('注册成功', 'login');
    }

    /**
     * 邮箱注册页面
     *
     * @return \think\Response
     */
    public function registeremail()
    {
        //临时关闭模板布局
        $this->view->engine->layout(false);
        return view('register-email');
    }

    //邮箱注册 表单提交
    public function email()
    {
        //接收数据
        $data = request()->param();
        //参数检测
        //验证规则
        $rule = [
            'email' => 'require|email|unique:user',
            'password' => 'require|length:6,16|confirm:repassword',
        ];
        //提示信息
        $msg = [
            'email.require' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已被注册',
            'password.require' => '密码不能为空',
            'password.length' => '密码长度必须为6到16个字符',
            'password.confirm' => '两次密码输入必须一致'
        ];
        //实例化验证类
        $validate = new \think\Validate($rule, $msg);
        //执行验证
        if(!$validate->check($data)){
            //验证不通过，报错
            $error = $validate->getError();
            $this->error($error);
        }
        //添加数据到用户表
        //密码加密
        $data['password'] = encrypt_password($data['password']);
        //特殊字段
        $data['username'] = $data['email'];
        //验证码用于后续激活时做校验
        $data['email_code'] = mt_rand(1000, 9999);
        //添加用户记录，属于未激活状态
        $user = \app\home\model\User::create($data, true);
        //发送激活邮件
        $subject = "TP5商城注册激活邮件";
//        $url = url("home/login/jihuo", ['id' => $user['id'], 'code' => $data['email_code']], '.html', true);
        $url = "http://www.tpshop.com/home/login/jihuo/id/" . $user['id'] . "/code/" . $data['email_code'];
        $body = "欢迎注册，请点击以下链接进行激活：<br><a href='$url'>点我激活</a><br>如果点击无法跳转，请复制链接到浏览器地址栏直接打开";
        $res = sendmail($data['email'], $subject, $body);
        dump($res);
        //页面跳转  正常情况下，需要显示html页面进行提示
        if($res === true){
            //邮件发送成功
            $this->success('邮件发送成功，请进行激活', 'login');
        }else{
            //邮件发送失败
            $this->error('邮件发送失败,请联系客服');
        }
    }

    //邮箱账号激活
    public function jihuo()
    {
        //接收参数
        $data = request()->param();
        //参数检测 略
        //激活账号
        //①直接修改 用户记录的  is_check字段
//        \app\home\model\User::update(['is_check' => 1], ['id' => $data['id'], 'email_code' => $data['code']]);
        //跳转页面
//        $this->success('账号激活成功', 'login');

        //②先查询再修改
        $user = \app\home\model\User::where(['id' => $data['id'], 'email_code' => $data['code']])->find();
        if($user){
            //修改is_check字段
            $user->is_check = 1;
            $user->save();
            $this->success('账号激活成功', 'login');
        }else{
            $this->error('用户不存在');
        }
    }

    //检测手机号是否已被注册
    public function checkphone()
    {
        //接收参数
        $phone = request()->param('phone');
        // sleep(2);
        //参数检测 直接用正则验证
        if(!preg_match('/^1[3-9]\d{9}$/', $phone)){
            //手机号格式不正确
            // $this->error("手机号格式不正确");
            // echo 0;die;
            $res = [
                'code' => 10001,
                'msg' => '手机号格式不正确'
            ];
            echo json_encode($res);die;
        }
        //数据处理 查询tpshop_user表
        $user = \app\home\model\User::where('phone', $phone)->find();
        //返回数据（json格式字符串） 一般包含错误码、错误提示、其他关键数据
        if($user){
            //用户名已被注册
            // echo 1;die;
            $res = [
                'code' => 10000,
                'msg' => 'success',
                'status' => 1
            ];
            echo json_encode($res);die;
        }else{
            //用户名可用
            // echo 2;die;
            $res = [
                'code' => 10000,
                'msg' => 'success',
                'status' => 0
            ];
            echo json_encode($res);die;
        }
    }

    //注册时发送短信验证码
    public function sendcode()
    {
        //接收手机号参数
        $phone = request()->param('phone');
        //验证手机号格式
        if(!preg_match('/^1[3-9]\d{9}$/', $phone)){
            //手机号格式不正确
            return json(['code' => 10001, 'msg' => '手机号格式不正确']);
        }
        //发送之前，进行频率检测
//        $last_time = session('register_sendtime_' . $phone) ?: 0;
        $last_time = cache('register_sendtime_' . $phone) ?: 0;
        if (time() - $last_time < 60){
            return json(['code' => 10003, 'msg' => '发送太频繁']);
        }
        //发送之前，进行ip检测  当天日志
        $ip = $_SERVER['REMOTE_ADDR'];
        $day = date('Ymd');
//        $num = session('register_ip_num_' . $ip . $day) ?: 0;
        $num = cache('register_ip_num_' . $ip . $day) ?: 0;
        if($num > 10){
            return json(['code' => 10004, 'msg' => '发送次数达到上限']);
        }
        // 短信模板  【成都创信信息】验证码为：****,欢迎注册平台！
        $code = mt_rand(1000, 9999);
        $msg = "【成都创信信息】验证码为：{$code},欢迎注册平台！";
        //发送短信
//        $res = sendmsg($phone, $msg);
        $res = true;//开发测试时，并不需要真正的发短信，可以假设发送成功
        if($res === true){
            //发送成功
            //记录发送的验证码，用于后续校验  必须保留验证码和手机号的对应关系
//            session('register_code_' . $phone, $code);
            cache('register_code_' . $phone, $code);
            //记录发送时间
//            session('register_sendtime_' . $phone, time());
            cache('register_sendtime_' . $phone, time());
            //根据ip 限制发送频率 当天的日期
//            session('register_ip_num_' . $ip . $day, $num + 1);
            cache('register_ip_num_' . $ip . $day, $num + 1);
//            return json(['code' => 10000, 'msg' => '发送成功']);//项目上线时
            return json(['code' => 10000, 'msg' => '发送成功', 'data'=>$code]);////开发测试时
        }else{
//            return json(['code' => 10002, 'msg' => '短信发送失败']);//项目上线时
            return json(['code' => 10002, 'msg' => $res]);//开发测试时
        }
    }

    //qq登录的回调地址
    public function qqcallback()
    {
        //参考 qq/example/oauth/callback.php
        require_once("./plugins/qq/API/qqConnectAPI.php");
        $qc = new \QC();
        $access_token = $qc->qq_callback();
        $openid = $qc->get_openid();
        //获取用户信息
        //重新实例化QC类
        $qc = new \QC($access_token, $openid);
        $info = $qc->get_user_info();
        //自动注册和登录
        //先根据openid查询用户表，如果不存在则创建用户
        $user = \app\home\model\User::where('openid', $openid)->find();
        if($user){
            //用户已存在  更新用户信息(昵称)
            $user->username = $info['nickname'];
            $user->save();
        }else{
            //用户不存在
            \app\home\model\User::create(['openid' => $openid, 'username' => $info['nickname']]);
        }
        //设置登录标识
        $user = \app\home\model\User::where('openid', $openid)->find();
        session('user', $user->toArray());
        //迁移cookie购物车数据
        \app\home\model\Cart::cookieTodb();
        //页面跳转  首页
        //获取session中的跳转地址
        $back_url = session('back_url') ? session('back_url') : 'home/index/index';
        $this->redirect($back_url);
    }

    //模拟接口编程， 先编写一个接口, 假设这是另外一个项目中的
    public function test_api()
    {
        //接收数据
        $data = request()->param();
        //处理数据 略
        //返回数据
        $res = [
            'code' => 10000,
            'msg' => 'success',
            'data' => $data,
        ];
        return json($res);
    }

    //模拟接口调用
    public function test_request()
    {
        //请求地址 发送get请求
//        $url = "http://www.tpshop.com/home/login/test_api/id/10/page/100";
        //调用封装的curl_request函数发送请求
//        $res = curl_request($url);
        //发送post请求
        $url = "http://www.tpshop.com/home/login/test_api";
        $param = [
            'id' => 10,
            'page' => 100
        ];
        $res = curl_request($url, true, $param);
        dump($res);die;
    }

    //测试短信发送
    public function test_msg()
    {
        $phone = '15313139033';
        $msg = '【成都创信信息】验证码为：5873,欢迎注册平台！';
        $res = sendmsg($phone, $msg);
        dump($res);die;
    }
}
