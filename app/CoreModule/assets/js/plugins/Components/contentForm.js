$(document).on("click", ".att-title-save", function() {
	var input = $(this).parents(".att-title-wrap").find(".att-title");
	var attId = input.data("id");
	var title = input.val();

	$.nette.ajax({
		url: Nette.links.attTitleSave,
		data: {
			attId: attId,
			title: title
		}
	});
});

$(function() {
	$(".items-sortable, table.items-sortable tbody").sortable({
		handle: ".item-order-btn",
		update: function(event, ui) {
			console.log(event);
			console.log(ui);
			var item = ui.item;
			var list = item.closest(".items-sortable");
			var itemId = item.data("id");
			var prevItem = item.prev().length ? item.prev() : list.prev().find(".item-container").first();
			var nextItem = item.next().length ? item.next() : list.next().find(".item-container").first();
			var prevId = prevItem.data("id");
			var nextId = nextItem.data("id");
			//var itemIndex = item.index();
			// var prevItemIndex = item.prev().index();
			// var nextItemIndex = item.next().index();

			console.log("item: " + item);
			console.log("itemId: " + itemId);
			console.log("list: " + list);
			console.log("prevId: " + prevId);
			console.log("nextId: " + nextId);

			var data = {};
			data["itemId"] = itemId;
			data["prevItemId"] = prevId !== undefined ? prevId : null;
			data["nextItemId"] = nextId !== undefined ? nextId : null;

			// console.log(data);

			$.nette.ajax({
				url: Nette.links.itemOrderChange,
				data: data
			});
		}
	});
});