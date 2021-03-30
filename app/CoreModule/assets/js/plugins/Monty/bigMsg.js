(function() {

var bigMsg = function () {
	var inner = {
		messageWrap: $("#big-message-wrap"),
		message: this.messageWrap.find(".message")
	};

	this.test = function() {
		// console.log("hovno vole");
	}

	// var init = function() {
	// 	this.showBigMessage();

	// 	$(document).on("click", this.message, function() {
	// 		$(this).fadeOut(300);
	// 	});
	// }

	var showBigMessage = function(settings) {
		// console.log("showBigMessage...");
	    //console.log(settings);


	    $messageWrap.click(function() {
	    	$(this).fadeOut(100);
	    });

	    var defaults = {
	    	fixed: false
	    };

	    settings = $.extend({}, defaults, settings);
	    // console.log("bigMessage settings", settings);

	    $message.html(settings.text);
	    $messageWrap.show();

	    if (!settings.fixed) {
			var msgInterval = setInterval(function() {
			    $messageWrap.fadeOut(300);
			    // console.log("bum!");
			    clearInterval(msgInterval);
			}, 3000);
	    }
	}
}

// $.nette = new ($.extend(nette, $.nette ? $.nette : {}));

$.monty = $.monty ? $.monty : {};
$.monty.bigMsg = $.monty.bigMsg ? $.monty.bigMsg : bigMsg;
// $.monty.bigMsg.init();

})();