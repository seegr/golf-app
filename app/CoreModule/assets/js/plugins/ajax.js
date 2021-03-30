import generateUUId from 'unique-identifier';


$(function() {
	console.log("naja init")
	// naja.defaultOptions = {
	// 		unique: false
	// 	};
	naja.initialize({
		history: false,
		// unique: false
	});
});


$.nette = {};

$.nette.ajax = function(settings) {
	console.log("ajax settings: ", settings);
	let method = settings.method != settings.method ? settings.method : "GET";

	let options = {};

	// options.start = settings.beforeSend;
	// options.complete = settings.success;

	console.log("naja options: ", options);
	console.log("naja data: ", settings.data);
	// naja.makeRequest(method, settings.url, settings.data, options);
	naja.makeRequest(method, settings.url, settings.data, options);

	if (settings.success) {
		// naja.addEventListener('success', settings.success);
	}
}

naja.uiHandler.addEventListener("interaction", function(e) {
	console.log("ajax interaction: ", e);
	let el = e.detail.element;
	let target = el.dataset.loader;
	// console.log("loader target: ", target);

	e.detail.options.target = target;
	e.detail.options.element = el;
});

naja.addEventListener("before", function(e) {
	console.log("ajax before: ", e);

	// console.log("e.detail.options.unique", e.detail.options.unique);
	if (e.detail.options.unique === "default") {
		$(".ajax").addClass("inactive");
	}

	let target = e.detail.options.target;
	let el = e.detail.options.element;

	if (target) {
		if (target === "this") {
			target = el;
		}
		// console.log("loader target: ", target);
		let loaderId = generateUUId();
		e.detail.options.loader = loaderId;
		loaders.loaderOn(target, loaderId);
	}	

	// e.detail.options.unique = false;
});

naja.addEventListener("success", function(e) {
	console.log("success: ", e);
	const detail = e.detail;
	const options = detail.options;
	const payload = detail.payload;

	if (payload.modal) {
		// console.log(payload.modal);
		if (payload.modal == "hide") {
			const $modal = $("#modal");
			if ($modal.hasClass("iziModal")) {
				$modal.iziModal("hide");
			} else {
				$("#modal").modal("hide");
			}
		} else {
			// console.log("showmodal...");
			Utils.showModal(payload.modal);
			const modalEl = document.getElementById("modal");
			// console.log("modalEl: ", modalEl);
			naja.uiHandler.bindUI(modalEl);
		}
	}

	console.log("isHardRedirect: ", e.detail.isHardRedirect);
	if (e.detail.isHardRedirect) {
		// delete $("body");
		$("body").remove();
	}
});

naja.addEventListener('complete', function(e) {
	console.log("ajax complete: ", e);

	if (e.detail.options.loader) {
		loaders.loaderOff(e.detail.options.loader);
	}

	$(".ajax").removeClass("inactive");
});

naja.snippetHandler.addEventListener('afterUpdate', (event) => {
    if (event.detail.snippet.id === "snippet--flashes") {
        flashes.init();
    }
});