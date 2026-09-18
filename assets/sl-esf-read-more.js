(function () {
	var LIMIT = 400;

	function init() {
		document.querySelectorAll('.read_more_text').forEach(function (container) {
			// Already handled (by this script or the legacy snippet).
			if (container.querySelector('.read-more-toggle')) return;

			var fullText = '';
			container.querySelectorAll('p').forEach(function (p) { fullText += p.textContent + ' '; });
			fullText = fullText.trim();
			if (fullText.length <= LIMIT) return;

			var shortEl = document.createElement('p');
			shortEl.className = 'read-more-short';
			shortEl.textContent = fullText.substring(0, LIMIT) + '...';

			var fullEl = document.createElement('p');
			fullEl.className = 'read-more-full';
			fullEl.style.display = 'none';
			fullEl.textContent = fullText;

			var toggle = document.createElement('a');
			toggle.href = '#';
			toggle.className = 'read-more-toggle';
			toggle.textContent = 'Read more';

			container.innerHTML = '';
			container.appendChild(shortEl);
			container.appendChild(fullEl);
			container.appendChild(toggle);

			toggle.addEventListener('click', function (e) {
				e.preventDefault();
				var expanded = fullEl.style.display !== 'none';
				shortEl.style.display = expanded ? 'block' : 'none';
				fullEl.style.display = expanded ? 'none' : 'block';
				toggle.textContent = expanded ? 'Read more' : 'Read less';
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
