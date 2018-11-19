//	定义马甲包
//	const coatFlag = false; 
	var packaging = window.localStorage.getItem("packaging");
//	var packaging = 0;
	if(packaging == '1'){
		$('.coatHid').hide()
	}


function timestampToTime(timestamp) {
    var date = new Date(timestamp * 1000);//时间戳为10位需*1000，时间戳为13位的话不需乘1000
    Y = date.getFullYear() + '/';
    M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '/';
    D = date.getDate() + ' ';
    h = (date.getHours() < 10 ? '0'+(date.getHours()) : date.getHours()) + ':';
    m = (date.getMinutes() < 10 ? '0'+(date.getMinutes()) : date.getMinutes()) + ':';
    s = (date.getSeconds()+1 < 10 ? '0'+(date.getSeconds()+1) : date.getSeconds()+1);
    return Y+M+D+h+m+s;
}
function HoMiSe(timestamp) {
    var date = new Date(timestamp * 1000);//时间戳为10位需*1000，时间戳为13位的话不需乘1000
    Y = date.getFullYear() + '/';
    M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '/';
    D = date.getDate();

    return Y+M+D;
}
//------------------------------------------- 密码登录 ----------------------------------
//mui(".PassLoginbody").on('tap','.LoginBtn #Passlogin',function(){
const myreg = /^[1][3,4,5,6,7,8][0-9]{9}$/;
$('.LoginBtn #Passlogin').click(function(){
	var passLogin_addcount = $('#PassLogin_account').val();
	var PassLogin_password = hex_md5($('#PassLogin_password').val());
	if(passLogin_addcount == ''){
		alert('请填写手机号')
		return false 
	}else if(!myreg.test(passLogin_addcount)){
		alert('请填写正确的手机号')
		return false 
	}else if($('#PassLogin_password').val() == '' ){
		alert('请输入密码');
		return false 
	}else{
		mui.ajax('http://www.1miclub.com/api/User/login', {
		    data: {
		    	account  :passLogin_addcount,
		    	password :PassLogin_password
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    		
//		    		mui.openWindow({
//	                  	url: 'fonts/home.html',
//	                  	id:  'home.html',
//	              	});
	              window.localStorage.setItem("userid",data.data.userData.userid)
	              window.localStorage.setItem("username",data.data.userData.username)
	              window.localStorage.setItem("usermoney",data.data.userData.money)
	              window.localStorage.setItem("usermobile",data.data.userData.mobile)
	              window.localStorage.setItem("usersex",data.data.userData.sex)
	              window.localStorage.setItem("userstatus",data.data.userData.status)
	              window.localStorage.setItem("userhead",data.data.userData.userhead)
	              window.localStorage.setItem("usertoken",data.data.token)
	              window.localStorage.setItem("regtime",data.data.userData.regtime)
	              
	              $('#PassLogin_account').val('');
	              $('#PassLogin_password').val('');
		    	}else{
		    		mui.toast('账号或密码输入错误');
		    	}
		    	document.activeElement.blur();
		    	
		    	window.location.href='fonts/home.html'
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}
})
//})


//------------------------------------------ 注册-> 验证码
mui(".regbody").on('tap','#reggetYzm',function(){
	var regaccount = $('#regaccount').val();
	var regpassword = $('#regpassword').val();
	if(regaccount == ''){
		alert('请填写手机号')
		return false;
	}else if(!myreg.test(regaccount)){
		alert('请填写正确的手机号')
		return false;
	}else{
		mui.ajax('http://www.1miclub.com/api/User/isUser', {
		    data: {
		    	phone:regaccount,
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code !== 0){
		    		mui.toast(data.msg)
		    		return false;
		    	}
		   
		    	var countdown=60;
				var obj = $("#reggetYzm");
				settime(obj,countdown);
	
				mui.ajax('http://www.1miclub.com/api/User/sendVercode', {
				    data: {
				    	phone  			:regaccount,
		//		    	is_register		:true
				    },
				    dataType: 'jsonp',
				    type: 'post',
				    timeout: 10000,
				    success: function(data) {
				    	data = JSON.parse(data)
				    	if(data.error_code === 0){
				    		mui.toast('验证码获取成功')
				    	}else{
				    		mui.toast('验证码获取失败')
				    	}
				    },
				    error: function() {
				        mui.toast('失败');
				    }
				});
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});
		
	
	}


})
//------------------------------------ 注册 ------------------------------
//mui(".regbody").on('tap','#reg',function(){
$('#reg').click(function(){
	var regaccount = $('#regaccount').val();
	var regpassword = hex_md5($('#regpassword').val());
	var reggetYzm  = $('#regYzm').val();
	if(regaccount == ''){
		alert('请填写手机号')
		return false;
	}else if($('#regpassword').val() == ''){
		alert('请输入密码');
		return false;
	}else if(reggetYzm == ''){
		alert('请输入验证码');
		return false;
	}else if(!myreg.test(regaccount)){
		alert('请填写正确的手机号')
		return false;
	}else if(regpassword<6){
		alert('密码字符长度不可少于6');
		return false;
	}else{
		mui.ajax('http://www.1miclub.com/api/User/register', {
		    data: {
		    	phone  			:regaccount,
		    	password		:regpassword,
		    	verifyCode		:reggetYzm
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    	mui.openWindow({
                  url: 'login.html',
                  id:  'login.html',
              });
              window.localStorage.setItem("userid",data.data.userData.userid)
              window.localStorage.setItem("username",data.data.userData.username)
              window.localStorage.setItem("usermoney",data.data.userData.money)
              window.localStorage.setItem("usermobile",data.data.userData.mobile)
              window.localStorage.setItem("usersex",data.data.userData.sex)
              window.localStorage.setItem("userstatus",data.data.userData.status)
              window.localStorage.setItem("userhead",data.data.userData.userhead)
              window.localStorage.setItem("usertoken",data.data.token)
              window.localStorage.setItem("regtime",data.data.userData.regtime)
             }else{
             	mui.toast(data.msg)
             }
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}


})

//----------------------------------- 找回密码 -> 验证码
mui(".forgetbody").on('tap','#forgetgetyzm',function(){
	var regaccount = $('#forgetaccount').val();
	var regpassword = $('#forgetpassword').val();
	if(regaccount == '' || regpassword == '' ){
		alert('请填写手机号或密码')
	}else if(!myreg.test(regaccount)){
		alert('请填写正确的手机号')
	}else if(regpassword<6){
		alert('密码字符长度不可少于6');
		return false;
	}else{
		var countdown=60;
		var obj = $("#forgetgetyzm");
		settime(obj,countdown);

		mui.ajax('http://www.1miclub.com/api/User/sendVercode', {
		    data: {
		    	phone  			:regaccount,
//		    	is_register		:true
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    		mui.toast('验证码获取成功')
		    	}else{
		    		mui.toast('验证码获取失败')
		    	}
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}


})
//找回密码
mui(".forgetbody").on('tap','#forgetbtn',function(){
	var forgetaccount = $('#forgetaccount').val();
	var forgetpassword = hex_md5($('#forgetpassword').val());
	var forgetyzm  = $('#forgetyzm').val();
	if(forgetaccount == ''){
		alert('请填写手机号');
		return false;
	}else if(!myreg.test(forgetaccount)){
		alert('请填写正确的手机号')
		return false;
	}else if($('#forgetpassword').val() == '' ){
		alert('请输入新密码');
		return false;
	}else if($('#forgetpassword').val() < 6){
		alert('密码字符长度不可少于6');
		return false;
	}else if(forgetyzm == ''){
		alert('请填写验证码')
		return false;
	}else{

		var countdown=60;
		var obj = $("#forgetbtn");
		settime(obj,countdown);

		mui.ajax('http://www.1miclub.com/api/User/rePass', {
		    data: {
		    	phone  			:forgetaccount,
		    	newPass			:forgetpassword,
		    	verifyCode		:forgetyzm
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    	mui.openWindow({
                  url: 'login.html',
                  id:  'login.html',
              });
             }else{
             	mui.toast(data.msg)
             }
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}


})

//--------------------------------------------验证码登录 -> 验证码

//mui(".Loginbody").on('tap','#LoginYzm',function(){
	
$('#LoginYzm').click(function(){
	var Loginaccount = $('#Loginaccount').val();
	if(Loginaccount == ''){
		alert('请填写正确的手机号与密码')
	}else if(!myreg.test(Loginaccount)){
		alert('请填写正确的手机号')
	}else{

		var countdown=60;
		var obj = $("#LoginYzm");
		settime(obj,countdown);

		mui.ajax('http://www.1miclub.com/api/User/sendVercode', {
		    data: {
		    	phone  			:Loginaccount,
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    		alert('验证码获取成功')
		    	}else{
		    		alert('验证码获取失败')
		    	}
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}


})
function settime(obj,countdown) { //发送验证码倒计时
    if (countdown == 0) {
        obj.attr('disabled',false);
        //obj.removeattr("disabled");
        obj.val("获取验证码");
        countdown = 60;
        return;
    } else {
        obj.attr('disabled',true);
        obj.val("重新发送(" + countdown + ")");
        countdown--;
    }
setTimeout(function() {
    settime(obj,countdown) }
    ,1000)
}

//-----------------------------------------验证码登录   -> 登录
//mui(".Loginbody").on('tap','#login',function(){
$('#login').click(function(){
	var Loginaccount = $('#Loginaccount').val();
	var LoginYzm     = $('#LoginYzmH').val();
	if(Loginaccount == ''){
		alert('请填写手机号')
	}else if(!myreg.test(Loginaccount)){
		alert('请填写正确的手机号')
	}else{
		mui.ajax('http://www.1miclub.com/api/User/smsLogin', {
		    data: {
		    	phone  		:Loginaccount,
		    	verifyCode	:LoginYzm
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
                mui.toast('登录成功');
                window.location.href='fonts/home.html'
		    	
	              window.localStorage.setItem("userid",data.data.userData.userid)
	              window.localStorage.setItem("username",data.data.userData.username)
	              window.localStorage.setItem("usermoney",data.data.userData.money)
	              window.localStorage.setItem("usermobile",data.data.userData.mobile)
	              window.localStorage.setItem("usersex",data.data.userData.sex)
	              window.localStorage.setItem("userstatus",data.data.userData.status)
	              window.localStorage.setItem("userhead",data.data.userData.userhead)
	              window.localStorage.setItem("usertoken",data.data.token)
	              window.localStorage.setItem("regtime",data.data.userData.regtime)
	              $('#Loginaccount').val('');
	              $('#LoginYzmH').val('')
             }else{
             	mui.toast(data.msg)
             }
             document.activeElement.blur();
		    },
		    error: function() {
		        mui.toast('失败');
		    }
		});

	}


})
//----------------------交流区 tab 内容---------------------------------
var currentPage = 1;
var page = 1;
var tabbtnid = "27";
var PicUrl = 'http://www.1miclub.com/';
var picHtml = '';




//第一次进入交流区时,进行数据初始化
//var aaa = sessionStorage.getItem('forumID');
//console.log("session = "+aaa)
//setdata(tabbtnid)




//点击发帖登录检测
function loginJC(url){
	if(!usertoken){
		mui.toast("登录后才能发帖")
		mui.openWindow({
			url:'../login.html',
			id:'login.html',
		});
	}else{
		mui.openWindow({
			url:'Issue.html',
			id:'Issue.html',
		});
	}
}

var usertoken = window.localStorage.getItem('usertoken');

//用户数据
mui.ajax('http://www.1miclub.com/api/User/UserInfo',{
    data: {
    	token : usertoken
    },
    dataType: 'jsonp',
    type: 'post',
    success: function(data) {
    	data = JSON.parse(data);
    	if(data.error_code === 0){
    		window.localStorage.setItem("usermoney",data.data.money)
    	}

    },
    error: function() {
        mui.toast('失败');
    }
});


//底部导航

mui(".rpfNav").on('tap','.home',function(){
	window.location.href = "home.html";
	sessionStorage.setItem("pageScroll", 0);
})
mui(".rpfNav").on('tap','.exchange',function(){
	window.location.href = "exchange.html";
})
mui(".rpfNav").on('tap','.superMarket',function(){
	window.location.href = "superMarket.html";
})
mui(".rpfNav").on('tap','.my',function(){
	if(!usertoken){
		window.location.href = "../login.html";
	}else{
		window.location.href = "my.html";
	}

})



//  复制方法
function copy_fun(copy){
	mui.plusReady(function(){
		//复制链接到剪切板
		//判断是安卓还是ios
		if(mui.os.ios){
			//ios
			var UIPasteboard = plus.ios.importClass("UIPasteboard");
		    var generalPasteboard = UIPasteboard.generalPasteboard();
		    //设置/获取文本内容:
		    generalPasteboard.plusCallMethod({
		        setValue:copy,
		        forPasteboardType: "public.utf8-plain-text"
		    });
		    generalPasteboard.plusCallMethod({
		        valueForPasteboardType: "public.utf8-plain-text"
		    });
		    mui.toast("已成功复制到剪贴板");
		}else{
			//安卓
			var context = plus.android.importClass("android.content.Context");
			var main = plus.android.runtimeMainActivity();
			var clip = main.getSystemService(context.CLIPBOARD_SERVICE);
			plus.android.invoke(clip,"setText",copy);
			mui.toast("已成功复制到剪贴板");
		}
	});
}