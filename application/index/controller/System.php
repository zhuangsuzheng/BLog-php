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
class System extends Controller
{
    public function _empty()
    {
        // 主要解决一些用户恶意操作
        $this->redirect("/");
    }
    public function PostLogDate(){
        $request = Request::instance();
        $data['URL'] = $request->url();
        $data['Model'] = $request->module();
        $data['Controller'] = $request->controller();
        $data['Function'] = $request->action();
        $data['IP'] = $request->ip();
        $data['Time'] = time();
        $data['Agent'] = $agent = $request::instance()->header('user-agent');
        $data['Other'] = "用户IP:【" . $data['IP'] . "】使用【" . $data['Agent'] . "】端浏览器,访问了:【" . $data['Model'] . "】模块下的" . "【" . $data['Controller'] . "】控制器下的" . "【" . $data['Function'] . "】方法，详细的URL为:【" . $data['URL'] = $request->url() . "】";
        $Log = new log;
        $TimeRes = $Log::get(['Time' => $data['Time']]);
        if(empty($TimeRes) && $data['Function'] != 'checktoken'){
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
}
