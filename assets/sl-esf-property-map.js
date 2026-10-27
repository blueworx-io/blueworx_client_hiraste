(function () {
	var settings = window.slEsfPropertyMap || {};
	var GOOGLE_MAPS = /(maps\.app\.goo\.gl|goo\.gl\/maps|google\.[a-z.]+\/maps)/i;

	function startMap(panel) {
		if (panel.slEsfMap || !window.L) return panel.slEsfMap;

		var lat = parseFloat(panel.getAttribute('data-lat'));
		var lng = parseFloat(panel.getAttribute('data-lng'));
		var canvas = panel.querySelector('.sl-esf-map__canvas');

		var map = L.map(canvas, {
			center: [lat, lng],
			zoom: settings.zoom || 14,
			zoomControl: false,
			attributionControl: false,
			scrollWheelZoom: false
		});

		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

		var marker = document.createElement('div');
		var label = document.createElement('span');
		label.className = 'sl-esf-map__label';
		label.textContent = panel.getAttribute('data-title') || '';
		var pin = document.createElement('img');
		pin.className = 'sl-esf-map__pin';
		pin.src = settings.markerIcon;
		pin.width = 58;
		pin.height = 58;
		pin.alt = '';
		marker.appendChild(label);
		marker.appendChild(pin);

		L.marker([lat, lng], {
			keyboard: false,
			icon: L.divIcon({ className: 'sl-esf-map__marker', html: marker, iconSize: [58, 58], iconAnchor: [29, 29] })
		}).addTo(map);

		panel.querySelectorAll('[data-sl-esf-map-zoom]').forEach(function (button) {
			button.addEventListener('click', function () {
				map.setZoom(map.getZoom() + parseInt(button.getAttribute('data-sl-esf-map-zoom'), 10));
			});
		});

		var expand = panel.querySelector('[data-sl-esf-map-expand]');
		var preview = panel.querySelector('.sl-esf-map__preview');
		expand.addEventListener('click', function () {
			if (document.fullscreenElement) {
				document.exitFullscreen();
			} else if (preview.requestFullscreen) {
				preview.requestFullscreen();
			} else {
				// No full screen support (iPhone): fill the window instead.
				panel.classList.toggle('is-expanded');
				map.invalidateSize();
			}
		});
		preview.addEventListener('fullscreenchange', function () {
			map.invalidateSize();
			map.panTo([lat, lng]);
		});

		panel.slEsfMap = map;
		return map;
	}

	var SLIDE_MS = 400;

	function toggle(panel) {
		var opening = !panel.classList.contains('is-open');
		clearTimeout(panel.slEsfSlide);
		panel.classList.toggle('is-open', opening);
		panel.style.height = panel.hidden ? '0px' : panel.offsetHeight + 'px';
		panel.hidden = false;

		if (opening) {
			startMap(panel);
			panel.slEsfMap && panel.slEsfMap.invalidateSize();
		}

		panel.offsetHeight; // Fix the start height before sliding.
		panel.style.height = opening ? panel.scrollHeight + 'px' : '0px';

		panel.slEsfSlide = setTimeout(function () {
			panel.style.height = '';
			panel.hidden = !opening;
		}, SLIDE_MS);
	}

	// The card's "View on map" is a Google Maps link. When the card has a map
	// panel, slide that open instead; on a property page, scroll to its map.
	document.addEventListener('click', function (e) {
		var link = e.target.closest && e.target.closest('a[href], [data-ep-wrapper-link]');
		if (!link) return;

		var url = link.getAttribute('href') || link.getAttribute('data-ep-wrapper-link') || '';
		if (!GOOGLE_MAPS.test(url) || link.closest('.sl-esf-map')) return;

		var card = link.closest('.sl-esf-results__item');
		var panel = card
			? card.querySelector('.sl-esf-map--collapsible')
			: document.querySelector('.sl-esf-map:not(.sl-esf-map--collapsible)');
		if (!panel) return;

		e.preventDefault();
		e.stopPropagation();

		if (card) {
			toggle(panel);
		} else {
			panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	}, true);

	function init() {
		var panels = document.querySelectorAll('.sl-esf-map:not(.sl-esf-map--collapsible)');
		if (!panels.length) return;

		// Load map tiles only when the map comes into view.
		if (!('IntersectionObserver' in window)) {
			panels.forEach(startMap);
			return;
		}
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				observer.unobserve(entry.target);
				startMap(entry.target);
			});
		}, { rootMargin: '300px' });
		panels.forEach(function (panel) { observer.observe(panel); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
