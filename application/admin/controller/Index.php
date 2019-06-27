<?php
namespace app\admin\controller;

use think\Cookie;
use think\Controller;
use think\View;
use think\Session;
use think\Db;

use \app\admin\model\Token;
use \app\admin\model\User;
class Index extends Controller
{

    
    public function index(){

        
        return view('login');
    }

    public function main(){

        return view('index');
    }

    public function elements(){
        
        // 查询状态为1的用户数据 并且每页显示10条数据
        $list = Db::table('User')->where('Jurisdiction',1)->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    public function chart(){
        // 查询状态为1的用户数据 并且每页显示10条数据
        $list = Db::table('BLog')->where('State',1)->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    public function panel(){

        return view('tab-panel');
    }

    public function table(){

        return view();
    }

    public function form() {
        //select stu_type,sum(score) from students group by id,stu_type order by id;
        $list = Db::table('Log')->Order('Time DESC')->paginate(5);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function empty(){

        return view();
    }

    public function Update(){

        return '123';
    }

    public function DelUser(){
        
        $Id = $_POST['Id'];
        $User = new User();
        $res = $User->save([
            'State'=>0,
        ],['Id'=>$Id]);
        if($res){
            return 1;
        }else{
            return 0;    
        }
    }

    public function UnDelUser(){
        $Id = $_POST['Id'];
        $User = new User();
        $res = $User->save([
            'State'=>1,
        ],['Id'=>$Id]);
        if($res){
            return 1;
        }else{
            return 0;    
        }
    }

    public function showinfo(){

        //$sql = "select * from Blog,User where(Blog.Id = " . $_GET['Id'] . ')';
        //$list = Db::query($sql);
        $list = Db::table('Blog')->where([
            'Id' => $_GET['Id']
        ])->find();
        $this->assign('list', $list);
        return $this->fetch();
        //return view();
    }

    public function findIP(){

        $res = Db::table('Log')->where(['IP' => $_GET['IP']])->order('Time DESC')->select();
        echo '<pre>';
        echo '<h1>' . $res[0]['IP'] . '信息如下</h1>';
        echo '<hr />';
        print_r($res);

    }
}
