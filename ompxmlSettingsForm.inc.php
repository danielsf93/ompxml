<?php

/**
 * @file plugins/generic/ompxml/ompxmlSettingsForm.inc.php
 *

 */

import('lib.pkp.classes.form.Form');

class ompxmlSettingsForm extends Form {

	/** @var int */
	var $_contextId;

	/** @var object */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin ompxmlPlugin
	 * @param $contextId int
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
			'email' => $this->_plugin->getSetting($this->_contextId, 'email'),
			'username' => $this->_plugin->getSetting($this->_contextId, 'username'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('email', 'username'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request, $template, $display);
	}

	/**
	 * Save settings.
	 */
	function execute(...$functionArgs) {

		$email = $this->getData('email');
		$username = $this->getData('username');
 
		if (isset($email) && isset($username)) {
			// Atualize as configurações para 'email' e 'username'
			$this->_plugin->updateSetting($this->_contextId, 'email', $email, 'string');
			$this->_plugin->updateSetting($this->_contextId, 'username', $username, 'string');
		}
 
		parent::execute(...$functionArgs);
	}

}

?>
