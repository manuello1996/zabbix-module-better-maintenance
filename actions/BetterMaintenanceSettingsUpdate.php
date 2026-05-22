<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Actions;

use CController;
use CControllerResponseData;
use CRoleHelper;
use Modules\BetterMaintenance\Includes\Config;

class BetterMaintenanceSettingsUpdate extends CController {

	protected function init(): void {
		$this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
	}

	protected function checkInput(): bool {
		$ret = $this->validateInput([
			'teams_enabled' => 'required|in 0,1',
			'teams_text' => 'string',
			'username_suffix_enabled' => 'required|in 0,1'
		]);

		if ($ret) {
			$teams = Config::normalizeTeams($this->getInput('teams_text', ''));

			if ($this->getInput('teams_enabled') == 1 && !$teams) {
				error(_('At least one team must be configured when the Teams list is enabled.'));
				$ret = false;
			}
		}

		if (!$ret) {
			$this->setResponse(new CControllerResponseData(['main_block' => json_encode([
				'error' => [
					'title' => _('Cannot update Better Maintenance settings'),
					'messages' => array_column(get_and_clear_messages(), 'message')
				]
			])]));
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->checkAccess(CRoleHelper::UI_ADMINISTRATION_GENERAL);
	}

	protected function doAction(): void {
		Config::save([
			'teams' => Config::normalizeTeams($this->getInput('teams_text', '')),
			'teams_enabled' => (int) $this->getInput('teams_enabled'),
			'username_suffix_enabled' => (int) $this->getInput('username_suffix_enabled')
		]);

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode([
			'success' => [
				'title' => _('Better Maintenance settings updated')
			]
		])]));
	}
}
