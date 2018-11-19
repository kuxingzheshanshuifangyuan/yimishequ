<?php
namespace app\common\tool;
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/19
 * Time: 16:43
 */
class PHPTree
{
    private static $config = [
        /* 主键 */
        'primary_key' 	=> 'id',
        /* 父键 */
        'parent_key'  	=> 'tid',
        /* 从那个级别开始搜索 */
        'from_id'       => 0
    ];

    /* 结果集 */
    protected static $result = array();

    /* 层次暂存 */
    protected static $level = array();

    public static function generate($data, $options = [])
    {
        // 格式化一下数据
        $resource = self::formatArray($data, $options);

        return self::makeTree(0, $resource);
    }

    private static function formatArray($data, $options)
    {
        self::$config = array_merge(self::$config, $options);
        $returnArray = [];
        foreach ($data as $item) {
            $id = $item[self::$config['primary_key']];
            $parentId = $item[self::$config['parent_key']];
            $returnArray[$parentId][$id] = $item;
        }
        return $returnArray;
    }

    private static function makeTree($index, $data)
    {
        foreach ($data[$index] as $key => $item) {
            $parentId = $item[self::$config['parent_key']];
            self::$level[$key] = $index ==0 ? 0 : self::$level[$parentId] + 1;
            $item['level'] = self::$level[$key];

            self::$result[] = $item;
            if(isset($data[$key])){
                self::makeTree($key, $data);
            }

            $result = self::$result;
        }
        return $result;
    }
}