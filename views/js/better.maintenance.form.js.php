<?php declare(strict_types = 0); ?>

window.better_maintenance_popup = new class {

	init() {
		this.overlay = overlays_stack.getById('better-maintenance-create');
		this.dialogue = this.overlay.$dialogue[0];
		this.form = this.overlay.$dialogue.$body[0].querySelector('form');
		this.name_preview = document.getElementById('better-maintenance-name-preview');

		document.getElementById('duration_mode').addEventListener('change', () => this.update());
		document.getElementById('start_mode').addEventListener('change', () => this.update());
		document.getElementById('name_suffix').addEventListener('input', () => this.updateNamePreview());

		for (const prefix of this.form.querySelectorAll('[name="prefix"]')) {
			prefix.addEventListener('change', () => this.updateNamePreview());
		}

		this.update();
		this.updateNamePreview();

		this.form.style.display = '';
		(this.form.querySelector('[name="prefix"]') || document.getElementById('name_suffix')).focus();
	}

	update() {
		const custom_selected = this.form.querySelector('[name="duration_mode"]:checked').value === 'custom';
		const custom_start_selected = this.form.querySelector('[name="start_mode"]:checked').value === 'custom';

		for (const element of this.form.querySelectorAll('.js-custom-duration')) {
			element.style.display = custom_selected ? '' : 'none';
		}

		for (const element of this.form.querySelectorAll('.js-custom-start')) {
			element.style.display = custom_start_selected ? '' : 'none';
		}

		document.getElementById('duration_custom').disabled = !custom_selected;
		document.getElementById('active_since').disabled = !custom_start_selected;
	}

	updateNamePreview() {
		if (this.name_preview === null) {
			return;
		}

		const parts = [];
		const prefix = this.form.querySelector('[name="prefix"]:checked')?.value || '';
		const detail = document.getElementById('name_suffix').value.trim();
		const username = <?= json_encode((string) $data['username']) ?>;
		const username_suffix_enabled = <?= json_encode((bool) $data['settings']['username_suffix_enabled']) ?>;

		if (prefix !== '') {
			parts.push(prefix);
		}

		if (detail !== '') {
			parts.push(detail);
		}

		if (username_suffix_enabled && username !== '') {
			parts.push(username);
		}

		this.name_preview.textContent = parts.length > 0
			? parts.join(' - ')
			: <?= json_encode(_('Preview will appear here once you start filling the name.')) ?>;
	}

	submit() {
		const fields = getFormFields(this.form);
		const duration_mode = fields.duration_mode;

		fields.duration = duration_mode === 'custom' ? fields.duration_custom.trim() : duration_mode;
		fields.prefix = fields.prefix || '';
		fields.name_suffix = fields.name_suffix.trim();
		fields.description = fields.description || '';
		delete fields.duration_mode;
		delete fields.duration_custom;

		if (fields.start_mode !== 'custom') {
			delete fields.active_since;
		}

		if (this.form.querySelector('[name="prefix"]') !== null && fields.prefix === '') {
			this.addError(<?= json_encode(_('Team must be selected.')) ?>);
			return;
		}

		if (fields.name_suffix === '') {
			document.getElementById('name_suffix').focus();
			return;
		}

		if (!/^[1-9][0-9]*[smhdw]?$/.test(fields.duration)) {
			this.addError(<?= json_encode(_('Duration must use a Zabbix time unit, for example 12h, 1d, or 1w.')) ?>);
			document.getElementById('duration_custom').focus();
			return;
		}

		if (fields.start_mode === 'custom' && (fields.active_since || '').trim() === '') {
			document.getElementById('active_since').focus();
			return;
		}

		const curl = new Curl('zabbix.php');
		curl.setArgument('action', 'better.maintenance.create');

		this.overlay.setLoading();

		fetch(curl.getUrl(), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(fields)
		})
			.then(response => response.json())
			.then(response => {
				if ('error' in response) {
					throw response.error;
				}

				overlayDialogueDestroy(this.overlay.dialogueid);
				this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
			})
			.catch(error => {
				this.addError(error && error.messages ? error.messages : [<?= json_encode(_('Unexpected server error.')) ?>],
					error ? error.title : null
				);
			})
			.finally(() => {
				this.overlay.unsetLoading();
			});
	}

	addError(messages, title = null) {
		for (const element of this.form.parentNode.children) {
			if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
				element.parentNode.removeChild(element);
			}
		}

		this.form.parentNode.insertBefore(
			makeMessageBox('bad', Array.isArray(messages) ? messages : [messages], title)[0],
			this.form
		);
	}
};

better_maintenance_popup.init();
