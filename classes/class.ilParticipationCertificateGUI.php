<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\Data\Factory;

/**
 * Class ilParticipationCertificateGUI
 *
 * @ilCtrl_isCalledBy ilParticipationCertificateGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilParticipationCertificateGUI: ilParticipationCertificateResultGUI
 */
class ilParticipationCertificateGUI
{
    public const CMD_SAVE = 'save';

    public const CMD_CANCEL = 'cancel';

    public const CMD_LOOP = 'loop';

    public const CMD_CONFIG = 'config';

    public const CMD_CONFIG_RESULT_TABLE = 'configResultTable';

    public const CMD_RESULT_TABLE_CONFIG = 'saveResultTableConfig';

    public const CMD_SELF_PRINT = 'selfPrint';

    public const CMD_SELF_PRINT_SAVE = 'saveSelfPrint';

    public const CMD_DISPLAY = 'display';

    public const CMD_SET_CERT_TEMPLATE = 'setCertTemplate';

    public const CMD_SET_OWN_CERT_TEXT_FROM_TEMPLATE = 'setOwnCertTextFromTemplate';

    public const TAB_CONFIG = 'config';

    public const TAB_CONFIG_DISPLAY = 'config_display';

    public const TAB_CONFIG_RESULT_TABLE = 'config_result_table';

    public const TAB_CONFIG_SELF_PRINT = 'config_self_print';

    private string $objecttype;

    private ?ilObject $learnGroup;

    public ilTemplate|ilGlobalTemplateInterface $tpl;

    public ilCtrl|ilCtrlInterface $ctrl;

    public ilTabsGUI $tabs;

    public ilGroupParticipants $learnGroupParticipants;

    public ilObjGroup $learningGroup;

    public ilParticipationCertificateConfig $object;

    public ilToolbarGUI $toolbar;

    public int $groupRefId;

    protected ilParticipationCertificatePlugin $pl;

    protected ilLanguage $lng;

    private $logger;
    /**
     *
     * @throws ilCtrlException
     */
    public function __construct()
    {
        global $DIC;

        $this->toolbar = $DIC->toolbar();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        //$this->objectDefinition = $DIC["objDefinition"];
        $this->groupRefId = (int) $_GET['ref_id'];
        $this->lng = $DIC->language();
        $this->logger = $DIC->logger()->root();

        //Access
        $cert_access = new ilParticipationCertificateAccess($this->groupRefId);
        if (!$cert_access->hasCurrentUserAdminAccess()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('no_permission'), true);
            $DIC->ctrl()->redirectToURL('login.php');
        }
        $this->objecttype = ilObject::_lookupType($this->groupRefId, true);
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->learnGroup = ilObjectFactory::getInstanceByRefId($this->groupRefId);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, ['ref_id', 'group_id']);
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd();
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(ilParticipationCertificateResultGUI::class):
                $ilParticipationCertificateTableGUI = new ilParticipationCertificateResultGUI();
                $this->ctrl->forwardCommand($ilParticipationCertificateTableGUI);
                break;
            default:
                switch ($cmd) {
                    case self::CMD_CONFIG:
                    case self::CMD_CONFIG_RESULT_TABLE:
                    case self::CMD_DISPLAY:
                    case self::CMD_SET_CERT_TEMPLATE:
                    case self::CMD_SET_OWN_CERT_TEXT_FROM_TEMPLATE:
                    case self::CMD_SAVE:
                    case self::CMD_RESULT_TABLE_CONFIG:
                    case self::CMD_SELF_PRINT:
                    case self::CMD_SELF_PRINT_SAVE:
                        $this->{$cmd}();
                        break;
                    default:
                        $this->{$cmd}();
                        break;
                }
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function config(): void
    {
        $this->configResultTable();
    }

    public function initHeader(): void
    {
        $this->tpl->setTitle($this->learnGroup->getTitle());
        $this->tpl->setDescription($this->learnGroup->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($this->learnGroup->getId()));

        $this->ctrl->saveParameterByClass(ilRepositoryGUI::class, 'ref_id');

        $this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', $this->groupRefId);
        $this->tabs->setBackTarget($this->pl->txt('header_btn_back'), $this->ctrl->getLinkTargetByClass(array(
            ilRepositoryGUI::class//,
            //ilObjGroupGUI::class
        )));


        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');
        $this->tabs->addTab(
            ilParticipationCertificateResultGUI::CMD_OVERVIEW,
            $this->pl->txt('header_overview'),
            $this->ctrl->getLinkTargetByClass(
                [ilUIPluginRouterGUI::class, ilParticipationCertificateResultGUI::class],
                ilParticipationCertificateResultGUI::CMD_CONTENT
            )
        );

        //$this->tabs->addTab(ilParticipationCertificateResultGUI::CMD_OVERVIEW,$this->pl->txt('header_overview'),$this->ctrl->getLinkTargetByClass(ilParticipationCertificateResultGUI::class,ilParticipationCertificateResultGUI::CMD_CONTENT));
        $this->tabs->addTab(self::TAB_CONFIG, $this->pl->txt('header_config'), $this->ctrl->getLinkTargetByClass(self::class, self::CMD_CONFIG));
        $this->tabs->activateTab(self::TAB_CONFIG);
    }

    /**
     * @throws ilCtrlException
     */
    protected function initConfTabs(): void
    {
        $this->tabs->addSubTab(self::TAB_CONFIG_RESULT_TABLE, $this->pl->txt('config_result_table'), $this->ctrl->getLinkTarget($this, self::CMD_CONFIG_RESULT_TABLE));
        $this->tabs->addSubTab(self::TAB_CONFIG_SELF_PRINT, $this->pl->txt('period_self_print'), $this->ctrl->getLinkTarget($this, self::CMD_SELF_PRINT));
        $this->tabs->addSubTab(self::TAB_CONFIG_DISPLAY, $this->pl->txt('plugin'), $this->ctrl->getLinkTarget($this, self::CMD_DISPLAY));
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    protected function display(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        if (method_exists($this->tpl, 'loadStandardTemplate')) {
            $this->tpl->loadStandardTemplate();
        } else {
            $this->tpl->getStandardTemplate();
        }
        $this->initHeader();

        $this->initConfTabs();
        $this->tabs->activateSubTab(self::TAB_CONFIG_DISPLAY);

        $form = $this->initForm();

        $this->tpl->setContent($renderer->render($form));
        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();
        } else {
            $this->tpl->show();
        }
    }

    /**
     * @param int $globalConfigId
     * @return void
     * @throws ilCtrlException
     */
    private function initToolbar(?int $globalConfigId = null): void
    {
        global $DIC;
        $ui = $DIC->ui()->factory();

        $this->toolbar->setFormAction(
            $this->ctrl->getFormAction($this, self::CMD_CONFIG)
        );

        $cert_global_configs = new ilParticipationCertificateGlobalConfigSets();
        $options_template = $cert_global_configs->getSelectOptions();


        $select = $ui->input()->field()
                              ->select('', $options_template)
                              ->withValue($globalConfigId ?? 0);

        $this->toolbar->addComponent($select);

        $button_fixed_form = $ui->button()->standard(
            $this->pl->txt('btn_reset'),
            $DIC->ctrl()->getLinkTarget($this, self::CMD_SET_CERT_TEMPLATE) . '&template_id=__TEMPLATE__'
        )->withOnLoadCode(function ($id) {
            return <<<JS
                const select = $('#ilToolbar select'); 
                const btn = $('#$id');
                
                btn.click(function(e) {
                    e.preventDefault();
                  
                    const selected = select ? select.val() : null;
                    const baseUrl = btn.data('action');
                    const url = baseUrl.replace('__TEMPLATE__', encodeURIComponent(selected));
                    
                    btn.closest('form').attr('action', url);
                    btn.closest('form').submit();
                });
        JS;
        });
        ;


        $button_editable_form = $ui->button()->standard(
            $this->pl->txt('btn_modify'),
            $DIC->ctrl()->getLinkTarget($this, self::CMD_SET_OWN_CERT_TEXT_FROM_TEMPLATE) . '&template_id=__TEMPLATE__'
        )->withOnLoadCode(function ($id) {
            return <<<JS
                const select = $('#ilToolbar select'); 
                const btn = $('#$id');
                
                btn.click(function(e) {
                    e.preventDefault();
                  
                    const selected = select ? select.val() : null;
                    const baseUrl = btn.data('action');
                    const url = baseUrl.replace('__TEMPLATE__', encodeURIComponent(selected));
                   
                    btn.closest('form').attr('action', url);
                    btn.closest('form').submit();
            });
        JS;
        });


        $this->toolbar->addComponent($button_fixed_form);
        $this->toolbar->addComponent($button_editable_form);
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     */
    public function initForm(): Standard
    {
        global $DIC;
        $ui = $DIC->ui()->factory();

        $inputFields = [];

        $cert_configs = new ilParticipationCertificateConfigs();
        $arr_config = $cert_configs->getObjConfigSetIfNoneCreateDefaultAndCreateNewObjConfigValues($this->groupRefId);

        $global_config_sets = new ilParticipationCertificateGlobalConfigSets();
        $global_config_id = null;
        if (count($arr_config) > 0) {
            $global_config_id = reset($arr_config)->getGlobalConfigId();
        }

        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');


        $this->initToolbar($global_config_id);

        if (!empty($global_config_id) && $global_config_id > 0) {
            $global_config_set = $global_config_sets->getConfigSetById($global_config_id);
            $this->tpl->setOnScreenMessage('info', $this->pl->txt('configset_type_1') . ' ' . $global_config_set->getTitle(), true);
        } else {
            $this->tpl->setOnScreenMessage('info', $this->pl->txt('configset_type_2'), true);
        }

        foreach ($arr_config as $config) {
            $disabled = false;
            if ($config->getConfigType() == ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE) {
                $disabled = true;
            }

            $key = $config->getConfigKey();
            switch ($key) {
                case 'logo':
                    if ($disabled) {
                        $configId = $global_config_id;
                    } else {
                        $configId = $this->groupRefId;
                    }

                    $file = new ilParticipationCertificateFiles();
                    $src = $file->getFileSrcByStorageType(
                        $config->getConfigValue(),
                        $configId,
                        'logo'
                    );

                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->file(
                        new ilParticipationCertificateFileUploadHandlerGUI(),
                        $this->pl->txt('logo'),
                        'Maximum upload size: 1024.0 MB. Allowed file types: .png' . "<br>\n" .
                        '<img src="' . $src . '">'
                    )->withAcceptedMimeTypes([
                        'image/jpeg',
                        'image/png'
                    ])->withMaxFileSize((2 * 1024 * 1024));
                    break;
                case 'page1_issuer_signature':

                    if ($disabled) {
                        $configId = $global_config_id;
                    } else {
                        $configId = $this->groupRefId;
                    }

                    $file = new ilParticipationCertificateFiles();
                    $src = $file->getFileSrcByStorageType(
                        $config->getConfigValue(),
                        $configId,
                        'page1_issuer_signature'
                    );

                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->file(
                        new ilParticipationCertificateFileUploadHandlerGUI(),
                        $this->pl->txt('page1_issuer_signature'),
                        'Maximum upload size: 1024.0 MB. Allowed file types: .png' . "<br>\n" .
                        '<img src="' . $src . '">'
                    )->withAcceptedMimeTypes([
                        'image/png'
                    ])->withMaxFileSize((2 * 1024 * 1024));
                    break;

                default:
                    $configValue = $config->getConfigValue();

                    $configValue = $this->replacePlaceholdersFromOldVersion($configValue);
                    if ($disabled) {
                        $inputFields[$config->getConfigKey()] = $ui->input()->field()->textarea(
                            $config->getConfigKey()
                        )->withValue($configValue ?? '')
                         ->withDisabled(true);
                    } else {
                        $inputFields[$config->getConfigKey()] = $ui->input()->field()->textarea(
                            $config->getConfigKey()
                        )->withValue($configValue ?? '');
                    }
                    break;
            }
        }

        $section = $ui->input()->field()->section(
            $inputFields,
            $this->pl->txt('config_plugin'),
            $this->pl->txt("placeholders") . ' <br>
		[[username]]: Anrede Vorname Nachname <br>
		[[date]]: Datum
		'
        );

        $formAction = $DIC->ctrl()->getFormActionByClass(
            self::class,
            self::CMD_SAVE
        );

        $form = $ui->input()->container()->form()->standard(
            $formAction,
            ['config' => $section]
        );

        if ($this->objecttype === 'grp') {
            $this->ctrl->saveParameterByClass(ilObjGroup::class, 'ref_id');
        } else {
            $this->ctrl->saveParameterByClass(ilObjCourse::class, 'ref_id');
        }
        return $form;
    }

    /**
     * @param string $configValue
     * @return array|string|string[]
     */
    private function replacePlaceholdersFromOldVersion(string $configValue)
    {
        $configValue = str_replace('{{', '[[', $configValue);
        return str_replace('}}', ']]', $configValue);
    }

    /**
     * @throws ilCtrlException|arException|ilTemplateException
     */
    public function save()
    {
        global $DIC;

        $this->logger->debug('Start saving participation certificate');

        $form = $this->initForm();
        $form = $form->withRequest($DIC->http()->request());
        $form_data = $form->getData()['config'];

        if ($form->getError()) {
            $this->logger->debug('Form validation error');

            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $form->getError());
            $this->selfPrint();
            return false;
        }

        foreach ($form_data as $key => $item) {
            $file = null;
            /**
             * @var ilParticipationCertificateConfig $config
             */
            $config = ilParticipationCertificateConfig::where(array(
                'config_key' => $key,
                'group_ref_id' => $this->groupRefId,
                'config_value_type' => ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT
            ))->first();

            if (!is_object($config)) {
                $config = new ilParticipationCertificateConfig();
                $config->setGroupRefId($this->groupRefId);
                $config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GROUP);
                $config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_CERT_TEXT);
                $config->setConfigKey($key);
                $config->setConfigValue("");
            }

            $input = $item;

            switch ($key) {
                case 'page1_issuer_signature':
                    $input = end($input);
                    if (!empty($input)) {
                        $file = 'page1_issuer_signature';
                    } else {
                        $input = $config->getConfigValue();
                    }
                    break;
                case 'logo':
                    $input = end($input);

                    if (!empty($input)) {
                        $file = 'logo';
                    } else {
                        $input = $config->getConfigValue();
                    }
                    break;
                default:

                    break;
            }

            try {
                $config->setConfigValue($input);
                $config->store();

                $this->logger->debug('Save key: ' . $key);

                if ($file === 'logo' || $file === 'page1_issuer_signature') {
                    ilParticipationCertificateFiles::setFile(
                        $this->groupRefId,
                        $file,
                        true
                    );

                    $this->logger->debug('Save ' . $key . ' in table: dhbw_part_cert_files');
                }
            } catch(\Throwable $e) {
                if (!empty($file)) {
                    $this->logger->debug("Error uploading file '{$file}': " . $e->getMessage());
                } else {
                    $this->logger->debug("Error on saving '{$key}': " . $e->getMessage());
                }
                $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $this->pl->txt('failure_save_config'), true);
                $this->ctrl->redirect($this, self::CMD_DISPLAY);
            }
        }

        $this->logger->debug('End saving participation certificate');

        $this->tpl->setOnScreenMessage('success', $this->pl->txt('successFormSave'), true);
        $this->ctrl->redirect($this, self::CMD_DISPLAY);

        return true;
    }

    /**
     * @throws ilCtrlException
     */
    public function setCertTemplate(): void
    {
        $globalTemplateId = (int) $_GET['template_id'];

        $cert_configs = new ilParticipationCertificateConfigs();
        if ($globalTemplateId != 0) {
            $cert_configs->setObjToUseCertTemplate($this->groupRefId, $globalTemplateId);
            $this->tpl->setOnScreenMessage('success', $this->pl->txt('successForm'), true);

        }
        $this->ctrl->redirect($this, self::CMD_DISPLAY);
    }

    /**
     * @throws ilCtrlException
     */
    public function setOwnCertTextFromTemplate(): void
    {
        $globalTemplateId = (int) $_GET['template_id'];

        if ($globalTemplateId != 0) {
            $cert_configs = new ilParticipationCertificateConfigs();
            $cert_configs->setOwnCertConfigFromTemplate($this->groupRefId, $globalTemplateId);
            $this->tpl->setOnScreenMessage('success', $this->pl->txt('successForm'), true);
        }
        $this->ctrl->redirect($this, self::CMD_DISPLAY);
    }

    /**
     *
     * @throws ilCtrlException|ilTemplateException
     */
    public function configResultTable(): void
    {
        global $DIC;

        if (method_exists($this->tpl, 'loadStandardTemplate')) {
            $this->tpl->loadStandardTemplate();
        } else {
            $this->tpl->getStandardTemplate();
        }
        $this->initHeader();

        $this->initConfTabs();
        $this->tabs->activateSubTab(self::TAB_CONFIG_RESULT_TABLE);

        $form = $this->initConfigResultTableForm();

        $renderer = $DIC->ui()->renderer();
        $this->tpl->setContent($renderer->render($form));

        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();
        } else {
            $this->tpl->show();
        }
    }

    /**
     * @return Standard
     * @throws ilCtrlException
     */
    protected function initConfigResultTableForm(): Standard
    {
        global $DIC;

        $ui = $DIC->ui()->factory();

        $periodStart = ilParticipationCertificateConfig::getConfig('period_start', $this->groupRefId);
        $startDate = !empty($periodStart) ? DateTimeImmutable::createFromFormat('d.m.Y', $periodStart) : null;

        $periodEnd = ilParticipationCertificateConfig::getConfig('period_end', $this->groupRefId);
        $endDate = !empty($periodEnd) ? DateTimeImmutable::createFromFormat('d.m.Y', $periodEnd) : null;

        $durationInput = $ui->input()->field()->duration($this->pl->txt('period'));
        $user = $DIC->user();
        if (!empty($startDate) && !empty($endDate)) {
            $period = $durationInput
                ->withTimezone($user->getTimeZone())
                ->withUseTime(false)
                ->withLabels($this->pl->txt('start'), $this->pl->txt('end'))
                ->withFormat($user->getDateFormat())
                ->withValue([$startDate, $endDate]);
        } else {
            $period = $durationInput
                ->withTimezone($user->getTimeZone())
                ->withUseTime(false)
                ->withLabels($this->pl->txt('start'), $this->pl->txt('end'))
                ->withFormat($user->getDateFormat());
        }

        $inputFields['period'] = $period;

        $calculationType = ilParticipationCertificateConfig::getConfig(
            'calculation_type_processing_state_suggested_objectives',
            $this->groupRefId
        ) ? ilParticipationCertificateConfig::getConfig(
            'calculation_type_processing_state_suggested_objectives',
            $this->groupRefId
        ) : ilLearnObjectSuggResult::CALC_TYPE_BY_POINTS;

        $radio = $ui->input()->field()->radio($this->pl->txt('calculation_type_processing_state_suggested_objectives'))
                    ->withOption(ilLearnObjectSuggResult::CALC_TYPE_BY_POINTS, $this->pl->txt('calculation_by_points'))
                    ->withOption(ilLearnObjectSuggResult::CALC_TYPE_BY_COMPLETED_OBJECTIVE, $this->pl->txt('calculation_by_completed_learning_objective'))
                    ->withOption(ilLearnObjectSuggResult::CALC_TYPE_HIGHEST_VALUE, $this->pl->txt('calculation_by_highest_value'))
                    ->withValue($calculationType);

        $inputFields['calculation_type'] = $radio;


        $ementoringSetting = ilParticipationCertificateConfig::getConfig('enable_ementoring', $this->groupRefId);
        if ($ementoringSetting === null) {
            $ementoringSetting = true;
        } else {
            $ementoringSetting = boolval($ementoringSetting);
        }

        $checkbox = $ui->input()->field()->checkbox($this->pl->txt('enable_ementoring'))
                             ->withValue($ementoringSetting);

        $inputFields['ementoring'] = $checkbox;

        $section = $ui->input()->field()->section(
            $inputFields,
            'Resultate für'
        );
        $formAction = $this->ctrl->getFormActionByClass(
            self::class,
            self::CMD_RESULT_TABLE_CONFIG,
            $this->pl->txt('save')
        );

        //Step 2: Define the form and attach the section.
        $form = $ui->input()->container()->form()->standard(
            $formAction,
            ['config' => $section]
        );
        return $form;
    }

    /**
     * @throws ilCtrlException|ilTemplateException
     */
    protected function saveResultTableConfig(): void
    {
        global $DIC;

        $form = $this->initConfigResultTableForm();

        $form = $form->withRequest($DIC->http()->request());

        if (empty($form->getData())) {
            $this->tpl->setOnScreenMessage('failure', $this->pl->txt('failure_timeframe_save'), true);
            $this->ctrl->redirect($this, self::CMD_CONFIG_RESULT_TABLE);
        }

        $form_data = $form->getData()['config'];

        if ($form->getError()) {
            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $form->getError());
            $this->selfPrint();
            return;
        }

        $period = $form_data['period'];
        $ementoring = $form_data['ementoring'];

        $periodStart = !empty($period['start']) ? $period['start']->format('d.m.Y') : null;
        $periodEnd = !empty($period['end']) ? $period['end']->format('d.m.Y') : null;

        ilParticipationCertificateConfig::setConfig('period_start', $periodStart, $this->groupRefId);
        ilParticipationCertificateConfig::setConfig('period_end', $periodEnd, $this->groupRefId);
        ilParticipationCertificateConfig::setConfig('enable_ementoring', $ementoring, $this->groupRefId);

        ilParticipationCertificateConfig::setConfig(
            'calculation_type_processing_state_suggested_objectives',
            $form_data['calculation_type'],
            $this->groupRefId
        );

        $this->tpl->setOnScreenMessage('success', $this->pl->txt('successFormSave'), true);
        $this->ctrl->redirect($this, self::CMD_CONFIG_RESULT_TABLE);
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    protected function selfPrint(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        if (method_exists($this->tpl, 'loadStandardTemplate')) {
            $this->tpl->loadStandardTemplate();
        } else {
            $this->tpl->getStandardTemplate();
        }
        $this->initHeader();

        $this->initConfTabs();
        $this->tabs->activateSubTab(self::TAB_CONFIG_SELF_PRINT);

        $form = $this->initSelfPrintForm();

        $this->tpl->setContent($renderer->render($form));
        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();
        } else {
            $this->tpl->show();
        }
    }

    /**
     * @return Standard
     * @throws ilCtrlException
     */
    protected function initSelfPrintForm(): Standard
    {
        global $DIC;

        $ui = $DIC->ui()->factory();

        $periodStart = ilParticipationCertificateConfig::getConfig('self_print_start', $this->groupRefId);
        $startDate = !empty($periodStart) ? DateTimeImmutable::createFromFormat('d.m.Y', $periodStart) : null;

        $periodEnd = ilParticipationCertificateConfig::getConfig('self_print_end', $this->groupRefId);
        $endDate = !empty($periodEnd) ? DateTimeImmutable::createFromFormat('d.m.Y', $periodEnd) : null;

        $durationInput = $ui->input()->field()->duration($this->pl->txt('period'));

        $user = $DIC->user();

        if (!empty($startDate) && !empty($endDate)) {
            $period = $durationInput
                ->withTimezone($user->getTimeZone())
                ->withUseTime(false)
                ->withLabels($this->pl->txt('start'), $this->pl->txt('end'))
                ->withFormat($user->getDateFormat())
                ->withMinValue($startDate)
                ->withMaxValue($endDate)
                ->withValue([$startDate, $endDate]);
        } else {
            $period = $durationInput
                ->withTimezone($user->getTimeZone())
                ->withUseTime(false)
                ->withLabels($this->pl->txt('start'), $this->pl->txt('end'))
                ->withFormat($user->getDateFormat());
        }

        $selfPrintIsEnabled = boolval(ilParticipationCertificateConfig::getConfig('enable_self_print', $this->groupRefId));

        if (!$selfPrintIsEnabled) {
            $inputFields['enable-self-printing'] = $ui->input()->field()->optionalGroup(
                [
                    'period' => $period
                ],
                $this->pl->txt('enable_self_print')
            )->withValue(null);

        } else {
            $inputFields['enable-self-printing'] = $ui->input()->field()->optionalGroup(
                [
                    'period' => $period
                ],
                $this->pl->txt('enable_self_print')
            );
        }

        $section = $ui->input()->field()->section(
            $inputFields,
            $this->pl->txt('period_self_print')
        );
        $formAction = $this->ctrl->getFormActionByClass(
            self::class,
            self::CMD_SELF_PRINT_SAVE,
            $this->pl->txt('save')
        );

        $form = $ui->input()->container()->form()->standard(
            $formAction,
            ['config' => $section]
        );
        return $form;
    }

    /**
     * @return void
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     * @throws ilCtrlException|ilTemplateException
     * @throws Exception
     */
    protected function saveSelfPrint(): void
    {
        global $DIC;

        $form = $this->initSelfPrintForm();

        $form = $form->withRequest($DIC->http()->request());
        $formData = $form->getData();

        if ($form->getError()) {
            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $form->getError());
            $this->selfPrint();
            return;
        }

        $enable = 0;
        if (!empty($formData['config']['enable-self-printing'])) {
            $enable = 1;
        }
        ilParticipationCertificateConfig::setConfig('enable_self_print', $enable, $this->groupRefId);

        if (!empty($formData['config']['enable-self-printing']['period']['start'])) {
            $startTimestamp = $formData['config']['enable-self-printing']['period']['start']->getTimestamp();
        }

        if (!empty($formData['config']['enable-self-printing']['period']['end'])) {
            $endTimestamp = $formData['config']['enable-self-printing']['period']['end']->getTimestamp();
        }

        $startDate = null;
        if (!empty($startTimestamp)) {
            $startDate = new DateTimeImmutable('@' . $startTimestamp);
            $startDate = $startDate->setTimezone(new DateTimeZone($DIC->user()->getTimeZone()));
            $startDate = $startDate->format('d.m.Y');
        }

        $endDate = null;
        if (!empty($endTimestamp)) {
            $endDate = new DateTimeImmutable('@' . $endTimestamp);
            $endDate = $endDate->setTimezone(new DateTimeZone($DIC->user()->getTimeZone()));
            $endDate = $endDate->format('d.m.Y');
        }

        ilParticipationCertificateConfig::setConfig('self_print_start', $startDate, $this->groupRefId);
        ilParticipationCertificateConfig::setConfig('self_print_end', $endDate, $this->groupRefId);

        $this->tpl->setOnScreenMessage('success', $this->pl->txt('successFormSave'), true);
        $this->ctrl->redirect($this, self::CMD_SELF_PRINT);
    }
}
