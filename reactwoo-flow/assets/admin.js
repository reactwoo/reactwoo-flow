(function ($) {
	'use strict';

	function parseRestResponse(response, fallbackMessage) {
		return response.json().then(function (body) {
			if (!response.ok) {
				var message = fallbackMessage;

				if (body && body.message) {
					message = body.message;
				} else if (body && body.data && body.data.error) {
					message = body.data.error;
				}

				throw new Error(message);
			}

			return body;
		});
	}

	function bindAgentAction(selector, urlSuffix, loadingLabel, doneLabel, errorLabel, defaultLabel) {
		$(selector).on('click', function () {
			var $button = $(this);
			var itemId = $button.data('item-id');
			var $status = $('.rwf-analysis-status');

			if (!itemId || !window.rwfAdmin) {
				return;
			}

			$button.prop('disabled', true).text(loadingLabel);
			$status.removeClass('rwf-status-error rwf-status-success').text('');

			window.fetch(rwfAdmin.restUrl + '/items/' + itemId + urlSuffix, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': rwfAdmin.restNonce
				}
			})
				.then(function (response) {
					return parseRestResponse(response, errorLabel);
				})
				.then(function () {
					$status.addClass('rwf-status-success').text(doneLabel);
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
				})
				.catch(function (error) {
					$status.addClass('rwf-status-error').text(error.message || errorLabel);
					$button.prop('disabled', false).text(defaultLabel);
				});
		});
	}

	$(function () {
		$('.rwf-select-all').on('change', function () {
			$('.rwf-inbox-table tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
		});

		bindAgentAction(
			'.rwf-analyse-button',
			'/analyse',
			rwfAdmin.analysing,
			rwfAdmin.doneLabel,
			rwfAdmin.errorLabel,
			rwfAdmin.analyseLabel
		);

		bindAgentAction(
			'.rwf-generate-spec-button',
			'/generate-specification',
			rwfAdmin.generatingSpec,
			rwfAdmin.specDoneLabel,
			rwfAdmin.specErrorLabel,
			rwfAdmin.generateSpecLabel
		);

		bindAgentAction(
			'.rwf-prepare-handoff-button',
			'/prepare-development-handoff',
			rwfAdmin.preparingHandoff,
			rwfAdmin.handoffDoneLabel,
			rwfAdmin.handoffErrorLabel,
			rwfAdmin.handoffLabel
		);

		bindAgentAction(
			'.rwf-generate-release-notes-button',
			'/generate-release-notes',
			rwfAdmin.generatingReleaseNotes,
			rwfAdmin.releaseNotesDoneLabel,
			rwfAdmin.releaseNotesErrorLabel,
			rwfAdmin.generateReleaseNotesLabel
		);

		$('.rwf-media-button').on('click', function () {
			var targetId = $(this).data('rwf-media-target');
			var $target = $('#' + targetId);
			var allowMultiple = targetId.indexOf('screenshots') !== -1;

			if (!$target.length || typeof wp === 'undefined' || !wp.media) {
				return;
			}

			var frame = wp.media({
				title: 'Select ReactWoo Flow attachment',
				button: {
					text: allowMultiple ? 'Add attachment URLs' : 'Use attachment URL'
				},
				multiple: allowMultiple
			});

			frame.on('select', function () {
				var selection = frame.state().get('selection');
				var urls = [];

				selection.each(function (attachment) {
					var json = attachment.toJSON();
					if (json.url) {
						urls.push(json.url);
					}
				});

				if (!urls.length) {
					return;
				}

				var existing = $target.val();
				var separator = existing && !existing.endsWith('\n') ? '\n' : '';
				var nextValue = existing ? existing + separator + urls.join('\n') : urls.join('\n');

				$target.val(nextValue);
			});

			frame.open();
		});
	});
})(jQuery);
