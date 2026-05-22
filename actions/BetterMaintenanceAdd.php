<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Actions;

use API;
use CController;
use CControllerResponseData;
use CRoleHelper;

class BetterMaintenanceAdd extends CController {

	protected function init(): void {
		$this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
	}

	protected function checkInput(): bool {
		$ret = $this->validateInput([
			'hostids' => 'required|array_id',
			'maintenanceid' => 'required|id'
		]);

		if ($ret) {
			$maintenance = $this->getAvailableMaintenance();

			if ($maintenance === null) {
				error(_('No permissions to referred object or it does not exist!'));
				$ret = false;
			}
		}

		if (!$ret) {
			$this->setResponse(new CControllerResponseData(['main_block' => json_encode([
				'error' => [
					'title' => _('Cannot update maintenance period'),
					'messages' => array_column(get_and_clear_messages(), 'message')
				]
			])]));
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		return $this->checkAccess(CRoleHelper::UI_CONFIGURATION_MAINTENANCE)
			&& $this->checkAccess(CRoleHelper::ACTIONS_EDIT_MAINTENANCE);
	}

	protected function doAction(): void {
		$maintenance = $this->getAvailableMaintenance();
		$hostids = array_values(array_unique(array_merge(
			array_column($maintenance['hosts'], 'hostid'),
			$this->getInput('hostids')
		)));

		$result = API::Maintenance()->update([
			'maintenanceid' => $this->getInput('maintenanceid'),
			'hosts' => zbx_toObject($hostids, 'hostid')
		]);

		if ($result) {
			$output = [
				'success' => [
					'title' => _('Maintenance period updated'),
					'messages' => [_n('%1$s host selected.', '%1$s hosts selected.', count($this->getInput('hostids')))]
				]
			];

			if ($messages = get_and_clear_messages()) {
				$output['success']['messages'] = array_merge(
					$output['success']['messages'],
					array_column($messages, 'message')
				);
			}
		}
		else {
			$output = [
				'error' => [
					'title' => _('Cannot update maintenance period'),
					'messages' => array_column(get_and_clear_messages(), 'message')
				]
			];
		}

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
	}

	private function getAvailableMaintenance(): ?array {
		$maintenances = API::Maintenance()->get([
			'output' => ['maintenanceid', 'active_since', 'active_till'],
			'selectHosts' => ['hostid'],
			'maintenanceids' => $this->getInput('maintenanceid'),
			'editable' => true,
			'preservekeys' => true
		]);

		$maintenance = reset($maintenances);
		$now = time();

		if (!$maintenance || $maintenance['active_till'] <= $now) {
			return null;
		}

		return $maintenance;
	}
}
