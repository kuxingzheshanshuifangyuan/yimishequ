<?php
return array(
	
	'open'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'是否使用七牛进行存储',//表单的文字
        'type'=>'radio',		 //表单的类型：text、textarea、checkbox、radio、select等
        'options'=>array(
            '1'=>'是',
            '0'=>'否',
        ),
        'value'=>'0',
        'tip'=>'不开启则使用本地存储',
			'labelwidth'=>'150px',
			
    ),
    'global'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'空间性质',//表单的文字
        'type'=>'radio',		 //表单的类型：text、textarea、checkbox、radio、select等
        'options'=>array(
            '1'=>'公有空间',
            '0'=>'私有空间',
        ),
        'value'=>'1',
        'tip'=>'七牛上可设置空间为私有或者公有,公有空间效率更高',
    		'labelwidth'=>'150px',
    		
    ),
   'configext'=>array(
                        'title'=>'允许的后缀：',
                        'type'=>'text',
                        'value'=>'rar,zip',
                        'tip'=>'允许的后缀名，多个后缀用半角逗号,隔开',
                    		'labelwidth'=>'150px',
                    		'width'=>'450px',
                    ),
                    'secrectKey'=>array(
                        'title'=>'secrectKey：',
                        'type'=>'text',
                        'value'=>'',
                        'tip'=>'七牛secrectKey',
                    		'labelwidth'=>'150px',
                    		'width'=>'350px',
                    ),
                    'accessKey'=>array(
                        'title'=>'accessKey：',
                        'type'=>'text',
                        'value'=>'',
                        'tip'=>'七牛accessKey',
                    		'labelwidth'=>'150px',
                    		'width'=>'350px',
                    ),
                    'domain'=>array(
                        'title'=>'domain：',
                        'type'=>'text',
                        'value'=>'',
                        'tip'=>'七牛domain',
                    		'labelwidth'=>'150px',
                    		'width'=>'350px',
                    ),
                     'bucket'=>array(
                        'title'=>'bucket：',
                        'type'=>'text',
                        'value'=>'',
                        'tip'=>'七牛bucket',
                     		'labelwidth'=>'150px',
                     		'width'=>'350px',
                    ),
          
);