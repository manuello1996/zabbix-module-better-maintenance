(function() {
	'use strict';

	function init() {
		const config = window.BetterMaintenanceSettingsConfig || {};
		const tabs = document.getElementById('tabs');

		if (!tabs || document.getElementById('better-maintenance-settings-tab')) {
			return;
		}

		const nav = tabs.querySelector('.ui-tabs-nav') || createNavigation(tabs, config);
		const main_footer = [...tabs.children].find((child) => child.classList.contains('form-buttons'));
		const link_item = document.createElement('li');
		const link = document.createElement('a');
		link.href = '#better-maintenance-settings-tab';
		link.id = 'tab_better-maintenance-settings-tab';
		link.textContent = config.tab_title || 'Maintenance';
		link_item.appendChild(link);
		nav.appendChild(link_item);

		const tab = document.createElement('div');
		tab.id = 'better-maintenance-settings-tab';
		tab.innerHTML = config.html || '';
		tab.style.display = 'none';
		tab.setAttribute('aria-hidden', 'true');
		tabs.appendChild(tab);

		const initial_tab = window.location.hash === '#better-maintenance-settings-tab' ? 1 : 0;

		if (jQuery(tabs).data('uiTabs')) {
			jQuery(tabs).tabs('refresh');
			jQuery(tabs).tabs('option', 'active', initial_tab);
		}
		else {
			jQuery(tabs).tabs({
				active: initial_tab,
				activate(event, ui) {
					if (!main_footer) {
						return;
					}

					main_footer.style.display = ui.newPanel.attr('id') === 'better-maintenance-settings-tab'
						? 'none'
						: '';
				}
			});
		}

		if (main_footer) {
			main_footer.style.display = initial_tab === 1 ? 'none' : '';
		}

		const teams_enabled = document.getElementById('bm-teams-enabled');
		const teams_text = document.getElementById('bm-teams-text');
		const save_button = document.getElementById('bm-save-settings');

		if (!teams_enabled || !teams_text || !save_button) {
			return;
		}

		teams_enabled.addEventListener('change', () => {
			teams_text.disabled = !teams_enabled.checked;
		});

		save_button.addEventListener('click', () => saveSettings(config));
	}

	function saveSettings(config) {
		const curl = new Curl('zabbix.php');
		curl.setArgument('action', config.save_action || 'better.maintenance.settings.update');

		const body = {
			[CSRF_TOKEN_NAME]: config.csrf_token,
			teams_enabled: document.getElementById('bm-teams-enabled').checked ? '1' : '0',
			teams_text: document.getElementById('bm-teams-text').value,
			username_suffix_enabled: document.getElementById('bm-username-suffix-enabled').checked ? '1' : '0'
		};

		fetch(curl.getUrl(), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(body)
		})
			.then((response) => response.json())
			.then((response) => {
				if ('error' in response) {
					throw response.error;
				}

				clearMessages();
				addMessage(makeMessageBox('good', [], response.success.title));
			})
			.catch((error) => {
				clearMessages();
				addMessage(makeMessageBox(
					'bad',
					error && error.messages ? error.messages : [config.unexpected_error || 'Unexpected server error.'],
					error ? error.title : null
				));
			});
	}

	function createNavigation(tabs, config) {
		const nav = document.createElement('ul');
		nav.className = 'ui-tabs-nav tabs-nav';

		const current_tab = tabs.querySelector('#other') || tabs.firstElementChild;
		const current_item = document.createElement('li');
		const current_link = document.createElement('a');
		current_link.href = `#${current_tab.id}`;
		current_link.id = `tab_${current_tab.id}`;
		current_link.textContent = config.base_tab_title || 'Other parameters';
		current_item.appendChild(current_link);
		nav.appendChild(current_item);

		tabs.insertBefore(nav, tabs.firstChild);

		return nav;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	}
	else {
		init();
	}
})();
