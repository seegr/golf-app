// const Nette = window.Nette = netteForms;

Nette.validators.MontyFormValidators_phoneFormatCheck = function(elem, args, val) {
	// console.log("phone check tralala");
	console.log("val", val);

	var regex = /^(\+\d{3})\s*(\d{3})\s*(\d{3})\s*(\d{3})$/;
	var match = val.match(regex);
	console.log("match", match);

	if (match) return true;
};

Nette.validators.MontyFormValidators_isUrlValid = function(elem, args, val) {
	// console.log("url check tralala");
	console.log("val", val);

	var regex = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;
	var match = val.match(regex);
	console.log("match", match);

	if (match) return true;
};

// Nette.validators.fileSize = function(elem, arg, val) {
// 	if (window.FileList) {
// 		for (var i = 0; i < val.length; i++) {
// 			if (val[i].size / 1048576 > arg) {
// 				return false;
// 			}
// 		}
// 	}
// 	return true;
// };
