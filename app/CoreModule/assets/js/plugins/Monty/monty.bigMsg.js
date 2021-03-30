(function(window, $, undefined) {

if (typeof $ !== 'function') {
	return console.error('jQuery is missing, load it please');
}

var bigMsg = function () {
	var settings = {
		// self: this,
		fixed: false,
		$messageWrap: null,
		$message: null,
		fadeTime: 300,
		fadeInterval: 1500
	};

	var msgInterval;

	// var test = "hovno";
	// var $messageWrap = $("#big-message-wrap");
	// var $message = $messageWrap.find(".message");

	this.init = function() {
		// console.log("bigMsg init...");
		settings.$messageWrap = $("#big-message-wrap");
		settings.$message = settings.$messageWrap.find(".message");

	    if (settings.$message.length && !settings.$message.hasClass("fixed")) {
			msgInterval = setInterval(function() {
			    settings.$messageWrap.fadeOut(settings.fadeTime);
			    console.log("bum!");
			    clearInterval(msgInterval);
			}, settings.fadeInterval);
	    }
	}

	// console.log("$message :", settings.$message);

	$(document).on("click", "#big-message-wrap .message", function(e) {
		console.log("bigmsg click");
		// var $target = $(e.target);
		// // console.log($(e.target));
		// console.log($messageWrap);
		// console.log(settings);
		settings.$messageWrap.fadeOut(settings.fadeTime);
		clearInterval(msgInterval);
	});

	/**
	 * Allows manipulation with extensions.
	 * When called with 1. argument only, it returns extension with given name.
	 * When called with 2. argument equal to false, it removes extension entirely.
	 * When called with 2. argument equal to hash of event callbacks, it adds new extension.
	 *
	 * @param  {string} Name of extension
	 * @param  {bool|object|null} Set of callbacks for any events OR false for removing extension.
	 * @param  {object|null} Context for added extension
	 * @return {$.nette|object} Provides a fluent interface OR returns extensions with given name
	 */
	this.hovno = function () {
		// console.log("hovno");
		// return this;
	}
};

$.monty = $.monty ? $.monty : {};
$.monty.bigMsg = new ($.extend(bigMsg, $.monty.bigMsg ? $.monty.bigMsg : {}));

// $.fn.netteAjax = function (e, options) {
// 	return $.nette.ajax(options || {}, this[0], e);
// };

// $.fn.netteAjaxOff = function () {
// 	return this.off('.nette');
// };

})(window, window.jQuery);