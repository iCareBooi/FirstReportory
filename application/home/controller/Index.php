<?php
namespace app\home\controller;

use think\Controller;
class Index extends Base
{
    public function index()
    {
        /* //判断缓存文件是否存在并且有效
        if (file_exists('./static.html') && time()-filemtime('./static.html') < 60){
            //跳转到static.html
            $this->redirect('http://www.tpshop.com/static.html');
        } */
        //开启ob缓存
        //ob_start();
        //模板渲染
        //return view();
        /* $html = cache('html');
        if ($html === false) {
            echo "mysql";
            $html = $this->fetch();
            cache('html',$html);
        }else {
            echo "cache";
            echo $html;
        } */
        return view();
        //获取缓存内容
        //$html = ob_get_contents();
        //将内容写入静态文件
        //file_put_contents('./static.html',$html);
        //跳转到static.html
        //$this->redirect('http://www.tpshop.com/static.html');
    }

    public function home(){
        return view();
    }
}
