<?php
namespace app\common\service;
use app\common\enum\TableName;
use \app\common\model\Message as MessageModel;
use \think\Db;

class Message
{
    // 待发送消息列表
    static private $message_queue = [];

    /**
     * 发送消息
     * @param $msg_tpl array 消息模板
     * @param $data array 消息变量 需包含to_user_id , from_user_id
     * @throws
     */
    public static function send($msg_tpl_name, $data){
        $msg_tpl = self::getMsgTpl($msg_tpl_name);
        // 得到当前参数列表 判断该数据是二维数据还是一维数据
        if(count($data) == count($data,COUNT_RECURSIVE)){ // 一维
            $data = array($data);
        }
        // 根据数据渲染模板 并且添加到待发送队列中
        foreach($data as $item){
            if(!isset($item['to_user_id']) || empty($item['to_user_id']) ){
                throw new \Exception('获取消息参数时, 缺少字段: to_user_id');
            }
            $content = self::getContentByTemplate($msg_tpl['content'],$item);
            $from_user_id = isset($item['from_user_id']) ? $item['from_user_id'] : 0;
            self::addMessageToQueque($item['to_user_id'], $msg_tpl['type'], $content, $from_user_id);
        }
        // 发送消息
        if(self::sendMessages()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取消息模板
     * @param $msg_tpl
     * @return mixed
     * @throws
     */
    public static function getMsgTpl($msg_tpl_name)
    {
        $result = Db::name(TableName::PLATFORM_MESSAGE_TEMPLATE)->field('content,type,comment')->where('name', '=',$msg_tpl_name)->find();
        if(!$result) {
            throw new \Exception('没有找到模板为 : '.$msg_tpl_name.' 的消息模板');
        }
        return $result;
    }
    /**
     * 通过模板生成消息
     * @param $template_name string 模板名称
     * @param $data array 传入模板数据
     * @return string 返回装填好的数据
     * @throws
     */
    private static function getContentByTemplate($result_content, $data)
    {
        foreach ($data as $key=>$value){
            $result_content = str_replace('{'.$key.'}',$value,$result_content);
        }

        // 判断模板中内容是否被完全替换 若没有, 则抛出异常
        if( strpos($result_content,'{') !== false ) {
            preg_match_all("/{([a-zA-Z_]*)}/",$result_content,$need_surplus_variables);
            $need_surplus_variables = $need_surplus_variables[1];
            if(!empty($need_surplus_variables)){
                throw new \Exception('模板 [' .$result_content. '] 还需要字段 : '.implode($need_surplus_variables,' , '));
            }
        }
        return $result_content;
    }

    /**
     * 格式化为待存储消息
     */
    static private function _formatMessage($user_id, $type, $message, $from_user_id = 0)
    {
        return [
            'user_id' => $user_id,
            'type' => $type,
            'message' => $message,
            'from_user_id' => $from_user_id,
            'no' => time().mt_rand(1000,9999)
        ];
    }

    /**
     * 添加到批量消息队列
     */
    static private function addMessageToQueque($user_id, $type, $message, $from_user_id = 0)
    {
        self::$message_queue[] = self::_formatMessage($user_id, $type, $message, $from_user_id);
    }

    /**
     * 发送消息到 数据库
     */
    static private function sendMessages()
    {
        if(!empty(self::$message_queue)){
            try{
                MessageModel::sendMessage(self::$message_queue);
                self::$message_queue = [];
            }catch (\Exception $e){
                return false;
            }
            return true;
        }else{
            return false;
        }

    }
}