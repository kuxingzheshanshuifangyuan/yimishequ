<?php
use think\Route;

// ****用户模块****
Route::post('api/:version/Login/login', 'api/:version.Login/login'); // 用户登录

Route::post('xgy_api/:version/user/set_password', 'xgy_api/:version.User/setPassword'); // 设置密码

// ****验证码模块****
Route::post('xgy_api/:version/vercode/send', 'xgy_api/:version.User/sendVercode'); // 发送验证码

// ****任务模块****
Route::post('xgy_api/:version/task/giveup', 'xgy_api/:version.Task/giveUp'); // 放弃催收

Route::get('xgy_api/:version/task/hands', 'xgy_api/:version.Task/hands'); // 手别

Route::get('xgy_api/:version/task/repaymentRecord', 'xgy_api/:version.Task/repaymentRecord'); // 回款记录

Route::get('xgy_api/:version/task/delayRecord', 'xgy_api/:version.Task/delayRecord'); // 延期记录
Route::post('xgy_api/:version/task/delayApply', 'xgy_api/:version.Task/delayApply'); // 延期申请
Route::post('xgy_api/:version/task/delayRemind', 'xgy_api/:version.Task/delayRemind'); // 延期提醒管理员

Route::get('xgy_api/:version/task/collectRecordList', 'xgy_api/:version.Task/collectRecordList'); // 催记 - 列表
Route::post('xgy_api/:version/task/collectRecordStore', 'xgy_api/:version.Task/collectRecordStore'); // 催记 - 保存

Route::get('xgy_api/:version/task/my_bonus', 'xgy_api/:version.Task/myBonus'); // 我的奖金
Route::get('xgy_api/:version/task/bonus_rankings', 'xgy_api/:version.Task/bonusRankings');// 奖金排行

Route::get('xgy_api/:version/task/detail', 'xgy_api/:version.Task/detail'); // 任务详情
Route::get('xgy_api/:version/task', 'xgy_api/:version.Task/lists'); // 任务列表

// ****上传图片****
Route::post('xgy_api/:version/upload', 'xgy_api/:version.Upload/upload'); // 上传图片

// ****消息模块****
Route::get('xgy_api/:version/notice/read', 'xgy_api/:version.Notice/read'); // 获取公告详情
Route::get('xgy_api/:version/notice', 'xgy_api/:version.Notice/lists'); // 获取公告列表

Route::get('xgy_api/:version/message/unread', 'xgy_api/:version.Message/getUnreadType'); // 获取未读类型列表 (包括公告)
Route::get('xgy_api/:version/message/read', 'xgy_api/:version.Message/readMessage');      // 读取一类消息
Route::post('xgy_api/:version/message/del', 'xgy_api/:version.Message/delMessage');      // 删除一条消息

// ****平台信息****
Route::get('xgy_api/:version/platform_info/common_question/detail', 'xgy_api/:version.PlatformInfo/questionDetail');// 常见问题 - 详情
Route::get('xgy_api/:version/platform_info/common_question', 'xgy_api/:version.PlatformInfo/questionList');// 常见问题 - 列表
Route::get('xgy_api/:version/platform_info/about_us', 'xgy_api/:version.PlatformInfo/aboutUs');// 关于我们
Route::get('xgy_api/:version/platform_info/skill', 'xgy_api/:version.PlatformInfo/skill');// 催收技巧列表
Route::get('xgy_api/:version/platform_info/skill_detail', 'xgy_api/:version.PlatformInfo/skillDetail');// 催收技巧详情


// ****考勤模块****

Route::get('xgy_api/:version/oa_attend/get_class_num_info', 'xgy_api/:version.OaAttend/getClassNumInfo');// 得到班次信息
Route::get('xgy_api/:version/oa_attend/get_day_record', 'xgy_api/:version.OaAttend/getDayRecord');// 得到日打卡详情
Route::get('xgy_api/:version/oa_attend/get_month_record', 'xgy_api/:version.OaAttend/getMonthRecord');// 得到月打卡详情


Route::get('xgy_api/:version/oa_attend/attend_params', 'xgy_api/:version.OaAttend/getCmpOaAttendParams'); // 获取打卡地点和wifi信息
Route::get('xgy_api/:version/oa_attend/pre_do_card', 'xgy_api/:version.OaAttend/preDoCard');// 预打卡信息
Route::post('xgy_api/:version/oa_attend/do_card', 'xgy_api/:version.OaAttend/doCard'); // 打卡


Route::get('xgy_api/:version/oa_attend/apply_list', 'xgy_api/:version.OaAttend/applyList');// 申请列表
Route::get('xgy_api/:version/oa_attend/apply_detail', 'xgy_api/:version.OaAttend/applyDetail');// 申请详情
Route::post('xgy_api/:version/oa_attend/apply_leave', 'xgy_api/:version.OaAttend/applyLeave'); // 申请请假
Route::post('xgy_api/:version/oa_attend/apply_supplement_card', 'xgy_api/:version.OaAttend/applySupplementCard'); // 申请补卡

Route::post('xgy_api/:version/oa_attend/cancel_apply', 'xgy_api/:version.OaAttend/cancelApply'); // 取消请假或补卡申请
// 考勤日报
Route::post('xgy_api/:version/oa_attend/submit_daily', 'xgy_api/:version.OaAttend/submitDaily'); // 提交工作日报
Route::get('xgy_api/:version/oa_attend/daily_list', 'xgy_api/:version.OaAttend/dailyList'); // 日报列表
Route::get('xgy_api/:version/oa_attend/daily_detail', 'xgy_api/:version.OaAttend/dailyDetail'); // 日报详情


// ****测试模块****
Route::any('xgy_api/:version/test', 'xgy_api/:version.Demo/test');