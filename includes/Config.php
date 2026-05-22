<?php declare(strict_types = 0);

namespace Modules\BetterMaintenance\Includes;

use APP;
use Zabbix\Core\CModule;

class Config {
	public const MODULE_ID = 'better-maintenance';

	private const DEFAULT_CONFIG = [
		'teams' => [],
		'teams_enabled' => 0,
		'username_suffix_enabled' => 0
	];

	public static function getModule(): ?CModule {
		return APP::ModuleManager()->getModule(self::MODULE_ID);
	}

	public static function get(): array {
		$module = self::getModule();

		if ($module === null) {
			return self::DEFAULT_CONFIG;
		}

		$config = array_replace(self::DEFAULT_CONFIG, $module->getConfig());
		$config['teams'] = self::normalizeTeams($config['teams']);
		$config['teams_enabled'] = (int) $config['teams_enabled'];
		$config['username_suffix_enabled'] = (int) $config['username_suffix_enabled'];

		return $config;
	}

	public static function save(array $config): void {
		$module = self::getModule();

		if ($module === null) {
			return;
		}

		$config['teams'] = self::normalizeTeams($config['teams'] ?? []);
		$config['teams_enabled'] = (int) ($config['teams_enabled'] ?? 0);
		$config['username_suffix_enabled'] = (int) ($config['username_suffix_enabled'] ?? 0);

		$module->setConfig(array_replace(self::DEFAULT_CONFIG, $config));
	}

	public static function normalizeTeams($teams): array {
		if (is_string($teams)) {
			$teams = preg_split('/\r\n|\r|\n/', $teams);
		}

		if (!is_array($teams)) {
			return [];
		}

		$teams = array_map(static fn(string $team): string => trim($team), $teams);
		$teams = array_values(array_unique(array_filter($teams, static fn(string $team): bool => $team !== '')));

		return $teams;
	}

	public static function buildMaintenanceName(string $name_detail, string $username, array $config,
			?string $team = null): string {
		// Build the visible name from optional module features in the same order the form presents them.
		$parts = [];

		if ((int) $config['teams_enabled'] === 1 && $team !== null && $team !== '') {
			$parts[] = $team;
		}

		$parts[] = trim($name_detail);

		if ((int) $config['username_suffix_enabled'] === 1 && $username !== '') {
			$parts[] = $username;
		}

		return implode(' - ', $parts);
	}
}
