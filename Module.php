<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance;

use APP;
use CCsrfTokenHelper;
use CButton;
use CCheckBox;
use CDiv;
use CFormField;
use CFormGrid;
use CLabel;
use CTextArea;
use CUrl;
use CWebUser;
use Modules\BetterMaintenance\Includes\Config;
use Zabbix\Core\CModule;

class Module extends CModule {
	private const GENERAL_MENU_ACTIONS = [
		'gui.edit',
		'autoreg.edit',
		'timeouts.edit',
		'image.list',
		'image.edit',
		'iconmap.list',
		'iconmap.edit',
		'regex.list',
		'regex.edit',
		'trigdisplay.edit',
		'geomaps.edit',
		'module.list',
		'connector.list',
		'miscconfig.edit'
	];

	public function getAssets(): array {
		$assets = parent::getAssets();
		$action = APP::Component()->router->getAction();
		$config = Config::get();

		if ($action === 'host.list') {
			$assets['js'][] = 'better-maintenance.js';
			$assets['css'][] = 'better-maintenance.css';

			// Expose the popup endpoints and lazy-loaded calendar asset to the host list page.
			zbx_add_post_js('window.BetterMaintenanceConfig = '.json_encode([
				'form_action' => 'better.maintenance.form',
				'add_form_action' => 'better.maintenance.add.form',
				'calendar_url' => (new CUrl('jsLoader.php'))
					->setArgument('lang', CWebUser::$data['lang'] ?? ZBX_DEFAULT_LANG)
					->setArgument('ver', ZABBIX_VERSION)
					->setArgument('files', ['class.calendar.js'])
					->getUrl()
			]).';');
		}
		elseif ($action === 'miscconfig.edit') {
			$assets['js'][] = 'better-maintenance-settings.js';

			// The settings tab is injected client-side into the existing general configuration view.
			zbx_add_post_js('window.BetterMaintenanceSettingsConfig = '.json_encode([
				'html' => $this->getSettingsHtml($config),
				'save_action' => 'better.maintenance.settings.update',
				'csrf_token' => CCsrfTokenHelper::get('better.maintenance.settings.update'),
				'base_tab_title' => _('Other parameters'),
				'tab_title' => _('Maintenance'),
				'unexpected_error' => _('Unexpected server error.'),
				'teams_enabled' => (int) $config['teams_enabled'],
				'teams_text' => implode("\n", $config['teams']),
				'username_suffix_enabled' => (int) $config['username_suffix_enabled']
			]).';');
		}
		if (in_array($action, self::GENERAL_MENU_ACTIONS, true)) {
			$assets['js'][] = 'better-maintenance-general-menu.js';

			zbx_add_post_js('window.BetterMaintenanceGeneralMenuConfig = '.json_encode([
				'url' => (new CUrl('zabbix.php'))
					->setArgument('action', 'miscconfig.edit')
					->getUrl().'#better-maintenance-settings-tab',
				'label' => _('Maintenance')
			]).';');
		}

		return $assets;
	}

	private function getSettingsHtml(array $config): string {
		$form_grid = (new CFormGrid())
			->addItem([
				new CLabel(_('Enable Teams list'), 'bm-teams-enabled'),
				new CFormField(
					(new CCheckBox('bm_teams_enabled'))
						->setId('bm-teams-enabled')
						->setChecked((int) $config['teams_enabled'] === 1)
				)
			])
			->addItem([
				new CLabel(_('Teams'), 'bm-teams-text'),
				new CFormField(
					(new CTextArea('bm_teams_text', implode("\n", $config['teams'])))
						->setId('bm-teams-text')
						->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
						->setMaxlength(65535)
						->setAttribute('placeholder', _("One team per line"))
						->setEnabled((int) $config['teams_enabled'] === 1)
				)
			])
			->addItem([
				new CLabel(_('Enable username suffix'), 'bm-username-suffix-enabled'),
				new CFormField(
					(new CCheckBox('bm_username_suffix_enabled'))
						->setId('bm-username-suffix-enabled')
						->setChecked((int) $config['username_suffix_enabled'] === 1)
				)
			]);

		$footer = (new CDiv(
			(new CButton('bm-save-settings', _('Update')))
				->addClass('btn-alt')
				->setAttribute('type', 'button')
		))->addClass('bm-form-buttons');

		return (new CDiv([$form_grid, $footer]))
			->setId('better-maintenance-settings')
			->toString();
	}
}
