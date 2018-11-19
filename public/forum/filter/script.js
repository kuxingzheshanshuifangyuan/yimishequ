$(document).ready(function(){
							
	$("#select1 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectA").remove();
		} else {
			var copyThisA = $(this).clone();
			if ($("#selectA").length > 0) {
				$("#selectA a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisA.attr("id", "selectA"));
			}
		}
	});
	
	$("#select2 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectB").remove();
		} else {
			var copyThisB = $(this).clone();
			if ($("#selectB").length > 0) {
				$("#selectB a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisB.attr("id", "selectB"));
			}
		}
	});
	
	$("#select3 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectC").remove();
		} else {
			var copyThisC = $(this).clone();
			if ($("#selectC").length > 0) {
				$("#selectC a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisC.attr("id", "selectC"));
			}
		}
	});
	$("#select4 dd").click(function () {
		
		console.log("asds")
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectD").remove();
		} else {
			var copyThisD = $(this).clone();
			if ($("#selectD").length > 0) {
				$("#selectD a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisD.attr("id", "selectD"));
			}
		}
	});
	$("#select5 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectE").remove();
		} else {
			var copyThisE = $(this).clone();
			if ($("#selectE").length > 0) {
				$("#selectE a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisE.attr("id", "selectE"));
			}
		}
	});
	$("#select6 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectF").remove();
		} else {
			var copyThisF = $(this).clone();
			if ($("#selectF").length > 0) {
				$("#selectF a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisF.attr("id", "selectF"));
			}
		}
	});
	$("#select7 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectG").remove();
		} else {
			var copyThisF = $(this).clone();
			if ($("#selectG").length > 0) {
				$("#selectG a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisF.attr("id", "selectG"));
			}
		}
	});
	$("#select8 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectH").remove();
		} else {
			var copyThisF = $(this).clone();
			if ($("#selectH").length > 0) {
				$("#selectH a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisF.attr("id", "selectH"));
			}
		}
	});
	$("#select9 dd").click(function () {
		$(this).addClass("selected").siblings().removeClass("selected");
		if ($(this).hasClass("select-all")) {
			$("#selectI").remove();
		} else {
			var copyThisF = $(this).clone();
			if ($("#selectI").length > 0) {
				$("#selectI a").html($(this).text());
			} else {
				$(".select-result dl").append(copyThisF.attr("id", "selectI"));
			}
		}
	});
	
	$("#selectA").live("click", function () {
		$(this).remove();
		$("#select1 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectB").live("click", function () {
		$(this).remove();
		$("#select2 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectC").live("click", function () {
		$(this).remove();
		$("#select3 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectD").live("click", function () {
		$(this).remove();
		$("#select4 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectE").live("click", function () {
		$(this).remove();
		$("#select5 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectF").live("click", function () {
		$(this).remove();
		$("#select6 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectG").live("click", function () {
		$(this).remove();
		$("#select7 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectH").live("click", function () {
		$(this).remove();
		$("#select8 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	$("#selectI").live("click", function () {
		$(this).remove();
		$("#select9 .select-all").addClass("selected").siblings().removeClass("selected");
	});
	
	$(".select dd").live("click", function () {
		if ($(".select-result dd").length > 1) {
			$(".select-no").hide();
		} else {
			$(".select-no").show();
		}
	});
	
});