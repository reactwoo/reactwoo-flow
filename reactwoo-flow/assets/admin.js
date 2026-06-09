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

	function getAgentOverrides(agentType) {
		var prefix = 'rwf_override_' + agentType + '_';
		var providerField = document.getElementById(prefix + 'provider');
		var modelField = document.getElementById(prefix + 'model');
		var body = {};

		if (providerField && providerField.value) {
			body.provider = providerField.value;
		}
		if (modelField && modelField.value) {
			body.model = modelField.value;
		}

		return body;
	}

	function bindAgentAction(selector, urlSuffix, agentType, loadingLabel, doneLabel, errorLabel, defaultLabel) {
		$(selector).on('click', function () {
			var $button = $(this);
			var itemId = $button.data('item-id');
			var $status = $('.rwf-analysis-status');
			var requestBody = agentType ? getAgentOverrides(agentType) : {};

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
				},
				body: JSON.stringify(requestBody)
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
			'planning',
			rwfAdmin.analysing,
			rwfAdmin.doneLabel,
			rwfAdmin.errorLabel,
			rwfAdmin.analyseLabel
		);

		bindAgentAction(
			'.rwf-generate-spec-button',
			'/generate-specification',
			'planning',
			rwfAdmin.generatingSpec,
			rwfAdmin.specDoneLabel,
			rwfAdmin.specErrorLabel,
			rwfAdmin.generateSpecLabel
		);

		bindAgentAction(
			'.rwf-prepare-handoff-button',
			'/prepare-development-handoff',
			'development',
			rwfAdmin.preparingHandoff,
			rwfAdmin.handoffDoneLabel,
			rwfAdmin.handoffErrorLabel,
			rwfAdmin.handoffLabel
		);

		bindAgentAction(
			'.rwf-generate-release-notes-button',
			'/generate-release-notes',
			'release',
			rwfAdmin.generatingReleaseNotes,
			rwfAdmin.releaseNotesDoneLabel,
			rwfAdmin.releaseNotesErrorLabel,
			rwfAdmin.generateReleaseNotesLabel
		);

		bindAgentAction(
			'.rwf-create-jira-button',
			'/integrations/jira/create-issue',
			null,
			rwfAdmin.creatingJira,
			rwfAdmin.jiraDoneLabel,
			rwfAdmin.jiraErrorLabel,
			rwfAdmin.createJiraLabel
		);

		bindAgentAction(
			'.rwf-publish-confluence-button',
			'/integrations/confluence/publish-specification',
			null,
			rwfAdmin.publishingConfluence,
			rwfAdmin.confluenceDoneLabel,
			rwfAdmin.confluenceErrorLabel,
			rwfAdmin.publishConfluenceLabel
		);

		bindAgentAction(
			'.rwf-sync-github-button',
			'/integrations/github/sync-pull-request',
			null,
			rwfAdmin.syncingGithub,
			rwfAdmin.githubDoneLabel,
			rwfAdmin.githubErrorLabel,
			rwfAdmin.syncGithubLabel
		);

		bindAgentAction(
			'.rwf-send-cursor-button',
			'/integrations/cursor/send-handoff',
			null,
			rwfAdmin.sendingCursor,
			rwfAdmin.cursorDoneLabel,
			rwfAdmin.cursorErrorLabel,
			rwfAdmin.sendCursorLabel
		);

		bindAgentAction(
			'.rwf-run-qa-review-button',
			'/run-qa-review',
			'qa',
			rwfAdmin.runningQaReview,
			rwfAdmin.qaReviewDoneLabel,
			rwfAdmin.qaReviewErrorLabel,
			rwfAdmin.runQaReviewLabel
		);

		bindAgentAction(
			'.rwf-run-ux-review-button',
			'/run-ux-review',
			'ux',
			rwfAdmin.runningUxReview,
			rwfAdmin.uxReviewDoneLabel,
			rwfAdmin.uxReviewErrorLabel,
			rwfAdmin.runUxReviewLabel
		);

		bindAgentAction(
			'.rwf-sync-jira-button',
			'/integrations/jira/sync-status',
			null,
			rwfAdmin.syncingJira,
			rwfAdmin.jiraSyncDoneLabel,
			rwfAdmin.jiraSyncErrorLabel,
			rwfAdmin.syncJiraLabel
		);

		bindAgentAction(
			'.rwf-apply-suggestions-button',
			'/apply-triage-suggestions',
			null,
			rwfAdmin.applyingSuggestions,
			rwfAdmin.suggestionsDoneLabel,
			rwfAdmin.suggestionsErrorLabel,
			rwfAdmin.applySuggestionsLabel
		);

		function populateGithubRepoSelect($select, repositories) {
			var selected = $select.data('selected') || $select.val() || '';
			var placeholder = (window.rwfAdmin.githubSettings && rwfAdmin.githubSettings.selectRepository) || '— Select repository —';

			$select.empty();
			$select.append($('<option>', { value: '', text: placeholder }));

			repositories.forEach(function (repo) {
				if (!repo || !repo.full_name) {
					return;
				}

				var option = $('<option>', {
					value: repo.full_name,
					text: repo.full_name
				});

				if (repo.full_name === selected) {
					option.prop('selected', true);
				}

				$select.append(option);
			});
		}

		function loadGithubRepositories() {
			var settings = window.rwfAdmin && rwfAdmin.githubSettings;
			var $status = $('#rwf-github-repos-status');
			var $selects = $('#rwf-github-product-map .rwf-github-repo-select');

			if (!settings || !$selects.length) {
				return;
			}

			if (!settings.hasToken) {
				$status.text(settings.saveTokenToLoadRepos || '');
				return;
			}

			$status.text(settings.loadingRepositories || '');

			window.fetch(settings.repositoriesUrl, {
				headers: {
					'X-WP-Nonce': rwfAdmin.restNonce
				}
			})
				.then(function (response) {
					return parseRestResponse(response, settings.repositoriesFailed || 'Failed to load repositories.');
				})
				.then(function (body) {
					var repositories = body && body.repositories ? body.repositories : [];

					$selects.each(function () {
						populateGithubRepoSelect($(this), repositories);
					});

					$status.text(settings.repositoriesLoaded || '');
				})
				.catch(function (error) {
					$status.text(error.message || settings.repositoriesFailed || '');
				});
		}

		loadGithubRepositories();

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
