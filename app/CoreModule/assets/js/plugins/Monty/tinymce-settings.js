import tinymce from 'tinymce/tinymce';
import 'tinymce/themes/silver';

import 'tinymce/plugins/paste/plugin';
import 'tinymce/plugins/link/plugin';
import 'tinymce/plugins/autoresize/plugin';
import 'tinymce/plugins/lists/plugin';
import 'tinymce/plugins/image/plugin';
import 'tinymce/plugins/fullscreen/plugin';
import 'tinymce/plugins/code/plugin';
import 'tinymce/plugins/table/plugin';

import 'tinymce/themes/silver';
import 'tinymce/skins/ui/oxide/skin.css';
import 'tinymce/icons/default';
import contentStyle from '!!raw-loader!tinymce/skins/ui/oxide/content.css';
import contentStyle2 from '!!raw-loader!tinymce/skins/content/default/content.css';


// require.context(
//   'file-loader?name=tinymce/[path][name].[ext]&context=node_modules/tinymce!tinymce/skins', 
//   true, 
//   /.*/
// )
// require.context(
//   '!file-loader?name=tinymce/[path][name].[ext]&context=node_modules/tinymce&outputPath=modules!tinymce/skins',
//   true,
//   /.*/
// );

const settings = {};
settings["basic"] = {
	// selector: "[data-tinymce]",
	skin: false,
	plugins: ['paste', 'link', 'autoresize'],
	init_instance_callback: function() {
		console.log("tinymce initialized");
	},
	branding: false,
	language: "cs",
	entity_encoding: "raw",
	menubar: false,
	toolbar: 'undo redo | bold italic',
	base_url: window.baseUrl + "/dist/modules/tinymce",
	document_base_url: window.basePath + "/",
	relative_urls: true,
	remove_script_host: true,
	// skin_url: window.baseUrl + "/dist/modules/tinymce/skins/ui/oxide",
	// theme_url: window.baseUrl + "/dist/modules/tinymce/skins/ui/oxide",
	content_style: contentStyle.toString() + '\n' + contentStyle2.toString(),
	content_css: [
		// window.baseUrl + "/dist/modules/tinymce/skins/content/dark/content.css",
		// window.baseUrl + "/dist/modules/tinymce/skins/ui/oxide/content.css",
		window.baseUrl + "/dist/css/front.style.css?{time()}"
	],
	setup: function(editor) {
		editor.on("change", function() {
			tinymce.triggerSave();
		});
		editor.on("NodeChange", function(e) {
			if (e.element.tagName === "IMG") {
				e.element.setAttribute("class", "img-fluid content-image");
			}
		});
	},	
};

window.tinyBasic = settings.basic;

settings["advanced"] = $.extend(false, settings["basic"], {
	plugins: "paste lists link placeholder",
	toolbar: "undo redo | styleselect | formatselect | bold italic bullist | alignleft aligncenter alignright alignjustify | link",
});

settings["admin"] = $.extend(false, settings["advanced"], {
	paste_as_text: false,
	plugins: "paste lists image link fullscreen table code",
	menubar: "table",
	toolbar: "undo redo | styleselect | formatselect | bold italic bullist | alignleft aligncenter alignright alignjustify | image link | code | fullscreen",
	file_picker_types: "file image",
	file_picker_callback: function(callback, value, meta) {
		console.log("meta", meta);
		tinymce.activeEditor.windowManager.openUrl({
			title: "Vložit Obrázek/Soubor",
			url: window.filePicker + "?type=images&picker=true",
			onMessage: function(e, details) {
				console.log("on action...");
				console.log("event", e);
				// console.log("even data", data);

				let data = details.data;
				console.log(data);
				let url = data.url;

				callback(url, {
					// alt: "file-data.id"
				});

				window.parent.postMessage({
					mceAction: "close"
				});
			},
			buttons: [{
				type: "cancel",
				text: "Cancel",
				onclick: 'close'
			}]
						
		});
	}
});

$(function() {
	var inputs = $("[data-tinymce]");
	// console.log(inputs);
	inputs.each(function(index) {
		let id = $(this).attr("id");
		// console.log("textarea id: ", id);
		// console.log("index: ", index);

		let dataSet = $(this).data("tinymce");
		// console.log("data-tinymce: ", dataSet);
		let height = $(this).data("height");
		console.log("tiny height: ", height);

		if (settings[dataSet] === undefined) {
			throw "You have to specify settings";	
		} else {
			let tinySettings = settings[dataSet];

			if (height) {
				tinySettings = Object.assign(tinySettings, {
					height: height
				});
			}
			console.log("tinySettings: ", tinySettings);

			if (id === undefined) {
				throw "Textarea must have ID specified";
			} else {
				// console.log("id: ", id);
				tinySettings["selector"] = "#" + id;
				tinymce.init(tinySettings);
				$(this).css("visibility", "visibile")
			}
		}

	});
});