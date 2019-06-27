<?php
namespace app\index\controller;

use think\File;
use app\index\model\Blog;
use app\index\model\User as DUser;
use app\index\model\Token;
use app\index\model\Log;
use think\Db;
use think\Controller;
use think\Request;
use think\Validate;
class Index extends Controller
{
    public function _empty()
    {
        // 主要解决一些用户恶意操作
        $this->redirect("/");
    }
    public function _initialize(){
              
        $model = new System;
        $model->PostLogDate();
        
    }
    // 显示主页 
    public function index()
    {
        // 设置条件,并显示分配信息
        $map = array([
            'State' => 1
        ]);
        action('SelectPage',$map);
        action('SelectType');
        if(empty($_GET['page'])){
            $this->assign('TypeKey','首页');
        }else{
            $this->assign('TypeKey',"第" . $_GET['page'] . "页 ");
        }
        return $this->fetch();
        
    }
    /*构件分类查询函数 */
    public function SelectType() {
        // 查询总数
        $count = Db::table('Blog')->where(['State' => 1])->count();
        $TypeList = Db::table('Blog')->group('Type')->where(['State' => 1])->select();
        $SelectList = Db::table('Type')->order('Id')->select();
        $this->assign("selectlist",$SelectList);
        $this->assign('count', $count);
        $this->assign('TypeList', $TypeList);
    }
    /**
        * 构件分页信息模块函数
        * 建立这个函数的目的一是为了减少代码量实现模块化  
        * 根据显示设备的不同 显示不同的东西
        * 因为手机端没有左侧边栏 所以理论上可以显示更多的东西
        * 
    */
    public function SelectPage($map){
        // 判断是不是为手机用户
        $blog = new Blog;
        $agent = Request::instance()->header('user-agent');
        $Flag = strpos($agent,'Mobile');
        if(!$Flag){
            // 查询状态为1的用户数据 并且每页显示10条数据
            $list = blog::where($map)->field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('PostTime desc')->paginate(10);
        }else{
            // 查询状态为1的用户数据 并且每页显示4条数据
            $list = blog::where($map)->field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('PostTime desc')->paginate(20);
        }
        $Hotlist = blog::where($map)->field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('Views desc')->paginate(15);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('Hotlist', $Hotlist);
        $this->assign('page', $page);
    }
    // 搜索功能
    public function SelectKeyWord($KeyWord){
        // 设置条件
        $map = array([
            'State' => 1,
            'Title' => ['like','%' . $KeyWord . "%"]
        ]);
        $this->assign('TypeKey','与 [' . $KeyWord . '] 相关的词条 ');
        action('SelectPage',$map);
        action('SelectType');
        return $this->fetch("Index/index");
    }

    // 查看指定Id博客
    public function ShowInfo($id) {

        //获取Id
        $blog = new Blog;
        $user = new DUser;
        $res = $blog::get(['Id' => $id,'State' => 1]);
        $TypeList = Db::table('Blog')->where("State=1")->group('Type')->select();
        $blog->save([
            'Views'=>$res['Views'] + 1,
        ],['Id' => $id]);
        $UserInfo = $user->where(['Id' => $res['UserId']])->find();
        if(empty($res)){
            $this->redirect("S_Error");
        }
        action('SelectType');
        $Hotlist = blog::field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('Views desc')->paginate(5);
        //$CatList = Db::table('Cat')->where(['Bid' => $id])->select();
        $sql = "select * from user u,cat c,blog b where b.Id=". $id ."  group by CatContent;";
        $CatList = Db::query($sql);
        $this->assign('Hotlist', $Hotlist);
        $this->assign('UserInfo', $UserInfo);
        $this->assign('TypeList', $TypeList);
        $this->assign("list",$res);
        $this->assign("CatList",$CatList);
        return $this->fetch('ShowInfo');
    }

    // 打开写博客页面
    public function WriteComments(){
        
        // 检查是否登录
        action('User\CheckToken');
        action('SelectType');
        $Hotlist = blog::field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('Views desc')->paginate(5);
        $this->assign('Hotlist', $Hotlist);
        $data['Id'] = "0";
        $data['Title'] = null;
        $data['Content'] = null;
        $data['OR'] = '1';
        $data['Type'] = "HTML";
        $this->assign("text",$data);
        return $this->fetch('WriteComments');
    }

    // 接受发布信息
    public function Release(){

        $validate = Validate::make([
            'Content' => 'require|min:10',
            'UserId' => 'require',
            'Title' => 'require|min:5|max:100',
            'Type' => 'require',
            'Formless' => 'require|min:10'       
        ]);
        $status = $validate->Check($_POST);
        if($status){
            $tokenobj = new Token;
            // 根据Token获取UserId
            $Token = cookie('Token');
            $TokenRes = $tokenobj::get(['Token' => $Token]);
            $_POST['UserId'] = $TokenRes['UserId'];
    
            // 检查是否登录
            action('User\CheckToken');
            $_POST['Formless'] = strip_tags($_POST['Formless']);
            $_POST['Formless'] = strip_tags($_POST['Formless']);
            $_POST['IP'] = $request = Request::instance()->ip();
            $_POST['Browser'] = Request::instance()->header('user-agent');
            $_POST['Host'] = Request::instance()->header('Host');
            $_POST['Referer'] = Request::instance()->header('Referer');
            $_POST['Accept'] = Request::instance()->header('Accept');
            $_POST['PostTime'] = time();
            // $_POST['OR'] = 1;
            $_POST['State'] = 1;
            $blod = new Blog($_POST);
            if($_POST['Id'] != '0'){
                $rows = Db::table('Blog')
                ->where('Id', $_POST['Id'])
                ->update([
                    'Title'  => $_POST['Title'],
                    'Type'  => $_POST['Type'],
                    'Formless'  => $_POST['Formless'],
                    'Content'  => $_POST['Content'],
                    'OR'  => $_POST['OR']
                ]);                 
            }else{
                $rows = $blod->allowField(true)->save();
            }
            if($rows){
                $ReturnData = array(['Id' => 1,'Mag' => '信息发布成功!','Time' => time()]);
                return json($ReturnData);
            }else{
                $ReturnData = array(['Id' => 0,'Mag' => '服务器繁忙,请重试!','Time' => time()]);
                return json($ReturnData);
            }
        }else{
            $ReturnData = array(['Id' => 0,'Mag' => $validate->getError(),'Time' => time()]);
            return json($ReturnData);
        }
        
    }

    // 输出分类

    public function ShowType($Type){

        // 设置条件,并显示分配信息
        $map = array([
            'State' => 1,
            'Type' => $Type
        ]);
        action('SelectPage',$map);
        
        // 获取分类
        $this->assign('TypeKey',$Type . " 专栏");
        action('SelectType');
        // 渲染模板输出
        return $this->fetch('Index/Index');
    }
    // 打开警告页面
    public function warning(){

        return view();        
    }

    public function S_Error(){

        return view('404');
    }

    public function ChangePasswd(){
        // 检查是否登录
        action('User\CheckToken');
        // 获取分类
        action('SelectType');
        return view();
    }

    public function ManagementBlog(){
        // 检查是否登录
        action('User\CheckToken');
        action('SelectType');
        $Token = cookie('Token');
        $ResToken = Db::table('Token')->where(["Token" => $Token])->find();
        $ResUser = Db::table('User')->where("Id=" . $ResToken['UserId'])->find();
        $this->assign('TypeKey',$ResUser['UserName'] . " 博客 - 庄宿正的官方网站");
        $map = array([
            'UserId' => $ResToken['UserId'],
            'State' => 1
        ]);
        action('SelectPage',$map);
        return $this->fetch();
    }
    
    // 修改博客
    public function Updateblog($id){

        // 判断用户是否登录
        action('User\CheckToken');
        // 判断登录的用户是否为作者 先取出登录用户的ID  再取出博客的ID 然后两者进行判断
        action('SelectType');
        $Token = cookie('Token');
        $ResToken = Db::table('Token')->where(["Token" => $Token])->find();
        // 取出博客的ID
        $ResBlog = Db::table('Blog')->where("Id=" . $id)->find();
        $Hotlist = blog::field('Id,UserId,Title,Type,Formless,Content,PostTime,OR,Views,State')->order('Views desc')->paginate(5);
        $this->assign('Hotlist', $Hotlist);
        if($ResToken['UserId'] != $ResBlog['UserId']){
            $this->redirect("S_Error");
        }else{
            $this->assign("text",$ResBlog);
            return $this->fetch("index/WriteComments");
        }
        
    }

    // 删除博客
    public function Delete($id){

        $res = Db::table('Blog')->where('Id=' . $id)->update([
            'State' => 0
        ]);
        if($res){
            echo '<script> alert("删除成功");history.go(-1)</script>';
        }
    }
}
