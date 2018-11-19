function tabSelect() {
  var index;
  $('.set-tab .set-tab-top span').click(function() {
    $(this).addClass('tab-seclet').siblings().removeClass('tab-seclet');
    index = $(this).index();
    $('.set-tab-content >div:eq('+index+')').addClass('tab-content-show').siblings().removeClass('tab-content-show');
  });
};