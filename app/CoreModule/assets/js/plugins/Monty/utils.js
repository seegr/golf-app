$.fn.iziModal = iziModal;

const Utils = {
	
	randomPassword: function(length) {
	    var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    var pass = "";
	    for (var x = 0; x < length; x++) {
	        var i = Math.floor(Math.random() * chars.length);
	        pass += chars.charAt(i);
	    }
	    return pass;
	},

	getColor: function(color) {
	    var style = getComputedStyle(document.body);
	    var theme = {};

	    return color.includes("#") ? color : style.getPropertyValue("--" + color);
	},

	showModal: function(settings) {
		console.log("showModal...");
	    //console.log(settings);

	    var $modal = $("#modal");

	    var defaults = {
	        autoOpen: true,
	        top: "5%",
	        transitionIn: "fadeInUp",
	        appendTo: "#modal-content",
	        appendToOverlay: "#modal-wrap",
	        //transitionInOverlay: "comingIn",
	        color: "success",
	        onOpened: function(modal) {
	        	// modal.state.$element.addClass("loaded");
	        }
	    };

	    if (settings.type || settings.alert) {
	    	console.log(1);
	        switch (settings.type) {
	            case "alert":
	            case "danger":
	            case "alert-danger":
	                settings.icon = settings.icon ? settings.icon : "fas fa-exclamation-triangle";
	                settings.color = "danger";
	            break;

	            case "warning":
	            case "alert-warning":
	                settings.icon = settings.icon ? settings.icon : "fas fa-exclamation";
	                settings.color = "warning";
	            break;

	            default:
	                settings.icon = settings.icon ? settings.icon : "fas fa-check";
	                settings.color = "success";
	            break;
	        }
	    } else {
	    	console.log(2);
	    }

	    settings.content = settings.text ? settings.text : settings.content;
	    if (settings.content) {
	        $modal.html(settings.content);
	        defaults.padding = "1em";
	    };
	    console.log(settings.color);
	    defaults.headerColor = this.getColor(settings.color ? settings.color : defaults.color);

	    settings = $.extend({}, defaults, settings);
	    console.log("iziModal settings", settings);

	    $modal.iziModal("destroy");
	    $modal.iziModal(settings);

	    if (settings.class != undefined) $modal.addClass(settings.class);

	    //** set max height
	   	var $header = $modal.find(".iziModal-header");
	    var headerH = $header.height();
	    var $wrap = $modal.find(".iziModal-wrap");
	    var $content = $modal.find(".iziModal-content");
	    var wH = $(window).height();

	    if ($wrap.height() + $modal.offset().top >= wH) {
			var maxHeight = wH * 0.8;
	    	console.log("max height", maxHeight);
	    	$modal.css("max-height", maxHeight + headerH + 20 + "px");
		    $wrap.css("max-height", maxHeight + "px");
		    $modal.addClass("scrollIt");
	    } else {
	    	console.log("no max height");
	    	$modal.css("max-height", "auto");
			$wrap.css('max-height', 'auto');
	    	$modal.removeClass('scrollIt');
	    }

		// if(contentHeight > wrapperHeight && outerHeight > windowHeight){
		// 	that.$element.addClass('hasScroll');
		// 	that.$wrap.css('height', modalHeight - (that.headerHeight+borderSize));
		// } else {
		// 	that.$element.removeClass('hasScroll');
		// 	that.$wrap.css('height', 'auto');
		// }

	    //$("#modal").iziModal("open");
	},

	showAlert(settings) {
	    //console.log(settings);

	    $.extend(settings, {
	        alert: true,
	        overlay: false,
	        timeout: 3000,
	        timeoutProgressbar: true,
	        pauseOnHover: true,
	        transitionIn: "fadeInDown",
	        borderBottom: false,
	        top: 0
	    });

	    showModal(settings);
	},

	randomString: function(length) {
	    var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz'.split('');

	    if (! length) {
	        length = Math.floor(Math.random() * chars.length);
	    }

	    var str = '';
	    for (var i = 0; i < length; i++) {
	        str += chars[Math.floor(Math.random() * chars.length)];
	    }
	    return str;
	},

	scrollToAnchor: function(target, speed) {
	    var target = $(target);
	    var speed = speed != undefined ? speed : 'slow';

	    $('html, body').animate({scrollTop: target.offset().top}, 'slow');
	},

	deactiveElement: function(el) {
		console.log("click deactive");

		var el = $(el);
		var h = el.outerHeight();
		var w = el.outerWidth();

		console.log("h: " + h);
		console.log("w: " + w);

		// el.css("position", "relative");
		// var wrap = $("<div class='inactive-wrap'></div>");
		// wrap.css({
		// 	position: "absolute",
		// 	height: h,
		// 	width: w
		// });
		// el.append(wrap);

		var toDeactive = [el];

		if (deStr = el.data("deactive")) {
			console.log(deStr);
			deStr = $.trim(deStr);
			console.log(deStr);
			var arr = deStr.split(",");
			console.log(arr);

			toDeactive = $.merge(toDeactive, arr);
			console.log(toDeactive);
		}

		$.each(toDeactive, function(i, el) {
			var el = $(el);
			el.css({
				filter: "grayscale(1)",
				opacity: ".8",
				cursor: "default"
			});
			el.attr("data-href", el.attr("href"));
			el.attr("href", "javascript:void(0)");
		});
	},

	myFunction: function() {
		/* Get the text field */
		var copyText = document.getElementById("myInput");

		/* Select the text field */
		copyText.select();
		copyText.setSelectionRange(0, 99999); /*For mobile devices*/

		/* Copy the text inside the text field */
		document.execCommand("copy");

		/* Alert the copied text */
		alert("Copied the text: " + copyText.value);
	},

	getRandomNumber: function(from, to) {
		var from = from == undefined ? 0 : from;
		var to = to == undefined ? 100 : to;

		return Math.floor(Math.random() * to) + from;
	}

}


export { Utils };