<?php

namespace app\home\controller;

use think\Controller;
use think\Request;

class Base extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        //$mem = new \Memcached();
        //$mem->addServer('127.0.0.1','11211');
        //$category = cache('category');
        //$category = $mem->get('category');
        //$redis = linkredis();
        // $redis->connect('127.0.0.1','6379');
        $category = cache('category');
        if ($category === false) {
            echo 'mysql';
            //查询商品分类信息
            $category = \app\home\model\Category::select();
            //$mem->set('category',$category);
            cache('category',$category);
        }
        $this->assign('category', $category);
    }
}
