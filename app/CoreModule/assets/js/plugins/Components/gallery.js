var Galleries = Galleries === undefined ? {} : Galleries;

$(function() {
	if (!$(".monty-gallery").length) {
		return;
	}

	// console.log("gallery script start");

	initGalleries();

	naja.addEventListener('complete', function() {
		initGalleries();
	});

	// naja.uiHandler.addEventListener('interaction', this.locateLoadingIndicator.bind(this));

	// $(document).ajaxComplete(
	// 	function(event, request, settings) {
	// 		alert("ajax url: ", settings.url); // output: "someScript.php"
	// 		alert("ajax data: ", settings.data); // output: "foo=bar&a=b"

	// 	}
	// );


	function initGalleries() {
		$(".monty-gallery").each(function() {
			var id = $(this).attr("id");

			// console.log("gallery id: ", id);
			var Gallery = window.Galleries[id];

			var settings = Gallery;
			// console.log("settings: ", settings);

			var galleryId = id;
			var gallery = "#" + galleryId;
			var $gallery = $("#" + id);
			var loadMoreButton = $gallery.find(".load-more");
			var galleryGrid = $gallery.find(".gallery-grid");
			var editable = settings.editable;
			var lookupPath = settings.lookupPath;
			var isEditsSaveCallback = settings.isEditsSaveCallback;
			var layout = settings.layout;

			// console.log("lookupPath: ", lookupPath);

			// console.log("gallery links: ", Gallery.links);

			if (!editable) {
				// console.log($gallery);
				// console.log(galleryGrid);
				// if ($(".gallery-grid").length == 1) loaderOn(galleryGrid);			

				$gallery.find(".grid").imagesLoaded().done(function() {
					// console.log("images loaded");
					// loaderOff();
					galleryGrid.fadeIn(200);
					//console.log(galleryGrid);
					if (!settings.editable) {
						switch (settings.layout) {
							case "grid":
								Galleries[id] = $("#" + id + " .grid").masonry({
								  itemSelector: '.grid-item',
								  columnWidth: '.grid-sizer',
								  percentPosition: true
								});
							break;

							case "justified-grid":
								console.log("justified-grid");
								Galleries[id] = $("#" + id + " .grid").justifiedGallery({
									rowHeight : settings.rowHeight,
									lastRow : 'nojustify',
									margins : 20,
									border: 0
								});

								$("#" + id + " .gallery-justified-grid").css("visibility", "visible");
							break;
						}
					}
				});
			}

			if (!settings.imagesPicker) galleryPopupIni(id);


			$(document).off("click", gallery + " .load-more").on("click", gallery + " .load-more", function() {
				//console.log("load more click");
				var data = {};
				data[lookupPath + "-page"] = $(this).data("page");

				$.ajax({
					url: Gallery.links.loadMore,
					data: data,
					beforeSend: function() {
						// Utils.loaderOn($gallery.find(".load-more-buttons"));
					},
					success: function(data) {
						gridRefresh(data);
					}
				});
			});

			$(document).off("click", gallery + " .load-all").on("click", gallery + " .load-all", function() {
				//console.log("load all click");							
				var data = {};
				data[lookupPath + "-page"] = $(this).data("page");

				$.ajax({
					url: Gallery.links.loadAll,
					data: data,
					beforeSend: function() {
						// Utils.loaderOn($gallery.find(".load-more-buttons"));
					},
					success: function(data) {
						gridRefresh(data);
					}
				});
			});

			$(".gallery-grid .grid.editable").sortable({
				handle: ".image-sorter",
				update: function(event, ui) {
					//console.log(event);
					//console.log(ui);
					var item = ui.item;
					var itemId = item.data("id");
					var itemPrevId = item.prev().data("id");
					var itemNextId = item.next().data("id");

					/*console.log(item);
					console.log(itemId);
					console.log(itemPrevId);
					console.log(itemNextId);
					console.log(role);*/

					data = {};
					data[lookupPath + "-item"] = itemId;
					data[lookupPath + "-itemPrev"] = itemPrevId ? itemPrevId : null;
					data[lookupPath + "-itemNext"] = itemNextId ? itemNextId : null;

					// console.log(data);

					$.nette.ajax({
						url: Gallery.links.orderChange,
						data: data,
						// beforeSend: loader.loaderOn("#" + galleryId + " .gallery-grid"),
						// success: removeClickListeners()
					});
				}	
			});


			$(document).off("change keyup", ".gallery-grid .image-form :input").on("change keyup", ".gallery-grid .image-form :input", function() {
				var form = $(this).parents(".image-form");
				var saveButton = $gallery.find(".gallery-save-edits");

				form.addClass("edited");
				saveButton.removeClass("btn-secondary").attr("disabled", false);
			});

			$(document).off("click", "#" + galleryId + " .gallery-save-edits").on("click", "#" + galleryId + " .gallery-save-edits", function() {
				var forms = $gallery.find(".image-form.edited");

				var formsVals = {};
				$.each(forms, function(key, imageForm) {
					var $imageForm = $(imageForm);
					//var submitButton = $imageForm.find(".image-form-submit").click();

					//console.log(submitButton);
					//$imageForm.css("background", "red");
					//$imageForm.submit();

					var vals = $imageForm.inputValues();
					// var vals = $imageForm.serialize();
					// console.log(vals);
					var formId = vals.id;
					formsVals[formId] = vals;
				});

				var data = {};
				//data[lookupPath + "-images"] = images;
				// console.log(formsVals);
				data[lookupPath + "-forms"] = JSON.stringify(formsVals);

				// console.log("data: " + data);

				$.nette.ajax({
					url: Gallery.links.editSave,
					data: data,
					// beforeSend: Utils.loaderOn(galleryGrid),
					success: function() {
						$gallery.find(".gallery-save-edits").attr("disabled", true);
					}
				});
			});

			Gallery.selectedImages = [];

			$(document).off("click", ".image-select").on("click", ".image-select", function() {
				console.log("click");
				var $image = $(this).parents(".gallery-image");
				var id = $image.data("id");
				// console.log("id", id);

				imageSelectToggle(id);
			});

			$(document).off("click", ".gallery-selected-delete").on("click", ".gallery-selected-delete", function() {
				var images = [];

				$.each(Gallery.selectedImages, function(key, image) {
					images.push($(image).data("id"));
				});

				// console.log(images);

				data = {};
				data[lookupPath + "-images"] = images;

				// console.log("selected: ", Gallery.selectedImages);
				// console.log("data: ", data);
				$.nette.ajax({
					url: Gallery.links.selectionDelete,
					data: data,
					success: function() {
						$.each(Gallery.selectedImages, function(key, image) {
							//$(image).remove();
						});

						Gallery.selectedImages = [];
						// removeClickListeners();
						initGalleries();
					}
				});

			});

			$(document).off("click", ".gallery-select-all").on("click", ".gallery-select-all", function() {
				$.each($gallery.find(".gallery-image"), function(key, image) {
					var id = $(image).data("id");
					imageSelectToggle(id);
				});
			});

			$(document).off("click", gallery + " .image-wrap").on("click", gallery + " .image-wrap", function(e) {
				if (!settings.imagePicker) {
					// console.log(e.target);
					if ($(e.target).hasClass("gallery-image-zoom") || $(e.target).parents(".image-zoom-box").length > 0) {
						$(this).parents(".grid-item").find(".gallery-image-zoom").trigger("click");
					}
				}
			});

			$(document).off("mouseenter", gallery + " .image-wrap").on("mouseenter", gallery + " .image-wrap", function(e) {
				//console.log("enter");
			});

			$(document).not(".monty-gallery.imagepicker, .monty-gallery.editable").off("mousemove", gallery + " .image-wrap").on("mousemove", gallery + " .image-wrap", function(e) {
				var boxW = $(this).width();
				var boxH = $(this).height();
				var imageBox = $(this).find(".image-box");
				var rotate = 10;
				
				// console.log(e);
				// console.log(boxW + " " + boxH);
				//console.log(e.offsetX + " " + e.offsetY);

				var degX = ((e.offsetX - (boxW / 2)) / boxW) * 2 * rotate;
				var degY = ((e.offsetY - (boxH / 2)) / boxH) * -2 * rotate;

				//console.log(degX + " " + degY);

				imageBox.css({
					transform: "rotateY(" + degX + "deg) rotateX(" + degY + "deg) scale(1.03)"
				});
			});

			$(document).off("mouseleave", gallery + " .image-wrap").on("mouseleave", gallery + " .image-wrap", function(e) {
				//console.log("leave");
				var imageBox = $(this).find(".image-box");
				imageBox.css({
					transform: "initial"
				});
			});

			if (settings.imagePicker) {
				// console.log(gallery + " .gallery-image");
				// console.log($(gallery + " .gallery-image"));
				$(document).off("click", gallery + " .gallery-image").on("click", gallery + " .gallery-image", function() {
					// console.log("click");
					// $(gallery + " .gallery-image").removeClass("selected");
					// $(this).addClass("selected");
					imageSelectToggle($(this));
				});
			}

			
			// function removeClickListeners() {
			// 	$(document).off("click", ".image-select");
			// 	$(document).off("change keyup", ".gallery-grid .image-form :input");
			// 	$(document).off("click", "#" + id + " .gallery-save-edits");
			// 	$(document).off("click", ".gallery-selected-delete");
			// 	$(document).off("click", ".gallery-select-all");
			// 	$(document).off("click", "#" + id + " .image-zoom-box");
			// }

			function imageSelectToggle(item) {
				id = typeof item == "object" ? item.data("id") : item;
				var $image = $gallery.find(".gallery-image[data-id='" + id + "']");
				var $images = $gallery.find(".gallery-item");
				var $btn = $image.find(".image-select");
				var $dltBtn = $gallery.find(".gallery-selected-delete");

				// console.log(id);
				// console.log($image);
				// console.log($btn);
				// console.log($dltBtn);

				var $icon = $('<div class="selected-icon"><i class="fas fa-check-circle"></i></div>');

				if (settings.multiselect) {
					$image.toggleClass("selected");
				} else {
					$images.not($image).removeClass("selected");
					$images.find(".selected-icon").remove();
					$image.toggleClass("selected");
				}

				$gallery.find(".gallery-item.selected").append($icon);

				/*if ($image.hasClass("selected")) {
					console.log("selected");
					$image.removeClass("selected");
					$btn.find(".checked").css("opacity", 0);
				} else {
					console.log("not selected");
					$image.addClass("selected");
					$btn.find(".checked").css("opacity", 1);
				}*/

				Gallery.selectedImages = $gallery.find(".gallery-image.selected");

				if (Gallery.selectedImages.length > 0) {
					$gallery.find(".gallery-selected-delete").attr("disabled", false);
				} else {
					$gallery.find(".gallery-selected-delete").attr("disabled", true);
				}
			}

			function galleryPopupIni(id) {
				//console.log(id);
				// console.log("gallery ini");
				// console.log(gallery);
				// console.log(gallery.find(".gallery-grid-image"));

				magnific = $gallery.find(".gallery-image-zoom").magnificPopup({
					type: "image",
				    image: {
				    	titleSrc: function(item) {
				    		console.log("titleSrc");
				    		console.log(item.el);
							var el = item.el;
							var label = el.attr("title") !== undefined ? el.attr("title") : el.attr("alt");
							var desc = el.data("desc");

							var caption = "<div class='text-left text-white mb-0'>";
							caption += label !== undefined ? "<div class='font-weight-bold'>" + label + "</div>" : "";
							caption += desc !== undefined ? "<div>" + desc + "</div>" : "";
							return caption;
						}
				    },
				    closeBtnInside: false,
				    gallery: {
				    	enabled: true,
						tPrev: 'Předchozí',
						tNext: 'Další',
						tCounter: '%curr% z %total%'		      
				    },
					mainClass: 'gallery-image-full',
					zoom: {
						enabled: true,
						duration: 300,
						easing: 'ease-in-out'
					},
					tLoading: "Načítám...",
					tClose: "Zavřít",
					// overflowY: "hidden auto",
					fixedContentPos: false,
					closeMarkup: "<div title='%title%' class='btn btn-danger gallery-popup-close-btn'><i class='fas fa-times'></i></div>"
				});

				Gallery.magnific = magnific;
			}

			function gridRefresh(data) {
				// console.log("layout: " + layout);

				switch (layout) {
					case "grid":
						//console.log("appended");
						var images = data.snippets["snippet-" + lookupPath + "-galleryImages"];
						var buttons = data.snippets["snippet-" + lookupPath + "-loadMoreButtons"];
						var $images = $(images).filter(".grid-item");

						var random = randomString(20);
						tempDiv = $("<div style='collapse' id='temp_"+random+"'></div>");
						$("body").append(tempDiv);
						//console.log($images);
						tempDiv.append($images);
						tempDiv.imagesLoaded().done(function() {
							//console.log("appended loaded");
							//console.log(Galleries[id]);
							Galleries[id].append($images).masonry('appended', $images);
							Galleries[id].masonry("layout");
							$images.removeClass("collapse");
							galleryPopupIni(id);
						});
						tempDiv.remove();

						$("#snippet-" + lookupPath + "-loadMoreButtons").html(buttons);
					break;

					case "justified-grid":
						// console.log("justified-grid");
					break;
				}
			}
		});
	}
});