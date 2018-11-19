<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Db;
use \app\api\validate\BaseValidate as Validate;
use \app\api\exception\BaseException as Exception;


class Base extends Controller
{
    // token失效过期时间，秒为单位（30天）
    private $expire = 2592000;
    private $user_id;

    /**
     * 验证请求参数
     * @param $arr_fields array 请求字段
     * @return array
     * @throws
     */
    public function validateRequestParams($arr_fields)
    {
        $method = strtolower($this->request->method());
        // 获取待检测请求数据
        $params = [];
        foreach ($arr_fields as $key => $value) {
            $request_param = $value;
            if (($pos = strpos($value, '/')) !== false) {
                $value = $arr_fields[$key] = substr($value, 0, $pos);
            }
            $params[$value] = $this->request->$method(
                $request_param,
                (isset($this->validate_param_rules[$value][2]) ? $this->validate_param_rules[$value][2] : null)
            );
        }
        // 得到请求数据key对应规则
        $fieldAndRule = Validate::getRule($arr_fields, $this->validate_param_rules);

        // 若接收到array 则做特殊处理
        foreach ($fieldAndRule as $key => &$val){
            if(strpos($val, 'array') !== false){
                if($val = 'array'){
                    unset($fieldAndRule[$key]);
                }elseif(strpos($val, '|array') !== false){
                    $val = str_replace('|array', '', $val);
                }elseif(strpos($val, 'array|') !== false){
                    $val = str_replace('array|', '', $val);
                }
                $request_k = substr($key, 0, strpos($key,'|'));
                if(!empty($params[$request_k]) && is_string($params[$request_k])){
                    $params[$request_k] = explode(',', $params[$request_k]);
                }else{
                    $params = [];
                }
            }
        }

        $validate = new Validate($fieldAndRule);
        if (!$validate->check($params)) {
            throw new Exception("validate params error : " . (is_array($validate->getError()) ? implode(';', $validate->getError()) : $validate->getError()), 10);
        }

        return $params;
    }

    /**
     * 校验数据是否正确
     */
    public function validateParams($data)
    {
        $keys = array_keys($data);

        $fieldAndRule = Validate::getRule($keys, $this->validate_param_rules);

        $validate = new Validate($fieldAndRule);
        if (!$validate->check($data)) {
            throw new Exception("validate params error : " . (is_array($validate->getError()) ? implode(';', $validate->getError()) : $validate->getError()), 10);
        }
        return true;
    }

    /**
     * 检测用户token
     * @param $token string token
     * @return int user_id
     * @throws
     */
    public function checkToken($token = '')
    {
        if (empty($this->user_id)) {
            if ($token == '' && !($token = $this->getTokenByRequest())) {
                throw new Exception("not found token in request params", 100);
            }

            $token_result = Db::name(TableName::USER_API_TOKEN)->where('token', '=', $token)->field('user_id, update_time')->find();
            if (empty($token_result)) {
                throw new Exception("not found token in database", 101);
            }

            // 判断token是否过期
            $expire = $this->expire;
            if ((strtotime($token_result['update_time']) + $expire) < time()) {
                throw new Exception("token expireed", 102);
            }
            $this->user_id = $token_result['user_id'];

            return $token_result['user_id'];
        } else {
            return $this->user_id;
        }
    }

    /**
     * 获取用户信息
     * @throws
     */
    public function getUserInfo($get_all = false)
    {
        $user_id = $this->checkToken();
        $user_fields = 'id, name, sex, phone, role_id, cmp_id, org_id';
        $user_info = Db::name(TableName::USER)->where('id', '=', $user_id)->field($user_fields)->find();

        if($get_all){
            $user_info['cmp_info'] = Db::name(TableName::COMPANY)
                ->field('cmp_name, short_name')
                ->where('id','=',$user_info['cmp_id'])->find();
            $user_info['org_info'] = Db::name(TableName::ORGANIZATION)
                ->field('org_name, org_no')
                ->where('id','=',$user_info['org_id'])->find();
        }

        if (empty($user_info)) {
            throw new Exception("not found user", 103);
        }
        $this->user_info = $user_info;
        return $user_info;
    }


    /**
     * 设置token 到数据库
     */
    public function setToken($user_id)
    {
        do {
            $isset_token = false;

            $new_token = md5($user_id . substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 26, 10));

            $search_result = Db::name(TableName::USER_API_TOKEN)->where('token', '=', $new_token)->field('user_id')->find();
            if (!empty($search_result)) {
                $isset_token = true;
            }
        } while ($isset_token);

        $user_result = Db::name(TableName::USER_API_TOKEN)->where('user_id', '=', $user_id)->field('user_id')->find();
        if ($user_result) {
            // 更新用户token
            $save_result = Db::name(TableName::USER_API_TOKEN)->where('user_id', '=', $user_id)->update([
                'token' => $new_token,
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
        } else {
            // 创建用户token
            $save_result = Db::name(TableName::USER_API_TOKEN)->insert([
                'user_id' => $user_id,
                'token' => $new_token,
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
        }

        if ($save_result) {
            return $new_token;
        } else {
            throw new Exception("token set fail", 104);
        }

    }

    /**
     * 列表数据分页
     * @param $model \think\Db\Query|string 上传模型类
     * @param $get_sql boolean 是否获取分页sql
     * @return array|string
     * @throws
     */
    public function paginate($model, $get_sql = false)
    {
        list($size, $page) = $this->getPaginateParams();
        if (!$get_sql) {
            return $model->paginate($size, true, ['page'=>$page])->toArray();
        } else {
            return 'LIMIT ' . ($page - 1) * $size . ',' . $size;
        }
    }

    /**
     * 获取当前分页参数
     * @return [size, current_page] array
     */
    public function getPaginateParams()
    {
        $size = $this->request->get('per_page', 5);
        $page = $this->request->get('current_page', 1);
        return [$size, $page];
    }

    /**
     * 获取请求中传来的token
     */
    public function getTokenByRequest()
    {
        return $this->request->param('token', null);
    }

    /**
     * 获取token 从数据库
     */
    public function getTokenByDatabase($user_id)
    {
        $token = Db::name(TableName::USER_API_TOKEN)->where('user_id', $user_id)->find();
        if($token){
            return $token['token'];
        }else{
            return false;
        }
    }

    /**
     * 将分处理为元为单位 (若金额数字中间有',', 如后会被转化为 1234,3000 =>12.34,30.00
     * @param $result array 结果数组
     * @param $field_arr array 需要处理字段
     * @param $is_list bool 是否传入的是列表
     * @param $normal_display bool 是正常显示',' ,默认为正常显示','
     * 例子 :
     *   1 : $this->dealMoneyToDisplay($result, ['return_money']);
     *   2 : $this->dealMoneyToDisplay($result, ['aa.return_money','b.money'],false);
     */
    protected function dealMoneyToDisplay(&$result , $field_arr , $is_list = true , $normal_display = true)
    {
        if($is_list){
            foreach($result as $k => $v){
                foreach ($field_arr as $field_item){
                    if( isset($result[$k][$field_item]) && $result[$k][$field_item]){
                        if(strpos($result[$k][$field_item],',') === false){
                            if($normal_display){
                                $result[$k][$field_item] =
                                    number_format($result[$k][$field_item]/100, 2);
                            }else{
                                $result[$k][$field_item] =
                                    number_format($result[$k][$field_item]/100, 2, ".", "¸");
                            }
                        }else{
                            $_tmp_arr = explode(',',$result[$k][$field_item]);
                            foreach ($_tmp_arr as &$m_item){
                                $m_item =
                                    number_format($m_item/100, 2, ".", "¸");
                            }
                            $result[$k][$field_item] = implode(",",$_tmp_arr);
                        }
                    }
                }
            }
        }else{
            foreach ($field_arr as $field_item){
                list($key1, $key2) = explode('.',$field_item);
                if(isset($result[$key1][$key2]) && $result[$key1][$key2]){
                    $result[$key1][$key2] = number_format($result[$key1][$key2]/100, 2);
                }
            }
        }
    }
}