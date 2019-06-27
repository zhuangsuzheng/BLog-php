<?php
namespace app\index\controller;

use think\File;
use app\index\model\User as DUser;
use app\index\model\Token;
use app\index\model\Log;
use think\Db;
use think\Controller;
use think\Request;
use think\Validate;
class Resource extends Controller
{
    public function _empty()
    {
        // 主要解决一些用户恶意操作
        $this->redirect("/");
    }
    public function _initialize(){
        // action('User\CheckToken');
        $agent = Request::instance()->header('user-agent');
        $Flag = strpos($agent,'Mobile');
        if(!$Flag){
            //手机用户
        }
        $request = Request::instance();
        $data['URL'] = $request->url();
        $data['Model'] = $request->module();
        $data['Controller'] = $request->controller();
        $data['Function'] = $request->action();
        $data['IP'] = $request->ip();
        $data['Time'] = time();
        $data['Agent'] = $agent = $request::instance()->header('user-agent');
        $data['Other'] = "用户IP:【" . $data['IP'] . "]使用【" . $data['Agent'] . "】端浏览器,访问了:【" . $data['Model'] . "】模块下的" . "【" . $data['Controller'] . "】控制器下的" . "【" . $data['Function'] . "】方法，详细的URL为:【" . $data['URL'] = $request->url() . "】";
        $Log = new log;
        $TimeRes = $Log::get(['Time' => $data['Time']]);
        if(empty($TimeRes)){
            $Log->save($data);
            $appkey = "orPQ809Bx7pGEAJHnn9x51B3rXOcTqnDmQSpRR";
            //************1.配置发送接口************
            $url = "http://wxmsg.dingliqc.com/send";
            $params = [
                  "title" => "IP:" . $data['IP'] . "用户,访问了:[" . $data['URL'] . "]",//类型
                  "msg" => $data['Other'],//返回数据格式：json或xml,默认json
                  "userIds" => $appkey,//你申请的key
            ];
            $paramstring = http_build_query($params);           /*生成 URL-encode 之后的请求字符串*/
            $ch = curl_init($url.'?'.$paramstring);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $contents = curl_exec($ch);
            //$title=$contents['msg'];            
        }
        
    }
    // 打开列表页面
    public function Resource(){
        $list = DB::table('Resource')->where("State=1")->select();
        $this->assign("list",$list);
        $con = controller("Index");
        $con->SelectType();
        return $this->fetch('list');
    }
    // 打开资源提交页面
    public function Postresource(){
        action('User\CheckToken');
        $con = controller("Index");
        $con->SelectType();
        return view();
    }
    // 接受资源提交页面传送过来的信息
    public function CheckResource(){
        // 根据Token获取UserId
        $data = array([
            'Id' => 0,
            'msg' =>'该功能正在维护!',
            'Time' => time()
        ]);
        return json($data);
        $tokenobj = new Token;
        $Token = cookie('Token');
        $TokenRes = $tokenobj::get(['Token' => $Token]);
        $_POST['UserId'] = $TokenRes['UserId'];
        $validate = Validate::make([
            'Title' => 'require',
            'Type' => 'require',
            'Link' => 'require',
            'Content' => 'max:500|min:10',
            'UserId' => 'require',
        ]);
        $_POST['Time'] = time();
        $_POST['State'] = 1;
        $status = $validate->Check($_POST);
        if(!$status){
            $res = Db::table('Resource')->insert($_POST);
            if($res){
                $data = array([
                    'Id' => 1,
                    'msg' =>'数据写入成功',
                    'Time' => time()
                ]);
                return json($data);
            }else{
                $data = array([
                    'Id' => 0,
                    'msg' =>'服务器繁忙,请重试',
                    'Time' => time()
                ]);
                return json($data);
            }
            
        }else{
            $data = array([
                'Id' => 0,
                'msg' =>$validate->getError(),
                'Time' => time()
            ]);
            return json($data);
        }
        
    }

    public function Info($id){
        $con = controller("Index");
        $con->SelectType();
        $list = Db::table('Resource')->where("Id=" . $id)->find();
        $this->assign('rels',$list);
        return $this->fetch('Info');
    }
}