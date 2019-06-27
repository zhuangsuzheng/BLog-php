<?php
namespace app\index\controller;

use think\Request;
use think\Controller;
use think\Db;
class Cat extends Controller
{
    /**
     * 发布聊天只有登录后的用户才可以。
     * 发布聊天只能用于存在的博客
     * 参数列表:博客Id 发布评论的用户Id 用户有效Token 评论内容  Bid Uid Content
     * 请求方式：post
     * 自动生成： Cat状态(是否可视) Cat发布时间 
     */
    public function CatRelease()
    {
        // 判断用户是否登录
        //action('User\CheckToken');
        $Token = cookie("Token");
        $BlogRes = Db::table('Blog')->where([
            'Id' => $_POST['Bid'],
        ])->find();
        if(empty($BlogRes)){    // 如果为空
            return json(['Id'=>'0','mag'=>'此博客不允许评论','Time'=>time()]);
        }
        $TokenRes = Db::table('Token')->where(['Token' => $Token])->find();
        $Data['Uid'] = $TokenRes['UserId'];
        $Data['Bid'] = $_POST['Bid'];
        $Data['CatContent'] = $_POST['Content'];
        $Data['Time'] = time();
        $Data['State'] = 1;
        $CatRes = Db::table('Cat')->insert($Data);
        if($CatRes){
            return json(['Id'=>'1','mag'=>'评论成功','Time'=>time()]);
        }else{
            return json(['Id'=>'0','mag'=>'请求超时','Time'=>time()]);
        }
    }
}