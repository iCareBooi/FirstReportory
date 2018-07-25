<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
//use app\admin\model\Goods as GoodsModel;

class Goods extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询所有的商品数据 分页查询--返回的是bootstrap类的对象
        $list = \app\admin\model\Goods::paginate(2);
//        $list = \think\Db::table('tpshop_goods')->order('id desc')->select();
//        $list = \think\Db::table('tpshop_goods')->order('id desc')->paginate(2);
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
        //查询一级分类数据
        $cate = \app\admin\model\Category::where('pid', 0)->select();
        //查询所有的商品类型，用于下拉列表展示
        $type = \app\admin\model\Type::select();
        return view('create', ['cate' => $cate, 'type' => $type]);
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
//        dump($data);die;
        //对富文本编辑器的字段，进行单独处理，使用封装的remove_xss函数进行过滤
//        $data['goods_introduce'] = remove_xss($_POST['goods_introduce']);
        $data['goods_introduce'] = $request->param('goods_introduce', '', 'remove_xss');
        //参数检测（表单验证）
        //定义验证规则数组$rule
        $rule = [
            'goods_name' => 'require',
            'goods_price' => 'require|float|gt:0',
            'goods_number' => 'require|integer|gt:0',
            'cate_id' => 'require|integer|gt:0',
            // 'goods_number' => 'regex:/^[1-9]\d*$/', //10
        ];
        //定义提示信息数组$msg
        $msg = [
            'goods_name.require' => '商品名称不能为空',
            'goods_price.require' => '商品价格不能为空',
            'goods_price.float' => '商品价格必须是数字',
            'goods_price.gt' => '商品价格必须大于0',
            'goods_number.require' => '商品数量不能为空',
            'goods_number.integer' => '商品数量必须是整数',
            'goods_number.gt' => '商品数量必须大于0',
            'cate_id.require' => '商品分类必须选择',
            'cate_id.integer' => '商品分类必须是整数',
            'cate_id.gt' => '商品分类必须大于0',
        ];
        //实例化验证类
        $validate = new \think\Validate($rule, $msg);
        //调用check方法进行验证
        if(!$validate->check($data)){
            //验证失败，check方法返回值是false
            $error = $validate->getError();
            $this->error($error);
        }
//        dump($data);die;
        //商品logo图片上传
        $data['goods_logo'] = $this->upload_logo();
        //将数据添加到数据表  第二个参数true表示过滤非数据表字段
        $goods = \app\admin\model\Goods::create($data, true);
        //相册图片上传
        $this->upload_pics($goods->id);
        //商品属性值 添加到tpshop_goods_attr表
        $goodsattr_data = [];
        foreach($data['attr_value'] as $k => $v){
            //$k 是属性id ； $v是多个属性值的数组
            foreach($v as $value){
                //$value 是一个属性值
                //组装一条数据
                $row = [
                    'goods_id' => $goods->id,
                    'attr_id' => $k,
                    'attr_value' => $value
                ];
                //放到结果数据，最后批量添加
                $goodsattr_data[] = $row;
            }
        }
        //批量添加数据到tpshop_goods_attr表
        $goodsattr = new \app\admin\model\GoodsAttr();
        $goodsattr->saveAll($goodsattr_data);
//        dump($goods);die;
        //页面跳转
        $this->success('操作成功', 'index');
    }

    //封装一个方法，用于上传商品logo图片
    private function upload_logo()
    {
        //获取上传的文件（文件对象）
        $file = request()->file('logo');
        //判断 是否有上传的文件
        if(empty($file)){
            $this->error('必须上传logo图片');
        }
        //将文件从临时目录移动到指定的文件上传目录（public/uploads目录）
        $info = $file->validate(['size' => 5*1024*1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info){
            //拼接图片的访问路径
            $goods_logo = DS . 'uploads' . DS . $info->getSaveName();
            //生成缩略图
            //调用open方法打开原始图片
            $image = \think\Image::open('.' . $goods_logo);
            //调用thumb方法生成缩略图，调用save方法保存缩略图
            $image->thumb(210, 240)->save('.' . $goods_logo);
            return $goods_logo;
        }else{
            //上传出错
            $error = $file->getError();
            $this->error($error);
        }
    }

    //封装一个方法，用于商品相册图片的上传
    private function upload_pics($goods_id)
    {
        //接收上传的图片（得到一个文件数组）
        $files = request()->file('goods_pics');
//        dump($files);die;
        //遍历文件数组，对每一个文件对象 进行处理
        $goods_pics = [];
        foreach($files as $file){
            //对每个文件，移动到指定的文件上传目录
            $info = $file->validate(['size' => 5*1024*1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                //上传成功，则进行处理，否则，继续处理下一个图片
                //拼接图片访问地址
                $pics = DS . "uploads" . DS . $info->getSaveName();
                // 20180610/dsafdsafdslsdfdsfds.png
                //分别获取子目录名称 和文件名称  $temp[0] 子目录  $temp[1] 文件名
                $temp = explode(DS, $info->getSaveName());
                //拼接两张缩略图的路径
                $pics_sma = DS .'uploads' . DS . $temp[0] . DS . 'thumb_400_' . $temp[1];
                $pics_big = DS .'uploads' . DS . $temp[0] . DS . 'thumb_800_' . $temp[1];
                //生成两张不同尺寸的缩略图 400*400  800*800
                $img = \think\Image::open('.' . $pics);
                $img->thumb(800, 800)->save('.' . $pics_big);
                $img->thumb(400, 400)->save('.' . $pics_sma);
                //组装一条数据
                $row = [
                    'goods_id' => $goods_id,
                    'pics_big' => $pics_big,
                    'pics_sma' => $pics_sma
                ];
                //将数据添加到结果数组，最后进行批量添加
                $goods_pics[] = $row;
                //将数据添加到相册表  一条一条添加，不推荐
//                \app\admin\model\Goodspics::create($row);
            }
        }
        //批量添加数据到相册表
        $goodspics_model = new \app\admin\model\Goodspics();
        $goodspics_model->saveAll($goods_pics);
    }
    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询一条数据
        $info = \app\admin\model\Goods::find($id);
//        dump($info);//包含数据的模型对象，相当于一个一维数组 ['id' => 33, 'goods_name' => 'iphone x']
//        dump($info->goods_name);die;
//        dump($info->toArray());
//        dump($info['goods_name']);die;
        return view();
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //查询原始的数据
        $info = \app\admin\model\Goods::find($id);
        //查询所有的一级分类 用于页面下拉列表展示
        $cate = \app\admin\model\Category::where('pid', 0)->select();
        //查询当前商品所属的三级分类信息（包含所属二级分类的id）
        $cate_three_info = \app\admin\model\Category::find($info['cate_id']);
        //查询当前商品所属的二级分类   id  $cate_three_info['pid']  $cate_two_info['pid']
        $cate_two_info = \app\admin\model\Category::find($cate_three_info['pid']);
        //查询所属的一级分类下 所有的二级分类
        $cate_two_all = \app\admin\model\Category::where('pid', $cate_two_info['pid'])->select();
        //查询所属的二级分类下 所有的三级分类
        $cate_three_all = \app\admin\model\Category::where('pid', $cate_two_info['id'])->select();
        //查询商品相册图片
        $goodspics = \app\admin\model\Goodspics::where('goods_id', $id)->select();
        //查询所有的商品类型数据，用于下拉列表展示
        $type = \app\admin\model\Type::select();
        return view('edit', [
            'info' => $info,
            'cate' => $cate,
            'cate_two_info' => $cate_two_info,
            'cate_two_all' => $cate_two_all,
            'cate_three_all' => $cate_three_all,
            'goodspics' => $goodspics,
            'type' => $type
        ]);
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
        //接收数据
        $data = $request->param();
//        dump($data);die;
        //对富文本编辑器的字段，进行单独处理，使用封装的remove_xss函数进行过滤
//        $data['goods_introduce'] = remove_xss($_POST['goods_introduce']);
        $data['goods_introduce'] = $request->param('goods_introduce', '', 'remove_xss');
//        dump($data);die;
        //数据检测（表单验证）
        //定义验证规则数组
        $rule = [
            'goods_name' => 'require',
            'goods_price' => 'require|float|gt:0',
            'goods_number' => 'require|integer|gt:0',
            'cate_id' =>  'require|integer|gt:0',
        ];
        //定义提示信息数组
        $msg = [
            'goods_name.require' => '商品名称不能为空',
            'goods_price.require' => '商品价格不能为空',
            'goods_price.float' => '商品价格必须是数字',
            'goods_price.gt' => '商品价格必须大于0',
            'goods_number.require' => '商品数量不能为空',
            'goods_number.integer' => '商品数量必须是数字',
            'goods_number.gt' => '商品数量必须大于0',
            'cate_id.require' => '商品分类必须选择'
        ];
        //实例化Validate验证类
        $validate = new \think\Validate($rule, $msg);
        //调用check方法执行验证
        if(!$validate->check($data)){
            //验证失败
            $error = $validate->getError();
            $this->error($error);
        }
        //修改商品logo图片
        //获取上传的文件
        $file = request()->file('logo');
        if($file){
            $data['goods_logo'] = $this->upload_logo();
        }
        //将数据修改到数据表
        \app\admin\model\Goods::update($data, [], true);
        //继续上传商品相册图片
        $this->upload_pics($id);
        //处理商品属性值
        $goodspics_data = [];
        foreach($data['attr_value'] as $k => $v){
            //$k 就是 属性id
            foreach($v as $value){
                //$value 就是一个属性值
                //组装一条数据
                $row = [
                    'goods_id' => $id,
                    'attr_id' => $k,
                    'attr_value' => $value
                ];
                $goodspics_data[] = $row;
            }
        }
        //将商品原始的属性值删掉，重新添加
        \app\admin\model\GoodsAttr::where('goods_id', $id)->delete();
        //进行批量添加
        $goodsattr = new \app\admin\model\GoodsAttr();
        $goodsattr->saveAll($goodspics_data);
        //页面跳转
        $this->success('操作成功', 'index');
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //接收参数  已使用参数绑定，不需要再接收
        //静态调用destroy方法删除
        \app\admin\model\Goods::destroy($id);
        //页面跳转
        $this->success('操作成功', 'index');
    }

    //根据分类id 查找子分类( 根据一级查找二级、根据二级查找三级)
    public function getsubcate(){
        //接收参数
        $id = request()->param('id');
        //参数检测
        if(!preg_match('/^\d+$/', $id)){
            //参数错误
            $res = [
                'code' => 10001,
                'msg' => '参数错误'
            ];
            echo json_encode($res);die;
        }
        //数据处理  根据pid = $id 条件 查询tpshop_category表
        $data = \app\admin\model\Category::where('pid', $id)->select();
        //返回数据
        $res = [
            'code' => 10000,
            'msg' => 'success',
            'data' => $data
        ];
        echo json_encode($res);die;
    }

    //ajax请求删除相册图片
    public function delpics()
    {
        //接收数据
        $id = request()->param('id');
        //参数检测 略
        //删除数据
        \app\admin\model\Goodspics::destroy($id);
        //返回数据
        $res = [
            'code' => 10000,
            'msg' => 'success',
        ];
        //原生php方式
//        echo json_encode($res);die;
        //TP框架中 返回json格式字符串
        //如果是ajax请求，可以直接return 结果数组； 框架会自动转化为json
//        return $res; // 只能是ajax请求可以这么写，不推荐
        // 框架还封装了一个助手函数 json函数
        return json($res); // 推荐
    }
}
