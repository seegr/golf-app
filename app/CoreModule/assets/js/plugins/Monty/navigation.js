// var id = {$id};
// var navSelector = "#" + id;

$(document).on("click", ".monty-navigation .item[data-alias]", function() {
	var alias = $(this).data("alias");

	triggerAlias(alias);
});

function triggerAlias(alias) {
	$(alias).trigger("click");
}
