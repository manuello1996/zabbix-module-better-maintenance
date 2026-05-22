<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;
use Modules\BetterMaintenance\Includes\Config;

class BetterMaintenanceForm extends CController {

	protected function init(): void {
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$ret = $this->validateInput([
			'hostids' => 'required|array_id'
		]);

		if (!$ret) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				])]))->disableView()
			);
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->checkAccess(CRoleHelper::UI_CONFIGURATION_MAINTENANCE)
			&& $this->checkAccess(CRoleHelper::ACTIONS_EDIT_MAINTENANCE);
	}

	protected function doAction(): void {
		$hosts = API::Host()->get([
			'output' => ['hostid', 'name'],
			'selectInventory' => ['notes'],
			'hostids' => $this->getInput('hostids'),
			'editable' => true,
			'preservekeys' => true
		]);

		$this->setResponse(new CControllerResponseData([
			'hostids' => array_keys($hosts),
			'hosts' => $hosts,
			'settings' => Config::get(),
			'csrf_token' => \CCsrfTokenHelper::get('better.maintenance.create'),
			'username' => (string) (\CWebUser::$data['username'] ?? ''),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}
}
