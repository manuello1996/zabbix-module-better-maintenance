(function() {
	'use strict';

	function init() {
		const config = window.BetterMaintenanceGeneralMenuConfig || {};
		const title = document.getElementById('page-title-general');
		const trigger = title ? title.closest('[data-menu-popup]') : null;

		if (!trigger || !config.url || !config.label) {
			return;
		}

		const menu = jQuery(trigger).data('menu-popup');

		if (!menu || !menu.data || !menu.data.submenu || !menu.data.submenu.main_section) {
			return;
		}

		const items = menu.data.submenu.main_section.items || {};

		if (Object.values(items).includes(config.label)) {
			return;
		}

		items[config.url] = config.label;
		menu.data.submenu.main_section.items = items;

		jQuery(trigger).data('menu-popup', menu);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	}
	else {
		init();
	}
})();
