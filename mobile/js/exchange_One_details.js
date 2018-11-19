mui.init({
	//			swipeBack:true, //启用右滑关闭功能
	gestureConfig: {
		longtap: true, //开启长按， 默认为false 
	},
});
        
 

//短按评论
//长按评论
mui('body').on('longtap', '.comment-item', function() {
	//	拿到该评论的评论内容
	var copy_content = $(this).find('.comment-con').text();

	var btnArray = [{
		title: "评论"
	}, {
		title: "复制"
	}, {
		title: "举报"
	}];
	
	mui.plusReady(function(){
		plus.nativeUI.actionSheet({
			title: "编辑评论",
			cancel: "取消",
			buttons: btnArray
		}, function(e) {
			var index = e.index;
			var text = "";
			switch(index) {
				case 1: //评论
					break;
				case 2: //复制
					copy_fun(copy_content);
					break;
				case 3: //举报
					report();
					break;
			}
		});
	});
})

//		----------分享弹出框----------
mui('body').on('tap', '.share-item', function() {
	mui('#share').popover('toggle');
})
mui('body').on('tap', '.share-item_', function() {
	mui('#share_').popover('toggle');
})
//删除帖子
mui('body').on('tap', '#del-forum', function() {
	var btnArray = ['否', '是'];
	mui.confirm('删除后不能恢复，确认删除？', '删除帖子', btnArray, function(e) {
		if(e.index == 1) {
			console.log('确认删除')
		} else {
			console.log('取消删除')
		}
	})
})
//复制链接
mui('body').on('tap', '.copyUrl', function() {
	let url = window.location.href;
	copy_fun(url);
})
//举报
mui('body').on('tap', '#report', function() {
	report();
})

//举报方法
function report() {
	var btnArray = [{
		title: "恶意灌水"
	}, {
		title: "广告诈骗"
	}, {
		title: "淫秽色情"
	}, {
		title: "辱骂他人"
	}, {
		title: "其他原因"
	}];
	
	mui.plusReady(function() {
		plus.nativeUI.actionSheet({
			title: "举报",
			cancel: "取消",
			buttons: btnArray
		}, function(e) {
			console.log(e.title)
			var index = e.index;
			switch(index) {
				case 1:
					mui.toast("举报成功-恶意灌水");
					break;
				case 2:
					mui.toast("举报成功-广告诈骗");
					break;
				case 3:
					mui.toast("举报成功-淫秽色情");
					break;
				case 4:
					mui.toast("举报成功-辱骂他人");
					break;
				case 5:
					mui.toast("举报成功-其他原因");
					break;
			}
		});
	});
}

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
//		--------------分享弹出框结束-------------

//		上拉加载评论
mui.init({
	pullRefresh: {
		container: '#pullrefresh',
		up: {
			height: 50,
			contentrefresh: "",
			contentnomore: '没有更多评论了',
			callback: pullupRefresh
		}
	}
});
//		上拉加载评论方法
var UserTzPage = 1;

function pullupRefresh() {
	var PlListHtml = ''
	var this_ = this;
	UserTzPage = UserTzPage + 1;
	//			setTimeout(function() {
	mui.ajax('http://www.1miclub.com/api/Forum/commentList', {
		data: {
			id: forum.forum_contid,
			page: UserTzPage,
			time: new Date().getTime()
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);

			if(UserTzPage > data.data.page.lastPage) {
				this_.endPullupToRefresh(true);
			} else {
				for(var i = 0; i < data.data.comment.length; i++) {
					PlListHtml += '<li class="mui-table-view-cell mui-media HomeList Sofanth" PLid="' + data.data.comment[i].id + '">' +
						'<a href="details.html">' +
						'<div class="mui-media-body">' +
						'<div class="FloltLeft ">' +
						'<img src="' + PicUrl + data.data.comment[i].userhead + '" alt=""  class="FloltLeft_tx"/>' +
						'<span class="sofa">' + data.data.comment[i].level + '楼</span>' +
						'</div>' +
						'<div class="FloltLeft">' +
						'<p class="FloltLeft_name">' + data.data.comment[i].username + ' </p>' +
						'<p class="FloltLeft_cont">' + escapeHTML(data.data.comment[i].content) + '</p>' +
						'<p class="FloltLeft_gn">' + timestampToTime(data.data.comment[i].create_time) + ' <span class="FloatRight PLzan" zanid="1"><span><img src="../images/zan1.png"/>赞</span></span></p>' +
						'</div>' +
						'</div>' +
						'</a>' +
						'</li>'
				}
				$('.PlList').append(PlListHtml)

				if(UserTzPage == data.data.page.lastPage) {
					this_.endPullupToRefresh(true);
				} else {
					this_.endPullupToRefresh(false);
				}
			}
		},
		error: function() {
			mui.toast('失败');
		}
	});
	//			}, 500);
}

//拿到帖子ID
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
var forum = GetRequest();
//		拿到帖子ID结束

mui(".FloltLeft_gn").on('tap', '.huifu', function() {
	$('.PLtext').focus();
})
// 登录用户金币

var usermoney = window.localStorage.getItem('usermoney');
$('.MyGold span').html(usermoney);
var usertoken = window.localStorage.getItem('usertoken');
var forum_contid = window.localStorage.getItem('forum_contid'); //帖子id

var userID = window.localStorage.getItem("userid"); //用户id
var fourm_uid = "" //帖子作者id


$(".reward-item").click(function(){
	alert("asd")
})

// 在帖子页面点击打赏作者按钮
mui(".detailsBody").on('tap', '.reward', function() {
	if(!usertoken) {
		mui.toast('请先登录');
		window.location.href = "../login.html";
		return false;
	} else if(userID == fourm_uid) {
		mui.toast('您不能打赏自己！');
	} else {
//		$('.DsMark').removeClass('DisNone');
		$('.reward-back').fadeIn();
//		var dasmoney = $('#dasmoney').html();
//		var num = "";
		initReward();
	}
	
});

function closeReward(){
	$('.reward-back').fadeOut();
}
//初始化赏金
function initReward(){
	mui.ajax('http://www.1miclub.com/api/Forum/giveRewardList', {
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			var data = JSON.parse(data);
			vue.rewards = data.data;
			
			
//			var money = "";
//			for(var i = 0; i < data.data.length; i++) {
//				money += `<div class="MiMi_cont">${data.data[i].money}米米</div>`;
//				if(data.data[i].money == dasmoney) {
//					num = i + 1;
//				}
//			}
//			money += `<div class="MiMi_cont customMimi">自定义</div>`;
//			$('#reward').html(money);
		},
//		complete: function() {
//			if(num != "") {
//				$('.MiMi_cont').eq(num - 1).addClass('mimiActive');
//			}
//
//		}
	})
}
//选择赏金
function checkMoney(dom){
	$('.reward-money').html(parseFloat($(dom).find('span').html()))
	$(dom).addClass('reward-item-active').siblings().removeClass('reward-item-active')
}
//提交打赏
function rewardSub(){
	let money = $('.reward-money').html();
	if(money == 0){
		mui.toast("请选择打赏金额")
	}else{
		mui.ajax('http://www.1miclub.com/api/Forum/giveReward', {
			data: {
				token: usertoken,
				id: forum.forum_contid,
				money: money
			},
			dataType: 'jsonp',
			type: 'post',
			timeout: 10000,
			success: function(data) {
				data = JSON.parse(data);
				if(data.error_code === 0) {
					mui.toast('打赏成功');
					$('.reward-back').fadeOut();
				} else {
					mui.toast(data.msg);
				}
			}
		})
	}
	console.log(money)
}

//自定义打赏金额
function rewardCustom(){
	$('.reward-item').removeClass("reward-item-active");
	$('.reward-money').text('0');
	let myMoney = $('.my-money').html();
//	e.detail.gesture.preventDefault(); //修复iOS 8.x平台存在的bug，使用plus.nativeUI.prompt会造成输入法闪一下又没了
	var btnArray = ['取消', '确定'];
	mui.prompt('请输入您要打赏的米币数', '您当前拥有' + myMoney + '米币', '自定义打赏金额', btnArray, function(e) {
		if(e.index == 1) {
			var reg = /^[0-9]+.?[0-9]*$/;
			if(!reg.test(e.value)) {
				mui.toast("请输入数字！")
			} else if(parseInt(e.value) > parseInt(myMoney)) {
				mui.toast("米币余额不足!")
			} else {
				$(".reward-money").html(e.value);
			}
		}
	})
}
//原来的选择金额方法
//mui(".MiMi").on('tap', '.MiMi_cont', function() {
//	$('.reward-money').html(parseFloat($(this).html()))
//	if($('.RewardGold span').html() > 0) {
//		$('.DsMark_cont_Btn').removeAttr('disabled')
//	}
//	$(this).addClass('mimiActive').siblings().removeClass('mimiActive')
//})
// 原来的 在打赏弹出框内 点击打赏作者
//mui(".DsMark").on('tap', '#DsMark_cont_Btn', function() {
//	var rewargold = $('.RewardGold').find('span').html();
//	if(rewargold == 0) {
//		mui.toast('请选择打赏金额');
//	} else {
//		mui.ajax('http://www.1miclub.com/api/Forum/giveReward', {
//			data: {
//				token: usertoken,
//				id: forum.forum_contid,
//				money: rewargold
//			},
//			dataType: 'jsonp',
//			type: 'post',
//			timeout: 10000,
//			success: function(data) {
//				data = JSON.parse(data);
//				if(data.error_code === 0) {
//					mui.toast('打赏成功');
//					$('.DsMark').addClass('DisNone');
//					//								$('.reward').val('已打赏作者').attr('disabled',true);
//					//								$('.reward').addClass('rewardAct')
//					$('.peopleReward span').html(parseInt($('.peopleReward span').html()) + 1)
//				} else {
//					mui.toast(data.msg);
//				}
//			}
//		})
//	}
//})
//点击打赏遮罩,打赏框消失
//mui(document).on('tap', '.DsMark', function() {
//	var e = e || window.event; //浏览器兼容性
//	var elem = e.target || e.srcElement;
//	while(elem) { //循环判断至跟节点，防止点击的是div子元素
//		if(elem.id && elem.id == 'test') {
//			return;
//		}
//		elem = elem.parentNode;
//	}
//	$('.DsMark').addClass('DisNone'); //点击的不是div或其子元素
//});
//$('.DsMark').bind("touchmove", function(e) {
//	e.preventDefault();
//});

//点击表情按钮 表情出来 点击别的对方 表情下去
function emoji(){
	if($('#list_emotion').css("display") == "none") {
		$('#list_emotion').slideDown(250);
		$('#nav_emotion').slideDown(250);
	} else {
		$('#list_emotion').slideUp(250);
		$('#nav_emotion').slideUp(250);
	}
	$(document).one("tap", function() {
		$('#list_emotion').slideUp(250);
		$('#nav_emotion').slideUp(250);
	});
//	e.stopPropagation();
}

//原来的 表情按钮
//$(".PinLun").on("tap", ".emojiBq", function(e) {
//	if($('#list_emotion').css("display") == "none") {
//		$('#list_emotion').slideDown(250);
//		$('#nav_emotion').slideDown(250);
//	} else {
//		$('#list_emotion').slideUp(250);
//		$('#nav_emotion').slideUp(250);
//	}
//	$(document).one("tap", function() {
//		$('#list_emotion').slideUp(250);
//		$('#nav_emotion').slideUp(250);
//	});
//	e.stopPropagation();
//})
//$(".PinLun").on("tap", ".box_swipe", function(e) {
//	e.stopPropagation();
//});

$(function() {
	$("#form_article").bind('input propertychange', function() {
		if($("#form_article").html().length == 0) {
			$("#form_article").addClass('placeholder');
		}else{
			$("#form_article").removeClass('placeholder');
		}
    });
	
	
//	if($("#form_article").html() === "") {
//		$("#form_article").addClass('placeholder')
//	}else{
//	}
//	$("#form_article").click(function() {
//		var a = $("#form_article").html();
//		if($("#form_article").html().length == 0) {
//			$('#list_emotion').slideUp(250);
//			$('#nav_emotion').slideUp(250);
//		}else{
//		}
//	});
//	$("#page_emotion  dd").click(function() {
//		$("#form_article").html($("#form_article").html().replace(say, ''));
//	});
});



//格式化帖子文本方法
function escapeHTML(a) {
	a = "" + a;
	return a.replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&amp;/g, "&").replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/src="/g, 'src="http://www.1miclub.com');
}


var vue = new Vue({
	el: '.post-detail',
	data: {
		post: "",
		create_time: '',
		postContent: '',
		rewards:'',
		comments:'',
		time:'',
		level:'',
		contents:'',
		
		sofa:'',
		
	},
})
//格式化时间方法
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
initPost();
getComments();
//初始化帖子
function initPost() {
	var UserTzHtml = '';
	var UserHtml = '';
	//			拿到帖子详情
	mui.ajax('http://www.1miclub.com/api/Forum/detail', {
		data: {
			id: forum.forum_contid,
			time: new Date().getTime()
		},
		dataType: 'jsonp',
		type: 'get',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);
			vue.post = data.data.forum_data; //========================

			fourm_uid = data.data.forum_data.uid
			vue.create_time = timestampToTime(data.data.forum_data.create_time)
			
			console.log(data.data.forum_data.content)
			vue.postContent = escapeHTML(data.data.forum_data.content);
			console.log(escapeHTML(data.data.forum_data.content))
			//			判断是否是发帖人是否是查看此贴的人，如果是，会出现删除该帖子的选项
			if(userID == fourm_uid) {
				$("#del-forum").show()
			}
			//					forum.forum_contid = data.data.forum_data.id;
			if(data.error_code == 0) {
				for(var i = 0; i < data.data.giveRrewardList.length; i++) {
					var userheada = '<div class="rewardHead">' +
						'<p><span>共<b>' + data.data.forum_data.reward_sum + '</b>人打赏</span><img src="' + PicUrl + data.data.giveRrewardList[i].userhead + '"/></p>' +
						'</div>'
				}
				UserTzHtml = '<li class="mui-table-view-cell mui-media HomeList tiezi" tieziid="1" usertieziid=' + data.data.forum_data.id + '>' +
					'<a href="javascript:;">' +
					'<div class="mui-media-body">' +
					' <div class="HomeList_pOne">' +
					data.data.forum_data.title +
					'</div>' +
					'<p class="mui-ellipsis HomeList_pTwo">' +
					'<img src="' + PicUrl + data.data.forum_data.userhead + '" class="HomeList_pTwo_img"/>' +
					'<span>' + data.data.forum_data.username + '</span>' +
					'<span>|</span>' +
					'<span>' + HoMiSe(data.data.forum_data.create_time) + '</span>' +
					'<span class="MarLeft5 "><img src="../images/dianzan.png"/><span class="zanNum">' + data.data.forum_data.praise + '</span></span>' +
					'<span class="MarLeft5"><img src="../images/liuyan.png" /><span class="liuyanNum">' + data.data.forum_data.reply + '</span></span>' +
					'<span class="MarLeft5"><img src="../images/liulan.png"/>' + (parseInt(data.data.forum_data.view) + 1) + '</span>' +
					'</p>' +
					' </div>' +
					'</a>' +
					'</li>' +
					'<div class="details_cont">' +
					'<p class="details_cont_cont">' + escapeHTML(data.data.forum_data.content) + '</p>' +
					'<p class="details_cont_author">本主题由 <span>' + data.data.forum_data.username + '</span>于<span>' + timestampToTime(data.data.forum_data.create_time) + '</span>分享</p>' +
					'<input type="button" value="打赏作者" class="reward"/>' +
					'<p class="peopleReward"><span>' + data.data.forum_data.reward_sum + '</span>人打赏</p>' +
					'<div class="buttonWrap">' +

					'<div class="praise" zanid="1"><img src="../images/huangzan.png" />赞<span>' + data.data.forum_data.praise + '</span></div>' +
					'<div class="trample" zanid="2"><img src="../images/lvxiazan.png" />踩<span>' + data.data.forum_data.trample + '</span></div>' +

					'</div>' +
					'</div>'

//					$('.HomeListOne').html(UserTzHtml)

				//将没有域名的图片地址加上本服务器的域名以展示出来
				var len = $('.post-content p img').length;
				for(var i = 0; i < len; i++) {
					var src = $('.post-content p img:eq(' + i + ')').attr('src');
					if(src.indexOf('http') == '-1') {
						src.replace(/src=&quot;/g, 'src="http://www.1miclub.com/');
	
						src = "http://www.1miclub.com/" + src;
						$('.post-content p img:eq(' + i + ')').attr('src', src)
					}
				}

				var DsMark_cont_tophtml = '<img src="' + PicUrl + data.data.forum_data.userhead + '" />' +
					'<div class="DsMark_cont_top_FloftLeft">' +
					'<p>' + data.data.forum_data.username + '</p>' +
					'<p>感谢分享，互相帮助aaa</p>' +
					'</div>'

				//$('.DsMark_cont_top').html(DsMark_cont_tophtml)

				//在帖子详情初始化之后,添加图片预览功能
				mui.plusReady(function() {
					var images = [].slice.call(document.querySelectorAll('.post-content p img'));
					var urls = [];
					images.forEach(function(item) {
						urls.push(item.src);
					});
					mui('.post-content').on('tap', 'img', function() {
						var index = images.indexOf(this);
						plus.nativeUI.previewImage(urls, {
							current: index,
							loop: false,
							indicator: 'number'
						});
					});
				});

			}
		},
		error: function() {
			mui.toast('失败');
		}
	});
}


function escapeHTML_comments(a) {
	a = "" + a;
	return a.replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&amp;/g, "&").replace(/&quot;/g, '"').replace(/&apos;/g, "'");
}
//获取评论
function getComments() {
	var UserTzPage = 1;
	var PlListHtml = '';
	var times = [];
	var contents = [];
	mui.ajax('http://www.1miclub.com/api/Forum/commentList', {
		data: {
			id: forum.forum_contid,
			page: UserTzPage,
			time: new Date().getTime()
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {

			data = JSON.parse(data);
			
			vue.comments = data.data.comment;
			
			if(data.data.page.lastPage == 0) {
				mui('#pullrefresh').pullRefresh().disablePullupToRefresh();
				$('.PlList').html('<p class="noComm">这里什么都没有哦！</p>')
			} else {
				for(var i = 0; i < data.data.comment.length; i++) {
					
					let time = timestampToTime(data.data.comment[i].create_time);
					times.push(time);
					vue.time = times;
					
					let content_ = escapeHTML_comments(data.data.comment[i].content)
					
					contents.push(content_);
					vue.contents = contents;
					
					
					if(data.data.comment[i].level == 1) {
						data.data.comment[i].level = '沙发'
					} else if(data.data.comment[i].level == 2) {
						data.data.comment[i].level = '板凳'
					} else if(data.data.comment[i].level == 3) {
						data.data.comment[i].level = '地板'
					} else {
						data.data.comment[i].level = (i + 1) + '楼'
					}
					PlListHtml += '<li class="mui-table-view-cell mui-media HomeList Sofanth" PLid="' + data.data.comment[i].id + '">' +
						'<a >' +
						'<div class="mui-media-body">' +
						'<div class="FloltLeft ">' +
						'<img src="' + PicUrl + data.data.comment[i].userhead + '" alt=""  class="FloltLeft_tx"/>' +
						'<span class="sofa">' + data.data.comment[i].level + '</span>' +
						'</div>' +
						'<div class="FloltLeft">' +
						'<p class="FloltLeft_name">' + data.data.comment[i].username + ' </p>' +
						'<p class="FloltLeft_cont">' + escapeHTML(data.data.comment[i].content) + '</p>' +
						'<p class="FloltLeft_gn">' + timestampToTime(data.data.comment[i].create_time) + ' <span class="FloatRight PLzan" zanid="1"><span><img src="../images/zan1.png"/>赞</span></span></p>' +
						'</div>' +
						'</div>' +
						'</a>' +
						'</li>'
				}
//				$('.PlList').html(PlListHtml);
//				$('.homeNewest_zuixin span').html(data.data.page.total)
			}

		},
		error: function() {
			mui.toast('失败');
		}
	});


}
//		发布评论
mui(".PinLun").on('tap', '.PL-FB', function() {
	var PLtext = $('.PLtext').html();
	if(PLtext == '' || PLtext == '说点什么...') {
		mui.toast('请添加内容');
		return false;
	}
	if(!usertoken) {
		//				mui.toast('请先登录');

		window.location.href = "../login.html";
		return false;
	}
	mui.ajax('http://www.1miclub.com/api/Forum/addComment', {
		data: {
			token: usertoken,
			content: PLtext,
			fid: forum.forum_contid,
			time: new Date().getTime(),
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);
			if(data.error_code === 0) {
				mui.toast('回帖成功');
				$('#form_article').text(''); //清空回复内容
				$('#form_article').blur();
				$('.reply-num').text(parseInt($('.reply-num').text()) + 1);
				getComments();

				//  					pullupRefresh();
				//						$('.mui-content').animate({scrollTop:20000},300);
				//  					$('html,body').animate({scrollTop:document.body.clientHeight + 'px'},300);
			}
			if(data.error_code === 100 || data.error_code === 101) {
				mui.toast('回帖失败，请稍后重试');
			}
		},
		error: function() {
			mui.toast('失败');
		}
	});

});

//		赞和踩的方法
function ZanCai(vote_type, type, id, add) {
	var usertoken = window.localStorage.getItem('usertoken');
	if(!usertoken) {
		mui.toast('请先登录');
		return false;
	}
	mui.ajax('http://www.1miclub.com/api/Forum/vote', {
		data: {
			token: usertoken,
			vote_type: vote_type,
			type: type,
			id: id
		},
		dataType: 'jsonp',
		type: 'post',
		timeout: 10000,
		success: function(data) {
			data = JSON.parse(data);
			if(data.error_code === 0) {
				if(vote_type == '1') {
					mui.toast('成功点赞！');
					$('.praise').find('span').html(parseInt($('.praise').find('span').html()) + 1);
					if(add == true) {
						$('.praise-num').text(parseInt($('.praise-num').text()) + 1);
					}

				} else {
					mui.toast('成功踩他！');
					$('.trample').find('span').html(parseInt($('.trample').find('span').html()) + 1);
				}
			} else {
				mui.toast(data.msg);
			}
		},
		error: function() {
			mui.toast('失败');
		}
	});
}
//赞
mui("body").on('tap', '.praise', function() {
	var zanid = $(this).attr('zanid');
	var tieziid = $('.post-info').attr('tieziid');
	var usertieziid = $('.post-info').attr('usertieziid');
	ZanCai(zanid, tieziid, usertieziid, true);
	//				$('.praise').find('span').html(parseInt($(this).find('span').html())+1)
})

//踩
mui("body").on('tap', '.trample', function() {
	var zanid = $(this).attr('zanid');
	var tieziid = $('.post-info').attr('tieziid');
	var usertieziid = $('.post-info').attr('usertieziid');
	ZanCai(zanid, tieziid, usertieziid, false)
	//				$(this).find('span').html()+1;
})
//评论赞
mui("body").on('tap', '.PLzan', function() {
	var zanid = $(this).attr('zanid');
	var tieziid = $('.post-info').attr('tieziid');
	var usertieziid = $(this).parents('.Sofanth').attr('plid');
	ZanCai(zanid, tieziid, usertieziid, false);
})
