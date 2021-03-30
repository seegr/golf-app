function cursorLoader(state) {
	var state = state == undefined ? "show" : state;
	var $el = $('<div class="cursor-loader-wrap"><div class="cursor-loader"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
	$el.css("position", "absolute");
	console.log(state);

	var body = $("body");
	var cls = "cursor-loader-active";

	if (state == "show") {
		if (body.hasClass(cls)) return;
		body.addClass(cls);
		$el = $("body").append($el);
	} else {
		body.removeClass(cls);
		$(".cursor-loader-wrap").remove();
	}
}

$(document).on("mousemove", function(e) {
	if (!$("body").hasClass("cursor-loader-active")) return;
	var x = e.pageX;
	var y = e.pageY;

	// console.log("mouse pos: " + x + " " + y);
	$(".cursor-loader-wrap").css({
		top: y,
		left: x
	});
});
