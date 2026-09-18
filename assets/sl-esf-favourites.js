(function () {
	var config = window.slEsfFavourites || {};

	function toggle(button) {
		if (button.classList.contains('is-loading')) return;

		if (!config.loggedIn) {
			alert(config.loginNotice || 'Please log in to save favourites.');
			return;
		}

		button.classList.add('is-loading');

		var formData = new FormData();
		formData.append('action', config.action || 'sl_toggle_favourite');
		formData.append('post_id', button.getAttribute('data-post-id'));
		formData.append('nonce', button.getAttribute('data-nonce'));

		fetch(config.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (response) { return response.json(); })
			.then(function (result) {
				button.classList.remove('is-loading');

				if (!result.success) {
					if (result.data && result.data.message) alert(result.data.message);
					return;
				}

				button.classList.toggle('is-active', !!result.data.is_favourited);
				button.setAttribute('aria-pressed', result.data.is_favourited ? 'true' : 'false');
			})
			.catch(function () {
				button.classList.remove('is-loading');
				alert(config.errorNotice || 'Something went wrong.');
			});
	}

	// Delegated so buttons rendered later (Elementor loops, popups, AJAX pagination) still work.
	document.addEventListener('click', function (event) {
		var button = event.target.closest('.sl-favourite-btn');
		if (button) toggle(button);
	});
})();
