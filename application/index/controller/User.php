<?php
namespace app\index\controller;

use think\Cookie;
use think\Session;
use app\index\model\User as DUser;
use app\index\model\Token;
use think\Controller;
use app\index\model\Log;
use think\Db;
use think\Request;
use think\Validate;


class User extends Controller
{
    public function _initialize(){
        
        $model = new System;
        $model->PostLogDate();
        $model2 = controller('Index');
        $model2->SelectType();
        
    }
    
    // 空连接跳转
    public function _empty()
    {
        // 主要解决一些用户恶意操作
        $this->redirect("/");
    }
    public function index(){
        $this->redirect('/');
    }
    // 打开用户登录页面
    public function Login() {
        
        return view();
    }

    /****************************
    * 登录接口须知：
    * 1.首先判断验证码
    * 2.验证用户名和密码以及权限是否为普通用户,如果正确判断账号状态是否正常
    * 3.如果一切正常就开始写入Token,否则返回错误信息
    *
    * ReturnJson(['Id' => '0','mag' => 'mag!','Time' => time());
    * TokenJson(['UserId' => -1,'Token' => 'NULL','Time' => time() + (7*24*60*60*1000)]);
    *   Id = 0 ? 操作失败:操作成功
    *  
    ****************************/

    public function CheckLogin() {
        // 实例化模型
        $User = new DUser;
        $Token = new Token;

        // 构建Json
        $ReturnData = array(['Id' => '-1','mag' => 'NULL','Time' => time()]);
        $TokenData = array(['UserId' => -1,'Token' => 'NULL','Time' => time() + (7*24*60*60*1000)]);
        
        // 检查验证码
        if(!captcha_check($_POST['Vcode'])){
            
            // 构建返回Json
            $ReturnData = array(['Id' => '0','mag' => '验证码错误!','Time' => time()]);
            return json($ReturnData);
        };

        // 检查用户名和密码以及权限是否正确
        $UserRes = $User::get([
            'Jurisdiction' => 1,
            'Email' => $_POST['Email'],
            'PassWord' => md5($_POST['PassWord'])
        ]);
        
        // 判断用户名和密码是否正确
        if($UserRes['Email'] != $_POST['Email']){
            $ReturnData = array(['Id' => '0','mag' => '账号和密码不匹配','Time' => time()]);
            return json($ReturnData);
        }
        if($UserRes['State'] == '1'){

            // 生成、构建Token
            $TokenData = array([
                'UserId' => $UserRes['Id'],
                'Token' => md5($UserRes['Email'] . $UserRes['PassWord'] . time()),
                'Time' => time() + (7*24*60*60*1000)
            ]);
            $TokenId = $Token::get(['UserId' => $UserRes['Id']]);
            // 存在更新,不存在写入
            if($TokenId){
                $Token->save($TokenData[0],['UserId' => $UserRes['Id']]);
            }else{
                $Token->save($TokenData[0]);
            }

            $TokenId = $Token::get(['UserId' => $UserRes['Id']]);
            $ReturnData = array(['Id' => '1','mag' => '登录成功','Time' => time(),'UserId' => $TokenId['UserId'],'Token' => $TokenId['Token'],'Time' => time() + (7*24*60*60*1000)]);
            return json($ReturnData);

            // 解决不能返回Token问题
            
        }else{

            // 创建返回Json
            $ReturnData = array(['Id' => '0','mag' => '您的账号因违反相关规定已被冻结,详情联系客服','Time' => time()]);
            return json($ReturnData);
        } 
    }

    // 检查用户Token
    public function CheckToken(){

        $Token = cookie('Token');
        $token = new Token;
        $user = new DUser;
        $res = $token::get([
            'Token' => $Token
        ]);
        if(!empty($res)){
            if($res['Time'] >= time()){
                $data['mag'] = '1';
                $UserRows = $user::get([
                    'Id' => $res['UserId']
                ]);
                $data['UserName'] = $UserRows['UserName'];
            }else{
                Cookie::delete('Token');
                $data['mag'] = '-1';
                $data['other'] = '登录时间超时,请重新登录';
                $this->redirect('/Login');
                
            }
        }else{
            $data['mag'] = '0';
            $this->redirect('/Login');
        }
        
        return json($data);
    }


    //根据用户Token返回用户ID
    public function IfTokenReturnUserId(){

        $token = new Token;
        $tokenres = $token::get([
            'Token' => $_POST['Token']
        ]);

        return $tokenres['UserId'];
    }

    // 打开注册页面
    public function Reg(){
        $Key = md5(time());
        $this->assign("Key",$Key);
        cookie('Key',$Key);
        return $this->fetch();
    }

    // 处理注册信息
    public function CheckReg() {

        // 获取访问者身份
        $_POST['IP'] = $request = Request::instance()->ip();
        $_POST['Browser'] = Request::instance()->header('user-agent');
        $_POST['Host'] = Request::instance()->header('Host');
        $_POST['Referer'] = Request::instance()->header('Referer');
        $_POST['Accept'] = Request::instance()->header('Accept');  
        $validate = Validate::make([
            // 键名代表定义需要的字段名
            "Email" => "require|email",
            "PassWord" => "require|min:6|max:18|confirm",
            "PassWord_confirm" => "require|min:6|max:18",
            "UserName" => "require|min:2|max:10",
            "PhoneNumber" => "require|number|/^1[34578]\d{9}$/",
            "Vcode" => "require",
            "IP" => "require",
            "Browser" => "require|min:10",
            "Host" => "require",
            "Referer" => "require",
            "Accept" => "require"
        ]);
        // 校验验证码
        if(!empty($_POST['Key'])){
            if(!captcha_check($_POST['Vcode'])){  
                $ReturnData = array(['Id' => '0','mag' => '验证码错误!','Time' => time()]);
                return json($ReturnData);
            }
        }
        // 验证注册账号是否已经存在
        $EmailStatus = Db::table('User')->where(['Email' => $_POST['Email']])->find();
        if(!empty($EmailStatus)){
            $ReturnData = array(['Id' => '0','mag' => $_POST['Email'] . "已经被其他用户注册!您可以尝试更换其他可用的用户名",'Time' => time()]);
            return json($ReturnData);
        }
        // 用返回的规则对POST数据进行验证，如果返回真则代表这个数据是通过的，如果为假表示这个数据是不通过的
        $status = $validate->Check($_POST);
        if($status){
            // 验证通过  -- 组织信息 -- 创建对象  -- 写入数据库
            $_POST['PassWord'] = md5($_POST['PassWord']);
            $_POST['RegTime'] = time();
            $_POST['Jurisdiction'] = 1;
            $_POST['State'] = 1;
            $User = new DUser($_POST);
            if(!empty($_POST['Key'])){
                $UserRes = $User->allowField(true)->save();
            }else{
                $ReturnData = array(['Id' => '2','mag' => "注册用户",'Time' => time()]);
                return json($ReturnData);  
            }
            if ($UserRes == 1){
                // 如果写入成功
                $ReturnData = array(['Id' => '1','mag' => "用户注册成功,请以【" . $_POST['Email'] . "】作为本站账号进行登录！",'Time' => time()]);
                return json($ReturnData);  
            }else{
                // 符合规则但是没有写入数据库
                $ReturnData = array(['Id' => '0','mag' => "服务器繁忙,请重试",'Time' => time()]);
                return json($ReturnData);
            }          
        }else{
            // 验证不通过
            $ReturnData = array(['Id' => '0','mag' => $validate->getError(),'Time' => time()]);
            return json($ReturnData);
        }
    }


    // 退出登录
    public function OutLogin(){

        $Token = new Token;
        $TokenData = cookie("Token");

        $Token::where('Token','=',$TokenData)->delete();
        $this->error("账号已经安全退出!");
        //$this->redirect("javascript:history.back(-1)");
    }


    // 打开找回密码页面

    public function forgetpasswd(){

        return view();
    }

    // 处理找回密码
    public function CheckForPassWd(){

        // 检查验证码
        if(!captcha_check($_POST['Vcode'])){
            
            // 构建返回Json
            return json([
                'Id' => 0,
                'mag' => "验证码错误！"
            ]);
        };

        // 检查用户传送过来的信息是否存在

        $User = new DUser;
        $res = $User::get([
            'UserName' => $_POST['UserName'],
            'Email' => $_POST['Email'],
            'PhoneNumer' => $_POST['PhoneNumer']
        ]);

        if(!$res){

            //没有验证成功
            return json([
                'Id' => 0,
                'mag' => "信息验证失败,不能证明您是这个账号的主人"
            ]);
        }
        
        // 防止劫匪！！
        
        if(empty($_POST['NewPassWord'])){
            
            // 如果没有新密码传过来就认为时第一次验证
            return json([
                'Id' => 1,
                'mag' => "信息验证成功,请给您的账号设置新的密码"
            ]);
        }else{

            // 如果有新密码传过来,就认为已经验证成功
            $URes = $User->save([
                'PassWord' => md5($_POST['NewPassWord'])
            ],[
                'UserName' => $_POST['UserName'],
                'Email' => $_POST['Email'],
                'PhoneNumer' => $_POST['PhoneNumer']
            ]);

            if($URes){
                return json([
                    'Id' => 2,
                    'mag' => "账号申诉成功"
                ]);
            }else{
                return json([
                    'Id' => 0,
                    'mag' => "新密码和旧密码相同"
                ]);
            }
        }

    }


    // 打开个人中心
    public function personal(){
        $index = new \app\index\controller\Index;
        $index->SelectType();
        return view();
    }

    // 打开详细页面
    public function selfinfo(){

        return view();
    }

    // 打开会员信息页面
    public function vip() {

        return view();
    }

    // 打开我的积分
    public function integral(){

        return view();
    }

    // 修改密码
    public function ChangePassWd(){

        $this->error("本功能还未实现!");
        if(!empty($_POST['OldPassWord'])){
            $this->error("密码不匹配");
        }
        $Token = cookie('Token');
        $To = new Token;
        $TokenRows = $To::get(['Token' => $Token]);
        
        if($TokenRows['UserId']){

        }    
        return $TokenRows;
        
        
    }
}
