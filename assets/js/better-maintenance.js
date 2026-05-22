(function() {
	'use strict';

	let calendar_script_promise = null;

	function init() {
		const config = window.BetterMaintenanceConfig || {};
		const params = new URLSearchParams(window.location.search);

		if ((params.get('action') || '') !== 'host.list') {
			return;
		}

		const form = document.forms.hosts;
		const actions = document.getElementById('action_buttons');

		if (form === undefined || actions === null || actions.querySelector('.js-better-maintenance-create')) {
			return;
		}

		const create_button = document.createElement('button');
		create_button.type = 'button';
		create_button.className = 'btn-alt js-no-chkbxrange js-better-maintenance-create';
		create_button.textContent = 'Create maintenance';
		create_button.addEventListener('click', e => openDialog({
			action: config.form_action || 'better.maintenance.form',
			dialogueid: 'better-maintenance-create',
			requires_calendar: true,
			config,
			trigger: e.target
		}));

		const add_button = document.createElement('button');
		add_button.type = 'button';
		add_button.className = 'btn-alt js-no-chkbxrange js-better-maintenance-add';
		add_button.textContent = 'Add to maintenance';
		add_button.addEventListener('click', e => openDialog({
			action: config.add_form_action || 'better.maintenance.add.form',
			dialogueid: 'better-maintenance-add',
			config,
			trigger: e.target
		}));

		actions.appendChild(create_button);
		actions.appendChild(add_button);
	}

	function openDialog({action, dialogueid, requires_calendar = false, config = {}, trigger}) {
		const hostids = getSelectedHostIds();

		if (hostids.length === 0) {
			showError('No hosts selected.');
			return;
		}

		if (requires_calendar && typeof window.toggleCalendar !== 'function') {
			ensureCalendarScript(config.calendar_url)
				.then(() => openDialog({action, dialogueid, config, trigger}))
				.catch(() => showError('Cannot load calendar widget.'));

			return;
		}

		const overlay = PopUp(action, {hostids}, {
			dialogueid,
			dialogue_class: 'modal-popup-large',
			prevent_navigation: true,
			trigger_element: trigger
		});

		overlay.$dialogue[0].addEventListener('dialogue.submit', e => {
			const data = e.detail;

			if ('success' in data) {
				postMessageOk(data.success.title);

				if ('messages' in data.success) {
					postMessageDetails('success', data.success.messages);
				}
			}

			uncheckTableRows('hosts');
			window.location.href = window.location.href;
		}, {once: true});
	}

	function getSelectedHostIds() {
		if (window.chkbxRange !== undefined && typeof chkbxRange.getSelectedIds === 'function') {
			return Object.keys(chkbxRange.getSelectedIds());
		}

		return [...document.querySelectorAll('form[name="hosts"] input[name^="hostids["]:checked')]
			.map(input => input.value);
	}

	function showError(messages, title = null) {
		clearMessages();
		addMessage(makeMessageBox('bad', Array.isArray(messages) ? messages : [messages], title));
	}

	function ensureCalendarScript(calendar_url) {
		if (typeof window.toggleCalendar === 'function') {
			return Promise.resolve();
		}

		if (calendar_script_promise !== null) {
			return calendar_script_promise;
		}

		calendar_script_promise = new Promise((resolve, reject) => {
			const script = document.createElement('script');
			script.src = calendar_url || 'jsLoader.php?files[]=class.calendar.js';
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});

		return calendar_script_promise;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	}
	else {
		init();
	}
})();
