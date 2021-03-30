const pageLoaderSel = "#monty-page-loader";

export function getPageLoader() {
	return $(pageLoaderSel);
}

export function getPageLoaderPr() {
	return $(pageLoaderSel).find(".progress");
}

export function pageLoaderStart() {
	getPageLoaderPr().stop();
	getPageLoaderPr().width(0);
	getPageLoader().show();
	getPageLoaderPr().animate({"width": "60%"}, 500, function() {
		$(this).animate({"width": "80%"}, 1500, function() {
			$(this).animate({"width": "90%"}, 2000, function() {
				$(this).animate({"width": "98%"}, 15000);
			});
		});
	});
}

export function pageLoaderDone() {
	getPageLoaderPr().stop().animate({"width": "100%"}, 200, function() {
		getPageLoader().hide();
	});
}


export function beforeUnload() {
	pageLoaderStart();
}

export function loaderOn(el, loaderId) {
	// console.log("loaderOn :", el);
	// console.log("loaderId :", loaderId);
    let $loaderWrap = $("<div class='loader-wrap'></div>");
    let $loaderBox = $("<div class='loader-box'></div>");
    let $loader = $("<div class='element-loader'></div>");
    let $el = $(el);
    let $window = $(window);
    let windowH = $window.height();
    let windowW = $window.width();

    // $loaderWrap.css({
    // 	"position: absolute",
    // });

    // $loaderBox.css({
    // 	"position": ""
    // });

    // $loader.css({
    // 	"position": "absolute"
    // });

    let $spinner = $('<div class="spinner round"></div>');

    $loader.html($spinner);
    if (loaderId) {
    	$loader.attr("id", loaderId);
    }

    // if ($el === undefined) {
    // 	throw "";
    // }

    if ($el.length) {
	    if ($el.css("position") !== "absolute") {
	        // if (width > windowW) {
	        //     width = windowW;
	        //     loaderOn("window");
	        //     return;
	        // }
	        // let height = $el.outerHeight();
	        // if (height > windowH) {
	        //     height = windowH;
	        //     loaderOn("window");
	        //     return;
	        // }

		    $el.addClass("loader-on loader-fixed");

	    	// $el.css("position", "relative");
	    } else {
	        let posY = $el.offset().top;
	        let posX = $el.offset().left;
	        // var position = "absolute";
	        let width = $el.outerWidth();

	        $loader.css({
	            "top": posY + "px",
	            "left": posX + "px",
	            "width": width + "px",
	            "height": height + "px"
	        });
	    }

	    // $el.append($loaderWrap);
	    // $loaderWrap.append($laoder);

	    $el.append($loader);
    }
}

export function loaderOff(loaderId) {
	if (loaderId) {
		let $loader = $("#" + loaderId);
		let $parent = $loader.parent();
		$loader.remove();
		$parent.removeClass("loader-on loader-fixed");
	}
}

// export function loaderOn(element) {
//     // console.log("loaderOn...");
//     // console.log(element);
//     var $window = $(window);
//     var windowH = $window.height();
//     var windowW = $window.width();
//     var $loader = $(".loader");
//     var $spinnerBig = $(".loader .spinner-big");
//     var $spinnerSmall = $(".loader .spinner-small");
//     var show = false;
//     var width;
//     var height;

//     if (element !== "window") {
//         if ($.type(element) == "string") {
//             element = $(element);
//         }

//         if (element.length) {
//             var posY = element.offset().top;
//             var posX = element.offset().left;
//             var position = "absolute";
//             width = element.outerWidth();

//             if (width > windowW) {
//                 width = windowW;
//                 loaderOn("window");
//                 return;
//             }
//             height = element.outerHeight();
//             if (height > windowH) {
//                 height = windowH;
//                 loaderOn("window");
//                 return;
//             }

//             $loader.css({
//                 "position": "absolute",
//                 "top": posY + "px",
//                 "left": posX + "px",
//                 "width": width + "px",
//                 "height": height + "px"
//             });

//             show = true;
//         }
//     } else {
//         element = $window;
//         height = windowH;

//         $loader.css({
//             "position": "fixed",
//             top: 0,
//             left: 0,
//             width: windowW,
//             height: windowH
//         });

//         show = true;
//     }

//     if (show) {
//         $loader.fadeIn(100);

//         if (height > 100) {
//             //console.log("big spinner");
//             $spinnerBig.show();
//         } else {
//             //console.log("small spinner");
//             $spinnerSmall.show();
//         }
//         //$("body").addClass("loader-blur");
//     }
// }

// export function loaderOff() {
//     //console.log("loaderOff");
//     $(".loader").fadeOut(100);
//     $(".loader-blur").removeClass("loader-blur");
//     $(".loader .spinner").hide();
// }