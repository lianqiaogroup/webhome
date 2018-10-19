<?php

namespace admin\helper;


class product extends common
{
    public function getAllProduct($filter = [], $pageSize = 20)
    {

        $uid = $this->getUids();
        !empty($uid['company_id']) && ($filter['product.company_id']=$uid['company_id']);
        if ($uid['uid']) {
            $filter['product.oa_id_department'] = $uid['id_department'];
        }

        if (!isset($uid['is_leader']) || !$uid['is_leader']) {
            unset($filter['product.ad_member']);
            if($uid['uid'] && !isset($filter['product.ad_member_id']) ){
                $filter['product.ad_member_id'] = $uid['uid'];//领导的下属员工列表
            }
        }

        //优先转为索引字段
        if (!empty($filter['product.ad_member_id'])) {
            $ad_member = $this->db->select('oa_users', ['name_cn(ad_member)'], ['uid' => $filter['product.ad_member_id']]);
            $ad_member = \array_column((array)$ad_member, 'ad_member');
            if($ad_member){
                unset($filter['product.ad_member_id']);
                $filter['product.ad_member'] = $ad_member;
            }
        }
        if (!empty($filter['product.userid'])) {
            $filter['product.ad_member_id'] = $filter['product.userid'];
            unset($filter['product.userid']);
        }
        $filter['ORDER'] = ['product.product_id' => "DESC"];
        $columns = [
                'product.thumb', 
                'product.title',
                'product.sales_title', 
                'product.price', 
                'product.id_zone', 
                'product.domain', 
                'product.identity_tag', 
                'product.id_department', 
                'product.is_open_sms', 
                'product.ad_member', 
                'product.add_time', 
                'product.theme', 
                'product.product_id', 
                'product.erp_number', 
                'product.is_del', 
                'product.aid', 
                'product.ad_member_id', 
                'product.currency_code', 
                'product.currency_prefix', 
                'product.available_zone_ids',
                'product.oa_id_department',
                'product.lang',
                'zone.code'
            ];
        // $columns = ['thumb', 'code'];
        $data = $this->db->tableJoinPage('product', ["[><]zone" => ["id_zone" => "id_zone"],], $columns, $filter, $pageSize);
        if ($data['goodsList']) {
            //find zone
            $id_zone = array_unique(array_column($data['goodsList'], 'id_zone'));
            $oa_id_department = array_unique(array_column($data['goodsList'], 'oa_id_department'));
            $D = new \admin\helper\country($this->register);
            $maps = ['id_zone' => $id_zone];
            $zone = $D->getAllZone($maps);
            $zone = array_column($zone, NULL, 'id_zone');
            $oa_model = new \admin\helper\oa_users($this->register);
            $oa = $oa_model->get_department(['id_department'=>$oa_id_department]);
            $oa  =array_column($oa,NULL,'id_department');
            foreach ($data['goodsList'] as $value) {
                $value['thumb'] = qiniu::get_image_path($value['thumb']);
                $value['price'] = money_int($value['price'], 'float');
                $value['zone'] = $zone[$value['id_zone']]['title'];
                $value['department'] = $oa[$value['oa_id_department']]['department'];
                $ret[] = $value;
            }
            $data['goodsList'] = $ret;
        }
        return $data;
    }

    public function getProductInfo($filter = [])
    {
        $map = ['is_del' => 0, 'ORDER' => ['add_time' => "DESC", 'product_id' => "DESC"]];
        if ($filter) {
            $map['AND'] = $filter;
        }
        $data = $this->db->select('product', ['product_id', 'title','ad_member_id','ad_member'], $map);
        return $data;
    }
    public function getProductByProductID($product_id)
    {
        return $this->db->get('product', "*", ['product_id' => $product_id]);
    }

    public function getProduct()
    {
        $map = ['is_del' => 0, 'ORDER' => ['add_time' => "DESC", 'product_id' => "DESC"]];
        if (!$_SESSION['admin']['is_admin']) {
            $map['OR'] = [
                'aid' => $_SESSION['admin']['uid'],
                // 'ad_member_id'=>$_SESSION['admin']['uid'],
            ];
            // $map['aid'] = $_SESSION['admin']['uid'];
        }
        $data = $this->db->select('product', '*', $map);
        return $data;
    }

    public function getOneProduct($product_id)
    {
        $ret = $this->db->get('product', "*", ['product_id' => $product_id]);
        if (!$ret) {
            return ['ret' => 0, 'msg' => "没有找到该产品！"];
        }
        if (($ret['aid'] != $_SESSION['admin']['uid']) && ($ret['ad_member'] != $_SESSION['admin']['username'])) {
            //下属、公司判定
            $uid = $this->getUids();
            if (isset($uid['is_leader']) && $uid['is_leader']) {
                if ($_SESSION['admin']['username'] !='googleID' && !in_array($ret['oa_id_department'], $uid['id_department'])) {
                    return ['ret' => 0, 'msg' => "你没有权限查看此产品！"];
                }
            } else {
                if ($uid['uid']) {
                    if (!empty($uid['company_id']) && ($uid['company_id'] != $ret['company_id'])) {
                        return ['ret' => 0, 'msg' => "你没有权限查看此产品！"];
                    }
                    if (!in_array($ret['aid'], $uid['uid']) && !in_array($ret['ad_member'], $uid['ad_member'])) {
                        return ['ret' => 0, 'msg' => "你没有权限查看此产品！"];
                    }
                }
            }
        }
        $ret['price'] = money_int($ret['price'], 'float');
        $ret['market_price'] = money_int($ret['market_price'], 'float');
        $ret['discount'] = money_int($ret['discount'], 'float');
        $ret['ret'] = 1;
        return $ret;
    }

    public function saveProduct($product_id, $data = [])
    {
        if (empty($data)) return false;

        if ($product_id) {
            $this->checkThemeDiy($product_id, $data['theme']);
            $_data = $this->db->get('product', '*', ['product_id' => $product_id]);
            $_data['la'] = trim($_data['la']);
            $_data['google_js'] = trim($_data['google_js']);
            $_data['yahoo_js'] = trim($_data['yahoo_js']);
            $_data['yahoo_js_trigger'] = trim($_data['yahoo_js_trigger']);
            unset($data['identity_tag']);//二级目录编辑保存不修改
            $ret = $this->db->update('product', $data, ['product_id' => $product_id]);
            $sql = $this->db->last();
            $this->log->write('product', print_r($sql, 1));
            if ($ret) {
                //仅成功的日志进入mysql日志
                $data['la'] = trim($data['la']);
                $data['google_js'] = trim($data['google_js']);
                $data['yahoo_js'] = trim($data['yahoo_js']);
                $data['yahoo_js_trigger'] = trim($data['yahoo_js_trigger']);
                $_d = array_diff($data, $_data);
                if ($_d) {
                    $log = [];
                    $msg = '修改字段如下：<br>';
                    $columnNames = $this->getColumnName();
                    foreach ($_d as $key => $value) {
                        //id转化 汉字值
                        if ($key == 'id_zone') {
                            $_data[$key] = $this->getZoneById($_data[$key]);
                            $value = $this->getZoneById($value);
                        } else if ($key == 'payment_ocean') {
                            $_data[$key] = ($_data[$key] > 0) ? '钱海支付开启' : '钱海支付关闭';
                            $value = ($value > 0) ? '钱海支付开启' : '钱海支付关闭';
                        } else if ($key == 'payment_underline') {
                            $_data[$key] = ($_data[$key] > 0) ? '线下支付开启' : '线下支付关闭';
                            $value = ($value > 0) ? '线下支付开启' : '线下支付关闭';
                        } else if ($key == 'payment_blue') {
                            $_data[$key] = ($_data[$key] > 0) ? 'Pay Blue支付开启' : 'Pay Blue支付关闭';
                            $value = ($value > 0) ? 'Pay Blue支付开启' : 'Pay Blue支付关闭';
                        } else if ($key == 'payment_online') {
                            $_data[$key] = ($_data[$key] > 0) ? '在线支付易极付开启' : '在线支付易极付关闭';
                            $value = ($value > 0) ? '在线支付易极付开启' : '在线支付易极付关闭';
                        } else if ($key == 'available_zone_ids') {
                            $_data[$key] = $this->getAvailableZonesById($_data[$key]);
                            $value = $this->getAvailableZonesById($value);
                        } else if ($key == 'aid') {
                            $_data[$key] = $this->getNameCnById($_data[$key]);
                            $value = $this->getNameCnById($value);
                        } else if ($key == 'content') {
                            $_data[$key] = '老页面内容(简写)';
                            $value = '新页面内容(简写) 页面内容已经成功进行修改';
                        }
                        if(isset($columnNames[$key])&&isset($_data[$key]))$msg .= "字段 {$columnNames[$key]} 旧值为{$_data[$key]}， 更新为 {$value}  <br>";
                    }
                    $log['act_table'] = 'product';
                    $log['act_sql'] = $sql;
                    $log['act_desc'] = $msg;
                    $log['act_time'] = time();
                    $log['act_type'] = 'update_product';
                    $log['product_id'] = $product_id;
                    $log['act_loginid'] = $_SESSION['admin']['login_name'];
                    $this->db->insert("product_act_logs", $log);
                }
            }
            return $ret;
        } else {
            $data['aid'] = $_SESSION['admin']['uid'];
            $this->db->insert("product", $data);
            $sql = $this->db->last();
            $this->log->write('product', print_r($sql, 1));
            $ret = $this->db->id();
            if ($ret) {
                //仅成功的日志进入mysql日志
                $sql = $this->db->last();
                $log = [];
                $msg = '新增产品';
                $log['act_table'] = 'product';
                $log['act_sql'] = $sql;
                $log['act_desc'] = $msg;
                $log['act_time'] = time();
                $log['act_type'] = 'insert_product';
                $log['product_id'] = $ret;
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            return $ret;
        }
    }

    public function productCheck($domain, $product_id)
    {
        $map = ["AND" => ['domain' => $domain, 'product_id[!]' => $product_id, 'is_del' => 0]];
        $ret = $this->db->get('product', '*', $map);
        if ($ret) return ['ret' => 0, 'msg' => '域名不能重复'];
        return ['ret' => 1];
    }

    /**
     * 域名重复验证（加上二级域名）
     * @param string $domain 域名
     * @param string $identity_tag uri
     * @param int $product_id  产品id
     * @return array
     */
    public function domainCheck($domain, $identity_tag, $product_id) {
        $map = ["AND" => ['domain' => $domain, 'product_id[!]' => $product_id, 'is_del' => 0, 'identity_tag' => $identity_tag]];
        $ret = $this->db->get('product', ['product_id'], $map);
        if ($ret) {
            return ['ret' => 0, 'msg' => '该域名下二级目录已存在.请勿重复'];
        }
        return ['ret' => 1];
    }

    public function productNumberCheck($number, $product_id)
    {
        $map = ["AND" => ['erp_number' => $number, 'product_id[!]' => $product_id, 'is_del' => 0]];
        $ret = $this->db->count('product', $map);

        if ($ret) {
            return ['ret' => 0, 'msg' => '产品erp编号不能重复'];
        }
        return ['ret' => 1];
    }


    public function getProduct_attr($product_id)
    {
        $map = ['product_id' => $product_id, 'ORDER' => ["sort", 'product_attr_id']];
        $attr = $this->db->select('product_attr', '*', $map);
        return $attr;
    }

    public function getProduct_group_attr($product_id)
    {
        $map = ['product_id' => $product_id, 'ORDER' => ['attr_group_id',"sort", 'product_attr_id']];
        $attr = $this->db->select('product_attr', '*', $map);
        return $attr;
    }

    public function getAttrGroup()
    {
        $map = ['ORDER' => ['sort', 'attr_group_id']];
        $attr_group = $this->db->select('attr_group', '*', $map);

        return $attr_group;
    }

    public function deleteAttr($id)
    {
        $map = ['product_attr_id' => $id];
        //判断属性组是否存在多个 存在多个则可以删除 否则不能删除
        $productAttr = $this->db->get('product_attr', ['attr_group_id', 'product_id'], $map);
        if (!$productAttr) {
            return ["ret" => 0, "msg" => "删除失败,没有该属性"];
        }

        $mapWhere = ['product_id' => $productAttr['product_id'], 'attr_group_id' => $productAttr['attr_group_id']];
        $count = $this->db->count('product_attr', $mapWhere);
        if ($count == 1) {
            return ["ret" => 0, "msg" => "删除失败,该属性组至少需要一个属性,否则下单后产品信息不完整"];
        }
        try {
            $sql2 = $sql3 = '';
            $this->db->pdo->beginTransaction();
            $ret = $this->db->delete('product_attr', $map);
            // $this->log->write('product', print_r($sql, 1));
            if (!$ret) {
                $this->db->pdo->rollBack();
                return ["ret" => 0, "msg" => '删除失败'];
            }
            $sql1 = $this->db->last();
            $comboGoodsList = (array) $this->db->select('combo_goods',['combo_goods_id','attr_id_desc','combo_id'],['product_id'=>$productAttr['product_id']]);

            $_combo_ids = [];//待删除套餐id
            foreach ($comboGoodsList as $k => $v) {
                //空的不做处理，始终会用最新的全部属性 or 是无属性的
                //不要用IF判断嵌套太深, 能continue break 就提前jump出流程
                if( ! $v['attr_id_desc'] ){ continue 1; }
                $_attr_id_desc = (array) json_decode($v['attr_id_desc'],true);
                if( ! $_attr_id_desc ){ continue 1; }

                $_check_arr = empty($_attr_id_desc[$productAttr['attr_group_id']])?[]:$_attr_id_desc[$productAttr['attr_group_id']];
                if( ! in_array($id, $_check_arr) ){ continue 1; }

                if(count($_check_arr) == 1){
                    $_combo_ids[] = $v['combo_id'];
                }else{
                    //delete 关联属性
                    if($_new = $_attr_id_desc[$productAttr['attr_group_id']]){
                        $_del_index = array_flip($_new);
                        unset($_new[$_del_index[$id]]);
                    }
                    $_attr_id_desc[$productAttr['attr_group_id']] = empty($_new)?[]:$_new;
                    $res = $this->db->update('combo_goods',['attr_id_desc'=>json_encode($_attr_id_desc)],['combo_goods_id'=>$v['combo_goods_id']]);
                    if($res){
                        $sql3 = $this->db->last();
                    }
                }
            }

            if($_combo_ids){//删除下架套餐
                $comboIdList = (array) $this->db->select('combo',['combo_id'],['combo_id'=>$_combo_ids, 'is_del'=>0]);
                $comboIdList = \array_values(\array_unique(\array_column($comboIdList, 'combo_id')));
                $res = $this->db->update('combo',['is_del'=>1],['AND'=>['combo_id'=> $comboIdList]]);
                if($res){
                    $sql2 = $this->db->last();
                    $this->db->update('combo_goods',['is_del'=>1],['AND'=>['combo_id'=>$_combo_ids]]);//套餐下架删除,套餐关联的商品也要下架
                }
                $show_msg = '删除成功，同时删除的套餐ID有'.implode(',', $comboIdList);
            }else{
                $show_msg = "删除成功";//只删除商品,没有套餐可以删除
            }

            //仅成功的日志进入mysql日志
            if($sql1){
                $log = [];
                $msg = '删除产品属性' . $id;
                $log['act_table'] = 'product_attr';
                $log['act_sql'] = $sql1;
                $log['act_desc'] = $msg;
                $log['act_time'] = time();
                $log['act_type'] = 'delete_product_attr';
                $log['product_id'] = $productAttr['product_id'];
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            if($sql2){
                $log = [];
                $msg = '非物理删除包含该产品的套餐' . implode(',', $_combo_ids);
                $log['act_table'] = 'combo';
                $log['act_sql'] = $sql2;
                $log['act_desc'] = $msg;
                $log['act_time'] = time();
                $log['act_type'] = 'del_combos';
                $log['product_id'] = $productAttr['product_id'];
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            if($sql3){
                $log = [];
                $msg = '删除该套餐包含该产品的可选属性' . $id;
                $log['act_table'] = 'combo_goods';
                $log['act_sql'] = $sql3;
                $log['act_desc'] = $msg;
                $log['act_time'] = time();
                $log['act_type'] = 'del_combo_goods_attr';
                $log['product_id'] = $productAttr['product_id'];
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            $this->db->pdo->commit();
            return ["ret" => 1, "msg" => $show_msg];
        } catch (\Exception $e) {
            $this->db->pdo->rollBack();
            return ["ret" => 0, "msg" => "删除失败"];
        }
    }

    public function addProductAttr($data)
    {
        $ret = $this->db->insert('product_attr', $data);
        return $ret;
    }

    /**
     * chenhk
     * 将产品属性遍历插入表
     * 获取每个插入的ID
     * 插入成功ID数组返回
     * @param $data
     * @return array
     */
    public function addForSingleProductAttr($data)
    {
        if (empty($data)) {
            return [];
        }

        $idArr = [];
        foreach ($data as $key => $item) {
            $ret = $this->db->insert('product_attr', $item);
            if ($ret) {
                //仅成功的日志进入mysql日志
                $sql = $this->db->last();
                $log = [];
                $msg = '更新产品属性';
                $log['act_table'] = 'product_attr';
                $log['act_sql'] = $sql;
                $log['act_desc'] = $msg;
                $log['act_time'] = time();
                $log['act_type'] = 'update_product_attr';
                $log['product_id'] = $item['product_id'];
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            $idArr[$key] = $ret;
        }
        return $idArr;
    }

    public function saveProductAttr($product_attr_id, $data, $product_id = '')
    {
        $map = ['product_attr_id' => $product_attr_id];
        $ret = $this->db->update('product_attr', $data, $map);
        if ($ret) {
            //仅成功的日志进入mysql日志
            $sql = $this->db->last();
            $log = [];
            $msg = '更新产品属性';
            $log['act_table'] = 'product_attr';
            $log['act_sql'] = $sql;
            $log['act_desc'] = $msg;
            $log['act_time'] = time();
            $log['act_type'] = 'update_product_attr';
            $log['product_id'] = $product_id;
            $log['act_loginid'] = $_SESSION['admin']['login_name'];
            $this->db->insert("product_act_logs", $log);
        }
    }

    /**
     * 上架/下架删除 按钮
     *
     * @param $product_id
     * @param array $data is_del={0,1}
     * @return array
     */
    public function delProduct($product_id, $data)
    {
        $map = ['product_id' => $product_id];
        $product = $this->db->get('product', ['domain', 'ad_member', 'identity_tag', 'theme', 'erp_number', 'new_erp', 'id_zone','oa_id_department'], $map);
        $this->db->pdo->beginTransaction();
        if (!$data['is_del']) {
            $ret = $this->domainCheck($product['domain'], $product['identity_tag'], $product_id);

            if (!$ret['ret']) {
                return ['ret' => 0, 'msg' => "恢复失败。=》" . $ret['msg']];
            }
        }
        if ($data['is_del']) {
            $data['del_time'] = date("Y-m-d H:i:s");
        }
        try {
            $sql2 = '';
            $ret = $this->db->update('product', $data, $map);
            if (!$ret) {
                $this->db->pdo->rollBack();
                return ['ret' => $ret, 'msg' => "恢复失败。=》数据库更改失败"];
            }
                $sql1 = $this->db->last();
                if (!$data['is_del']){ //上架操作
                    //check domain_department and product department
                    $ret = $this->checkDomain($product['domain']);
                    if (!$ret) {
                        return ['ret' => 0, 'msg' => "获取域名信息失败，稍后重试"];
                    }
                    $id_departments = [$product['oa_id_department']];
                    if($product['oa_id_department'] !=$_SESSION['admin']['id_department']){
                        $id_departments[] = $_SESSION['admin']['id_department'];
                    }
                    if ( !in_array($ret['erp_department_id'],$id_departments)) {
                        return ['ret' => 0, 'msg' => "恢复失败，该域名绑定部门与该产品部门不一致"];
                    }
                    // //后批次 转移人之后，产品恢复 且更新：产品部门与域名部门一致 // 不在这里做
                    // if(($ret['erp_department_id'] == $_SESSION['admin']['id_department']) &&  ($product['oa_id_department'] !=$_SESSION['admin']['id_department'])){
                    //     $this->db->update('product',['oa_id_department'=>$ret['erp_department_id']],['product_id'=>$product_id]);
                    // }

                    //check if can save product  恢复的时候 再次建档判断 仅对新产品
                    $seo_loginid = $this->db->get('oa_users', 'username', ['name_cn' => $product['ad_member'], 'username[!]' => '','password[!]' => '']);
                    if ($product['new_erp']) {
                        //$params = [trim($product['erp_number']), trim($product['domain']), $product['identity_tag'], $product['id_zone'], $product_id, $seo_loginid];
                        $res = $this->sendDomainToErp(trim($product['erp_number']), trim($product['domain']), $product['identity_tag'], $product['id_zone'], $product_id, $seo_loginid);
                        if ($res['ret']) {
                            // $key_product = 'PRO_'.$product['domain']."_".str_replace('/','',$product['identity_tag']);
                            // $cache->del($key_product);
                        } else {
                            $this->db->pdo->rollBack();
                            return ['ret' => 0, 'msg' => '恢复失败。erp-msg:=》' . $res['desc']];
                        }
                    }
                    $show_msg = '恢复成功';//恢复的时候 不做关联 套餐恢复
                }else{ //下架操作
                    //删除、下架的时候，产品关联的套餐一并删除--非物理删
                    $comboGoodsIdList = (array) $this->db->select('combo_goods','combo_id',['product_id'=>$product_id, 'is_del'=>0]);
                    $comboGoodsIdList = \array_values(\array_filter($comboGoodsIdList));
                    if( $comboGoodsIdList ){
                        $res = $this->db->update('combo',['is_del'=>1],['AND'=>['combo_id'=>$comboGoodsIdList]]);
                        if($res){
                            $sql2 = $this->db->last();
                            $this->db->update('combo_goods',['is_del'=>1],['AND'=>['combo_id'=>$comboGoodsIdList]]);//套餐下架,套餐的关联产品也要下架
                        }
                        $show_msg = '删除成功，同时删除的套餐id有'.implode(',', $comboGoodsIdList);
                    }else{
                        $show_msg = '删除成功，没有同时删除的套餐';
                    }
                }

            //仅成功的日志进入mysql日志
            if($sql1){
                $log = [];
                if ($data['is_del']) {
                    $msg = '删除产品-非物理删';
                } else {
                    $msg = '恢复产品';
                }
                $log['act_table'] = 'product';
                $log['act_sql'] = $sql1;
                $log['act_desc'] = $msg . $show_msg;
                $log['act_time'] = time();
                $log['act_type'] = 'delete_product';
                $log['product_id'] = $product_id;
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }
            if($sql2){
                $log = [];
                $msg = '非物理删除包含该产品的套餐';
                $log['act_table'] = 'combo';
                $log['act_sql'] = $sql2;
                $log['act_desc'] = $msg . $show_msg;
                $log['act_time'] = time();
                $log['act_type'] = 'del_combos';
                $log['product_id'] = $product_id;
                $log['act_loginid'] = $_SESSION['admin']['login_name'];
                $this->db->insert("product_act_logs", $log);
            }

            $this->db->pdo->commit();
            return ['ret' => 1, 'msg' => $show_msg];
        } catch (\Exception $e) {
            $_m = $data['is_del']?'删除':'恢复';
            return ['ret' => 0, 'msg' => $_m."失败。=》数据库更改失败"];
        }
    }

    //转移域名部门
    public function departmentChange($domain, $id)
    {
        $domain = array_unique($domain);
        $map = ['domain' => $domain];
        $save['id_department'] = $id;

        $ret = $this->db->update('product', $save, $map);
        if (!$ret) {
            return ['ret' => $ret, 'msg' => "转移失败" . $this->db->last()];
        }

        //写日志
        $log = new \lib\log();
        $log->write('admin', '操作人：' . $_SESSION['admin']['username'] . ' 转移部门：' . $id . " 转移域名：" . print_r($domain, 1));

        return ['ret' => 1, 'msg' => "OK"];
    }

    /**
     * 产品部门迁移
     * @param $products     迁移产品id数组
     * @param $target_id    目标aid
     * @return array
     */
    public function updateProductDepartment($products, $target_id)
    {
        $productIDs = array_unique($products);
        $map = ['product_id' => $productIDs];
        $updateData['aid'] = $target_id;

        $ret = $this->db->update('product', $updateData, $map);

        if (!$ret) {
            //写日志
            $log = new \lib\log();
            $log->write('admin', '操作人：' . $_SESSION['admin']['username'] . ' 转移部门失败：' . $target_id . " 转移产品ID：" . print_r($productIDs, 1) . '失败原因:' . print_r($this->db->last(), 1));
            return ['ret' => 0, 'msg' => "转移失败" . $this->db->last()];
        } else {
            //写日志
            $log = new \lib\log();
            $log->write('admin', '操作人：' . $_SESSION['admin']['username'] . ' 转移部门：' . $target_id . " 转移产品ID：" . print_r($productIDs, 1));
            return ['ret' => 1, 'msg' => "OK"];
        }
    }

    // 获取erp
    //域名信息
    public function getErpDomainInfo($domain)
    {
        // 老ERP接口关闭

        ##jade add
        // $company = new \admin\helper\company($this->register);  ##后台配置之后才能用
        // $objname = $company->getErpDomainObjname();
        $objname = '';
        // $objname = 'admin\helper\api\erpseo';
        /*
        if (!$objname) {
            $uri = 'http://erp.stosz.com:9090/Domain/Api/get_all?name=' . $domain;
            if (environment == 'office') {
                $uri = 'http://192.168.109.252:8081/Domain/Api/get_all?name=' . $domain;
            }
            $ret = file_get_contents($uri);
            $ret = json_decode($ret, true);
        } else {
            $params = ['company_id' => $_SESSION['admin']['company_id'], 'loginid' => $_SESSION['admin']['login_name'], 'name' => $domain];
            if (environment == 'office') {
                $obj = new $objname('dev');
            } else {
                $obj = new $objname();
            }

            $ret = $obj->getSeo($params);
        }

        return $ret;*/
        return [];
    }

    /**
     * @param $data
     * @return array
     * 复制一个产品
     */
    public function productCopy($data)
    {
        //find
        $id = $data['id'];
        $product = $this->db->get('product', '*', ['product_id' => $id, 'is_del' => 0]);
        if (!$product) {
            return ['ret' => 0, 'msg' => "产品不存在或已删除"];
        }
        $id_zone = $data['id_zone'];
        $product['identity_tag'] = trim($data['identity_tag']);

        //找到域名
        if ($product['domain'] != $data['domain']) {
            //判断修改的域名部门和被复制的产品部门是否一致
            $ret = $this->getErpDomainInfo($data['domain']);
            if (!$ret['status']) {
                return ['ret' => 0, 'msg' => "获取域名信息失败，稍后重试"];
            }
            $erpDomain = $ret['data'];
            if ($erpDomain['domain']['id_department'] != $product['id_department']) {
                return ['ret' => 0, 'msg' => "复制失败，该域名绑定部门与被复制产品部门不一致"];
            }
            $product['domain'] = $data['domain'];
        }

        $D = new \admin\helper\country($this->register);
        $C = new \admin\helper\currency($this->register);
        $zone = $D->getOne($id_zone);
        $currency = $C->getOne($zone['currency_id']);
        $product['id_zone'] = $id_zone;
        $product['currency'] = $currency['currency_code'];
        $product['currency_prefix'] = 0;
        $product['currency_code'] = $currency['symbol_right'];
        if ($currency['symbol_left']) {
            $product['currency_prefix'] = 1;
            $product['currency_code'] = $currency['symbol_left'];
        }
        $product['add_time'] = date("Y-m-d H:i:s", time());
        //开启事务
        $this->db->pdo->beginTransaction();
        //insert
        unset($product['product_id']);
        $product_id = $this->db->insert('product', $product);
        if (!$product_id) {
            $this->db->pdo->rollBack();
            return ['ret' => 0, 'msg' => '复制产品表失败'];
        }

        //仅成功的日志进入mysql日志
        $sql = $this->db->last();
        $log = [];
        $msg = '复制产品';
        $log['act_table'] = 'product';
        $log['act_sql'] = $sql;
        $log['act_desc'] = $msg;
        $log['act_time'] = time();
        $log['act_type'] = 'copy_insert_product';
        $log['product_id'] = $product_id;
        $log['act_loginid'] = $_SESSION['admin']['login_name'];
        $this->db->insert("product_act_logs", $log);

        //套餐
        $combo = $this->db->select('combo', '*', ['product_id' => $id, 'is_del' => 0]);
        if ($combo) {
            //套餐产品
            $combo_id = array_column($combo, 'combo_id');
            $combo_goods = $this->db->select('combo_goods', '*', ['combo_id' => $combo_id, 'is_del' => 0]);

            foreach ($combo as $c) {
                $old_combo_id = $c['combo_id'];
                unset($c['combo_id']);
                $c['product_id'] = $product_id;
                $add_combo_id = $this->db->insert('combo', $c);
                if (!$add_combo_id) {
                    $this->db->pdo->rollBack();
                    return ['ret' => 0, 'msg' => '复制套餐表失败'];
                }

                foreach ($combo_goods as $key => $goods) {
                    if ($goods['combo_id'] == $old_combo_id) {
                        unset($goods['combo_goods_id']);
                        $add_goods[$key] = $goods;
                        $add_goods[$key]['combo_id'] = $add_combo_id;
                    }
                }
            }

            if (!$add_goods) {
                $this->db->pdo->rollBack();
                return ['ret' => 0, 'msg' => '复制套餐表失败'];
            }

            $this->db->insert('combo_goods', $add_goods);
        }
        //产品图集
        $photos = $this->db->select('product_thumb', '*', ['product_id' => $id]);
        if ($photos) {
            foreach ($photos as $key => $photo) {
                unset($photo['thumb_id']);
                $photo['product_id'] = $product_id;
                $add_photos[] = $photo;
            }

            if ($add_photos) {
                $this->db->insert('product_thumb', $add_photos);
            }
        }

        //属性
        $attr = $this->db->select('product_attr', '*', ['product_id' => $id]);
        if ($attr) {
            foreach ($attr as $key => $attrs) {
                unset($attrs['product_attr_id']);
                $attrs['product_id'] = $product_id;
                $add_attr[] = $attrs;
            }

            if ($add_attr) {
                $this->db->insert('product_attr', $add_attr);
            }

        }

        //分类
        $cat = $this->db->get('product_category', '*', ['product_id' => $id]);
        if ($cat) {
            unset($cat['id']);
            $cat['product_id'] = $product_id;
            $this->db->insert('product_category', $cat);
        }

        $this->db->pdo->commit();

        return ['ret' => 1, 'msg' => '复制成功'];
    }

    /**
     * @param $data
     * @param $super_register
     * @return array
     * 复制一个产品
     */
    public function productCopyWithOriginImage($data, $super_register = null)
    {
        //find
        $id = $data['id'];
        $product = $this->db->get('product', '*', ['product_id' => $id, 'is_del' => 0]);
        if (!$product) return ['ret' => 0, 'msg' => "产品不存在或已删除"];

        // 原产品语言与目标语言一致才能复制评论
        $isComment = $data['is_comment'];
        unset($data['is_comment']);
        if (!empty($isComment) && $product['lang'] != $data['lang']) {
             return ['ret' => 0, 'msg' => "原产品语言与目标语言不一致,不能复制评论"];
        } 

        $id_zone = $data['id_zone'];
        $product['identity_tag'] = trim($data['identity_tag']);

        //找到域名
        //判断修改的域名部门和被复制的产品部门是否一致
        $ret = $this->checkDomain($data['domain']);
        if (!$ret) return ['ret' => 0, 'msg' => "获取域名信息失败，稍后重试"];

        //判断二级目录和域名是否已经存在
        $condition = ['domain' => $data['domain'], 'identity_tag' => trim($data['identity_tag'])];
        $retIdentify = $this->db->get('product', '*', $condition);
        if ($retIdentify) return ['ret' => 0, 'msg' => "复制失败,该域名下的二级目录已经存在"];

        //jimmy部门判断fix //$ret['erp_department_id'] != $_SESSION['admin']['id_department']
        $id_departments = [$product['oa_id_department']];
        if($product['oa_id_department'] !=$_SESSION['admin']['id_department']){
            $id_departments[] = $_SESSION['admin']['id_department'];
        }
        if ( !in_array($ret['erp_department_id'],$id_departments)) return ['ret' => 0, 'msg' => "复制失败，该域名绑定部门与被复制产品部门不一致"];

        $obj = new \admin\helper\api\sensitive();

        $sensitive = $product['waybill_title'].'|'.$product['sales_title'].'|';
        $product['domain'] = $data['domain'];
        $product['is_open_sms'] =0;
        $product['service_email'] = $ret['user_name'] . '@' . $ret['domain'];
        
        // 潜规则: 按地区是否强制开启短信(开放地区：台湾、泰国、柬埔寨、巴基斯坦、菲律宾、印尼)
        $D = new \admin\helper\country($this->register);
        $zone =  $D->getOne($id_zone);
        if ($_SESSION['admin']['company_id'] == 1 && $zone['is_force_open_sms'] == 'enable') {
            $product['is_open_sms'] = 1;
        }

        $C = new \admin\helper\currency($this->register);
        $currency = $C->getOne($zone['currency_id']);
        $product['id_zone']     = $id_zone;
        $product['photo_txt']   = $data['domain'] . '/' . trim($data['identity_tag']);
        $product['currency']    = $currency['currency_code'];
        $product['currency_prefix'] = 0;
        $product['theme']           = $data['theme'];
        $product['lang']            = $data['lang'];
        $product['currency_code']   = $currency['symbol_right'];
        if ($currency['symbol_left']) {
            $product['currency_prefix'] = 1;
            $product['currency_code'] = $currency['symbol_left'];
        }
        $product['new_erp']   =1;

        //属性和套餐原ID和插入ID
        $ori_attr_ids = [];
        $ori_combo_ids = [];
        $ori_photo_ids = [];

        $product['add_time'] = $product['last_utime'] = date("Y-m-d H:i:s", time());
        //开启事务
        $this->db->pdo->beginTransaction();
        //insert
        unset($product['product_id']);
        $product_id = $this->db->insert('product', $product);

        if (!$product_id) {
            $this->db->pdo->rollBack();
            return ['ret' => 0, 'msg' => '复制产品表失败'];
        }
        //仅成功的日志进入mysql日志
        $sql = $this->db->last();
        $log = [];
        $msg = '复制产品';
        $log['act_table'] = 'product';
        $log['act_sql'] = $sql;
        $log['act_desc'] = $msg;
        $log['act_time'] = time();
        $log['act_type'] = 'copy_insert_product';
        $log['product_id'] = $product_id;
        $log['act_loginid'] = $_SESSION['admin']['login_name'];
        $this->db->insert("product_act_logs", $log);
        //套餐
        $combo = $this->db->select('combo', '*', ['product_id' => $id, 'is_del' => 0]);
        if ($combo) {
            //套餐产品
            $combo_id = array_column($combo, 'combo_id');
            $combo_goods = $this->db->select('combo_goods', '*', ['combo_id' => $combo_id, 'is_del' => 0]);

            foreach ($combo as $c) {
                $old_combo_id = $c['combo_id'];
                unset($c['combo_id']);
                $c['product_id'] = $product_id;
                $add_combo_id = $this->db->insert('combo', $c);
                $ori_combo_ids[$old_combo_id] = $add_combo_id;
                if (!$add_combo_id) {
                    $this->db->pdo->rollBack();
                    return ['ret' => 0, 'msg' => '复制套餐表失败'];
                }

                $sensitive .= $c['title'].'|';
                foreach ($combo_goods as $key => $goods) {
                    $sensitive .=$goods['sale_title'].'|';

                    if ($goods['combo_id'] == $old_combo_id) {
                        unset($goods['combo_goods_id']);
                        $add_goods[$key] = $goods;
                        $add_goods[$key]['combo_id'] = $add_combo_id;
                    }
                }
            }

            if (!$add_goods) {
                $this->db->pdo->rollBack();
                return ['ret' => 0, 'msg' => '复制套餐表失败'];
            }

            $this->db->insert('combo_goods', $add_goods);
        }

        $results = $obj->getSensitive(['sensitive'=>$sensitive]);
        if(!empty($results)){
            $this->db->pdo->rollBack();
            echo json_encode(['ret'=>0,'msg'=>'产品外文名称或面单名称以及套餐内包含敏感词:'.$results]);
            exit;
        }

        //产品图集
        $photos = $this->db->select('product_thumb', '*', ['product_id' => $id]);
        if ($photos) {
            foreach ($photos as $key => $photo) {
                $temp = $photo['thumb_id'];
                unset($photo['thumb_id']);
                $photo['product_id'] = $product_id;
                $combo_id = $this->db->insert('product_thumb', $photo);
                $ori_photo_ids[$temp] = $combo_id;
            }
        }
        // 产品视频
        $videoModel = new \admin\helper\productvideo($this->register);
        $videoModel->copyProductVideo($id, $product_id);

        //属性
        $attr = $this->db->select('product_attr', '*', ['product_id' => $id]);
        if ($attr) {
            foreach ($attr as $key => $attrs) {
                $temp = $attrs['product_attr_id'];
                unset($attrs['product_attr_id']);
                $attrs['product_id'] = $product_id;
                $attr_id = $this->db->insert('product_attr', $attrs);

                $ori_attr_ids[$temp] = $attr_id;
            }
        }

        // 分类
        $cat = $this->db->get('product_category', '*', ['product_id' => $id]);
        if ($cat) {
            unset($cat['id']);
            $cat['product_id'] = $product_id;
            $this->db->insert('product_category', $cat);
        }

        // 产品评论
        if ($isComment) {
            $productComments = $this->db->select('product_comment', '*', ['product_id' => $id]);
            if (!empty($productComments)) {
                //
                foreach ($productComments as $key => $comment) {
                    $originCommentID = $comment['comment_id'];
                    $productCommentsThumbs = $this->db->select('product_comment_thumb', '*', ['comment_id' => $originCommentID]);
                    unset($comment['comment_id']);
                    $comment['product_id'] = $product_id;
                    // 逐个插入评论表
                    $newCommentID = $this->db->insert('product_comment', $comment);
                    if (!$newCommentID) {
                        $this->db->pdo->rollBack();
                        echo json_encode(['ret'=>0,'msg'=>'复制评论表失败']);
                        exit;
                    } else {
                        // 批量插入评论图片表
                        $newCommentsThumbsTmp = array_map(
                                                function($val) use($newCommentID){
                                                    unset($val['commont_thumb_id']);
                                                    $val['comment_id'] = $newCommentID;
                                                    return $val;
                                                }, 
                                                $productCommentsThumbs
                                            );
                        $commenThumbResult = $this->db->insert('product_comment_thumb', $newCommentsThumbsTmp);
                        if (!$commenThumbResult) {
                            $this->db->pdo->rollBack();
                            echo json_encode(['ret'=>0,'msg'=>'复制评论图片表失败']);
                            exit;
                        }
                    }

                }
                
            }
           
           
        }


        $ret = $this->updateProductImage($id, $product_id, $data, $ori_attr_ids, $ori_combo_ids, $ori_photo_ids);
        if (!$ret['ret']) {
            $this->db->pdo->rollBack();
            return $ret;
        }
        ## 建档判断
        $seo_loginid = $this->db->get('oa_users', 'username', ['name_cn' => $product['ad_member'], 'username[!]' => '','department[!]' => '','id_department[!]' => 0,'password[!]' => '']);
        if ($product['new_erp']) {
            $params = [trim($product['erp_number']), trim($product['domain']), $product['identity_tag'], $product['id_zone'], $product_id, $seo_loginid];
            $res = $this->sendDomainToErp(trim($product['erp_number']), trim($product['domain']), $product['identity_tag'], $product['id_zone'], $product_id, $seo_loginid);
            if ($res['ret']) {
                // $key_product = 'PRO_'.$product['domain']."_".str_replace('/','',$product['identity_tag']);
                // $cache->del($key_product);
            } else {
                $this->db->pdo->rollBack();
                return ['ret' => 0, 'msg' => '复制失败：建档提示：erp-msg:=》' . $res['desc']];
            }
        }

        $this->db->pdo->commit();
        return ['ret' => 1, 'msg' => '复制成功', 'product' => $product];
    }

    /**
     * [getThemesByIdzonAndLanguage 根据id_zone和language获取主题]
     * @param  [type] $idZone   [description]
     * @param  [type] $language [description]
     * @return [type]           [description]
     */
    public function getThemesByIdzonAndLanguage($idZone, $language, $id_department) {

        $res = false;

        if (empty($idZone) || empty($language) || empty($id_department)) {
            return $res;
        }

        //v4.35之前, belong_id_department tinyint(0){0全部部门,大于0指定部门ID}
        $sql = "select theme from `theme` where find_in_set('" . $idZone . "', zone) > 0
                    and find_in_set('" . $language . "', lang)>0 and belong_id_department in (0," . $id_department . ")
                    and is_del=0";

        //ZhuangDeBiao: v4.35需求,支持多个部门ID,varchar逗号分隔 http://jira.stosz.com/browse/DPZ-648
//        $sql =<<<GET_THEME_LIST
//select theme from `theme` where
//find_in_set('{$idZone}', `zone`) > 0 AND
//find_in_set('{$language}', `lang`) > 0 AND
//find_in_set('{$id_department}', `belong_id_department`) > 0 AND
//is_del = 0 limit 100;
//GET_THEME_LIST;

        $where = ["is_del=0","FIND_IN_SET('$idZone',zone)>0", "FIND_IN_SET('$language',lang)>0"];
        $departmentIdData = \array_unique(\explode(",", $id_department));
        if (is_array($departmentIdData) && $departmentIdData) {
            $departmentWhere = ["FIND_IN_SET('0',belong_id_department)>0"];
            foreach ($departmentIdData as $departmentId) {
                $departmentWhere[] = "FIND_IN_SET('$departmentId',belong_id_department)>0";
            }
            if($departmentWhere){
                $departmentWhere = implode(' OR ', $departmentWhere);
                $where[] = '('.$departmentWhere.')';
            }
        }
        $where = implode(' AND ', $where);
        $sql = "SELECT * FROM theme WHERE $where ORDER BY tid DESC";
        $query = $this->db->query($sql);
        if($query) {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $res;
        }
        return [];
    }

    public function updateProductImage($id, $product_id, $data, $ori_attr_ids, $ori_combo_ids, $ori_photo_ids)
    {
        //查询原图上传七牛
        $ogl_images = $this->db->select('product_original_thumb', '*', ['product_id' => $id]);
        if ($ogl_images) {
            $upload = new upload();
            $water_txt = $data['domain'] . '/' . $data['identity_tag'];
            $insert_data = [];

            //开启事务
            //   $this->db->pdo->beginTransaction();

            //循环遍历产品原图 上传图片和加水印
            foreach ($ogl_images as $key => $item) {
                //上传七牛
                $type = '';
                $thumb = $item['thumb'];
                $temp = [];
                switch ($item['type']) {
                    case 1:
                        $type = 'logo';
                        break;
                    case 2:
                        $type = 'thumb';
                        if (strpos($thumb, ',') !== false) {
                            $temp = explode(',', $thumb);
                            $thumb = end($temp);
                        }
                        break;
                    case 3:
                        $type = 'photos';
                        break;
                    case 4:
                        $type = 'attr';
                        if (strpos($thumb, ',') !== false) {
                            $temp = explode(',', $thumb);
                            $thumb = end($temp);
                        }
                        break;
                    case 5:
                        $type = 'combo';
                        break;
                    case 6:
                        $temp = explode(',', $thumb);
                        $thumb = end($temp);
                        break;
                    case 7:
                        $type = 'cover';
                        break;
                }
                if(strpos($thumb,'upload') !==false){
                    if (!file_exists(app_path . $thumb)) {
                        return ['ret' => 0, 'msg' => '没找到图片'.$thumb.'，复制失败'];
                    }

                    //加水印
                    $files = $upload->imgTxt($thumb, $water_txt);
                    if (!$files) {
                        return ['ret' => 0, 'msg' => '添加水印失败'];
                    }
                    //上传到7牛
                    $result = qiniu::uploadImgTxt($type, $files);
                    if ($result['state'] != 'SUCCESS') {
                        return ['ret' => 0, 'msg' => '图片上传失败'];

                    }
                    $url = qiniu::changImgDomain($result['url']);
                }else{
                    $url = $thumb;
                }
                $data = [];
                $fg_id = 0;
                $ogi_thumb = $item['thumb'];

                //根据图片类型 做相应更新处理
                switch ($item['type']) {
                    case 1:
                        $data = [];
                        $data['logo'] = $url;
                        $this->db->update('product', $data, array('product_id' => $product_id));
                        break;
                    case 2:
                        $data['thumb'] = $url;
                        $this->db->update('product', $data, array('product_id' => $product_id));
                        break;
                    case 3:
                        $data = [];
                        $data['thumb'] = $url;
                        $fg_id = $ori_photo_ids[$item['fg_id']];
                        $this->db->update('product_thumb', $data, array('product_id' => $product_id, 'thumb_id' => $fg_id));
                        break;
                    case 4:
                        $data = [];
                        $data['thumb'] = $url;
                        $fg_id = $ori_attr_ids[$item['fg_id']];
                        $this->db->update('product_attr', $data, array('product_id' => $product_id, 'product_attr_id' => $fg_id));
                        break;
                    case 5:
                        $data = [];
                        $data['thumb'] = $url;
                        $fg_id = $ori_combo_ids[$item['fg_id']];
                        $this->db->update('combo', $data, array('product_id' => $product_id, 'combo_id' => $fg_id));
                        break;
                    case 6:
                        $content = $this->db->get('product', 'content', array('product_id' => $product_id));
                        if (strpos($content, $temp[0])) {

                            //进行老数据兼容
                            $imageUrl = '';
                            if(stripos($url,'http') === false && stripos($url,'$imageUrl') === false)$imageUrl = '$imageUrl';
                            $data = [];
                            if($imageUrl){
                                if(stripos($temp[0],'$imageUrl') === false){
                                    $data['content'] = str_replace($imageUrl.$temp[0], $imageUrl.$url, $content);
                                }else{
                                    $data['content'] = str_replace($temp[0], $imageUrl.$url, $content);
                                }
                            }else{
                                    $data['content'] = str_replace($temp[0], $url, $content);
                            }
                            
                            $this->db->update('product', $data, array('product_id' => $product_id));
                            $ogi_thumb = $url . ',' . $temp[1];
                        }
                        break;
                    case 7:
                        $data = [];
                        $data['cover_url'] = $url;
                        $this->db->update('product_video', $data, array('product_id' => $product_id));
                        break;
                }

                //重新封装图片数据 保存到产品原图表
                $temp_data['product_id'] = $product_id;
                $temp_data['type'] = $item['type'];
                $temp_data['fg_id'] = $fg_id;
                $temp_data['thumb'] = $ogi_thumb;
                $temp_data['add_time'] = time();
                $insert_data[] = $temp_data;
            }
            //将产品所有原图保存到产品原图表
            $this->db->insert('product_original_thumb', $insert_data);
        }
        return ['ret' => 1];
    }

    public function sendDomainToErp($erp_id, $domain, $tags, $id_zone, $product_id = '', $seo_loginid = '')
    {
        ##jade add
        // $company = new \admin\helper\company($this->register);  ##后台配置之后才能用
        // $objname = $company->getErpDomainObjname();
        $id_zone = $this->db->get('zone', 'erp_id_zone', ['id_zone' => $id_zone]);
        $objname = 'admin\helper\api\erpdomain';
        $params = ['company_id' => $_SESSION['admin']['company_id'], 'loginid' => $_SESSION['admin']['login_name'], 'domain' => $domain, 'erp_id' => $erp_id, 'tags' => $tags, 'zone_id' => (int)$id_zone, 'product_id' => $product_id, 'seo_loginid' => $seo_loginid];

        $obj = new $objname();
        $ret = $obj->sendDomain($params);
        //$this->log->write('product',print_r($params,1).'=>'.print_r($ret,1));
        return $ret;
    }


    public function getDomainIdDepartment($domain)
    {
        ##jade add
        // $id_department
        // $department = file_get_contents();
        $url = 'http://domain.stosz.com/Home/Api/getDomainDepartment';
        $data = ['domain' => $domain];
        $res = $this->sendPost($url, $data);
        $res = json_decode($res['message'], 1);
        $res['msg'] = $res['message'];
        return $res;
    }

    protected function sendPost($url, $data, $headers = [])
    {
        #'Content-Type:application/x-www-form-urlencoded'
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($curl);
        $retdata['status'] = 1;

        if ($error = curl_error($curl)) {
            $retdata['status'] = 0;
            $retdata['message'] = $error;
        } else {
            $retdata['message'] = $result;
        }
        curl_close($curl);
        return $retdata;
    }

    public function getZoneList($zone_names)
    {
        $zone_names = array_unique($zone_names);
        $res = $this->db->select('zone', ['id_zone', 'title', 'code'], ['AND' => ['title' => $zone_names]]);
        return $res;
    }

    public function getZoneIdList($zone_names)
    {
        $zone_names = array_unique($zone_names);
        $res = $this->db->select('zone', 'id_zone', ['AND' => ['title' => $zone_names]]);
        return $res;
    }

    /**
     * chenhk
     * 保存或者更新产品扩展表中的google数据
     * @param int $product_id
     * @param $data
     * @return bool
     */
    public function saveProductGoogleExt($product_id, $data)
    {
        if (empty($data)) {
            return false;
        }

        $ret = $this->db->has('product_ext', ['product_id' => $product_id]);
        if (empty($ret)) {
            //保存
            $res = $this->db->insert('product_ext', $data);
            return $res;
        } else {
            //更新
            $this->db->update('product_ext', $data, ['product_id' => $product_id]);
        }
    }

    /**
     * 根据产品ID查询google数据
     * @param $product_id
     * @return array
     */
    public function getProductGoogleExt($product_id)
    {
        if (empty($product_id)) {
            return [];
        };

        $ret = $this->db->get('product_ext', '*', ['product_id' => $product_id]);

        if (empty($ret)) {
            return [];
        } else {
            return $ret;
        }
    }

    /**
     * chenhk
     * 值增加产品保存用,其他不用
     * @param $product_id
     * @param $data
     * @return bool
     */
    public function addProductOriginalImage($product_id, $data)
    {
        if (empty($data)) {
            return false;
        }
        $this->db->insert('product_original_thumb', $data);
    }

    /**
     * chenhk
     * 更新产品 LOGO 缩略图
     * @param $data
     * @param $condition
     */
    public function updateProductOriginalImage($data, $condition)
    {
        $ret = $this->db->has('product_original_thumb', $condition);
        if ($ret) {
            $this->db->update('product_original_thumb', $data, $condition);
        }
    }

    /**
     * [updateOrCreateProductOriginalImage 存在就更新， 不存在就创建视频cover原始图片]
     * @param  [type] $data      [description]
     * @param  [type] $condition [description]
     * @return [type]            [description]
     */
    public function updateOrCreateProductOriginalImage($data, $condition)
    {
        $ret = $this->db->has('product_original_thumb', $condition);
        if ($ret) {
            $this->db->update('product_original_thumb', $data, $condition);
        } else {
            $data = array_merge($data, $condition);
            $this->db->insert('product_original_thumb', $data);
        }
    }

    /**
     * [updateProductVideo 更新产品视频以及视频封面]
     * @param  [type] $data      [需要更新的数据]
     * @param  [type] $condition [更新数据的条件]
     * @return [type]            []
     */
    public function updateProductVideo($data, $condition)
    {
        $ret = $this->db->has('product_original_thumb', $condition);
        if ($ret) {
            $this->db->update('product_original_thumb', $data, $condition);
        }
    }

    /**
     * chenhk
     * 值增加产品保存用,其他不用
     * @param $data
     */
    public function saveProductOriginalImage($data)
    {
        $this->db->insert('product_original_thumb', $data);
    }

    /**
     *
     * 通过域名管理系统--检测域名是否可用
     * @param string domain
     * @return bool
     */
    function checkDomain($domain)
    {
        $objname = 'admin\helper\api\domain';
        $obj = new $objname();
        $ret = $obj->getDomain(['domain' => $domain]);
        return $ret;
    }

    /**
     *
     * 通过域名管理系统--获取优化师 对应域名列表
     * @param string domain
     * @return array
     */
    function getSeoDomain($params)
    {
        $objname = 'admin\helper\api\domain';
        $obj = new $objname();
        $ret = $obj->getSeoDomain($params);
        return $ret;
    }

    public function checkThemeDiy($product_id, $theme)
    {
        $map = ['product_id' => $product_id];
        $themeDiy = $this->db->get('theme_diy', '*', $map);
        if ($themeDiy) {
            $themes = $this->db->get('product', 'theme', $map);
            if ($themes != $theme) {
                $this->db->delete('theme_diy', $map);
            }
        }
    }

    function getZoneById($id_zone)
    {
        return $this->db->get('zone', 'title', ['id_zone' => $id_zone]);
    }

    function getZoneByZoneId($id_zone)
    {
        return $this->db->get('zone', 'code', ['id_zone' => $id_zone]);
    }

    function getAvailableZonesById($id_zones)
    {
        if (empty($id_zones)) {
            return '';
        }
        $list = $this->db->select('zone', 'title', ['AND' => ['id_zone' => explode(',', $id_zones)]]);
        return $list ? implode($list, ',') : '';
    }

    function getNameCnById($uid)
    {
        return $this->db->get('oa_users', 'name_cn', ['uid' => $uid]);
    }

    function getColumnName()
    {
        return [
            'product_attr' => '属性',
            'theme' => '模版',
            'ad_member_id' => '优化师id',
            'ad_member' => '优化师',
            'aid' => '建站人员',
            // 'theme'=>'模版',
            'identity_tag' => '二级目录',
            'id_zone' => '地区',
            'available_zone_ids' => '限制可投放地区',
            'id_lang' => '语言',
            'is_open_sms' => '是否开启短信验证',
            'sales_title' => '产品外文名称',
            'seo_title' => 'seo标题',
            'seo_description' => 'seo描述',
            'price' => '价格',
            'market_price' => '市场价',
            'discount' => '折扣',
            'currency' => '货币',
            'currency_prefix' => '货币前缀',
            'currency_code' => '货币编码',
            'lang' => '语言编码',
            'product_category' => '产品分类',
            'payment_ocean' => '钱海支付',
            'payment_underline' => '线下支付',
            'payment_blue' => 'Pay Blue',
            'payment_online' => '在线支付易极付',
            'sales' => '销量',
            'stock' => '库存',
            'service_email' => '联系邮箱',
            'service_contact_id' => 'pop800 id',
            'photo_txt' => '水印文字',
            'logoUrl' => 'logo图片',
            'originalLogoUrl' => '原始logo图片',
            'thumbsUrl' => '缩略图',
            'originalThumbsUrl' => '原始缩略图',
            'photos' => '图集',
            'original_photos' => '原始图集',
            // 'up_attr_group_title'=>'二级目录',
            'up_name' => '属性组名称',
            'add_up_name' => '新增属性值名称',
            'del_up_name' => '删除属性值名称',
            // 'combo'
            'fb_px' => 'FB通用像素',
            'la' => '51la的js代码',
            'tips' => '购买提示',
            'google_js' => 'Google追踪代码',
            'google_analytics_id' => 'Google Analytics ID',
            'google_conversion_id' => 'Google转化ID',
            'google_conversion_label' => 'Google转化Label',
            'yahoo_js' => 'yahoo追踪代码',
            'yahoo_js_trigger' => 'yahoo追踪代码-触发',
            'content' => '页面内容',

        ];
    }

    function saveBILink($params){
        $data = [];
        if(!empty($params['id'])){
            $data['id'] = $params['id'];
        }
        $data['product_id'] = $params['product_id'];
        $data['ad_new_channel'] = $params['ad_new_channel'];
        $data['ad_channel'] = $params['ad_channel'];
        $data['ad_media'] = $params['ad_media'];
//        $data['ad_group'] = $params['ad_group'];
        $data['ad_series'] = empty($params['ad_series'])?'':$params['ad_series'];
        $data['ad_name'] = $params['ad_name'];
        $data['ad_bilink'] = $params['ad_bilink'];
        $data['ad_id_department'] = $params['ad_id_department'];
        $data['ad_loginid'] = empty($params['ad_loginid'])?'':$params['ad_loginid'];
        $data['ad_loginname'] = empty($params['ad_loginname'])?'':$params['ad_loginname'];
        $data['ad_member'] = empty($params['ad_member'])?'':$params['ad_member'];
        $data['loginid'] = $_SESSION['admin']['login_name'];
        $t = time();
        $data['add_time'] = $t;
        if(!empty($data['id']) ){
            if($_r = $this->db->get('product_bilink','product_id',['id'=>$data['id']])){
                $res = $this->db->update('product_bilink',$data,['id'=>$data['id']]);
                $r = ['res'=>'succ','data'=>['msg'=>'更新保存成功']];
            }else{
                $r = ['res'=>'succ','data'=>['msg'=>'更新保存失败，id错误']];
            }
        }else{
            $data['times'] = 1 + (int)$this->db->max('product_bilink','times',['product_id'=>$params['product_id']]);
            if($res = $this->db->insert('product_bilink',$data)){
                $r = ['res'=>'succ','data'=>['msg'=>'新增保存成功']];
            }else{
                $r = ['res'=>'fail','data'=>['msg'=>'新增保存失败']];
            }
        }
        $sql = $this->db->last();
        $this->log->write('product', $sql . print_r($r, 1));
        return $r;
    }

    function getBILink($params){
        $res = $this->db->pageSelect('product_bilink','*',$params);
        $ad_department = '';

        $departmentID = $res['goodsList'][0]['ad_id_department'];
        if(!empty($departmentID)){
            $ad_department = $this->db->get('oa_users','department',['id_department'=>$departmentID,'department[!]'=>'']);
            $depMemberLists = $this->db->select('oa_users',['uid','username','name_cn'],['id_department'=>$departmentID]);
        }else{
            $departmentID = $this->db->get('product','oa_id_department', ['product_id'=>$params['product_id']]);
            $depMemberLists = $this->db->select('oa_users',['uid','username','name_cn'],['id_department'=>$departmentID]);
        }

        $adMember = $res['goodsList'][0]['ad_member'];
        if (empty($adMember)) {
            $adMember = $this->db->get('product','ad_member_id', ['product_id'=>$params['product_id']]);
        }
        if(is_array($res['goodsList']) && (count($res['goodsList']) >0)){
            $r = ['res'=>'succ','data'=>['bidata'=>$res,
                                         'ad_department'=>$ad_department,
                                         'dep_member_lists' => $depMemberLists,
                                         'ad_member' => $adMember,
                                     ]];
        }else{
            $r = ['res'=>'fail','data'=>['bidata'=>[]],'msg'=>'no data found'];
        }
        // $sql = $this->db->last();
        // $this->log->write('product', $sql . print_r($r, 1));
        return $r;
    }

    function getProductExtData($params){
        $res = [];
        $department = '';
        $data['product_id'] = $params['product_id'];
        $filter = $field = [];
        $p =  $this->db->get('product',['ad_member_id','oa_id_department','ad_member','domain','identity_tag'],['product_id'=>$params['product_id']]);
        if(!empty($p['oa_id_department'])){
            
            $department = $this->db->get('oa_users',['uid','department'],['username[!]'=>'','department[!]'=>'','id_department'=>$p['oa_id_department']]);
            $departmentMembers = $this->db->select('oa_users',['uid','username','name_cn'],['id_department'=>$p['oa_id_department']]);
        }
        if(!empty($p['ad_member'])){
            $filter['name_cn'] = $p['ad_member'];
            $filter['username[!]'] = '';
            $filter['id_department[!]'] = 0;
            $filter['department[!]'] = '';
            $field[] = 'username(loginid)';
        }
        $res = $this->db->get('oa_users',$field,$filter);
        if($res){
            $res['department'] = $department['department'];
            $res['total_times'] = (int)$this->db->max('product_bilink','times',['product_id'=>$params['product_id']]);
            $res['domain'] = $p['domain'];
            $res['ad_member'] =  $p['ad_member_id'];
            $res['dep_member_lists'] = $departmentMembers;
            $res['identity_tag'] = $p['identity_tag'];
            $r = ['res'=>'succ','data'=>['extdata'=>$res]];
        }else{
            $r = ['res'=>'fail','data'=>['extdata'=>[]],'msg'=>'no data found'];
        }
        $sql = $this->db->last();
        $this->log->write('product', $sql . print_r($r, 1));
        return $r;
    }

    /**
     * 产品 部门迁移，#转移之前判断
     * @param $params 参数数组
     * @return boolean
     */
    function checkoutToNewDepartment($params){
        $data = [];
        $data['oa_id_department'] = $params['oa_id_department'];
        $data['department'] = $params['department'];
        $data['succ_list'] = [];
        $data['fail_list'] = [];
        $params['list'] = explode(',', $params['list']);
        // $params['list'] = ['www.livestou.com/kljjkjk5665'];
        if($params['list'] && ($params['list'] = array_filter($params['list'])) ){
            foreach ($params['list'] as $value) {
                $site_url = [];
                $pos = strpos($value,'/');
                if($pos){
                    $site_url[0] = substr($value, 0,$pos);
                    $site_url[1] = substr($value, $pos+1);
                }else{
                    $site_url[0] = $value;
                    $site_url[1] = '';
                }
                $_pdata = $this->db->select('product',['product_id','ad_member'],['domain'=>trim($site_url[0]),'identity_tag'=>trim($site_url[1])]);
                if(is_array($_pdata) && (count($_pdata)>0) ){
                    foreach ($_pdata as $p) {
                        $_d_id_department = $this->db->get('oa_users','id_department',['name_cn'=>$p['ad_member'],'username[!]'=>'','id_department[!]'=>0,'department[!]'=>'']);
                        if($_d_id_department != $params['oa_id_department']){
                            $data['fail_list'][] = ['product_id'=>$p['product_id'],'ad_member'=>$p['ad_member'],'site_url'=>$value,'msg'=>'优化师不在待转移部门'];
                        }else {
                            $ret = $this->checkDomain(trim($site_url[0]));
                            if(!empty($ret['erp_department_id']) && ($ret['erp_department_id'] != $params['oa_id_department'])){
                                $data['fail_list'][] = ['product_id'=>$p['product_id'],'ad_member'=>$p['ad_member'],'site_url'=>$value,'msg'=>'域名不在待转移部门'];
                            }else{
                                $data['succ_list'][] = ['product_id'=>$p['product_id'],'ad_member'=>$p['ad_member'],'site_url'=>$value,'msg'=>''];
                            }
                        }
                    }
                }else{
                    $data['fail_list'][] = ['product_id'=>0,'ad_member'=>'','site_url'=>$value,'msg'=>'站点不存在!'];
                }
            }
        }
        if($data['fail_list']){
            $data['ret'] = 0;
            return $data;
        }
        $data['del_product_list'] = [];
        $data['del_combo_list'] = [];
        if($data['succ_list']){
            $product_ids = array_column($data['succ_list'], 'product_id');
            $domain_list = $this->db->select('product','domain',['AND'=>['product_id'=>$product_ids]]);
            // 查出 没有转过去的 所有域名 相关产品id
            $del_product_list = $this->db->select('product',['product_id','domain','identity_tag','ad_member','is_del','oa_id_department'],['AND'=>['domain'=>$domain_list,'oa_id_department[!]'=>$params['oa_id_department'],'product_id[!]'=>$product_ids]]);
            if($del_product_list){
                $joinCondition = ['[>]combo'=>['combo_id'=>'combo_id']];
                $fields = ['combo.product_id','combo_goods.combo_id','combo.title','combo.is_del'];
                $del_combo_list = $this->db->select('combo_goods',$joinCondition,$fields,['AND'=>['combo_goods.product_id'=>array_column($del_product_list, 'product_id')]]);
                $data['del_product_list'] = $del_product_list;
                $data['del_combo_list'] = $del_combo_list?$del_combo_list:[];
            }
        }

        $data['ret'] = 1;
        return $data;
    }

    /**
     * 产品 部门迁移，按域名条件
     * @param $params 参数数组
     * @return boolean
     */
    function changeNewDepartment($params){
        $data['oa_id_department'] = $params['oa_id_department'];
        $data['department'] = $params['department'];
        // $params['list'] = [1111];
        $product_ids = explode(',', $params['list']);
        // $params['list'] = ['www.livestou.com/kljjkjk5665'];
        if($product_ids && ($product_ids = array_filter($product_ids)) ){
            try {
                $this->db->pdo->beginTransaction();
                $res = $this->db->update('product',['oa_id_department'=>$params['oa_id_department']],['AND'=>['product_id'=>$product_ids]]);
                $sql = $this->db->last();
                $this->log->write('product', $sql . print_r($params, 1));
                if($res){
                    //仅成功的日志进入mysql日志
                    $log = [];
                    $msg = '批量更新产品所属部门';
                    $log['act_table'] = 'product';
                    $log['act_sql'] = $sql;
                    $log['act_desc'] = $msg;
                    $log['act_type'] = 'group_update_product';
                    $log['act_loginid'] = $_SESSION['admin']['login_name'];
                    $log['act_time'] = time();
                    foreach ($product_ids as $k => $v) {
                        $log['product_id'] = $v;
                        $this->db->insert("product_act_logs", $log);
                    }
                }
                $this->db->pdo->commit();
                $this->db->pdo->beginTransaction();
                // 查出 转产品用到的所有域名
                $domain_list = $this->db->select('product','domain',['AND'=>['product_id'=>$product_ids]]);
                // 查出 没有转过去的 所有域名 相关产品id
                $del_pid_list = $this->db->select('product','product_id',['AND'=>['domain'=>$domain_list,'oa_id_department[!]'=>$params['oa_id_department']]]);
                // 下架 其它产品--没有转过去的所有的域名 相关产品id
                $res = $this->db->update('product',['is_del'=>1,'del_time'=>date("Y-m-d H:i:s")],['AND'=>['product_id'=>$del_pid_list]]);
                $sql = $this->db->last();
                $this->log->write('product', $sql . print_r($params, 1));
                if($res){
                    //仅成功的日志进入mysql日志
                    $log = [];
                    $msg = '批量下架失效域名对应产品';
                    $log['act_table'] = 'product';
                    $log['act_sql'] = $sql;
                    $log['act_desc'] = $msg;
                    $log['act_type'] = 'group_del_product';
                    $log['act_loginid'] = $_SESSION['admin']['login_name'];
                    $log['act_time'] = time();
                    foreach ($product_ids as $k => $v) {
                        $log['product_id'] = $v;
                        $this->db->insert("product_act_logs", $log);
                    }
                }
                if($del_pid_list){
                    $_combo_ids = $this->db->select('combo_goods','combo_id',['AND'=>['product_id'=>$del_pid_list],'is_del'=>0]);
                    if($_combo_ids){
                        $res = $this->db->update('combo',['is_del'=>1],['AND'=>['combo_id'=>$_combo_ids]]);
                        if($res){
                            $sql = $this->db->last();
                            $this->db->update('combo_goods',['is_del'=>1],['AND'=>['combo_id'=>$_combo_ids]]);//套餐下架删除,套餐关联的商品也要下架
                            //仅成功的日志进入mysql日志
                            $log = [];
                            $msg = '非物理删除包含该产品的套餐';
                            $log['act_table'] = 'combo';
                            $log['act_sql'] = $sql;
                            $log['act_desc'] = $msg . ' 受影响的套餐id有' . implode(',', $_combo_ids);
                            $log['act_time'] = time();
                            $log['act_type'] = 'del_combos';
                            $log['act_loginid'] = $_SESSION['admin']['login_name'];
                            foreach ($del_pid_list as $product_id) {
                                $log['product_id'] = $product_id;
                                $this->db->insert("product_act_logs", $log);
                            }   
                        }
                    }
                }
                // $this->db->pdo->rollBack();
                $this->db->pdo->commit();
                return ['ret' => 1];
            } catch (Exception $e) {
                $this->db->pdo->rollBack();
                return ['ret' => 0, 'msg' => "处理异常"];
            }
            return ['ret' => 0, 'msg' => "出现未知异常"];
        }else{
            return ['ret' => 0, 'msg' => "没有数据"];
        }
        
        

    }

    public function getAllDepartment(){
        $department = $this->db->query("SELECT DISTINCT id_department,department FROM oa_users WHERE department!='' AND id_department!=0 AND username!=''");
        if(!$department || !$department = $department->fetchAll()){
            return [];
        }
        return $department;
    }

    /**
     * 获取套餐产品属性数据
     * @param type $product_id
     * @param type $attr_id_desc
     * @return type
     */
    public function getComboProductAttr($product_id, $attr_id_desc = '') {
        $product_attr = $this->db->select('product_attr', '*', ['product_id' => $product_id, 'is_del' => 0, 'ORDER' => ['attr_group_id' => 'ASC']]);
        $product_available_attr = [];
        $_available_attr_ids = [];
        if ($attr_id_desc && ($product_available_attr = json_decode($attr_id_desc, 1) )) {
            foreach ($product_available_attr as $k => $v) {
                $_available_attr_ids = array_merge($_available_attr_ids, $v);
            }
        }
        if ($product_attr) {
            foreach ($product_attr as $k => &$v) {

                if (empty($_available_attr_ids)) {
                    $v['checked'] = 1;
                } else {
                    if (in_array($v['product_attr_id'], $_available_attr_ids)) {
                        $v['checked'] = 1;
                    }
                }

                $v['thumb'] = \admin\helper\qiniu::get_image_path($v['thumb']);
            }
        }
        return $product_attr ? $product_attr : [];
        // $data = [];
        // $product_attr?$product_attr:[];
        // $c = count($product_attr);
        // $page_c = ceil($c/20);
        // if($c <= 20){
        //     $data['page'] = 1;
        //     $data['pageCount'] = 1;
        //     $data['goodsList'] = $product_attr;
        //     $data['count'] = $c;
        //     $data['pageHtml'] = $this->db->Pagebarht($_GET,20,$data['page'],$data['count']);
        // }else{
        //     $p = ((int)$_REQUEST['p']>0)?(int)$_REQUEST['p']:1;
        //     $data['page'] = ($p>=$page_c)?$page_c:$p;
        //     $data['pageCount'] = $page_c;
        //     $data['goodsList'] = array_slice($product_attr,$p-1,20);
        //     $data['count'] = $c;
        //     $data['pageHtml'] = $this->db->Pagebarht($_GET,20,$data['page'],$data['count']);
        // }
        // return $data;
    }

    public function getRelComboByAttr($product_id,$product_attr_id){
        if(!$product_id || !$product_attr_id){
            return [];
        }
        $attr_group_id = $this->db->get('product_attr','attr_group_id',['product_attr_id'=>$product_attr_id]);
        $joinCondition = ['[>]combo_goods'=>['combo_id'=>'combo_id'],'[>]product'=>['product_id'=>'product_id']];
        $fields = ['combo_goods.combo_id','combo.title','combo.product_id','combo.thumb','combo_goods.attr_id_desc','product.title(product_title)'];
        $combo_list = $this->db->select('combo', $joinCondition,$fields,[
            //TODO 还原代码, 给combo_goods.product_id加索引
            'combo_goods.product_id'=>$product_id,
            'combo.is_del'=>0
        ]);
        $data = [];
        if($combo_list){
            foreach ($combo_list as $k => $v) {
                if($v['attr_id_desc'] && ($_attr_id_desc = json_decode($v['attr_id_desc'],1) ) ){
                    if(in_array($product_attr_id,$_attr_id_desc[$attr_group_id])){
                        if(count($_attr_id_desc[$attr_group_id]) ==1 ){
                            $v['del'] = 1;
                        }
                        $data[] = $v;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 提示用户: 如果删除该产品,下列产品中的套餐将失效,是否删除?
     * 返回: 套餐ID, 套餐名称, 套餐主产品ID, 套餐主产品名称, 套餐图片, 子产品attr_id_desc
     *
     * @param int $productId 子产品ID
     * @return array
     */
    public function getRelComboByPid($productId){
        if(!$productId){
            return [];
        }

        $comboGoodsList = (array) $this->db->select('combo_goods',
            //套餐ID, 子产品attr_id_desc
            ['combo_id', 'attr_id_desc'],
            ['product_id'=> $productId, 'is_del' => 0, 'ORDER' => 'combo_id']
        );
        foreach($comboGoodsList as $index => &$comboGoodsInfo){
            //套餐名称, 套餐主产品ID,  套餐图片
            $comboInfo = (array) $this->db->get('combo',  ['product_id', 'title','thumb'], [
                'combo_id' => $comboGoodsInfo['combo_id'], 'is_del' => 0
            ] );
            if(empty($comboInfo)){
                unset($comboGoodsList[$index]);
                continue 1;
            }

            //套餐主产品名称
            $productTitle = (string) $this->db->get('product',  'title', [
                'product_id' => $comboInfo['product_id']
            ] );
            if(empty($productTitle)){
                unset($comboGoodsList[$index]);
                continue 1;
            }

            $comboGoodsInfo = \array_merge($comboGoodsInfo, $comboInfo);
            $comboGoodsInfo['product_title'] = $productTitle;
        }
        $comboGoodsList = \array_values($comboGoodsList);

        return $comboGoodsList;

//        禁止联表操作
//        $joinCondition = ['[>]combo_goods'=>['combo_id'=>'combo_id'],'[>]product'=>['product_id'=>'product_id']];
//        $fields = ['combo_goods.combo_id','combo.title','combo.product_id','combo.thumb','combo_goods.attr_id_desc','product.title(product_title)'];
//        $combo_list = $this->db->select('combo', $joinCondition,$fields,[
//            'combo_goods.product_id'=>$productId,
//            'combo.is_del'=>0,
//            'GROUP'=>'combo_id'
//        ]);


//        return $combo_list?$combo_list:[];
    }

    function saveProductLog($data){
        $this->db->insert("product_act_logs", $data);
    }

    function attrCheckout($product_id, $attr_id_desc) {

        if (empty($attr_id_desc)) {//如果没有属性，就直接返回
            return '';
        }

        $attr_id_desc = json_decode($attr_id_desc, 1);
        if (empty($attr_id_desc)) {//如果没有属性，就直接返回
            return '';
        }

        //获取产品属性数据
        $product_attr = $this->db->select('product_attr', ['product_attr_id', 'attr_group_id'], ['product_id' => $product_id, 'is_del' => 0, 'ORDER' => ['attr_group_id' => 'ASC']]);
        $_available_attr = []; //有效的属性id
        $_available_group = []; //有效的属性组id
        foreach ($product_attr as $k => $v) {
            $attr_group_id = $v['attr_group_id'];
            if (!in_array($attr_group_id, $_available_group)) {
                $_available_group[] = $attr_group_id; //有效的属性组id
                $_available_attr[$attr_group_id] = []; //有效的属性id 初始化
            }
            $_available_attr[$attr_group_id][] = (int) $v['product_attr_id']; //有效的属性id
        }

        foreach ($attr_id_desc as $k => &$v) {
            if (!in_array($k, $_available_group)) {
                unset($attr_id_desc[$k]); //attr_id_desc 删除出现多余的属性组
            } else {
                $attr_id_desc[$k] = array_intersect($v, $_available_attr[$k]);
                if (!$attr_id_desc[$k]) {
                    return false; //属性组 交集属性为空，套餐应当删除
                }
            }
        }

        if (empty($attr_id_desc)) {
            return '';
        }

        return json_encode($attr_id_desc);
    }

    /**
     * [selectedProductList 查询相关信息]
     * @param  Array|array $filter [description]
     * @return [type]              [description]
     */
    function selectedProductList(Array $filter = []){
        if (!empty($filter)) {
            $sql = 'select * from `product` where product_id in ('. implode(',', $filter) .')';
            $data = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            return $data;
        } else {
            return null;
        }
    }

    

    /**
     * [getDepartmentList 获取所有的部门]
     * @return [type] [description]
     */
    function getDepartmentList()
    {
        $sql = "SELECT id_department,department FROM `oa_users`  GROUP BY id_department;";
        $data = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $tmp = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                if (!empty($value['department'])) {
                    array_push($tmp, $value);
                }
            }
        }
        unset($data);
        return $tmp;
    }


    /**
     * [getDepartmentList 通过deptID获取部门人员]
     * @return [type] []
     */
    function getMembersByDpID($departmentID)
    {
        $sql = "SELECT uid,username,name_cn FROM `oa_users` where id_department=" . $departmentID;
        $data = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $data;        
    }


    /**
     * [updateProductForceOpenSmsByZond 根据Zone更新产品开启短信]
     * @param  [type] $idZone [description]
     * @return [type]         [description]
     */
    public function updateProductForceOpenSmsByZone($idZone)
    {
        $filter = ['company_id'=>1,'is_open_sms'=>0, 'id_zone'=>$idZone];
        $ret = $this->db->update('product', ['is_open_sms'=>1], $filter);
        $sql = $this->db->last();
        $this->log->write('product', print_r($sql, 1));
        return $ret;

    }


    /**
     * chenhk
     * 保存埋点
     * @param $data
     */
    public function saveProductMd($product_id, $data)
    {
        $md = $this->db->select('product_md', '*', ['product_id'=>$product_id]);
        if(!empty($md[0]))
        {

            if(empty($data['md']))
            {
                $this->db->delete('product_md', ['product_id'=>$product_id]);
            }
            elseif($md[0]['md']!=$data['md'])
            {
                $this->db->update('product_md', $data, ['product_id' => $product_id]);   
            }
        }
        else
        {
            $this->db->insert('product_md', array($data));
        }
        
    }

    /**
     * chenhk
     * 查询埋点
     * @param $data
     */
    public function getProductMd($product_id=0)
    {


        if(empty($product_id))return array();

        $md = $this->db->select('product_md', '*', ['product_id'=>$product_id]);


        if(!empty($md[0]))
        {

            return $md[0];
        }
        else
        {
            return array();
        }
        
    }

    /**
     * chenhk
     * 保存cloak
     * @param $data
     */
    public function saveProductCloak($product_id, $data)
    {
        $md = $this->db->select('product_cloak', '*', ['product_id'=>$product_id]);
        if(!empty($md[0]))
        {

            if(empty($data['safepage']))
            {
                $this->db->delete('product_cloak', ['product_id'=>$product_id]);
            }
            elseif($md[0]['safepage']!=$data['safepage'])
            {
                $this->db->update('product_cloak', $data, ['product_id' => $product_id]);   
            }
        }
        else
        {
            if(!empty($data['safepage']))$this->db->insert('product_cloak', array($data));
        }
        
    }

    /**
     * chenhk
     * 查询埋点
     * @param $data
     */
    public function getProductCloak($product_id=0)
    {


        if(empty($product_id))return array();

        $md = $this->db->select('product_cloak', '*', ['product_id'=>$product_id]);


        if(!empty($md[0]))
        {

            return $md[0];
        }
        else
        {
            return array();
        }
        
    }

}
