console.log("globals...");

Nette.initOnLoad();

Nette = Object.assign(Nette, {
	initNetteLinks: function() {
		if (Nette.links === undefined) {
			Nette.links = {};
		}
	},

	addLink: function(id, link) {
		this.initNetteLinks();

		Nette.links[id] = link;
	},

	addLinks: function(links) {
		this.initNetteLinks();

		Object.assign(Nette.links, links);
	}
});

// export { Nette };