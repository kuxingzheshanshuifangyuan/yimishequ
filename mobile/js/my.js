mui.init({
	//			swipeBack:true //启用右滑关闭功能
});

//返回刷新方法
window.addEventListener('refresh', function(e){//执行刷新
    location.reload();
});


mui("body").on('tap', '#exchange', function() {
	window.location.href = "exchange.html"
	//			window.localStorage.setItem("forumID",'27')
})
mui("body").on('tap', '.home', function() {
	window.location.href = "home.html"
})
mui("body").on('tap', '.superMarket', function() {
	window.location.href = "superMarket.html"
})
var usertoken = window.localStorage.getItem('usertoken');
mui(".Mybody").on('tap', '.UserInfo .userSz', function() {

//	window.location.href = "Setting.html"
	mui.openWindow({
		url:'Setting.html',
		id:'Setting.html',
	});
})
mui(".Mybody").on('tap', '.My_Xx div', function() {
	var YwxgIndex = $(this).index() + 1;
	if(!usertoken) {
		mui.toast('请登录重试');
	} else {
		window.location.href = 'YwxgTab.html?dataNum=' + YwxgIndex
		//		 	mui.openWindow({
		//				url:'YwxgTab.html?dataNum='+YwxgIndex,
		//				id:'YwxgTab.html',
		//			});
	}

})
mui(".Mybody").on('tap', '.task', function() {
	if(!usertoken) {
		mui.toast('请登录重试');
	} else {
		mui.openWindow({
			url: 'task.html',
			id: 'task.html',
		});
	}
})
mui(".Mybody").on('tap', '#message', function() {
	mui.openWindow({
		url: 'message.html',
		id: 'message.html',
	});

})
mui(".Mybody").on('tap', '.Pay', function() {
	mui.openWindow({
		url: 'Pay.html',
		id: 'Pay.html',
	});
})

mui(".Mybody").on('tap', '#setTool', function() {
	mui.openWindow({
		url: 'setTool.html',
		id: 'setTool.html',
	});

})

//邀请好友
mui(".Mybody").on('tap', '.invitation', function() {
//	mui.toast('功能暂未开放,敬请期待!')
	mui.openWindow({
		url: 'invi.html',
	});
})
//帮助中心
mui(".Mybody").on('tap', '.helpCenter', function() {
	mui.openWindow({
		url: 'helpcenter.html',
		id: 'helpcenter.html',
	});
})

var usertoken = window.localStorage.getItem('usertoken');
var userXxHtml = '';
var My_xxHtml = '';

var vue = new Vue({
	el:'#userInfo',
	data:{
		userInfo:'',
		userHead:''
	}
})
initUser();

var PicUrl = 'http://www.1miclub.com/'
function initUser(){
	mui.ajax('http://www.1miclub.com/api/User/UserInfo', {
	data: {
		token: usertoken
	},
	dataType: 'jsonp',
	type: 'post',
	success: function(data) {
		data = JSON.parse(data);
		
		console.log(data)
		vue.userInfo = data.data;
		
		vue.userHead =  PicUrl + data.data.userhead;
		if(data.data.messageStatus) {
			$('.newsd').show()
		} else {
			$('.newsd').hide()
		}
	},
	error: function() {
		mui.toast('失败');
	}
});
}

mui(".Mybody").on('tap', '.JfHtml', function() {
	mui.openWindow({
		url: 'JfRecord.html',
	});
})
mui(".Mybody").on('tap', '.MbHtml', function() {
	mui.openWindow({
		url: 'MbRecord.html',
	});
})
mui(".Mybody").on('tap', '.signIn', function() {
	mui.openWindow({
		url: 'signIn.html',
	});
})