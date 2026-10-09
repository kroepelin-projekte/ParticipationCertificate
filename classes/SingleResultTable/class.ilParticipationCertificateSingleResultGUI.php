<?php

/**
 * @ilCtrl_isCalledBy ilParticipationCertificateSingleResultGUI: ilUIPluginRouterGUI
 */
class ilParticipationCertificateSingleResultGUI
{
    public const string CMD_DISPLAY = 'display';

    public const string IDENTIFIER = 'usr_id';

    public ilTabsGUI $tabs;

    protected ilTemplate|ilGlobalTemplateInterface $tpl;

    protected ilCtrl|ilCtrlInterface $ctrl;

    protected ilParticipationCertificatePlugin $pl;

    protected ilToolbarGUI $toolbar;

    protected ?ilObject $learn_group;

    protected array $usr_ids;

    protected int $usr_id;

    protected ilParticipationCertificateSingleResultTableGUI $table;

    /**
     * @throws ilCtrlException
     * @throws ilObjectNotFoundException
     * @throws ilDatabaseException
     */
    public function __construct()
    {
        global $DIC;

        $this->toolbar = $DIC->toolbar();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->learn_group = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);

        $this->ctrl->saveParameterByClass(ilParticipationCertificateUIHookGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateUIHookGUI::class, 'usr_id');

        $cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);
        $this->usr_ids = $cert_access->getUserIdsOfGroup();

        $usr_id = $_GET[self::IDENTIFIER];

        if(empty($usr_id) && !empty($_GET['config_entry'])) {
            $urlParameters = $this->excludeURLParameters($_GET['config_entry'][0]);
            $usr_id = (int) $urlParameters[0];
        }
        $this->usr_id = $usr_id;
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_DISPLAY);
                $this->{$cmd}();
                break;
        }
    }

    /**
     * @throws ilTemplateException
     * @throws ilCtrlException
     */
    public function display(): void
    {
        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');
        $this->initHeader();

        $this->initTable();

        $this->tpl->setContent($this->table->getHTML());
        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();
        } else {
            $this->tpl->show();
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function initHeader(): void
    {
        $this->tpl->setTitle($this->learn_group->getTitle());
        $this->tpl->setDescription($this->learn_group->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($this->learn_group->getId()));
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');
        $this->tabs->setBackTarget($this->pl->txt('header_btn_back'), $this->ctrl->getLinkTargetByClass(ilParticipationCertificateResultGUI::class, ilParticipationCertificateResultGUI::CMD_CONTENT));
    }

    /**
     * @throws ilException
     * @throws ilCtrlException
     */
    public function initTable(): void
    {
        $this->table = new ilParticipationCertificateSingleResultTableGUI($this, ilParticipationCertificateSingleResultGUI::CMD_DISPLAY, $this->usr_id);
    }

    /**
     * @param string $parameter
     * @return string[]
     */
    private function excludeURLParameters(string $parameter): array
    {
        return explode('_', $parameter);
    }
}