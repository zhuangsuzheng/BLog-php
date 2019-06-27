<?php
namespace app\admin\controller;

use think\Cookie;
use think\Controller;
use think\View;
use think\Session;
use think\Db;

use \app\admin\model\Token;
use \app\admin\model\User as Root;
class User extends Controller
{

    
    public function index(){
        
        action('CheckStatic');
        //return view();
    }

    public function login(){
        
        $User = new Root;
        $Token = new Token;
        $res = Db::table('User')->where([
            'Email'=>$_POST['UserName'],
            'PassWord'=>md5($_POST['PassWord']),
            'State'=>1,
            'Jurisdiction'=>2
        ])->find();
        if($res){
            
            $ToRes = $Token::get([
                'UserId'=>$res['Id']
            ]);
            if($ToRes){

                //更新数据
                $Token->save([
                    'UserId'=>$res['Id'],
                    'Token'=>md5($_POST['UserName'] . time()),
                    'Time'=>time()+180
                ],['Id' => $ToRes['Id']]);
            }else{

                //添加数据
                $Token->UserId = $res['Id'];
                $Token->Token = md5($_POST['UserName'] . time());
                $Token->Time = time()+180;
                $Token->save();
            }
            cookie("AdminToken",md5($_POST['UserName'] . time()));
            $this->success("登录成功",'Index/main');
        }else{
            $this->error("登录失败");
        }
        

    }

    public function CheckStatic(){

        //检查用户状态
        $Token = new Token;
        $res = $Token::get([
            'Token'=>cookie('AdminToken')
        ]);
        if(!$res || $res['Time'] <= time()){
            $this->error("身份失效",'admin\Index\index');
        }else{

            //更新数据
            $Token->save([
                'UserId'=>$res['UserId'],
                'Time'=>time()+180
            ],['Token' => cookie('AdminToken')]);
        }
    }


}

