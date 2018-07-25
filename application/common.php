<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/* if (!function_exists('linkredis')) {
    //连接redis
    function linkredis() {
        $redis = new \Redis();
        $redis->connect('127.0.0.1','6379');
        return $redis;
    }
} */
if (!function_exists('encrypt_password')) {
    /**
     * 密码加密函数
     */
    function encrypt_password($password)
    {
        // 明文 123456
        $salt = 'bo5oi2by1'; //加盐
        return md5(md5($password) . $salt);
    }
}

if (!function_exists('remove_xss')) {
    //使用htmlpurifier防范xss攻击
    function remove_xss($string){
        //相对index.php入口文件，引入HTMLPurifier.auto.php核心文件
        require_once './plugins/htmlpurifier/HTMLPurifier.auto.php';
        // 生成配置对象
        $cfg = HTMLPurifier_Config::createDefault();
        // 以下就是配置：
        $cfg -> set('Core.Encoding', 'UTF-8');
        // 设置允许使用的HTML标签
        $cfg -> set('HTML.Allowed','div,b,strong,i,em,a[href|title],ul,ol,li,br,p[style],span[style],img[width|height|alt|src]');
        // 设置允许出现的CSS样式属性
        $cfg -> set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align');
        // 设置a标签上是否允许使用target="_blank"
        $cfg -> set('HTML.TargetBlank', TRUE);
        // 使用配置生成过滤用的对象
        $obj = new HTMLPurifier($cfg);
        // 过滤字符串
        return $obj -> purify($string);
    }
}

if (!function_exists('getTree')) {
    //递归方法实现无限极分类
    function getTree($list,$pid=0,$level=0) {
        static $tree = array();
        foreach($list as $row) {
            if($row['pid']==$pid) {
                $row['level'] = $level;
                $tree[] = $row;
                getTree($list, $row['id'], $level + 1);
            }
        }
        return $tree;
    }
}

if(!function_exists('curl_request')){
    //封装一个函数 使用curl函数库发送请求
    function curl_request($url, $post = false, $param = [], $https = false){
//        ①使用curl_init初始化请求会话
        $ch = curl_init($url);
//        ②使用curl_setopt设置请求一些选项
        if($post){
            //post请求 需要设置请求方式和请求参数
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }
        //https请求，需要禁止从服务端验证本地证书
        if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
//        ③使用curl_exec执行，发送请求
        //设置，让curl_exec直接返回结果数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
//        ④使用curl_close关闭请求会话
        curl_close($ch);
        return $res;
    }
}

if(!function_exists('sendmsg')){
    //封装一个函数 发短信
    function sendmsg($phone, $msg)
    {
        //请求地址
        $gateway = config('msg.gateway');
        $appkey = config('msg.appkey');
        //发送请求 get请求
        $url = $gateway . "?appkey=$appkey&mobile=$phone&content=$msg";
        $res = curl_request($url, false, [], true);
        if(!$res){
            return '请求失败';
        }
        //返回值是json格式字符串
        $arr = json_decode($res, true);
        if($arr['code'] == 10000){
            //发送成功
            return true;
        }else{
            return $arr['msg'];
        }
    }
}

if(!function_exists('sendmail')){
    //使用PHPMailer插件发送邮件
    function sendmail($email, $subject, $body)
    {
//        use PHPMailer\PHPMailer\PHPMailer;
//        use PHPMailer\PHPMailer\Exception;
//        require 'vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer();       //传参数true，表示使用异常机制处理错误
            //Server settings
//            $mail->SMTPDebug = 2;                                 // 如果设置不为0，会输出一些调试信息
        $mail->isSMTP();                                      // 设置使用SMTP服务
        $mail->Host = config('email.host');                       // 设置发件服务器的地址
        $mail->SMTPAuth = true;                               // 设置进行SMTP认证
        $mail->Username = config('email.username');                 // 发件账号
        $mail->Password = config('email.password');                    // 发件授权码
        $mail->SMTPSecure = 'tls';                            // 安全加密方式 tls ssl
        $mail->Port = 25;                                    // 发送邮件的端口
        $mail->CharSet = 'UTF-8';                           //设置邮件内容的编码

        //Recipients
        $mail->setFrom(config('email.username'));       //设置发件人(及昵称)
        $mail->addAddress($email);     // 添加收件人(及昵称)
//            $mail->addAddress('ellen@example.com');               // Name is optional
//            $mail->addReplyTo('info@example.com', 'Information'); //添加回复人
//            $mail->addCC('cc@example.com');//添加抄送人
//            $mail->addBCC('bcc@example.com');//添加密送人

            //Attachments
//            $mail->addAttachment('/var/tmp/file.tar.gz');         // 添加附件
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        //Content
        $mail->isHTML(true);                                  // 设置邮件内容是html格式
        $mail->Subject = $subject;                          // 设置邮件主题
        $mail->Body    = $body;                               // 设置邮件内容
//            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients'; //邮件客户端不支持html时，显示的普通文本

        $res = $mail->send();
        if($res){
            return true;
        }else{
            return $mail->ErrorInfo;
        }
    }
}

if(!function_exists('encrypt_phone')){
    //手机号加密函数  15312345678   =》  153****5678
    function encrypt_phone($phone){
        return substr($phone, 0, 3) . '****' . substr($phone, 7);
    }
}