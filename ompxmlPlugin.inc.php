<?php

/**
 * @file plugins/generic/ompxml/ompxmlPlugin.inc.php
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.plugins.ImportExportPlugin');


class ompxmlPlugin extends GenericPlugin {
	
	public function __construct()
    {
        parent::__construct();
    }

	public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return $success;
        }
        if ($success && $this->getEnabled()) {
            $this->addLocaleData();
            $this->import('ompxmlDeployment');
        }

        return $success;
    }

	public function getName()
    {
        return 'ompxmlplugin';
    }

	 //forma o prefixo do arquivo .xml
	 public function getPluginSettingsPrefix()
	 {
		 return 'exml';
	 }

	 /**
     * FUNÇÃO PRINCIPAL, RESPOSÁVEL PELA ESTRUTURA DO ARQUIVO XML.
     */
    public function exportSubmissions($submissionIds, $context, $user, $request)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
        $submissions = [];
        $app = new Application();
        $request = $app->getRequest();
        $press = $request->getContext();

        /********************************************		FOREACH'S	********************************************/
        foreach ($submissionIds as $submissionId) {
            $submission = $submissionDao->getById($submissionId, $context->getId());
            if ($submission) {
                $submissions[] = $submission;
            }
        }
        $authorsInfo = [];
        $authors = $submission->getAuthors();

        foreach ($authors as $author) {
            $authorInfo = [
        'givenName' => $author->getLocalizedGivenName(),
        'surname' => $author->getLocalizedFamilyName(),
        'afiliation' => $author->getLocalizedAffiliation(),
        'orcid' => $author->getOrcid(),
    ];
            $authorsInfo[] = $authorInfo;
        }

        foreach ($submissions as $submission) {
            // Obtendo o título da submissão
            $submissionTitle = $submission->getLocalizedFullTitle();
            //obtendo o tipo de conteudo, capitulo e monografia. crossref só aceita "edited_book, monograph, reference, other" porém ao iniciar uma nova publicação, só há entrada para 'monograph' e 'other'
            $types = [1 => 'other', 2 => 'monograph', 3 => 'other', 4 => 'other'];
            $type = $submission->getWorkType();

            $abstract = $submission->getLocalizedAbstract();
            $doi = $submission->getStoredPubId('doi');
            $publicationUrl = $request->url($context->getPath(), 'catalog', 'book', [$submission->getId()]);
            $copyright = $submission->getLocalizedcopyrightHolder();
            // aqui retorna ano mes dia $publicationYear = $submission->getDatePublished();
            $publicationDate = $submission->getDatePublished();
            $publicationYear = date('Y', strtotime($publicationDate));
            $publicationMonth = date('m', strtotime($publicationDate));
            $publicationDay = date('d', strtotime($publicationDate));
            //timestamp
            $timestamp = date('YmdHis').substr((string) microtime(), 2, 3);

            // aqui retorna xx_XX (pt-BR ou en_US etc) sendo o idioma em que a publicação foi submetida
            $submissionLanguage = substr($submission->getLocale(), 0, 2); //aqui retorna xx
            $publisherName = $press->getData('publisher');
            $registrant = $press->getLocalizedName();

            // Obtendo dados dos autores
            $authorNames = [];
            $authors = $submission->getAuthors();
            foreach ($authors as $author) {
                $givenName = $author->getLocalizedGivenName();
                $surname = $author->getLocalizedFamilyName();
                $afiliation = $author->getLocalizedAffiliation();
                $authorNames[] = $givenName.' '.$surname;
            }
            $authorName = implode(', ', $authorNames);
            $orcid = $author->getOrcid();

            $isbn = '';
            $publicationFormats = $submission->getCurrentPublication()->getData('publicationFormats');
            foreach ($publicationFormats as $publicationFormat) {
                $identificationCodes = $publicationFormat->getIdentificationCodes();
                while ($identificationCode = $identificationCodes->next()) {
                    if ($identificationCode->getCode() == '02' || $identificationCode->getCode() == '15') {
                        // 02 e 15: códigos ONIX para ISBN-10 ou ISBN-13
                        $isbn = $identificationCode->getValue();
                        break; // Encerra o loop ao encontrar o ISBN
                    }
                }
            }

            /*
             *
             * ESTRUTURA XML
             *
             * */

            //---início estrutura xml codigos obrigatórios
            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
		<doi_batch version="4.4.2" xmlns="http://www.crossref.org/schema/4.4.2" 
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
		xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1" 
		xsi:schemaLocation="http://www.crossref.org/schema/4.4.2 http://www.crossref.org/schema/deposit/crossref4.4.2.xsd">';

            //$xmlContent .= '<TESTE>'.$xablau.'</TESTE>';//tag para testes

            $xmlContent .= '<head>';
            //segundo documentação, doi_batch_id pode ser o proprio nome da publicação: https://www.crossref.org/documentation/register-maintain-records/verify-your-registration/submission-queue-and-log/
            $xmlContent .= '<doi_batch_id>'.htmlspecialchars($submissionTitle).'</doi_batch_id>';
            $xmlContent .= '<timestamp>'.$timestamp.'</timestamp>';
            $xmlContent .= '<depositor>';
            //por hora em hardcoding - buscando solução para obter info via form de depositor e email
            $xmlContent .= '<depositor_name>sibi:sibi</depositor_name> ';
            $xmlContent .= '<email_address>dgcd@abcd.usp.br</email_address>';
            $xmlContent .= '</depositor>';
            $xmlContent .= '<registrant>WEB-FORM</registrant>';
            $xmlContent .= '</head>';

            $xmlContent .= '<body>';
            $xmlContent .= '<book book_type="'.htmlspecialchars($types[$type]).'">';
            $xmlContent .= '<book_metadata>';

            $xmlContent .= '<contributors>';

            //AUTORES:
            // Primeiro autor - obrigatório
            $firstAuthor = reset($authorsInfo);
            if (!empty($authorInfo['afiliation'])) {
                $xmlContent .= '<organization sequence="additional" contributor_role="author">'.htmlspecialchars($authorInfo['afiliation']).'</organization>';
            }
            $xmlContent .= '<person_name sequence="first" contributor_role="author">';
            $xmlContent .= '<given_name>'.htmlspecialchars($firstAuthor['givenName']).'</given_name>';
            $xmlContent .= '<surname>'.htmlspecialchars($firstAuthor['surname']).'</surname>';
            if (!empty($authorInfo['orcid'])) {
                $xmlContent .= '<ORCID>'.htmlspecialchars($authorInfo['orcid']).'</ORCID>';
            }
            $xmlContent .= '</person_name>';
            // Autores adicionais
            foreach ($authorsInfo as $index => $authorInfo) {
                if ($index > 0) {
                    $xmlContent .= '<person_name sequence="additional" contributor_role="author">';
                    $xmlContent .= '<given_name>'.htmlspecialchars($authorInfo['givenName']).'</given_name>';
                    $xmlContent .= '<surname>'.htmlspecialchars($authorInfo['surname']).'</surname>';
                    if (!empty($authorInfo['orcid'])) {
                        $xmlContent .= '<ORCID>'.htmlspecialchars($authorInfo['orcid']).'</ORCID>';
                    }
                    $xmlContent .= '</person_name>';
                    if (!empty($authorInfo['afiliation'])) {
                        $xmlContent .= '<organization sequence="additional" contributor_role="author">'.htmlspecialchars($authorInfo['afiliation']).'</organization>';
                    }
                }
            }
            $xmlContent .= '</contributors>';

            //dados do livro
            $xmlContent .= '<titles>';
            $xmlContent .= '<title>'.htmlspecialchars($submissionTitle).'</title>';
            $xmlContent .= '</titles>';
            $xmlContent .= '<jats:abstract xml:lang="'.htmlspecialchars($submissionLanguage).'">';
            $xmlContent .= '<jats:p>'.htmlspecialchars($abstract).'</jats:p>';
            $xmlContent .= '</jats:abstract>';
            $xmlContent .= '<publication_date media_type="online">';
            $xmlContent .= '<month>'.htmlspecialchars($publicationMonth).'</month>';
            $xmlContent .= '<day>'.htmlspecialchars($publicationDay).'</day>';
            $xmlContent .= '<year>'.htmlspecialchars($publicationYear).'</year>';
            $xmlContent .= '</publication_date>';

            $xmlContent .= '<isbn>'.htmlspecialchars($isbn).'</isbn>';

            $xmlContent .= '<publisher>';
            //como no modelo, publisher é o detentor do copyright
            $xmlContent .= '<publisher_name>'.htmlspecialchars($copyright).'</publisher_name>';
            $xmlContent .= '</publisher>';
            $xmlContent .= '<doi_data>';
            $xmlContent .= '<doi>'.htmlspecialchars($doi).'</doi>';
            $xmlContent .= '<resource>'.htmlspecialchars($publicationUrl).'</resource>';
            $xmlContent .= '</doi_data>';
            $xmlContent .= '</book_metadata>';
            $xmlContent .= '</book>';
            $xmlContent .= '</body>';
            $xmlContent .= '</doi_batch>';
        }

        return $xmlContent;
    }

    /**
     * Final estrutura XML.
     */

    /**
     * @copydoc ImportExportPlugin::executeCLI
     */
    public function executeCLI($scriptName, &$args)
    {
        $opts = $this->parseOpts($args, ['no-embed', 'use-file-urls']);
        $command = array_shift($args);
        $xmlFile = array_shift($args);
        $pressPath = array_shift($args);

        AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);
        $pressDao = DAORegistry::getDAO('PressDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $press = $pressDao->getByPath($pressPath);

        if (!$press) {
            if ($pressPath != '') {
                echo __('plugins.importexport.common.cliError')."\n";
                echo __('plugins.importexport.common.error.unknownPress', ['pressPath' => $pressPath])."\n\n";
            }
            $this->usage($scriptName);

            return;
        }

        if ($xmlFile && $this->isRelativePath($xmlFile)) {
            $xmlFile = PWD.'/'.$xmlFile;
        }

        switch ($command) {
            case 'export':
                $outputDir = dirname($xmlFile);
                if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
                    echo __('plugins.importexport.common.cliError')."\n";
                    echo __('plugins.importexport.common.export.error.outputFileNotWritable', ['param' => $xmlFile])."\n\n";
                    $this->usage($scriptName);

                    return;
                }

                if ($xmlFile != '') {
                    switch (array_shift($args)) {
                        case 'monograph':
                        case 'monographs':
                            $selectedSubmissions = array_slice($args, 1);
                            $xmlContent = $this->exportSubmissions($selectedSubmissions);
                            file_put_contents($xmlFile, $xmlContent);

                            return;
                    }
                }
                break;
        }
        $this->usage($scriptName);
    }

    /**
     * @copydoc ImportExportPlugin::usage
     */
    public function usage($scriptName)
    {
        fatalError('Not implemented.');
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

	public function display($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();

        parent::display($args, $request);

        $templateMgr->assign('plugin', $this);

        switch (array_shift($args)) {
            //aqui monta a página do plugin
            case 'settingsForm':
            case '':
                $apiUrl = $request->getDispatcher()->url($request, ROUTE_API, $context->getPath(), 'submissions');
                $submissionsListPanel = new \APP\components\listPanels\SubmissionsListPanel(
                    'submissions',
                    __('common.publications'),
                    [
                        'apiUrl' => $apiUrl,
                        'count' => 100,
                        'getParams' => new stdClass(),
                        'lazyLoad' => true,
                    ]
                );
                $submissionsConfig = $submissionsListPanel->getConfig();
                $submissionsConfig['addUrl'] = '';
                $submissionsConfig['filters'] = array_slice($submissionsConfig['filters'], 1);
                $templateMgr->setState([
                    'components' => [
                        'submissions' => $submissionsConfig,
                    ],
                ]);
                $templateMgr->assign([
                    'pageComponent' => 'ImportExportPage',
                ]);
                $templateMgr->display($this->getTemplateResource('settingsForm.tpl'));
                break;
                //aqui exporta o livro
                case 'export':
                    $exportXml = $this->exportSubmissions(
                        (array) $request->getUserVar('selectedSubmissions'),
                        $request->getContext(),
                        $request->getUser(),
                        $request
                    );
                    import('lib.pkp.classes.file.FileManager');
                    $fileManager = new FileManager();
                    //'monographs' aparece no nome do arquivo .xml
                    $exportFileName = $this->getExportFileName($this->getExportPath(), 'monographs', $context, '.xml');
                    $fileManager->writeFile($exportFileName, $exportXml);
                    $fileManager->downloadByPath($exportFileName);
                    $fileManager->deleteByPath($exportFileName);
                    break;
//Parte responsávle pelo form
                    case 'settings':
                        $this->getSettings($templateMgr);
                        $this->updateSettings();
                        $request->redirect(null, 'management', 'importexport', ['plugin', 'exml']);
                        // no break
            default:
                $dispatcher = $request->getDispatcher();
                $dispatcher->handle404();
        }
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
