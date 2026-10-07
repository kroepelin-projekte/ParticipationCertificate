<?php

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Table\Data;

/**
 * Class ilParticipationCertificateConfigGUI
 * @author       Silas Stulz <sst@studer-raimann.ch>
 * @ilCtrl_IsCalledBy  ilParticipationCertificateConfigGUI: ilObjComponentSettingsGUI
 * @ilCtrl_Calls ilParticipationCertificateConfigGUI: ilParticipationCertificatePDFGenerator
 */
class ilParticipationCertificateConfigGUI extends ilPluginConfigGUI
{

    const CMD_CONFIRM_RESET_CONFIG = 'confirm_reset_config';
    const CMD_RESET_CONFIG = 'resetConfig';
    const CMD_SHOW_FORM = 'showForm';
    const CMD_SHOW_FORM_ERR = 'showErrForm';
    const CMD_ADD_CONFIG = 'addConfig';
    const CMD_COPY_CONFIG = 'copyConfig';
    const CMD_CREATE_TEMPLATE_FRON_LOCAL_CONFIG = 'createTemplateFromLocalConfig';
    const CMD_DELETE_CONFIG = 'deleteConfig';
    const CMD_SET_ACTIVE = 'setActive';
    const CMD_SET_INACTIVE = 'setInactive';
    const CMD_CONFIGURE = 'configure';

    const CMD_SAVE = 'save';

    const CMD_ACTION = 'action';

    const CMD_SORTING = 'sorting';

    const CMD_SAVE_ORDER = 'saveOrder';
    const CMD_CANCEL = 'cancel';
    protected ilParticipationCertificateConfig $object;
    protected ilParticipationCertificatePlugin $pl;
    protected ilTemplate|ilGlobalTemplateInterface $tpl;
    protected ilCtrl|ilCtrlInterface $ctrl;
    protected ilParticipationCertificateConfigSetTableGUI $table;
    protected ilTabsGUI $tabs;
    protected ilToolbarGUI $ilToolbar;
    protected ilGroupParticipants $learnGroupParticipants;
    protected ilObjCourse $courseobject;
    protected ilDBInterface $db;
    public mixed $dropValues;
    public string $surname;
    public string $lastname;
    public string $gender;

    private bool $err_helper;

    private $logger;

    /**
     * ilParticipationCertificateConfigGUI constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->db = $DIC->database();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->ilToolbar = $DIC->toolbar();
        $this->logger = $DIC->logger()->root();

        $this->pl = ilParticipationCertificatePlugin::getInstance();
    }

    /**
     * @throws ilCtrlException
     */
    function performCommand(string $cmd): void
    {
        if ($cmd !== 'configure') {
            $this->tabs->addTab(
                'return-back',
                $this->plugin_object->txt('back'),
                $this->ctrl->getLinkTarget($this, 'returnBack')
            );
        } else {
            $this->tabs->addTab(
                'sorting',
                $this->plugin_object->txt('sorting'),
                $this->ctrl->getLinkTarget($this, self::CMD_SORTING)
            );
        }

        switch ($cmd) {
            default:
            case self::CMD_ADD_CONFIG:
            case self::CMD_RESET_CONFIG:
            case self::CMD_CONFIRM_RESET_CONFIG;
            case self::CMD_DELETE_CONFIG:
            case self::CMD_SET_ACTIVE:
            case self::CMD_SET_INACTIVE:
            case self::CMD_COPY_CONFIG:
            case self::CMD_SHOW_FORM:
            case self::CMD_SHOW_FORM_ERR:
            case self::CMD_CONFIGURE:
            case self::CMD_SAVE:
            case self::CMD_CANCEL:
            case self::CMD_SAVE_ORDER:
            case self::CMD_ACTION:
            case self::CMD_SORTING:
                $this->$cmd();
                break;
        }
    }

    /**
     * @throws ilCtrlException
     */
    private function sorting(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        $form = $this->sortingForm();

        $this->tpl->setContent($renderer->render($form));
    }

    /**
     * @return Standard
     * @throws ilCtrlException
     */
    private function sortingForm(): Standard
    {
        global $DIC;
        $ui = $DIC->ui()->factory();

        $global_configs = new ilParticipationCertificateConfigSets();
        $data = $global_configs->getAllConfigSets();

        foreach ($data as $configSet) {
            $value = intval($configSet['order_by']) * 10;
            if ($configSet['order_by'] > 0) {
                $inputFields[$configSet['conf_id']] = $ui->input()->field()->text(
                    $configSet['title'],
                    ''
                )->withValue((string) $value)->withRequired(true);
            }
        }

        $section = $ui->input()->field()->section(
            $inputFields,
            $this->pl->txt('sorting'),
        );

        $formAction = $DIC->ctrl()->getFormActionByClass(
            self::class,
            'saveOrder'
        );

        $form = $ui->input()->container()->form()->standard(
            $formAction,
            ['config' => $section]
        );

        return $form;
    }

    public function addConfig(): void
    {
        $gl_configs = new ilParticipationCertificateGlobalConfigSets();
        $gl_config = $gl_configs->getDefaultConfig();
        $configs = new ilParticipationCertificateConfigs();

        $new_config_set = ilParticipationCertificateGlobalConfigSet::createNewFromConfigs($configs->getGlobalConfigSet($gl_config->getId()));

        $this->ctrl->setParameter($this, 'id', $new_config_set->getId());
        $this->ctrl->setParameter($this, 'set_type', ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     */
    public function createTemplateFromLocalConfig(): void
    {
        $entry = $_GET['config_entry'][0];
        $explodedEntry = explode('_', $entry);
        $grp_ref_id = $explodedEntry[2];

        if ($grp_ref_id == 0) {
            $this->ctrl->redirect($this, '');
        }

        $configs = new ilParticipationCertificateConfigs();

        $new_config_set = ilParticipationCertificateGlobalConfigSet::createNewFromConfigs($configs->getObjectConfigSet($grp_ref_id));

        $this->ctrl->setParameter($this, 'id', $new_config_set->getId());
        $this->ctrl->setParameter($this, 'set_type', ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    /**
     * @throws ilCtrlException|arException
     */
    public function copyConfig(): void
    {
        $entry = $_GET['config_entry'][0];
        $explodedEntry = explode('_', $entry);
        $id = $explodedEntry[0];

        if ($id == 0) {
            $this->ctrl->redirect($this, '');
        }

        $configs = new ilParticipationCertificateConfigs();

        $new_config_set = ilParticipationCertificateGlobalConfigSet::createNewFromConfigs($configs->getGlobalConfigSet($id));

        $this->ctrl->setParameter($this, 'id', $new_config_set->getId());
        $this->ctrl->setParameter($this, 'set_type', ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE);
        $this->ctrl->redirect($this, self::CMD_SHOW_FORM);
    }

    public function resetConfig(): void
    {
        global $DIC;

        foreach (ilParticipationCertificateConfig::get() as $config) {
            $config->delete();
        }

        foreach (ilParticipationCertificateGlobalConfigSet::get() as $configset) {
            $configset->delete();
        }

        $files = ilParticipationCertificateFiles::get();

        foreach ($files as $file) {
            $file->delete();
        }


        //Global Config
        //set global plugin configurations
        $config = ilParticipationCertificateConfig::where(['config_key' => 'udf_firstname'])->first();

        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigKey('udf_firstname');
        	$config->setGlobalConfigId(0);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setOrderBy(1);
        	$config->store();
        	}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'udf_lastname'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('udf_lastname');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(2);
        	$config->store();
		}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'udf_gender'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('udf_gender');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(3);
        	$config->store();
		}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'keyword'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('keyword');
        	$config->setConfigValue('Lerngruppe');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(0);
        	$config->store();
		}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'color'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('color');
        	$config->setConfigValue('fff5ba');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(4);
        	$config->store();
		}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'unsugg_color'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('unsugg_color');
        	$config->setConfigValue('000a35');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(5);
		$config->store();
	}

        $config = ilParticipationCertificateConfig::where(['config_key' => 'true_name_helper'])->first();
        if (!is_object($config)) {
            	$config = new ilParticipationCertificateConfig();
        	$config->setConfigKey('true_name_helper');
        	$config->setConfigValue('');
        	$config->setConfigType(ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL);
        	$config->setConfigValueType(ilParticipationCertificateConfig::CONFIG_VALUE_TYPE_OTHER);
        	$config->setGlobalConfigId(0);
        	$config->setOrderBy(6);
		$config->store();
	}

        //Config Template
        require_once "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ParticipationCertificate/vendor/autoload.php";
        $part_cert_default_config_set = new ilParticipationCertificateGlobalConfigSet();
        $part_cert_default_config_set->setOrderBy(1);
        $part_cert_default_config_set->store();

        $part_cert_configs = new ilParticipationCertificateConfigs();
        foreach ($part_cert_configs->returnCertTextDefaultValues() as $key => $value) {
            /**
             * @var $value ilParticipationCertificateConfig
             */
            $config = new ilParticipationCertificateConfig();
            $config->setGlobalConfigId($part_cert_default_config_set->getId());
            $config->setConfigKey($value->getConfigKey());
            $config->setOrderBy($value->getOrderBy());
            $config->setConfigType($value->getConfigType());
            $config->setConfigValueType($value->getConfigValueType());
            $config->setOrderBy($value->getOrderBy());

            $config->store();
        }

        $this->tpl->setOnScreenMessage('success',$this->pl->txt('config_reseted'), true);

        $DIC->ctrl()->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @throws ilCtrlException
     */
    public function confirm_reset_config(): void
    {
        global $DIC;

        $confirmation = new ilConfirmationGUI();

        $confirmation->setFormAction($DIC->ctrl()->getFormAction($this));

        $confirmation->setHeaderText($this->pl->txt('confirm_reset_config'));

        $confirmation->setConfirm($this->pl->txt('reset_config'), self::CMD_RESET_CONFIG);
        $confirmation->setCancel($DIC->language()->txt('cancel'), self::CMD_CONFIGURE);

        $DIC->ui()->mainTemplate()->setContent($confirmation->getHTML());
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     */
    public function action(): void
    {
        global $DIC;

        $action = $_GET['config_action'];

        if (!empty($action)) {
            switch ($action) {
                case 'edit':
                    $this->showForm();
                    break;

                case 'copy':
                    $this->copyConfig();
                    break;

                case 'delete':
                    $this->deleteConfig();
                    break;

                case 'activate':
                    $this->setActive();
                    break;

                case 'deactivate':
                    $this->setInactive();
                    break;

                case 'create-template':
                    $this->createTemplateFromLocalConfig();
                    break;

                case 'go-to':
                    $entry = $_GET['config_entry'][0];
                    $explodedEntry = explode('_', $entry);
                    $objRefId = $explodedEntry[2];
                    $DIC->ctrl()->redirectToURL(ilLink::_getStaticLink($objRefId));

                    break;
            }
        }
    }

    /**
     * @throws ilCtrlException|arException
     * @throws arException
     */
    public function deleteConfig(): void
    {
        $entry = $_GET['config_entry'][0];
        $explodedEntry = explode('_', $entry);
        $id = $explodedEntry[0];

        $gl_config = new ilParticipationCertificateGlobalConfigSet($id);

        if ($gl_config->getOrderBy() === 1) {
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        }

        $gl_config->delete();

        $configs = new ilParticipationCertificateConfigs();
        foreach ($configs->getGlobalConfigSet($id) as $config) {
            $config->delete();

            switch($config->getConfigKey()) {
                case 'logo':
                case 'page1_issuer_signature':
                    $file = ilParticipationCertificateFiles::getFile(
                        $config->getGlobalConfigId(),
                        $config->getConfigKey()
                    );

                    if (!empty($file)) {
                        $file->delete();
                    }
                    break;

                default:
                    break;
            }
        }

        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @throws ilCtrlException
     */
    public function setActive(): void
    {
        $entry = $_GET['config_entry'][0];
        $explodedEntry = explode('_', $entry);
        $id = $explodedEntry[0];

        $gl_config = new ilParticipationCertificateGlobalConfigSet($id);
        $gl_config->setActive(1);
        $gl_config->store();

        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @throws ilCtrlException
     */
    public function setInactive(): void
    {
        $entry = $_GET['config_entry'][0];
        $explodedEntry = explode('_', $entry);
        $id = $explodedEntry[0];

        $gl_config = new ilParticipationCertificateGlobalConfigSet($id);

        if ($gl_config->getOrderBy() === 1) {
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        }
        $gl_config->setActive(0);
        $gl_config->store();

        $this->ctrl->redirect($this, self::CMD_CONFIGURE);
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    public function saveOrder(): void
    {
        global $DIC;

        $form  = $this->sortingForm();

        $form  = $form->withRequest($DIC->http()->request());
        $form_data = $form->getData();

        $configs = new ilParticipationCertificateGlobalConfigSets();
        $configs->saveAndRearangeOrderBy($form_data['config']);
        $this->ctrl->redirect($this, self::CMD_SORTING);
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     */
    public function showErrForm(): void
    {
	    self::showForm(true);
    }

    /**
     * @throws ilCtrlException|arException
     */
    public function showForm(): void
    {
        global $DIC;

        $id = filter_input(INPUT_GET, 'id');
        $set_type = filter_input(INPUT_GET, 'set_type');

        if (empty($id) && empty($set_type)) {
            $entry = $_GET['config_entry'][0];
            $explodedEntry = explode('_', $entry);
            $id = $explodedEntry[0];
            $set_type = $explodedEntry[1];
        }

        $this->ctrl->setParameter($this, 'id', $id);

        $renderer = $DIC->ui()->renderer();

        $form = $this->buildForm((int) $id, (int) $set_type);

        $this->tpl->setContent($renderer->render($form));
    }

    /**
     * @param int $global_config_id
     * @param int $set_type
     * @return Standard
     * @throws arException
     * @throws ilCtrlException
     */
    private function buildForm(int $global_config_id, int $set_type): Standard
    {
        global $DIC;

        $ui = $DIC->ui()->factory();

        $DIC->ctrl()->setParameter($this, 'id', $global_config_id);
        $DIC->ctrl()->setParameter($this, 'set_type', $set_type);

        $inputFields = [];

        /**
         * @var ilParticipationCertificateGlobalConfigSet $global_config
         */
        if ($set_type == ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE) {
            $global_config = ilParticipationCertificateGlobalConfigSet::findOrGetInstance($global_config_id);

            $inputFields['config_title'] = $ui->input()->field()->text(
                $this->pl->txt('config_title')
            )->withValue($global_config->getTitle() ?? '');
        }

        $configs = ilParticipationCertificateConfig::where(array(
            'config_type' => (int) $set_type,
            'global_config_id' => $global_config_id
        ))->orderBy('order_by')->get();

        foreach ($configs as $config) {
            /**
             * @var ilParticipationCertificateConfig $config
             */
            switch ($config->getConfigKey()) {
                case 'udf_firstname':
                case 'udf_lastname':
                case 'udf_gender':
                    $options = $this->getUdfDropdownValues();

                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->select(
                        $this->pl->txt($config->getConfigKey()),
                        $options,
                        ''
                    )->withValue($config->getConfigValue() ?? '')->withRequired(true);

                    break;

                case 'color':
                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->colorSelect(
                        $this->pl->txt('color'),
                        ''
                    )->withValue('#' . $config->getConfigValue() ?? '');
                    break;
                case 'unsugg_color':
                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->colorSelect(
                        $this->pl->txt('unsugg_color'),
                        ''
                    )->withValue('#' . $config->getConfigValue() ?? '');
                    break;

                case 'keyword':
                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->text(
                        $this->pl->txt('keyword')
                    )->withValue($config->getConfigValue() ?? '');

                    break;

                case 'logo':
                    $file = new ilParticipationCertificateFiles();
                    $src = $file->getFileSrcByStorageType(
                        $config->getConfigValue(),
                        $global_config_id,
                        'logo'
                    );

                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->file(
                        new ilParticipationCertificateFileUploadHandlerGUI(),
                        $this->pl->txt('logo'),
                        'Maximum upload size: 1024.0 MB. Allowed file types: .png' . "<br>\n" .
                        '<img src="' . $src . '">'
                    )->withAcceptedMimeTypes([
                        'image/png'
                    ])->withMaxFileSize((2 * 1024 * 1024));

                    break;

                case 'page1_issuer_signature':

                    $file = new ilParticipationCertificateFiles();
                    $src = $file->getFileSrcByStorageType(
                        $config->getConfigValue(),
                        $global_config_id,
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

                case 'true_name_helper':
                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->textarea(
                        $this->pl->txt('true_name_helper')
                    )->withValue($config->getConfigValue() ?? '');

                    break;

                default:
                    $configValue = $config->getConfigValue();
                    if (!empty($configValue)) {
                        $configValue = $this->replacePlaceholdersFromOldVersion($configValue);
                    }

                    $inputFields[$config->getConfigKey()] = $ui->input()->field()->textarea(
                        $config->getConfigKey()
                    )->withValue($configValue ?? '');

                    break;

            }
        }

        $section = $ui->input()->field()->section(
            $inputFields,
            $this->pl->txt('config_plugin'),
            $this->pl->txt('placeholders') . ' <br>
		[[username]]: Anrede Vorname Nachname <br>
		[[date]]: Datum
		'
        );

        $formAction = $DIC->ctrl()->getFormActionByClass(
            self::class,
            'save'
        );

        $form = $ui->input()->container()->form()->standard(
            $formAction,
            ['config' => $section]
        );

        return $form;
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     */
    public function save(): bool
    {
        global $DIC;

        $this->logger->debug('Start saving participation certificate');

        $global_config_id = filter_input(INPUT_GET, 'id');
        $set_type = filter_input(INPUT_GET, 'set_type');

        $DIC->ctrl()->setParameter($this, 'id', $global_config_id);
        $DIC->ctrl()->setParameter($this, 'set_type', $set_type);

        $form  = $this->buildForm((int) $global_config_id, (int) $set_type);

        $form  = $form->withRequest($DIC->http()->request());
        $form_data = $form->getData()['config'];

        $this->err_helper = false;

        $part_cert_configs = new ilParticipationCertificateConfigs();
        switch ($set_type) {
            case ilParticipationCertificateConfig::CONFIG_SET_TYPE_TEMPLATE:
                foreach ($form_data as $key => $item) {

                    if($key !== 'config_title') {
                        $this->logger->debug('Get config key: ' . $key);

                        $config = ilParticipationCertificateConfig::where(array(
                            'config_key' => $key,
                            'global_config_id' => $global_config_id,
                        ))->first();
                    }

                    $input = $item;

                    switch ($key) {
                        case 'config_title':
                            $global_config = ilParticipationCertificateGlobalConfigSet::findOrGetInstance($global_config_id);
                            $global_config->setTitle($input);
                            $global_config->store();

                            $this->logger->debug('Save key: ' . $key);

                            break;
                        case 'logo':
                            $file = 'logo';
                            if (!empty($input)) {
                                $input = end($input);
                            } else {
                                $input = $config->getConfigValue();
                            }

                            if (!empty($input)) {
                                try {
                                    $global_config = $part_cert_configs->getParticipationTemplateConfigValueByKey(
                                        $global_config_id,
                                        $key
                                    );

                                    $global_config->setConfigValue($input);
                                    $global_config->store();

                                    $this->logger->debug('Save key: ' . $key);

                                    ilParticipationCertificateFiles::setFile(
                                        $global_config_id,
                                        $file,
                                        true
                                    );
                                    $this->logger->debug('Save value for ' . $key . ' in table: dhbw_part_cert_files');
                                } catch (\Throwable $e) {
                                    $this->logger->debug("Error uploading file '{$file}': " . $e->getMessage());
                                    $this->ctrl->redirect($this, self::CMD_SHOW_FORM_ERR);
                                    return false;
                                }
                            }

                            break;
                        case 'page1_issuer_signature':
                            $file = 'page1_issuer_signature';
                            if (!empty($input)) {
                                $input = end($input);
                            } else {
                                $input = $config->getConfigValue();
                            }

                            if (!empty($input)) {
                                try {
                                    $global_config = $part_cert_configs->getParticipationTemplateConfigValueByKey(
                                        $global_config_id,
                                        $key
                                    );

                                    $global_config->setConfigValue($input);
                                    $global_config->store();

                                    $this->logger->debug('Save key: ' . $key);

                                    ilParticipationCertificateFiles::setFile(
                                        $global_config_id,
                                        $file,
                                        true
                                    );
                                    $this->logger->debug('Save value for ' . $key . ' in table: dhbw_part_cert_files');
                                } catch (\Throwable $e) {
                                    $this->logger->debug("Error uploading file '{$file}': " . $e->getMessage());
                                    $this->ctrl->redirect($this, self::CMD_SHOW_FORM_ERR);
                                    return false;
                                }
                            }

                            break;
                        default:
                            $global_config = $part_cert_configs->getParticipationTemplateConfigValueByKey(
                                $global_config_id,
                                $key
                            );

                            $global_config->setConfigValue($input);
                            $global_config->store();
                            $this->logger->debug('Save key: ' . $key);
                            break;
                    }
                }
                break;
            case ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL:
                foreach ($form_data as $key => $item) {
                    $file = null;
                    $input = $item;

                    switch ($key) {
                        case 'logo':
                            $input = end($input);

                            if (!empty($input)) {
                                $file = 'logo';
                            } else {
                                $input = $item;
                            }
                            break;

                        case 'true_name_helper':
                            $userinput = trim($item);
                            if (!ctype_digit($userinput) and $userinput != '') {
                                $userinput = '';
                                $this->err_helper = true;
                            }
                            $input = $userinput;

                            break;

                        case 'color':
                        case 'unsugg_color':
                            $hexValue = $this->rgbToHex(
                                $item->r(),
                                $item->g(),
                                $item->b()
                            );

                            $hexValue = str_replace('#', '', $hexValue);
                            $input = $hexValue;

                            break;
                        default:
                            break;
                    }
                    $global_config = $part_cert_configs->getParticipationGlobalConfigValueByKey($key);
                    $global_config->setConfigValue($input);
                    $global_config->store();
                    $this->logger->debug('Save key: ' . $key);
                }
        }

        if ($this->err_helper) {
            $this->ctrl->redirect($this, self::CMD_SHOW_FORM_ERR);
        } else {
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        }
        $this->logger->debug('End saving participation certificate');

        return true;
    }

    private function rgbToHex($r, $g, $b) {
        return sprintf("#%02X%02X%02X", $r, $g, $b);
    }

    /**
     */
    /*protected function addTabs(
        string $id,
        string $text,
        string $link
    ) : void {
        $this->tabs->addTab(
            $id,
            $text,
            $link
        );
    }*/

    /**
     * @return void
     * @throws ilCtrlException
     */
    private function returnBack()
    {
        $this->ctrl->redirectByClass(self::class, 'configure');
    }

    protected function getUdfDropdownValues(): array
    {

        $sql = "SELECT * FROM udf_definition";

        $results = $this->db->query($sql);

        $data = array();
        while ($row = $this->db->fetchAssoc($results)) {
            $data[$row['field_id']] = $row['field_name'];
        }

        return $data;
    }

    /**
     * @throws ilCtrlException
     */
    public function configure(): void
    {
        global $DIC;

        $r = $DIC->ui()->renderer();

        $ui = $DIC->ui()->factory();

        $this->tpl->loadStandardTemplate();

        $toolbarButton = $ui->button()->standard(
            $this->pl->txt('add_config'),
            $this->ctrl->getLinkTargetByClass(ilParticipationCertificateConfigGUI::class, ilParticipationCertificateConfigGUI::CMD_ADD_CONFIG)
        );
        $this->ilToolbar->addComponent($toolbarButton);

        $toolbarButton = $ui->button()->standard(
            $this->pl->txt('reset_config'),
            $this->ctrl->getLinkTargetByClass(ilParticipationCertificateConfigGUI::class, ilParticipationCertificateConfigGUI::CMD_CONFIRM_RESET_CONFIG)
        );
        $this->ilToolbar->addComponent($toolbarButton);

        $table = $this->initTable();


        $this->tpl->setContent(
            $r->render($table->withRequest($DIC->http()->request()))
        );
    }

    /**
     * @return Data
     * @throws ilCtrlException
     */
    protected function initTable(): Data
    {
        $table = new ilParticipationCertificateConfigSetTableGUI();

        return $table->getTableForRepresentation();

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
}
