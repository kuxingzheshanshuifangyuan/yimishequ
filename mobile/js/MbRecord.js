//切换选项卡
$('.tab').click(function(){
	var index = $(this).index();
	$(this).addClass('tab-active').siblings().removeClass('tab-active')
	$('.nav-item:eq('+index+')').show().siblings().hide();
})



mui.init()
var usertoken = window.localStorage.getItem("usertoken")
getMb();


var vue = new Vue({
	el:"#vue-con",
	data:{
		mbs:"",
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
	        	getMoreMb();
	        },500)
    	}
	}
});


var page = 1;
function getMoreMb() {
	page = page + 1;
	var mbList = '';
	mui.ajax('http://www.1miclub.com/api/User/moneyRecord', {
		data: {
			token: usertoken,
			page: page
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			console.log(data)
			var data_ = data.data.moneyList.data;
			
//			判断是否还有下一页
			if(data.data.page.lastPage == data.data.page.currentPage) {
				noMore = true;
	    		$('.loading').hide();
	    		$('.noMore').show();
			}
			for(var i = 0; i < data_.length; i++) {
				var date = timestampToTime(data_[i].create_time)
				if(data_[i].is_reduce == 0) {
					numType = "mbNumAdd"
				} else {
					numType = "mbNumLess"
				}
				mbList +=	`<div class="mbItem">
								<div class="mbIcon">
									<img src="../images/by.png"/>
								</div>
								<div class="mbText">
									<p>` +data_[i].comment+ `</p>
									<p>`+date+`</p>
								</div>
								<div class="mbNum">
									<span class="`+numType+`"> `+data_[i].money+`</span>米币
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

function getMb() {
	mui.ajax('http://www.1miclub.com/api/User/moneyRecord', {
		data: {
			token: usertoken
		},
		dataType: 'jsonp',
		type: 'post',
		success: function(data) {
			data = JSON.parse(data);
			var data = data.data.moneyList.data;
			console.log(data)
			vue.mbs = data;
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