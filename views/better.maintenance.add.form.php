<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$form = (new CForm())
	->setId('better-maintenance-add-form')
	->setName('better_maintenance_add_form')
	->addStyle('display: none;')
	->addItem(getMessages())
	->addItem((new CVar(CSRF_TOKEN_NAME, $data['csrf_token']))->removeId());

foreach ($data['hostids'] as $hostid) {
	$form->addItem((new CVar('hostids[]', $hostid))->removeId());
}

$hosts_table = (new CTableInfo())
	->setHeader([_('Host'), _('note')]);

foreach ($data['hosts'] as $host) {
	$hosts_table->addRow([
		$host['name'],
		$host['inventory']['notes'] ?? ''
	]);
}

$make_maintenance_table = static function(array $maintenances, bool $show_active_since = false): CTableInfo {
	$maintenance_table = (new CTableInfo())
		->setHeader($show_active_since
			? ['', _('Name'), _('Type'), _('Active since'), _('Active till')]
			: ['', _('Name'), _('Type'), _('Active till')]
		);

	foreach ($maintenances as $maintenance) {
		// Use a radio per row because hosts can only be appended to one maintenance period at a time.
		$id = 'maintenanceid_'.$maintenance['maintenanceid'];
		$row = [
			(new CInput('radio', 'maintenanceid', $maintenance['maintenanceid']))
				->setId($id)
				->setAriaRequired(),
			new CLabel($maintenance['name'], $id),
			$maintenance['maintenance_type'] == MAINTENANCE_TYPE_NORMAL
				? _('With data collection')
				: _('No data collection')
		];

		if ($show_active_since) {
			$row[] = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $maintenance['active_since']);
		}

		$row[] = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $maintenance['active_till']);

		$maintenance_table->addRow($row);
	}

	return $maintenance_table;
};

$has_maintenances = (bool) ($data['maintenances'] || $data['approaching_maintenances']);

$form_grid = (new CFormGrid())
	->addItem([
		(new CLabel(_('Maintenance'), 'maintenanceid'))->setAsteriskMark(),
		new CFormField($data['maintenances']
			? (new CDiv($make_maintenance_table($data['maintenances'])))->addClass('better-maintenance-list')
			: (new CDiv(_('No active maintenance periods available.')))->addClass(ZBX_STYLE_GREY)
		)
	])
	->addItem([
		new CLabel(_('Approaching maintenance')),
		new CFormField($data['approaching_maintenances']
			? (new CDiv($make_maintenance_table($data['approaching_maintenances'], true)))->addClass(
				'better-maintenance-list'
			)
			: (new CDiv(_('No approaching maintenance periods available.')))->addClass(ZBX_STYLE_GREY)
		)
	])
	->addItem([
		new CLabel(_('Selected hosts')),
		new CFormField((new CDiv($hosts_table))->addClass('better-maintenance-hosts'))
	]);

$form
	->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN))
	->addItem($form_grid);

$output = [
	'header' => _('Add hosts to maintenance'),
	'body' => $form->toString(),
	'buttons' => [
		[
			'title' => _('Add'),
			'class' => 'js-add',
			'keepOpen' => true,
			'isSubmit' => true,
			'enabled' => $has_maintenances,
			'action' => 'better_maintenance_add_popup.submit();'
		]
	],
	'script_inline' => getPagePostJs().
		$this->readJsFile('better.maintenance.add.form.js.php')
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
