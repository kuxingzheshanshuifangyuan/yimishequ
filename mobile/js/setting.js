mui.init({
	//swipeBack:true //启用右滑关闭功能
})
window.addEventListener('refresh', function(e){//执行刷新
    location.reload();
});


$('#history_').click(function(){
	mui.back()
})
//选择头像
mui('body').on('tap', '#choose-head', function() {
	var btnArray = [{title:"拍照"},{title:"手机相册选择"}];
	plus.nativeUI.actionSheet( {
		title:"选择头像",
		cancel:"取消",
		buttons:btnArray
	}, function(e){
		var index = e.index;
		switch (index){
			case 1:
			openCamera();
				break;
			case 2:
			galleryImg();
				break;
		}
	});
})
//选择性别
mui('body').on('tap', '#choose-sex', function() {
	var btnArray = [{title:"男"},{title:"女"},{title:"保密"}];
	plus.nativeUI.actionSheet( {
		title:"选择性别",
		cancel:"取消",
		buttons:btnArray
	}, function(e){
		var index = e.index;
		updateUserSex(index);
		switch (index){
			case 1:
			$('#choose-sex span').text('男');
				break;
			case 2:
			$('#choose-sex span').text('女');
				break;
			case 3:
			$('#choose-sex span').text('保密');
				break;
		}
	});
});
//修改性别接口
function updateUserSex(sex) {
	mui.ajax('http://www.1miclub.com/api/User/updateUserSex.html', {
		data: {
			token: usertoken,
			sex: sex
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			console.log(data);
			mui.toast("修改成功!")
		},
		error: function() {
			mui.toast('修改失败');
		}
	});
}


//跳转至修改签名页面
mui('body').on('tap', '.sign', function() {
	mui.openWindow({
		url:"setSign.html"
	})
})
//跳转至用户组页面
mui('body').on('tap', '#user-level', function() {
	mui.openWindow({
		url:"userLevel.html"
	})
})
//跳转至商务合作页面
mui('body').on('tap', '#busCoop', function() {
	mui.openWindow({
		url:"busCoop.html"
	})
})
//跳转至关于页面
mui('body').on('tap', '#about', function() {
	mui.openWindow({
		url:"about.html"
	})
})

mui('body').on('tap', '.mui-popover-action li>a', function() {
	var a = this,
		parent;
	//根据点击按钮，反推当前是哪个actionsheet
	for(parent = a.parentNode; parent != document.body; parent = parent.parentNode) {
		if(parent.classList.contains('mui-popover-action')) {
			break;
		}
	}
	//关闭actionsheet
	mui('#' + parent.id).popover('toggle');
	console.log(a.innerHTML)
})

//打开手机摄像头
function openCamera() {
    var cmr = plus.camera.getCamera();
    cmr.captureImage(function(p) {//拍照成功的回调
        compression(p)//压缩-转base64-上传
    }, function(e) {//拍照失败的回调
        plus.nativeUI.closeWaiting(); //开启照相机失败,关闭loading框
//      mui.toast('aa失败：' + e.message); //打印错误原因,给出错提示
    }, {//拍照参数
        filename: '_doc/camera/', //图片名字
        index: 1 //摄像头id
    });
}

//---------获取base64方法---------
function getBase64Image(img, width, height) {
	var canvas = document.createElement("canvas");
	canvas.width = width ? width : img.width;
	canvas.height = height ? height : img.height;
	var ctx = canvas.getContext("2d");
	ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
	var dataURL = canvas.toDataURL();
	return dataURL;
}

function getCanvasBase64(img) {
	var image = new Image();
	//至关重要
	image.crossOrigin = '';
	image.src = img;
	//至关重要
	var deferred = $.Deferred();
	if(img) {
		image.onload = function() {
			deferred.resolve(getBase64Image(image)); //将base64传给done上传处理
		}
		return deferred.promise(); //问题要让onload完成后再return sessionStorage['imgTest']
	}
}
//---------获取base64方法结束---------
//上传图片，成功后关闭等待框
function uploadHead(imgUrl,wait){
	mui.ajax('http://www.1miclub.com/api/User/updateUserHead.html', {
		data: {
			token: usertoken,
			head: imgUrl
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			console.log(data);
			mui.toast('上传头像成功！')
			wait.close();
		},
		error: function() {
			mui.toast('上传头像失败');
		}
	});
}

// 从相册中选择图片 
function galleryImg(){
	// 从相册中选择图片
    plus.gallery.pick( function(imgUrl){
//  	for(var i in e.files){ 
//	    	var fileSrc = e.files[i]
//	    	console.log(fileSrc)
//  	}
    	//压缩 -转为base64-上传
		compression(imgUrl)
    }, function ( e ) {
    	console.log( "取消选择图片" );
    },{
    	filter: "image",
    	multiple: false,
    	maximum: 5,
    	system: false,
    	onmaxed: function() {
    		plus.nativeUI.alert('最多只能选择1张图片');
    	}
    });
}
//压缩图片 - 转base64 - 上传
function compression(imgUrl){ //参数：图片url
	plus.io.resolveLocalFileSystemURL(imgUrl, function(entry) {
        var wait = plus.nativeUI.showWaiting("上传图片中...", ""); 
        plus.zip.compressImage({
            src: entry.toLocalURL(),
            dst: imgUrl,
            overwrite: true,
            format: "jpg",
            width: "30%"
        }, function(zip) {
            if(zip.size > (1 * 1024 * 1024)) {
                return mui.toast('文件超大,请调整相机重新拍照');
            }
            let file_url = zip.target;
			console.log("压缩之后的："+file_url)
			//转为base64
			getCanvasBase64(file_url)
				.then(function(base64) { 
					uploadHead(base64,wait);//拿到base64格式的图片，并关闭等待框
				}, function(err) {
					console.log(err);
				});
			
				$('.touxiang').find('img').attr('src', file_url)
        }, function(zipe) {
            plus.nativeUI.closeWaiting();
            mui.toast('压缩失败！')
        });
    }, function(e) {
        plus.nativeUI.closeWaiting(); //获取图片失败,loading框取消
        mui.toast('失败：' + e.message); //打印失败原因,或给出错误提示
    });
}



//		退出登录
mui(".Settingbody").on('tap', '.logout', function() {
	var btnArray = ['否', '是'];
	mui.confirm('确认退出登录吗？', '', btnArray, function(e) {
		if (e.index == 1) {
			logout();
		}
	})
})
//		登出方法
function logout() {
	window.localStorage.removeItem("usertoken");
	window.localStorage.removeItem("DcListid");
	window.localStorage.removeItem("forum_contid");
	window.localStorage.removeItem("userhead");
	window.localStorage.removeItem("userid");
	window.localStorage.removeItem("usermobile");
	window.localStorage.removeItem("usermoney");
	window.localStorage.removeItem("username");
	window.localStorage.removeItem("usersex");
	window.localStorage.removeItem("userstatus");
	window.location.href = "home.html";
}

mui(".Settingbody").on('tap', '.forget', function() {
	mui.openWindow({
		url:"../forget.html",
	})
})


//上传头像
var PicUrl = "http://www.1miclub.com/";
var usertoken = window.localStorage.getItem('usertoken');
var username = window.localStorage.getItem('username');
var userhead = window.localStorage.getItem('userhead');
var usermobile = window.localStorage.getItem('usermobile').replace(/^(\d{3})\d{4}(\d{4})$/, "$1****$2");
var regtime = timestampToTime(window.localStorage.getItem('regtime'));

//$('.regtime').find('span').html(regtime)
//$('.nicheng').find('span').html(username)
//$('.touxiang').find('img').attr('src', PicUrl + userhead)
$('.shoujihao').find('span').html(usermobile)

var vue = new Vue({
	el:'#user-info',
	data:{
		userInfo:'',
		usuerHead:"",
		regtime:'',
		userSex:'',
		is_username:''
	}
})
var is_username;
//拿到用户信息

initUserInfo()
function initUserInfo(){
	mui.ajax('http://www.1miclub.com/api/User/UserInfo', {
	    data: {
	    	token: usertoken
	    },
	    dataType: 'jsonp',
	    type: 'post',
	    timeout: 10000,
	    success: function(data) {
    	
	  		data = JSON.parse(data)
	    	if(data.error_code == 0){
	    		console.log(data)
    		
	    		vue.userInfo = data.data;
	    		vue.usuerHead = PicUrl + data.data.userhead;
	    		vue.regtime = timestampToTime(data.data.regtime);
	    		
	    		is_username = data.data.is_username;
	    		if(data.data.is_username > 0){
	    			$('.nicheng').removeClass('mui-navigate-right')
	    		}
	    		switch (data.data.sex){
	    			case 1:
	    			vue.userSex = "男";
	    				break;
					case 2:
	    			vue.userSex = "女";
	    				break;
					case 3:
	    			vue.userSex = "保密";
	    				break;
	    			default:
	    				break;
	    		}
    		
	    	}
    	
	    },
	    error: function() {
	        mui.toast('失败');
	    }
	});
}

//跳转至修改昵称页面
mui('body').on('tap', '.nicheng', function() {
	if(is_username > 0){
		mui.toast("昵称只能修改一次");
	}else{
		mui.openWindow({
			url:"setNickname.html"
		})
	}
})


function timestampToTime(timestamp) {
	var date = new Date(timestamp * 1000); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
	Y = date.getFullYear() + '/';
	M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '/';
	D = date.getDate() + ' ';
	h = (date.getHours() < 10 ? '0' + (date.getHours()) : date.getHours()) + ':';
	m = (date.getMinutes() < 10 ? '0' + (date.getMinutes()) : date.getMinutes()) + ':';
	s = (date.getSeconds() + 1 < 10 ? '0' + (date.getSeconds() + 1) : date.getSeconds() + 1);
	return Y + M + D + h + m + s;
}