<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 引入系统类

use think\Route;

//定义路由规则

Route::rule('/','index/index/index');
Route::rule('/','index/index');
// Route::rule('login','index/User/Login');
// Route::rule('OutLogin','index/User/OutLogin');
// Route::rule('WriteComments','index/Index/WriteComments');


// GET请求
Route::get('Login','index/User/Login');
Route::get('Reg','index/User/reg');
Route::get('ForGetPassWord','index/User/forgetpasswd');
Route::get('ShowType/:Type','index/index/ShowType');
Route::get('Blog/:id','index/index/showinfo');
Route::get('Key/:KeyWord','index/index/selectkeyword');
Route::get('WriteComments','index/index/WriteComments');
Route::get('ResList','index/Resource/Resource');
Route::get('PostResource','index/Resource/PostResource');
Route::get('Resource/:id','index/Resource/Info');
Route::get('MagBlog',"index/index/ManagementBlog");
Route::get('Update/:id','index/index/Updateblog');
Route::get('Delete/:id','index/index/Delete');

// POST请求
Route::post('CheckToken','index/User/CheckToken');
Route::post('CheckLogin','index/User/CheckLogin');
Route::post('CheckReg','index/User/CheckReg');
Route::post('CheckForPassWd','index/User/CheckForPassWd');
Route::post('CheckToken','index/User/CheckToken');
Route::post('CatRelease','index/Cat/CatRelease');
// // 带参数的路由
// Route::rule("KeyWord/:KeyWord","index/index/selectkeyword");
 return [
     '__pattern__' => [
         'name' => '\w+',
     ],
     '[hello]'     => [
         ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
         ':name' => ['index/hello', ['method' => 'post']],
     ],

 ];
