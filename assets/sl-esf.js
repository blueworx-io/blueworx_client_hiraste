
(function(){
	function clamp(n, min, max){
		n = parseInt(n, 10);
		if (Number.isNaN(n)) n = min;
		return Math.min(max, Math.max(min, n));
	}

	function qs(root, sel){ return root.querySelector(sel); }
	function qsa(root, sel){ return Array.prototype.slice.call(root.querySelectorAll(sel)); }

	function initOne(root){
		const maxUnits = clamp(root.getAttribute('data-max-units'), 1, 20);
		const defaultUnits = clamp(root.getAttribute('data-default-units'), 1, maxUnits);

		const advancedToggle = qs(root, '.sl-esf__advanced-toggle');
		const advancedPanel = qs(root, '.sl-esf__advanced-panel');
		const metaEl = qs(root, '[data-sl-esf-advanced-meta]');
		const unitsWrap = qs(root, '[data-sl-esf-units]');
		const addUnitBtn = qs(root, '[data-sl-esf-add-unit]');
		const unitBoxesInput = qs(root, '[data-sl-esf-unit-boxes]');
		const unitTemplate = qs(root, '[data-sl-esf-unit-template]');
		const form = qs(root, 'form');

		function setAdvanced(open){
			advancedToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) advancedPanel.removeAttribute('hidden');
			else advancedPanel.setAttribute('hidden', '');
		}

		advancedToggle.addEventListener('click', function(){
			const isOpen = advancedToggle.getAttribute('aria-expanded') === 'true';
			setAdvanced(!isOpen);
		});

		function updateUnitNumbers(){
			qsa(unitsWrap, '[data-sl-esf-unit]').forEach(function(unitEl, idx){
				const numEl = qs(unitEl, '[data-sl-esf-unit-number]');
				if (numEl) numEl.textContent = String(idx + 1);
			});
		}

		function serializeUnits(){
			const parts = qsa(unitsWrap, '[data-sl-esf-unit]').map(function(unitEl){
				const sleeps = qs(unitEl, '[data-sl-esf-counter="sleeps"] [data-sl-esf-value]');
				const beds = qs(unitEl, '[data-sl-esf-counter="bedrooms"] [data-sl-esf-value]');
				return (sleeps ? sleeps.textContent : '0') + ',' + (beds ? beds.textContent : '0');
			});
			unitBoxesInput.value = parts.map(p => p + '/').join(' ');
		}

		function updateMeta(){
			const checked = qsa(root, '.sl-esf__advanced-panel input[type="checkbox"]:checked').length;
			const unitCount = qsa(unitsWrap, '[data-sl-esf-unit]').length;
			const filters = checked + (unitCount > 0 ? 1 : 0);
			metaEl.textContent = filters + (filters === 1 ? ' filter set' : ' filters set');
		}

		function bindCounter(unitEl){
			qsa(unitEl, '[data-sl-esf-counter]').forEach(function(counter){
				const valueEl = qs(counter, '[data-sl-esf-value]');
				const min = clamp(valueEl.getAttribute('data-min'), 0, 999);
				const max = clamp(valueEl.getAttribute('data-max'), min, 999);

				counter.addEventListener('click', function(e){
					const minus = e.target.closest('[data-sl-esf-minus]');
					const plus = e.target.closest('[data-sl-esf-plus]');
					if (!minus && !plus) return;

					const cur = clamp(valueEl.textContent, min, max);
					const next = clamp(cur + (plus ? 1 : -1), min, max);
					valueEl.textContent = String(next);
					serializeUnits();
					updateMeta();
				});
			});
		}

		const unitsSelect = qs(root, 'select[name="units"]');

		function syncUnitsSelect(){
			if (!unitsSelect) return;
			const count = qsa(unitsWrap, '[data-sl-esf-unit]').length;
			unitsSelect.value = count ? String(count) : '';
		}

		function addUnit(skipSelectSync){
			const current = qsa(unitsWrap, '[data-sl-esf-unit]').length;
			if (current >= maxUnits) return;

			const frag = document.createElement('div');
			frag.innerHTML = unitTemplate.innerHTML.trim();
			const unitEl = frag.firstElementChild;

			qs(unitEl, '[data-sl-esf-remove-unit]').addEventListener('click', function(){
				unitEl.remove();
				updateUnitNumbers();
				serializeUnits();
				updateMeta();
				syncUnitsSelect();
			});

			bindCounter(unitEl);
			unitsWrap.appendChild(unitEl);

			updateUnitNumbers();
			serializeUnits();
			updateMeta();
			if (!skipSelectSync) syncUnitsSelect();
		}

		function removeLastUnit(skipSelectSync){
			const units = qsa(unitsWrap, '[data-sl-esf-unit]');
			const last = units[units.length - 1];
			if (!last) return;
			last.remove();
			updateUnitNumbers();
			serializeUnits();
			updateMeta();
			if (!skipSelectSync) syncUnitsSelect();
		}

		function syncDynamicUnitsToSelect(){
			if (!unitsSelect) return;

			const raw = unitsSelect.value;
			let current = qsa(unitsWrap, '[data-sl-esf-unit]').length;

			if (raw === '') {
				while (current > 0) {
					removeLastUnit(true);
					current--;
				}
				serializeUnits();
				updateMeta();
				syncUnitsSelect();
				return;
			}

			const target = clamp(raw, 1, maxUnits);

			while (current < target) {
				addUnit(true);
				current++;
			}
			while (current > target) {
				removeLastUnit(true);
				current--;
			}

			serializeUnits();
			updateMeta();
			syncUnitsSelect();
		}

		addUnitBtn.addEventListener('click', function(){
			addUnit();
		});
		if (unitsSelect) {
			unitsSelect.addEventListener('change', syncDynamicUnitsToSelect);
		}

		// Any checkbox change updates meta
		qsa(root, '.sl-esf__advanced-panel input[type="checkbox"]').forEach(function(cb){
			cb.addEventListener('change', updateMeta);
		});

		// Preserve Elementor search props if present in current URL
		(function preserveElementorProps(){
			const params = new URLSearchParams(window.location.search || '');
			const v = params.get('e_search_props');
			if (!v) return;
			if (form.querySelector('input[name="e_search_props"]')) return;
			const h = document.createElement('input');
			h.type = 'hidden';
			h.name = 'e_search_props';
			h.value = v;
			form.appendChild(h);
		})();

		// Clear
		const clearBtn = qs(root, '[data-sl-esf-clear]');
		if (clearBtn) {
			clearBtn.addEventListener('click', function(){
				// Reset all inputs
				const inputs = qsa(root, 'input, select');
				inputs.forEach(function(el){
					if (el.type === 'checkbox') el.checked = false;
					else if (el.tagName === 'SELECT') el.value = '';
					else if (el.type === 'hidden' && el.name === 'unit_boxes') el.value = '';
				});

				// Clear dynamic units
				let units = qsa(unitsWrap, '[data-sl-esf-unit]');
				units.forEach(function(u){ u.remove(); });

				updateUnitNumbers();
				serializeUnits();
				updateMeta();

				// Reset dropdowns to "Any"
				const unitsSelect = qs(root, 'select[name="units"]');
				if (unitsSelect) unitsSelect.value = '';

				const guestsSelect = qs(root, 'select[name="guests"]');
				if (guestsSelect) guestsSelect.value = '';

				const destSelect = qs(root, 'select[name="destination_term"]');
				if (destSelect) destSelect.value = '';
			});
		}

		// Ensure WordPress treats it as a search request
		form.addEventListener('submit', function(){
			const sInput = form.querySelector('input[name="s"]');
			if (sInput) sInput.value = '';
		});

		// Seed default units
		if (unitsSelect) {
			unitsSelect.value = String(defaultUnits);
			syncDynamicUnitsToSelect();
		} else {
			for (let i=0; i<defaultUnits; i++) addUnit();
		}
	}

	document.addEventListener('DOMContentLoaded', function(){
		document.querySelectorAll('.sl-esf').forEach(initOne);
	});
})();
