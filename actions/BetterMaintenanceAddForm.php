<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;

class BetterMaintenanceAddForm extends CController {

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
		$hostids = $this->getInput('hostids');

		$hosts = API::Host()->get([
			'output' => ['hostid', 'name'],
			'selectInventory' => ['notes'],
			'hostids' => $hostids,
			'editable' => true,
			'preservekeys' => true
		]);

		$maintenances = API::Maintenance()->get([
			'output' => ['maintenanceid', 'name', 'active_since', 'active_till', 'maintenance_type'],
			'editable' => true,
			'preservekeys' => true,
			'sortfield' => 'name',
			'sortorder' => ZBX_SORT_UP
		]);

		[$active_maintenances, $approaching_maintenances] = $this->splitMaintenancesByState($maintenances, time());

		$this->setResponse(new CControllerResponseData([
			'hostids' => array_keys($hosts),
			'hosts' => $hosts,
			'maintenances' => $active_maintenances,
			'approaching_maintenances' => $approaching_maintenances,
			'csrf_token' => \CCsrfTokenHelper::get('better.maintenance.add'),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		]));
	}

	private function splitMaintenancesByState(array $maintenances, int $now): array {
		$active = [];
		$approaching = [];

		foreach ($maintenances as $maintenanceid => $maintenance) {
			// Ignore expired entries so the add dialog only shows usable maintenance periods.
			if ($maintenance['active_till'] <= $now) {
				continue;
			}

			if ($maintenance['active_since'] <= $now) {
				$active[$maintenanceid] = $maintenance;
			}
			else {
				$approaching[$maintenanceid] = $maintenance;
			}
		}

		return [$active, $approaching];
	}
}
