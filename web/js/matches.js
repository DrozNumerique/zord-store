bindSearch = function(params) {
	
	var inputSelector = params.input;
	var buttonSelector = params.button;
	var search = params.search;
	var source = params.source;
	var minLength = params.minLength !== undefined ? params.minLength : 3;
	var position = params.position !== undefined ? params.position : {my:'right top',at:'right bottom'};
	var classes = params.classes !== undefined ? params.classes : {'ui-autocomplete':'matches'};
	var location = params.location;
	var select = params.select !== undefined ? params.select : function(event, ui) {
		window.location.href = location(ui);
	};
	var render = params.render !== undefined ? params.render : function(list, item) {        
        var highlight = String(item.label);
        [].forEach.call(this.term.split(' '), function(keyword) {
        	highlight = highlight.replace(
			    new RegExp(keyword, "gi"),
			    "<strong>$&</strong>"
		    );
        });
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<div>" + highlight + "</div>")
            .appendTo(list);
    };
	var searchInput  = document.querySelector(inputSelector);
	var searchButton = document.querySelector(buttonSelector);
	
	if (searchButton) {
		searchButton.addEventListener('click', function(event) {
			search(searchInput);
		});
	}
	
	if (searchInput) {
		if (searchButton) {
			searchInput.addEventListener('keypress', function(event) {
			    var key = event.which || event.keyCode;
			    if (key === 13) {
			    	search(searchInput);
			    }
			});
		}
		$(inputSelector).autocomplete({
			minLength: minLength,
			source: source,
			position: position,
			classes: classes,
			select: select
		});
		$.ui.autocomplete.prototype._renderItem = render;
	}
}