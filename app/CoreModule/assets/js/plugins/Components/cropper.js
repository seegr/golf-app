$(function() {
	if ($(".monty-cropper").length) {
		// console.log("cropper tralala");
		var id = $(".monty-cropper").attr("id");
		cropper = "#" + id;
		// console.log("Croppers :", Croppers);
		// console.log("id: ", id);
		var Cropper = Croppers[id];
		settings = Cropper.settings;
		// console.log("settings: ", settings);
		$cropper = $(cropper);
		var image = settings.image;

		//buttonsInit();

			// console.log("je tam cropper");
			naja.addEventListener('complete', function() {
				console.log("complete cropper ajax");
				if (Croppers[id]["image"]) cropperInit(id);
			});

			// $.nette.ext({
			// 	success: function(payload, status, jqXHR, settings) {
			// 		console.log(settings);
			// 		if (settings.nette == undefined) return;

			// 		console.log($(settings.nette.el));

			// 		if ($(settings.nette.el).hasClass("upload-form-btn")) cropperInit();
			// 	}
			// });
		// $.nette.ext({
		// 	// console.log(e);
		// 	//init();
		// 	//buttonsInit();
			
		// 	complete: function() {
		// 		console.log("tralala");
		// 		if (Croppers[id]["image"]) cropperInit();
		// 	}
		// });

		$(document).on("click", cropper + " .crop-btn", function() {
			// console.log("cropping...");
			var cropper = $cropper.find(".orig-image img").data('cropper');

			var canvas = cropper.getCroppedCanvas();
			
			$img = $(canvas);
			$img.addClass("img-fluid");

			$cropper.find(".preview-image").html($img);
			var data = cropper.getData();
			// console.log(data);
			var dataStr = JSON.stringify(data);
			var imageName = $(this).data("image");
			$cropper.find(".image-input").val(imageName);
			$cropper.find(".image-data-input").val(dataStr);
			$cropper.find(".save-cropped-image").show();
		});

		/*$(document).on("click", ".image-remove", function() {
			$(".bubble-image").remove();
			buttonsInit();
		});*/

		// $.nette.ajax({
		// 	success: function() {
		// 		cropperInit();
		// 	}
		// // });
		// $.nette.ext({
		// 	success: function(payload, status, jqXHR, settings) {
		// 		console.log(settings);
		// 		if (settings.nette == undefined) return;

		// 		console.log($(settings.nette.el));

		// 		if ($(settings.nette.el).hasClass("upload-form-btn")) cropperInit();
		// 	}
		// });

	}

	function buttonsInit() {
		if ($(".image-cropper").length > 0) {
			$(".upload-form").hide();
		} else {
			$(".upload-form").show();
		}
	}	
});

function cropperInit(id) {
	console.log("cropperInit");
	$(".current-bubble").remove();

	settings = Croppers[id].settings;
	console.log("Croppers settings: ", settings);

	$cropper.find(".orig-image img").cropper({
	  aspectRatio: settings.ratio,
	  movable: true,
	  viewMode: 1,
	  dragMode: "move",
	  crop: function(event) {
	    // console.log(event.detail.x);
	    // console.log(event.detail.y);
	    // console.log(event.detail.width);
	    // console.log(event.detail.height);
	    // console.log(event.detail.rotate);
	    // console.log(event.detail.scaleX);
	    // console.log(event.detail.scaleY);
	  }
	});

	Utils.scrollToAnchor(cropper);
}	