
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
		const defaultUnits = clamp(root.getAttribute('data-default-units'), 0, maxUnits);

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

		// Ensure WordPress treats it as a search request, and give each search its own shuffled order
		form.addEventListener('submit', function(){
			const sInput = form.querySelector('input[name="s"]');
			if (sInput) sInput.value = '';

			let shuffleInput = form.querySelector('input[name="shuffle"]');
			if (!shuffleInput) {
				shuffleInput = document.createElement('input');
				shuffleInput.type = 'hidden';
				shuffleInput.name = 'shuffle';
				form.appendChild(shuffleInput);
			}
			shuffleInput.value = String(Math.floor(Math.random() * 999999) + 1);
		});

		// Seed default units
		if (unitsSelect) {
			if (defaultUnits > 0) {
				unitsSelect.value = String(defaultUnits);
			}
			syncDynamicUnitsToSelect();
		} else {
			for (let i=0; i<defaultUnits; i++) addUnit();
		}
	}

	function getCarouselVisible(root){
		const desktop = clamp(root.getAttribute('data-sl-esf-carousel-columns-desktop') || root.getAttribute('data-sl-esf-carousel-columns'), 1, 4);
		const tablet = clamp(root.getAttribute('data-sl-esf-carousel-columns-tablet'), 1, 4);
		const mobile = clamp(root.getAttribute('data-sl-esf-carousel-columns-mobile'), 1, 4);
		if (window.innerWidth <= 767) return mobile;
		if (window.innerWidth <= 1024) return tablet;
		return desktop;
	}

	function initCarousel(root){
		if (root.getAttribute('data-sl-esf-carousel-ready') === '1') return;
		root.setAttribute('data-sl-esf-carousel-ready', '1');

		const track = qs(root, '[data-sl-esf-carousel-track]');
		if (!track) return;

		const slides = qsa(root, '[data-sl-esf-carousel-slide]');
		const prev = qs(root, '[data-sl-esf-carousel-prev]');
		const next = qs(root, '[data-sl-esf-carousel-next]');
		const dotsWrap = qs(root, '[data-sl-esf-carousel-dots]');
		const loopEnabled = root.getAttribute('data-sl-esf-carousel-loop') === '1';
		let current = 0;
		let visible = 1;
		let maxIndex = 0;
		let slideStep = 0;
		let autoplayTimer = null;
		let isMoving = false;
		let loopDirection = '';

		function getGap(){
			const styles = window.getComputedStyle(root);
			const raw = styles.getPropertyValue('--sl-esf-results-column-gap') || '40px';
			return clamp(raw, 0, 200);
		}

		function buildDots(){
			if (!dotsWrap) return;
			dotsWrap.innerHTML = '';
			const dotCount = loopEnabled && maxIndex > 0 ? slides.length : maxIndex + 1;
			for (let i = 0; i < dotCount; i++) {
				const dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'sl-esf-related-carousel__dot';
				dot.setAttribute('aria-label', 'Go to slide ' + String(i + 1));
				dot.addEventListener('click', function(){
					current = i;
					renderLoopOrder();
					update(false);
				});
				dotsWrap.appendChild(dot);
			}
		}

		function getDotIndex(){
			if (loopEnabled && slides.length) return ((current % slides.length) + slides.length) % slides.length;
			if (current < 0) return maxIndex;
			if (current > maxIndex) return maxIndex;
			return current;
		}

		function updateDots(){
			if (!dotsWrap) return;
			const activeIndex = getDotIndex();
			qsa(dotsWrap, 'button').forEach(function(dot, idx){
				const active = idx === activeIndex;
				dot.classList.toggle('is-active', active);
				dot.setAttribute('aria-current', active ? 'true' : 'false');
			});
		}

		function renderLoopOrder(){
			if (!loopEnabled || !slides.length) return;
			const normalized = getDotIndex();
			slides.forEach(function(_, idx){
				track.appendChild(slides[(normalized + idx) % slides.length]);
			});
		}

		function update(animate){
			const gap = getGap();
			const viewportWidth = root.clientWidth;
			const slideWidth = visible > 1 ? (viewportWidth - (gap * (visible - 1))) / visible : viewportWidth;
			slideStep = slideWidth + gap;

			if (animate === false) {
				track.style.transition = 'none';
			} else {
				track.style.transition = '';
			}

			slides.forEach(function(slide){
				slide.style.flex = '0 0 ' + slideWidth + 'px';
			});

			track.style.gap = gap + 'px';
			track.style.transform = loopEnabled ? 'translate3d(0,0,0)' : 'translate3d(-' + (slideStep * current) + 'px,0,0)';
			root.classList.toggle('sl-esf-related-carousel--locked', maxIndex < 1);
			updateDots();

			if (animate === false) {
				window.requestAnimationFrame(function(){
					track.style.transition = '';
				});
			}
		}

		function resetLoopTrack(){
			track.style.transition = 'none';
			track.style.transform = 'translate3d(0,0,0)';
			window.requestAnimationFrame(function(){
				track.style.transition = '';
				isMoving = false;
			});
		}

		function goNext(){
			if (maxIndex < 1) return;

			if (loopEnabled) {
				if (isMoving) return;
				isMoving = true;
				loopDirection = 'next';
				track.style.transition = '';
				track.style.transform = 'translate3d(-' + slideStep + 'px,0,0)';
				return;
			}

			current = current >= maxIndex ? 0 : current + 1;
			update();
		}

		function goPrev(){
			if (maxIndex < 1) return;

			if (loopEnabled) {
				if (isMoving) return;
				isMoving = true;
				loopDirection = 'prev';
				current = (current - 1 + slides.length) % slides.length;
				track.insertBefore(slides[current], track.firstElementChild);
				track.style.transition = 'none';
				track.style.transform = 'translate3d(-' + slideStep + 'px,0,0)';
				track.offsetHeight;
				track.style.transition = '';
				track.style.transform = 'translate3d(0,0,0)';
				updateDots();
				return;
			}

			current = current <= 0 ? maxIndex : current - 1;
			update();
		}

		function startAutoplay(){
			if (autoplayTimer) window.clearInterval(autoplayTimer);
			if (maxIndex < 1) return;
			autoplayTimer = window.setInterval(goNext, 4000);
		}

		function refresh(){
			visible = Math.max(1, Math.min(getCarouselVisible(root), slides.length || 1));
			maxIndex = Math.max(0, slides.length - visible);
			current = Math.max(0, Math.min(current, maxIndex));
			if (loopEnabled) {
				renderLoopOrder();
				maxIndex = slides.length > visible ? slides.length - 1 : 0;
			}
			buildDots();
			update(false);
			startAutoplay();
		}

		if (prev) {
			prev.addEventListener('click', function(){
				goPrev();
				startAutoplay();
			});
		}

		if (next) {
			next.addEventListener('click', function(){
				goNext();
				startAutoplay();
			});
		}

		track.addEventListener('transitionend', function(e){
			if (e.target !== track || !loopEnabled || !isMoving) {
				return;
			}

			if (loopDirection === 'next') {
				const first = track.firstElementChild;
				if (first) {
					track.appendChild(first);
				}
				current = (current + 1) % slides.length;
				updateDots();
			}

			loopDirection = '';
			resetLoopTrack();
		});

		window.addEventListener('resize', refresh);
		refresh();
	}

	function initCarousels(root){
		(root || document).querySelectorAll('[data-sl-esf-carousel]').forEach(initCarousel);
	}

	document.addEventListener('DOMContentLoaded', function(){
		document.querySelectorAll('.sl-esf').forEach(initOne);
		initCarousels(document);
	});

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/sl_esf_property_related_items.default', function($scope){
			const root = $scope && $scope[0] ? $scope[0] : document;
			initCarousels(root);
		});
	}
})();
