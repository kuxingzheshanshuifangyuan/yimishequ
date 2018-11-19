//切换选项卡
$('.tab').click(function(){
	var index = $(this).index();
	$(this).addClass('tab-active').siblings().removeClass('tab-active')
	$('.nav-item:eq('+index+')').show().siblings().hide();
})



mui.init()
var usertoken = window.localStorage.getItem("usertoken")
getJF();


var vue = new Vue({
	el:"#vue-con",
	data:{
		JFs:"",
		dates:[]
	},
})


var noMore = false;
$(window).scroll(function () {
     //已经滚动到上面的页面高度
    var scrollTop = $(this).scrollTop();
     //页面高度
    var scrollHeight = $(document).height();
       //浏览器窗口高度
    var windowHeight = $(this).height();
      //此处是滚动条到底部时候触发的事件，在这里写要加载的数据，或者是拉动滚动条的操作
    if (scrollTop + windowHeight == scrollHeight) {
    	if(!noMore){
	        $('.loading').show();
	        $('.noMore').hide();
	        setTimeout(function(){
	        	getMoreJF();
	        },500)
    	}
	}
});


var page = 1;
function getMoreJF() {
	console.log("刷新")
	page = page + 1;
	var mbList = '';
	mui.ajax('http://www.1miclub.com/api/User/pointRecord', {
		data: {
			token: usertoken,
			page: page
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			var data_ = data.data.pointList.data;
			
//			判断是否还有下一页
			if(data.data.page.lastPage == data.data.page.currentPage) {
				noMore = true;
	    		$('.loading').hide();
	    		$('.noMore').show();
			}
			for(var i = 0; i < data_.length; i++) {

				var date = timestampToTime(data_[i].create_time)
				var Zcolor = '';
				//			                    var value = data_[i].is_reduce ?  '-'  :  '+';
				if(data_[i].is_reduce == 0) {
					numType = "mbNumAdd"
				} else {
					numType = "mbNumLess"
				}
				mbList +=	`<div class="mbItem">
								<div class="mbText">
									<p>` +data_[i].comment+ `</p>
									<p>`+date+`</p>
								</div>
								<div class="mbNum">
									<span class="`+numType+`"> `+data_[i].point+`</span>积分
								</div>
							</div>`;
			}
			$('.loading').hide()
			$('.mb-list').append(mbList);
			
		},
		error: function() {
			mui.toast('失败');
		}
	});
}

function getJF() {
	mui.ajax('http://www.1miclub.com/api/User/pointRecord', {
		data: {
			token: usertoken
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			var data = data.data.pointList.data;
			console.log(data)
			vue.JFs = data;
			for(var i = 0; i < data.length; i++) {
				var date = timestampToTime(data[i].create_time)
				vue.dates.push(date)
			}
		},
		error: function() {
			mui.toast('失败');
		}
	});
}

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


