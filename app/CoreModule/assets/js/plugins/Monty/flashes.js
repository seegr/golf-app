$(document).on("click", ".flashes .flash", function() {
	if ($(this).hasClass("flash")) {
		var msg = $(this);
	} else {
		var msg = $(this).parents(".flash");
	}

	hideMsg(msg);
});

$(document).on("click", ".flashes", function(e) {
	if (e.target !== this) {
		return;
	}
	$(".flashes").fadeOut(100, function() {
		$(".flashes .flash").remove();	
	});		
});

export function init() {
	var $flashes = $(".flashes");
	var $msgs = $flashes.find(".flash");

	if ($msgs.length > 0) {
		$flashes.show("slide", {direction: 'right', duration: 400});

		setInterval(function() {
		    var msg = $flashes.find(".flash:not(.fixed):not(.stay)").first();
		    hideMsg(msg);
		}, 3000);
	}	
}

function hideMsg(msg) {
	msg.hide("slide", {direction: "right", duration: 400}, function() {
		msg.remove();			
		if ($(".flashes .flash").length === 0) {
			$(".flashes").hide();
		}
	});
}