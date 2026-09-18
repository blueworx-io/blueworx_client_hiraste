(function () {
	var config = window.slEsfUnitPopup || {};
	var POPUP_LINK = 'a[href*="elementor-action"][href*="popup%3Aopen"]';

	var startX = 0, startY = 0, moved = false;
	var lastUnitId = null;

	function getUnitIdFromAnchor(a) {
		var loopItem = a.closest('.e-loop-item');
		if (!loopItem) return null;
		var m = (loopItem.className || '').match(/\bpost-(\d+)\b/);
		return m ? m[1] : null;
	}

	function setUnitIdInUrl(unitId) {
		var url = new URL(window.location.href);
		url.searchParams.set('unit_id', String(unitId));
		window.history.replaceState({}, '', url);
	}

	function getUnitId() {
		return lastUnitId || new URL(window.location.href).searchParams.get('unit_id');
	}

	function waitForHolder(maxMs) {
		var start = Date.now();
		return new Promise(function (resolve, reject) {
			(function tick() {
				var el = document.getElementById('unit-popup-render');
				if (el) return resolve(el);
				if (Date.now() - start > (maxMs || 2000)) return reject(new Error('unit-popup-render not found'));
				requestAnimationFrame(tick);
			})();
		});
	}

	function loadUnitIntoPopup() {
		var unitId = getUnitId();
		if (!unitId) return;

		waitForHolder().then(function (holder) {
			var fd = new FormData();
			fd.append('action', config.action || 'unit_popup_render');
			fd.append('unit_id', unitId);

			return fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
				.then(function (res) { return res.json(); })
				.then(function (json) {
					if (json && json.success && json.data && json.data.html) holder.innerHTML = json.data.html;
				});
		}).catch(function () {});
	}

	// Drag detection so dragging a carousel does not count as a click.
	document.addEventListener('pointerdown', function (e) {
		if (!e.target.closest(POPUP_LINK)) return;
		startX = e.clientX;
		startY = e.clientY;
		moved = false;
	}, true);

	document.addEventListener('pointermove', function (e) {
		if (Math.abs(e.clientX - startX) > 6 || Math.abs(e.clientY - startY) > 6) moved = true;
	}, true);

	document.addEventListener('click', function (e) {
		var a = e.target.closest(POPUP_LINK);
		if (!a) return;

		var unitId = getUnitIdFromAnchor(a);
		if (!unitId) return;

		lastUnitId = unitId;
		if (!moved) setUnitIdInUrl(unitId);
	}, true);

	document.addEventListener('elementor/popup/show', function (event) {
		if (!event.detail || String(event.detail.id) !== String(config.popupId)) return;
		loadUnitIntoPopup();
	});
})();
