import * as loader from "./Monty/page.loader.js";


$(function() {
	$.nette.ext('init').formSelector = 'form.ajax, .modal-content form.ajax';
	$.nette.init();

	$(document).ajaxStop(loader.loaderOff);
});

$(function() {
	$(".ajax").click(function() {
		var $loader = $(this).data("loader");
		var clickedEl = $(this);
		$(document).ajaxStart(function(e) {
			if (loader != undefined) {
				if (loader == "this") {
					loader.loaderOn(clickedEl);
				} else {
					loader.loaderOn($loader );
				}
			}
		});
	});

	$.nette.ext({
		start: function(jqXHR, settings) {
			if (settings != null) {		        
				ajaxSettings = settings;
				if (settings.nette != null) {
					if (settings.nette.e != null) {
						options = settings.nette.e.target.dataset;

						if (options.loader != undefined) {
							console.log("ajax loader...");
							//console.log(options.loader);
							if (options.loader == "this") {
								// console.log(settings.nette.e);
								loader.loaderOn($(settings.nette.e.target));
							} else if (options.loader == "cursor") {
								cursorLoader();
							} else {
								loader.loaderOn(options.loader);
							}
						}
					}
				}
			}
		},
		before: function(jqXHR, settings) {	
			// console.log("ajax before...");
			// console.log("settings");
			// console.log(settings);

			if (settings != null) {
				if (settings.nette != undefined) {
					var question = settings.nette.el.data('confirm') ? settings.nette.el.data('confirm') : settings.nette.el.data('datagrid-confirm');
					if (question) {
						return confirm(question);
					}	
				}	
		        
				ajaxSettings = settings;
				//console.log(settings);
				if (settings.nette != null) {
					//console.log("nette");
					if (settings.nette.e != null) {
						options = settings.nette.e.target.dataset;

						// console.log("options");
						// console.log(options);

						// if (options.modal == "hide") {
						// 	$("#modal").modal("hide");
						// }

						/*if (options.loader != undefined) {
							console.log("ajax loader...");
							console.log(options.loader);
							loaderOn(options.loader);
						}*/

					}
				}
			}
		},
		success: function(payload, status, jqXHR, settings) {
			// console.log("ajax success...");
			/*console.log("payload");
			console.log(payload);
			console.log("success settings");
			console.log(settings);*/

			if (settings != null) {
				//console.log(settings);
				if (settings.nette != null) {
					if (settings.nette.e != null) {
						options = settings.nette.e.target.dataset;

						//console.log(options);

						// if (options.modal == "show") {
						// 	$("#modal").modal("show");
						// }

						// if (options.modal == "hide") {
						// 	$("#modal").modal("hide");
						// }

						if (options.callback != undefined) {
							console.log(options.callback);
							window[options.callback]();
						}

					}
				}
				//console.log(settings);
			}

			if (payload != null) {
				// console.log("payload");
				// console.log(payload);
				// if (payload.modal == "show") {
				// 	$("#modal").modal("show");
				// }

				if (payload.modal == "hide") {
					$(".modal").modal("hide");
				}

				if (payload.change != null) {
					$(payload.change).trigger("change");
				}

				if (payload.url) {
					// console.log("tralala");
		    		// console.log(payload.url);
		    		window.history.pushState(null, null, payload.url);
		    	}

		    	if (payload.click) {
		    		//console.log(payload.click);
		    		$(payload.click).click();
		    	}

		    	if (payload.modal) {
		    		// console.log(payload.modal);
		    		showModal(payload.modal);
		    		$.nette.load();
		    	}
		    	if (payload.alert) {
		    		// console.log(payload.alert);
		    		showAlert(payload.alert);
		    	}
		    	if (payload.bigMessage) {
		    		showBigMeesage(payload.bigMessage)
		    	}
		    	// console.log(payload.callback);
		    	if (payload.callback != undefined) window[payload.callback]();
			}

			loader.loaderOff();
			cursorLoader("hide");
			init();
		}
	});

	// $(window).on("stop", function() {
	// 	loaderOff();
	// 	cursorLoader("hide");
	// 	init();
	// });

	// $.nette.ext('init').formSelector = 'form.ajax, .modal-content form.ajax';
	
	// var ajaxInit = $.nette.init();
	// ajaxInit.linkSelector = "a.ajax:not(.disabled), button.ajax:not(.disabled)";

	$(document).ajaxStop(loader.loaderOff);

	//** pushtate history browser steps
	// window.onpopstate = function(event) {
	// 	console.log("location: " + document.location + ", state: " + JSON.stringify(event.state));
	// 	console.log(document.location.href);
	// 	window.location.reload();
	// };
});