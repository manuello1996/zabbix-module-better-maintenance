<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Actions;

use API;
use CAbsoluteTimeParser;
use CController;
use CControllerResponseData;
use CParser;
use CRoleHelper;
use CWebUser;
use Modules\BetterMaintenance\Includes\Config;

class BetterMaintenanceCreate extends CController {

	protected function init(): void {
		$this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
	}

	protected function checkInput(): bool {
		$fields = [
			'hostids' => 'required|array_id',
			'prefix' => 'string',
			'name_suffix' => 'required|string|not_empty',
			'maintenance_type' => 'required|in '.implode(',', [MAINTENANCE_TYPE_NORMAL, MAINTENANCE_TYPE_NODATA]),
			'start_mode' => 'required|in now,custom',
			'active_since' => 'string',
			'duration' => 'required|string|not_empty',
			'description' => 'required|string'
		];

		$ret = $this->validateInput($fields);

		if ($ret) {
			$data = $this->getNormalizedInput();

			if (!$data['hostids']) {
				error(_('At least one host must be selected.'));
				$ret = false;
			}
			elseif ($data['config']['teams_enabled'] && !in_array($data['prefix'], $data['config']['teams'], true)) {
				error(_('Team must be selected.'));
				$ret = false;
			}
			elseif ($data['active_since'] === null) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Active since'), _('invalid time')));
				$ret = false;
			}
			elseif (!validateUnixTime($data['active_since'])) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Active since'), _('value is too large')));
				$ret = false;
			}
			elseif ($data['period'] === null || $data['period'] <= 0) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Duration'), _('invalid time')));
				$ret = false;
			}
			elseif (!validateUnixTime($data['active_since'] + $data['period'])) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Duration'), _('value is too large')));
				$ret = false;
			}
			elseif (strlen($data['name']) > 128) {
				error(_s('Incorrect value for field "%1$s": %2$s.', _('Name'), _('value is too long')));
				$ret = false;
			}
		}

		if (!$ret) {
			$this->setResponse(new CControllerResponseData(['main_block' => json_encode([
				'error' => [
					'title' => _('Cannot create maintenance period'),
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
		$data = $this->getNormalizedInput();
		$active_till = $data['active_since'] + $data['period'];

		$maintenance = [
			'name' => $data['name'],
			'maintenance_type' => $this->getInput('maintenance_type'),
			'description' => $this->getInput('description', ''),
			'active_since' => $data['active_since'],
			'active_till' => $active_till,
			'hosts' => zbx_toObject($data['hostids'], 'hostid'),
			'timeperiods' => [[
				'timeperiod_type' => TIMEPERIOD_TYPE_ONETIME,
				'start_date' => $data['active_since'],
				'period' => $data['period']
			]]
		];

		if ($maintenance['maintenance_type'] == MAINTENANCE_TYPE_NORMAL) {
			$maintenance += [
				'tags_evaltype' => MAINTENANCE_TAG_EVAL_TYPE_AND_OR,
				'tags' => []
			];
		}

		$result = API::Maintenance()->create($maintenance);

		if ($result) {
			$output = [
				'success' => [
					'title' => _('Maintenance period created'),
					'messages' => [_n('%1$s host selected.', '%1$s hosts selected.', count($data['hostids']))]
				],
				'maintenanceids' => $result['maintenanceids'] ?? []
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
					'title' => _('Cannot create maintenance period'),
					'messages' => array_column(get_and_clear_messages(), 'message')
				]
			];
		}

		$this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
	}

	private function getNormalizedInput(): array {
		$config = Config::get();
		$prefix = trim($this->getInput('prefix', ''));
		$name_suffix = trim($this->getInput('name_suffix'));
		$username = (string) (CWebUser::$data['username'] ?? '');

		return [
			'active_since' => $this->getActiveSince(),
			'config' => $config,
			'hostids' => array_values(array_unique($this->getInput('hostids', []))),
			'name' => Config::buildMaintenanceName($name_suffix, $username, $config, $prefix !== '' ? $prefix : null),
			'period' => timeUnitToSeconds($this->getInput('duration')),
			'prefix' => $prefix
		];
	}

	private function getActiveSince(): ?int {
		if ($this->getInput('start_mode') === 'now') {
			return time();
		}

		// Reuse Zabbix' absolute time parser so custom timestamps behave like native forms.
		$absolute_time_parser = new CAbsoluteTimeParser();

		if ($absolute_time_parser->parse($this->getInput('active_since', '')) != CParser::PARSE_SUCCESS) {
			return null;
		}

		return $absolute_time_parser->getDateTime(true)->getTimestamp();
	}
}
