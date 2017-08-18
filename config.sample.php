<?php
//mysql
$host='localhost';
$user='root';
$pass='';
$db='budang';

//要模仿的浏览器
$ua='netdisk;4.6.1.0;PC;PC-Windows;6.2.9200;WindowsBaiduYunGuanJia';

//后台显示的跳转地址，目前准备弃用此项
$jumper = 'http://localhost/jump.php?';

//直链功能的开关
$enable_direct_link = true;

//enable_high_speed_link选项已取消

//直接播放视频功能的开关【跳转页需要使用HTTPS方可播放】
$enable_direct_video_play = false;

//应用户要求：强制只开启直链、禁用提取的开关，会覆盖enable_direct_link
$force_direct_link = false;

//管理员注册码
// String: 添加管理员需要输入该字符串
// NULL: 添加管理员不需要输入注册码
// FALSE: 禁用前界面添加管理员功能
$registCode = FALSE;
