<?php
namespace app\index\controller;

use think\Request;
use think\Controller;

class Error extends Controller
{
    public function index()
    {
        // 跳转到404页面
        $this->redirect("/");
    }
    public function _empty()
    {
        // 主要解决一些用户恶意操作
        $this->redirect("/");
    }
}