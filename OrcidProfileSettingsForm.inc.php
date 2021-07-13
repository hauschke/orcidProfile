<?php

/**
 * @file OrcidProfileSettingsForm.inc.php
 *
 * Copyright (c) 2015-2019 University of Pittsburgh
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OrcidProfileSettingsForm
 * @ingroup plugins_generic_orcidProfile
 *
 * @brief Form for site admins to modify ORCID Profile plugin settings
 */


import('lib.pkp.classes.form.Form');

class OrcidProfileSettingsForm extends Form {

	const CONFIG_VARS = array(
		'orcidProfileAPIPath' => 'string',
		'orcidClientId' => 'string',
		'orcidClientSecret' => 'string',
		'sendMailToAuthorsOnPublication' => 'bool',
		'logLevel' => 'string',
		'isSandBox' => 'bool'
	);
	/** @var $contextId int */
	var $contextId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $contextId int
	 */
	function __construct(&$plugin, $contextId) {
		$this->contextId = $contextId;
		$this->plugin =& $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		if (!$this->plugin->isGloballyConfigured()) {
			$this->addCheck(new FormValidator($this, 'orcidProfileAPIPath', 'required',
				'plugins.generic.orcidProfile.manager.settings.orcidAPIPathRequired'));
		}
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidatorCustom($this, 'orcidClientId', 'required', 'plugins.generic.orcidProfile.manager.settings.orcidClientId.error', function ($clientId) {
			return $this->_validateClientId($clientId);

		}));
		$this->addCheck(new FormValidatorCustom($this, 'orcidClientSecret', 'required', 'plugins.generic.orcidProfile.manager.settings.orcidClientSecret.error', function ($clientSecret) {
			return $this->validateClientSecret($clientSecret);

		}));


	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->contextId;
		$plugin =& $this->plugin;
		$this->_data = array();
		foreach (self::CONFIG_VARS as $configVar => $type) {
			$this->_data[$configVar] = $plugin->getSetting($contextId, $configVar);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys(self::CONFIG_VARS));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('globallyConfigured', $this->plugin->isGloballyConfigured());
		$templateMgr->assign('pluginName', $this->plugin->getName());
		$templateMgr->assign('prerequisitesMissing', $this->_checkPrerequisites());
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$plugin =& $this->plugin;
		$contextId = $this->contextId;
		foreach (self::CONFIG_VARS as $configVar => $type) {
			if ($configVar === 'orcidProfileAPIPath') {
				$plugin->updateSetting($contextId, $configVar, trim($this->getData($configVar), "\"\';"), $type);
			} else {
				$plugin->updateSetting($contextId, $configVar, $this->getData($configVar), $type);
			}
		}
		if (strpos($this->getData("orcidProfileAPIPath"), "sandbox.orcid.org") == true) {
			$plugin->updateSetting($contextId, "isSandBox", true, "bool");
		}

		parent::execute(...$functionArgs);
	}
	public function _checkPrerequisites() {
		$messages = array();

		$clientId = $this->plugin->getSetting($this->_contextId, 'orcidClientId');
		if (!$this->_validateClientId($clientId)) {
			$messages[] = __('plugins.generic.orcidProfile.manager.settings.orcidClientId.error');
		}
		return $messages;
	}

	/**
	 * @param $str
	 * @return bool
	 */
	private function _validateClientId($str): bool {
		if (preg_match('/^APP-[\da-zA-Z]{16}|(\d{4}-){3,}\d{3}[\dX]/', $str) == 1) {
			$this->plugin->setEnabled(true);
			return true;
		} else {
			$this->plugin->setEnabled(false);
			return false;
		}
	}

	/**
	 * @param $str
	 * @return bool
	 */
	private function validateClientSecret($str): bool {
		if (preg_match('/^(\d|-|[a-f]){36,64}/', $str) == 1) {
			$this->plugin->setEnabled(true);
			return true;
		} else {
			$this->plugin->setEnabled(false);
			return false;
		}
	}
}

