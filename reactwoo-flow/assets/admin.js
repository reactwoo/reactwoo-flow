(function ($) {
	'use strict';

	$(function () {
		$('.rwf-select-all').on('change', function () {
			$('.rwf-inbox-table tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
		});

		$('.rwf-analyse-button').on('click', function () {
			var $button = $(this);
			var itemId = $button.data('item-id');
			var $status = $('.rwf-analysis-status');

			if (!itemId || !window.rwfAdmin) {
				return;
			}

			$button.prop('disabled', true).text(rwfAdmin.analysing);
			$status.removeClass('rwf-status-error rwf-status-success').text('');

			window.fetch(rwfAdmin.restUrl + '/items/' + itemId + '/analyse', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': rwfAdmin.restNonce
				}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}

					return response.json();
				})
				.then(function () {
					$status.addClass('rwf-status-success').text(rwfAdmin.doneLabel);
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
				})
				.catch(function () {
					$status.addClass('rwf-status-error').text(rwfAdmin.errorLabel);
					$button.prop('disabled', false).text(rwfAdmin.analyseLabel);
				});
		});

		$('.rwf-generate-spec-button').on('click', function () {
			var $button = $(this);
			var itemId = $button.data('item-id');
			var $status = $('.rwf-analysis-status');

			if (!itemId || !window.rwfAdmin) {
				return;
			}

			$button.prop('disabled', true).text(rwfAdmin.generatingSpec);
			$status.removeClass('rwf-status-error rwf-status-success').text('');

			window.fetch(rwfAdmin.restUrl + '/items/' + itemId + '/generate-specification', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': rwfAdmin.restNonce
				}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}

					return response.json();
				})
				.then(function () {
					$status.addClass('rwf-status-success').text(rwfAdmin.specDoneLabel);
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
				})
				.catch(function () {
					$status.addClass('rwf-status-error').text(rwfAdmin.specErrorLabel);
					$button.prop('disabled', false).text(rwfAdmin.generateSpecLabel);
				});
		});

		$('.rwf-prepare-handoff-button').on('click', function () {
			var $button = $(this);
			var itemId = $button.data('item-id');
			var $status = $('.rwf-analysis-status');

			if (!itemId || !window.rwfAdmin) {
				return;
			}

			$button.prop('disabled', true).text(rwfAdmin.preparingHandoff);
			$status.removeClass('rwf-status-error rwf-status-success').text('');

			window.fetch(rwfAdmin.restUrl + '/items/' + itemId + '/prepare-development-handoff', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': rwfAdmin.restNonce
				}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('Request failed');
					}

					return response.json();
				})
				.then(function () {
					$status.addClass('rwf-status-success').text(rwfAdmin.handoffDoneLabel);
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
				})
				.catch(function () {
					$status.addClass('rwf-status-error').text(rwfAdmin.handoffErrorLabel);
					$button.prop('disabled', false).text(rwfAdmin.handoffLabel);
				});
		});

		$('.rwf-media-button').on('click', function () {
			var targetId = $(this).data('rwf-media-target');
			var $target = $('#' + targetId);

			if (!$target.length || typeof wp === 'undefined' || !wp.media) {
				return;
			}

			var frame = wp.media({
				title: 'Select ReactWoo Flow attachment',
				button: {
					text: 'Use attachment URL'
				},
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var existing = $target.val();
				var separator = existing ? '\n' : '';

				$target.val(existing + separator + attachment.url);
			});

			frame.open();
		});
	});
})(jQuery);
