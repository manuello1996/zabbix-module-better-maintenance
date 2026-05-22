<?php declare(strict_types = 0);

/**
 * @var CView $this
 * @var array $data
 */

$form = (new CForm())
	->setId('better-maintenance-form')
	->setName('better_maintenance_form')
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

$settings = $data['settings'];
$form_grid = new CFormGrid();

if ($settings['teams_enabled'] && $settings['teams']) {
	// Team selection is optional at module level, but required when the Teams feature is enabled.
	$team_list = (new CRadioButtonList('prefix', ''))
		->setModern(true)
		->setAriaRequired();

	foreach ($settings['teams'] as $team) {
		$team_list->addValue($team, $team);
	}

	$form_grid->addItem([
		(new CLabel(_('Team'), 'prefix'))->setAsteriskMark(),
		new CFormField($team_list)
	]);
}

$form_grid
	->addItem([
		(new CLabel(_('Name detail'), 'name_suffix'))->setAsteriskMark(),
		new CFormField(
			(new CTextBox('name_suffix', '', false, 80))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setAriaRequired()
		)
	])
	->addItem([
		new CLabel(_('Maintenance name')),
		new CFormField(
			$data['settings']['username_suffix_enabled']
				? _s('Username suffix will be appended automatically: %1$s', $data['username'])
				: _('The maintenance name will use only the entered values.')
		)
	])
	->addItem([
		(new CLabel(_('Maintenance type'), 'maintenance_type')),
		new CFormField(
			(new CRadioButtonList('maintenance_type', MAINTENANCE_TYPE_NORMAL))
				->addValue(_('With data collection'), MAINTENANCE_TYPE_NORMAL)
				->addValue(_('No data collection'), MAINTENANCE_TYPE_NODATA)
				->setModern()
		)
	])
	->addItem([
		(new CLabel(_('Start'), 'start_mode'))->setAsteriskMark(),
		new CFormField(
			(new CRadioButtonList('start_mode', 'now'))
				->addValue(_('Now'), 'now')
				->addValue(_('Date/time'), 'custom')
				->setModern()
				->setAriaRequired()
		)
	])
	->addItem([
		(new CLabel(_('Active since'), 'active_since'))->addClass('js-custom-start'),
		(new CFormField(
			(new CDateSelector('active_since', date(ZBX_DATE_TIME, time())))
				->setDateFormat(ZBX_DATE_TIME)
				->setPlaceholder(_('YYYY-MM-DD hh:mm'))
		))->addClass('js-custom-start')
	])
	->addItem([
		new CLabel(_('Duration'), 'duration_mode'),
		new CFormField(
			(new CRadioButtonList('duration_mode', '15m'))
				->addValue('15m', '15m')
				->addValue('30m', '30m')
				->addValue('60m', '60m')
				->addValue(_('Custom'), 'custom')
				->setModern(true)
		)
	])
	->addItem([
		(new CLabel(_('Custom duration'), 'duration_custom'))->addClass('js-custom-duration'),
		(new CFormField(
			(new CTextBox('duration_custom', '', false, 32))
				->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
				->setAttribute('placeholder', '12h')
		))->addClass('js-custom-duration')
	])
	->addItem([
		new CLabel(_('Description'), 'description'),
		new CFormField(
			(new CTextArea('description', ''))
				->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
				->setMaxlength(DB::getFieldLength('maintenances', 'description'))
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
	'header' => _('Create maintenance'),
	'body' => $form->toString(),
	'buttons' => [
		[
			'title' => _('Create'),
			'class' => 'js-create',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'better_maintenance_popup.submit();'
		]
	],
	'script_inline' => getPagePostJs().
		$this->readJsFile('better.maintenance.form.js.php')
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
