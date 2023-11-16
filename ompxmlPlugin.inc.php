<?php

/**
 * @file plugins/generic/ompxml/ompxmlPlugin.inc.php
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ompxmlPlugin extends GenericPlugin {
	
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {

			}
		return $success;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('OMPXML');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('tentativa');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('ompxmlSettingsForm');
				$form = new ompxmlSettingsForm($this, $context->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	


}
?>
