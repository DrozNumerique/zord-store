var CSL_OBJECTS = 'csl.objects';
var CSL_STYLES  = 'csl.styles';
var CSL_LOCALES = 'csl.locales';
var CSL_PARAMS  = 'csl.params';
var CSL_KEY     = '${KEY}';
var CSL_URL     = {
	'csl.locales': '/store/csl/locales/locales-' + CSL_KEY + '.xml',
	'csl.styles' : '/store/csl/styles/' + CSL_KEY + '.csl'
}

var getCSLObjects = function(name) {
	return getContextProperty(CSL_OBJECTS + '.' + name, {});
}

var setCSLObjects = function(name, cslObjects) {
	setContextProperty(CSL_OBJECTS + '.' + name, cslObjects);
}

var addCSLObject = function(name, object) {
	var cslObjects = getCSLObjects(name);
	cslObjects[object.id] = object;
	setCSLObjects(name, cslObjects);
}

var removeCSLObject = function(name, id) {
	var cslObjects = getCSLObjects(name);
	if (cslObjects[id] !== undefined) {
		delete cslObjects[id];
	}
	setCSLObjects(name, cslObjects);
}

var getCSLResource = function(key, name) {
	resources = getSessionProperty(key);
	if (resources == undefined || resources == null) {
		resources = {};
	}
	resource = resources[name];
	if (resource == undefined || resource == null) {
		request = new XMLHttpRequest();
		url = CSL_URL[key].replace(CSL_KEY, name);
		request.open('GET', url, false);
		request.send(null);
		resource = request.responseText;
		resources[name] = resource;
		setSessionProperty(key, resources);
	}
	return resource;
}

var getCSLEngine = function(name, style, lang) {
	cslEngine = new CSL.Engine({
		retrieveLocale: function() {
			return getCSLResource(CSL_LOCALES, lang);
		},
		retrieveItem: function(id) {
			cslObjects = getCSLObjects(name);
			if (cslObjects !== undefined && cslObjects !== null) {
				return cslObjects[id];
			} else {
				return null;
			}
		}
	}, getCSLResource(CSL_STYLES, style));
	return cslEngine;
}

var getCSLParam = function(key) {
	params = getSessionProperty(CSL_PARAMS, {
		'lang':  LANG,
		'style': CONFIG.default.csl.style
	});
	return params[key];
}

var setCSLParams = function(params) {
	setSessionProperty(CSL_PARAMS, params);
}

var getBiblio = function(name, id) {
	cslEngine = getCSLEngine(name, getCSLParam('style'), getCSLParam('lang'));
	cslEngine.updateItems([id]);
	result = cslEngine.makeBibliography(id);
	return result[1].join('');
}

var setBiblio = function(name, element, reference) {
	element.setAttribute('data-ref', reference.id)
	element.innerHTML = getBiblio(name, element.getAttribute('data-ref'));
}

var refreshBiblio = function(name, elements) {
	cslObjects = getCSLObjects(name);
	if (cslObjects !== undefined && cslObjects !== null) {
		[].forEach.call(elements, function(element) {
			for (var id in cslObjects) {
				if (cslObjects[id].ean == element.getAttribute('data-isbn')) {
					element.innerHTML = getBiblio(name, id);
					break;
				}
			}
		});
	}
}
