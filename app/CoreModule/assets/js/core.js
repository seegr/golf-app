// import $ from "jquery";
import moment from "moment";
import "bootstrap";

import "./nette.js";
import "jquery-ui-dist/jquery-ui.min.js";
import "ublaboo-datagrid/assets/datagrid.js";
import "ublaboo-datagrid/assets/datagrid-spinners.js";
import "ublaboo-datagrid/assets/datagrid-instant-url-refresh.js";
import "selectize/dist/js/standalone/selectize.js";
import "pc-bootstrap4-datetimepicker/build/js/bootstrap-datetimepicker.min.js";
import "masonry-layout/dist/masonry.pkgd.min.js";
import "justifiedGallery/dist/js/jquery.justifiedGallery.min.js";
import "magnific-popup/dist/jquery.magnific-popup.min.js";
import "bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js";
import "bootstrap-datepicker/js/locales/bootstrap-datepicker.cs.js";
import "dropzone/dist/dropzone.js";
import "jquery-mask-plugin/dist/jquery.mask.min.js";
import { init } from "./functions";
import "CorePlugins/ajax.js";
import "CorePlugins/Monty/navigation.js";
import "CorePlugins/Monty/monty.bigMsg.js";
import "CorePlugins/Monty/form-validators.js";
import "CorePlugins/Components/gallery.js";

// imagesLoaded jQuery plugin
var imagesLoaded = require('imagesloaded');
var $ = require('jquery');
imagesLoaded.makeJQueryPlugin($);

console.log("app start");

// Nette.initOnLoad();

global.$ = $;
global.Nette = Nette;
global.naja = naja;
global.$ = $;
global.loaders = loaders;

// console.log("global: ", global);


$(function() {
	init();
});

loaders.pageLoaderStart();

$(window).on("load", loaders.pageLoaderDone);

// $.nette.ext("beforeAjax", {
// 	start: function() {
// 		$(".ajax").removeClass("ajax-active");
// 	}
// });

// $.nette.ext("loader", {
// 	start: function() {
// 		loader.beforeUnload();
// 		console.log("tralala start");
// 	},
// 	success: function() {
// 		loader.pageLoaderDone();
// 		console.log("tralala success");
// 	}
// });


const daterangepicker = {
	// startDate: moment(), 
	// endDate: moment().endOf('month'),
	ranges: {
		'Dnes': [moment(), moment()],
		'Posledních 7 dní': [moment().subtract(7, 'days'), moment()],
		'Posledních 30 dní': [moment().subtract(30, 'days'), moment()],
		'Aktuální měsíc': [moment().startOf('month'), moment().endOf('month')],
		'Předchozí měsíc': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
		'Příští měsíc': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')]
	},
	alwaysShowCalendars: true,
	showCustomRangeLabel: false,
	autoUpdateInput: false,
	locale: {
		applyLabel: "Potvrdit",
		cancelLabel: "Vynulovat",
		// customRangeLabel: "Vlastní interval"
	}
};

$(document).on("click", "[href='#']", function(e) {
	if ($(this).hasClass("click-deactive")) {
		deactiveElement($(this));
	}

	e.preventDefault();
});

$(document).on("click", ".click-deactive", function() {
	// deactiveElement($(this));	
});

$(document).on("click", '[data-toggle="tab"]', function() {
	var target = $(this).attr("href");

	if (target != undefined) window.location.hash = target;
}); 



// console.log("Object", Object);


// if (global.Nette === undefined) {
// 	global.Nette = {};
// 	// throw "Nette object is not defined";
// }