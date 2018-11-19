mui.init();
mui("body").on('tap', '.Xs li', function() {
	mui.openWindow({
		url: 'Setting.html',
		id: 'Setting.html',
	});
})

var vm = new Vue({
	el:'#task-con',
	data:{
		tasks:"",
		signEexist:"",
		comleDes:""
	}
})

var usertoken = window.localStorage.getItem('usertoken'); //帖子id
var taskList = '';
var statusr = '';

initTastList();
var comdeArray = [];
function initTastList(){
	mui.ajax('http://www.1miclub.com/api/Task/taskList', {
		data: {
			token: usertoken,
			time: new Date().getTime()
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			
			if(data.error_code === 0) {
				vm.tasks = data.data.day_task;
				vm.signEexist = data.data.signEexist
				
				for(var i = 0; i < data.data.day_task.length; i++) {
//					if(data.data.day_task[i].taskStatus == 0) {
//						statusr = data.data.day_task[i].taskNumber + '/' + data.data.day_task[i].task_per
//					}
//					if(data.data.day_task[i].taskStatus == 1) {
//						statusr = '<span class="Right back boder">已完成</span>'
//					}
					
					
					let comleDe = (data.data.day_task[i].taskNumber / data.data.day_task[i].task_per * 100) + '%'
					comdeArray.push(comleDe)
//					var comleDe = (1/3 * 100) + '%';
					console.log(comleDe)
				}
				vm.comleDes = comdeArray;
			}
	
		},
		error: function() {
			mui.toast('失败');
		}
	});
}
mui("body").on('tap', '.signinBtn', function() {
	mui.ajax('http://www.1miclub.com/api/Task/sign', {
		data: {
			token: usertoken,
			time: new Date().getTime()
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			if(data.error_code === 0) {
				mui.toast(data.msg);
				initTastList();
				$('.signInnerLine').css('width','100%')
//				$('.qdcont .qdCont').html('<span class="back opct qdstudt">已签到</span>')
			} else {
				mui.toast(data.msg);
			}

		},
		error: function() {
			mui.toast('失败');
		}
	});
})