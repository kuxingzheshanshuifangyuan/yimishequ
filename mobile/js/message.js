$('.tab').click(function(){
	var index = $(this).index();
	$(this).addClass('tab-active').siblings().removeClass('tab-active')
	$('.nav-item:eq('+index+')').show().siblings().hide();
})
