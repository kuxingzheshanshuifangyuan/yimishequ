const myreg = /^[1][3,4,5,6,7,8][0-9]{9}$/;
//------------------------------------------  验证码
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
mui(".inviregbody").on('tap','#reggetYzm',function(){
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
$('#inviReg').click(function(){
	var regaccount = $('#regaccount').val();
	var regpassword = hex_md5($('#regpassword').val());
	var reggetYzm  = $('#regYzm').val();
	var tid = $('#hiddenTid').val();
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
		    	verifyCode		:reggetYzm,
		    	tid				:tid
		    },
		    dataType: 'jsonp',
		    type: 'post',
		    timeout: 10000,
		    success: function(data) {
		    	data = JSON.parse(data)
		    	if(data.error_code === 0){
		    		window.location.href = 'http://www.1miclub.com/index/index/invite_dowload';
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