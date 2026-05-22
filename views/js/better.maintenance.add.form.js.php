<?php declare(strict_types = 0); ?>

window.better_maintenance_add_popup = new class {

	init() {
		this.overlay = overlays_stack.getById('better-maintenance-add');
		this.dialogue = this.overlay.$dialogue[0];
		this.form = this.overlay.$dialogue.$body[0].querySelector('form');

		this.form.style.display = '';
	}

	submit() {
		const fields = getFormFields(this.form);
		fields.maintenanceid = fields.maintenanceid || '';

		if (fields.maintenanceid === '') {
			this.addError(<?= json_encode(_('Maintenance must be selected.')) ?>);
			return;
		}

		const curl = new Curl('zabbix.php');
		curl.setArgument('action', 'better.maintenance.add');

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

better_maintenance_add_popup.init();
