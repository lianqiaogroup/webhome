<?php

// +----------------------------------------------------------------------
// |[ 订单类操作控制器 - 查询订单数据(只有管理员权限才有) ]
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | Team:   Cuckoo
// +----------------------------------------------------------------------
// | Date:   2018/3/1 09:00
// +----------------------------------------------------------------------
require_once 'ini.php';

$controller = new \admin\Controllers\OrderController();
$controller->dispatch();
