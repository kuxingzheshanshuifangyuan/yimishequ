mui.init({
	
	gestureConfig:{
	    longtap: true, //开启长按 默认为false 
	},
	
	pullRefresh: {
		container: '#pullrefresh', //待刷新区域标识，querySelector能定位的css选择器均可，比如：id、.class等
		up: {
			height: 50, //可选.默认50.触发上拉加载拖动距离
			contentrefresh: "正在加载...", //可选，正在加载状态时，上拉加载控件上显示的标题内容
			contentnomore: '没有更多帖子了', //可选，请求完毕若没有更多数据时显示的提醒内容；
			callback: pullupRefresh //必选，刷新函数，根据具体业务来编写，比如通过ajax从服务器获取新数据；
		}
	}
});
// 接受参数
function GetRequest() {
	var url = location.search; //获取url中"?"符后的字串
	var theRequest = new Object();
	if(url.indexOf("?") != -1) {
		var str = url.substr(1);
		strs = str.split("&");
		for(var i = 0; i < strs.length; i++) {
			theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]);
		}
	}
	return theRequest;
}
var a = GetRequest();

//			拿到页面滚动距离
function exPageToTop() {
	sessionStorage.setItem("expPageScroll", $(document).scrollTop());
}

//每次点击tab,更换id,进行遍历
mui(".exchangerBody").on('tap', '.tab_btn a', function() {
	$(this).siblings().removeClass('mui-active');
	mui('#pullrefresh').pullRefresh().refresh(true);
	tabbtnid = $(this).attr('tabbtnid');
	setdata(tabbtnid)
	window.localStorage.setItem("forumID", tabbtnid)
})

//初始化帖子列表
function setdata(forumID) {
	let TopHtml = "";
	let tabHtml = '';
	// 定义置顶接受数组名字
	var arrData1 = [];
	// 定义普通接受数组名字
	var arrData = [];

	mui.ajax('http://www.1miclub.com/api/Forum/forumList', {
		data: {
			forumCate: forumID,
			page: 1,
			//  		time	  : random_time,
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);
			//  		判断帖子条数如果少于8个，不显示上拉加载
			if(data.data.list.length < 8) {
				$('.mui-pull').hide()
			} else {
				$('.mui-pull').show()
			}

			if(data.data.list.length == 0) {
				$('.homeNewest .homeNewestList').html('<p class="no-fourm">暂无帖子...</p>');
			}
			if(data.error_code === 0) {
				currentPage = 'data.data.page.currentPage';
				for(var i = 0; i < data.data.list.length; i++) {
					var picHtml = data.data.list[i].pic;
					if(picHtml) {
						picHtml = '<img class="mui-media-object mui-pull-right HomeList_img" src="' + PicUrl + data.data.list[i].pic + '">';
					} else {
						picHtml = '';
					}
					// 判断json对象里面settop等于1的添加到置顶的数组里面
					if(data.data.list[i].settop == 1) {
						arrData1.push(data.data.list[i])
					}
					// 判断json对象里面settop等于0的添加到普通的数组里面
					if(data.data.list[i].settop == 0) {
						// console.log(data.data.list[i])
						arrData.push(data.data.list[i])
					}
				}
				// 判断如果置顶数组里面没有数据，就让置顶的父元素隐藏，如果有就渲染数据
				if(arrData1.length == '0') {
					$('.homeNewestZd').parents('.homeNewest').css('display', 'none');
				} else {
					$('.homeNewestZd').parents('.homeNewest').show();
					// 渲染置顶数据
					//								$('.homeNewestZd').parents('.homeNewest').css('display','block');
					for(var g = 0; g < arrData1.length; g++) {
						TopHtml += '<li class="mui-table-view-cell mui-media HomeList" tab-contid=' + arrData1[g].id + '>' +
							'<a href="javascript:;">' +
							picHtml +
							'<div class="mui-media-body">' +
							'<div class="HomeList_pOne">' +
							'<span>【置顶】</span>' + arrData1[g].title +
							'</div>' +
							'<p class="mui-ellipsis HomeList_pTwo">' +
							'<img src="' + PicUrl + arrData1[g].userhead + '"/>' +
							'<span>' + arrData1[g].username + '</span>' +
							'<span>|</span>' +
							'<span>' + arrData1[g].create_time_text + '</span>' +
							'<span>' + arrData1[g].view + '阅读</span>' +
							'</p>' +
							'</div>' +
							'</a>' +
							'</li>'
						$('.homeNewestZd').html(TopHtml)
					}
				}
				// console.log(arrData)
				// 渲染普通数据
				for(var j = 0; j < arrData.length; j++) {
					var picHtml = arrData[j].pic;
					if(picHtml) {
						picHtml = '<img class="mui-media-object mui-pull-right HomeList_img" src="' + PicUrl + arrData[j].pic + '">';
					} else {
						picHtml = '';
					}
					tabHtml += '<li class="mui-table-view-cell mui-media HomeList" tab-contid=' + arrData[j].id + '>' +
						'<a href="javascript:;">' +
						picHtml +
						'<div class="mui-media-body">' +
						'<div class="HomeList_pOne">' +
						arrData[j].title +
						'</div>' +
						'<p class="mui-ellipsis HomeList_pTwo">' +
						'<img src="' + PicUrl + arrData[j].userhead + '"/>' +
						'<span>' + arrData[j].username + '</span>' +
						'<span>|</span>' +
						'<span>' + arrData[j].create_time_text + '</span>' +
						'<span>' + arrData[j].view + '阅读</span>' +
						'</p>' +
						'</div>' +
						'</a>' +
						'</li>'
					$('.homeNewestList').html(tabHtml)
				}
			}

			var _offset = sessionStorage.getItem("expPageScroll");　　
			$(document).scrollTop(_offset);
		},
		error: function() {
			mui.toast('失败');
		}
	});
}

var forumID = window.localStorage.getItem('forumID');

var packaging = window.localStorage.getItem("packaging");
var forumCateApi = "";

if(packaging == '1') {
	forumCateApi = "http://www.1miclub.com/api/Home/forumCateList";
} else {
	forumCateApi = "http://www.1miclub.com/api/Forum/forumCateList";
}
initTabs(forumCateApi);

//			初始化头部tab
function initTabs(forumCateApi) {
	var tab_btnHtml = '';
	var tabHtml = '';
	mui.ajax(forumCateApi, {
		data: {},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);
			for(var i = 0; i < data.data.length; i++) {
				tab_btnHtml += '<a class="mui-control-item" href="#item' + i + 'mobile" tabBtnid=' + data.data[i].id + '>' +
					'' + data.data[i].name + '</a>'
			}
			$('.tab_btn').html(tab_btnHtml)

		},
		error: function() {
			mui.toast('失败');
		},
		complete: function() {
			//请求完成的处理

			// 调用渲染数据
			if(forumID) {
				var dom = $(".mui-control-item[tabbtnid = " + forumID + "]");
			} else {
				var dom = $(".mui-control-item:first-child");
				forumID = dom.attr('tabbtnid');
			}
			dom.addClass('mui-active');
			var left = dom.position().left;
			$('.tab_btn').scrollLeft(left)
			setdata(forumID)
			var transform = sessionStorage.getItem('transform');
			$('.mui-slider-item').css('transform', transform)
		},
	});

}

// 从交流区进入帖子详情
mui(".exchangerBody").on('tap', '.HomeList', function() {
	//				拿到帖子id
	var contid = $(this).attr('tab-contid');
	//				拿到页面高度并保存
	exPageToTop();
	window.location.href = 'exchange_One_details.html?forum_contid=' + contid;
	//				拿到导航栏的左右位置，并保存
	var transform = $('.mui-slider-item').css('transform')
	sessionStorage.setItem('transform', transform)
})

/**
 * 上拉加载具体业务实现
 */
function pullupRefresh() {
	var exImgHtml = '';
	var tabHtml = '';
	var this_ = this;
	page = page + 1;
	mui.ajax('http://www.1miclub.com/api/Forum/forumList', {
		data: {
			forumCate: forumID,
			time: new Date().getTime(),
			page: page
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);

			if(page > data.data.page.lastPage) {
				this_.endPullupToRefresh(true);
			} else {

				for(var i = 0; i < data.data.list.length; i++) {
					if(data.data.list[i].pic) {
						exImgHtml = '<img class="mui-media-object mui-pull-right HomeList_img" src="' + PicUrl + data.data.list[i].pic + '">'
					} else {
						exImgHtml = '';
					}
					if(data.data.list[i].settop == 1) {
						tabHtml += '<li class="mui-table-view-cell mui-media HomeList" tab-contid=' + data.data.list[i].id + '>' +
							'<a href="javascript:;">' +
							exImgHtml +
							'<div class="mui-media-body">' +
							'<div class="HomeList_pOne">' +
							'<span>【置顶】</span>' + data.data.list[i].title +
							'</div>' +
							'<p class="mui-ellipsis HomeList_pTwo">' +
							'<img src="' + PicUrl + data.data.list[i].userhead + '"/>' +
							'<span>' + data.data.list[i].username + '</span>' +
							'<span>|</span>' +
							'<span>' + data.data.list[i].create_time_text + '</span>' +
							'<span>' + data.data.list[i].view + '</span>' +
							'</p>' +
							'</div>' +
							'</a>' +
							'</li>'
						$('.homeNewestZd').append(tabHtml)
					} else {
						tabHtml += '<li class="mui-table-view-cell mui-media HomeList" tab-contid=' + data.data.list[i].id + '>' +
							'<a href="javascript:;">' +
							exImgHtml +
							'<div class="mui-media-body">' +
							'<div class="HomeList_pOne">' +
							data.data.list[i].title +
							'</div>' +
							'<p class="mui-ellipsis HomeList_pTwo">' +
							'<img src="' + PicUrl + data.data.list[i].userhead + '"/>' +
							'<span>' + data.data.list[i].username + '</span>' +
							'<span>|</span>' +
							'<span>' + data.data.list[i].create_time_text + '</span>' +
							'<span>' + data.data.list[i].view + '阅读</span>' +
							'</p>' +
							'</div>' +
							'</a>' +
							'</li>'
						$('.homeNewestList').append(tabHtml)
					}
				}
				if(page == data.data.page.lastPage) {
					this_.endPullupToRefresh(true);
					//									$('.homeNewestList').css('margin-bottom','0')
				} else {
					this_.endPullupToRefresh(false);
				}
			}
			//						到达指定页面高度
			var _offset = sessionStorage.getItem("expPageScroll");　　
			$(document).scrollTop(_offset);
		},
		error: function() {
			mui.toast('失败');
		}
	});
}

//点击发帖按钮去issue页面
mui(".exchangerBody").on('tap', '.Fbxt', function() {
	var tab_btnid = $('.tab_btn .mui-active').attr('tabbtnid');
	if(!usertoken) {
		mui.toast("登录后才能发帖")
		mui.openWindow({
			url: '../login.html',
			id: 'login.html',
		});
	} else {
		mui.openWindow({
			url: 'Issue.html?tab_btnid=' + tab_btnid,
			id: 'Issue.html',
		});
	}
})