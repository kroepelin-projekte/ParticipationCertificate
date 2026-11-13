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

        $ref_id = (int) (ilParticipationCertificateHelper::fetchUrlParameter('ref_id', 'string') ?? 0);

        $cert_access = new ilParticipationCertificateAccess($ref_id);
        if (!$cert_access->hasCurrentUserWriteAccess()) {
            $this->tpl->setOnScreenMessage('failure', $DIC->language()->txt('no_permission'), true);
            $this->ctrl->redirectToURL('login.php');
        }

        $usr_id = (int) (ilParticipationCertificateHelper::fetchUrlParameter('usr_id', 'string') ?? 0);

        if ($usr_id === 0) {
            $config_entry = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');
            if (!empty($config_entry)) {
                $usr_id = (int) $this->excludeURLParameters($config_entry[0])[0];
            }
        }

        // the user must be a member of this course/group, regardless of where the id came from
        if ($cert_access->filterUserIdsOfGroup([$usr_id]) === []) {
            $this->tpl->setOnScreenMessage('failure', $DIC->language()->txt('no_permission'), true);
            $this->ctrl->setParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id', $ref_id);
            $this->ctrl->redirectByClass(
                [ilUIPluginRouterGUI::class, ilParticipationCertificateResultGUI::class],
                ilParticipationCertificateResultGUI::CMD_CONTENT
            );
        }

        $this->usr_id = $usr_id;
        $this->usr_ids = $cert_access->getUserIdsOfGroup();
        $this->learn_group = ilObjectFactory::getInstanceByRefId($ref_id);

        $this->ctrl->saveParameterByClass(ilParticipationCertificateUIHookGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateUIHookGUI::class, 'usr_id');
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass();

        $cmd = $this->ctrl->getCmd(self::CMD_DISPLAY);
        $this->{$cmd}();
    }

    /**
     * @throws ilTemplateException
     * @throws ilCtrlException
     */
    public function display(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();
        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');
        $this->tpl->addJavaScript('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/js/participation-certificate.js');

        $this->initHeader();

        $arr_usr_data = ilPartCertUsersData::getData($this->pl, [$this->usr_id]);
        $name_user = $arr_usr_data[$this->usr_id]->getPartCertFirstname() . ' ' . $arr_usr_data[$this->usr_id]->getPartCertLastname();
        if ($name_user == ' ') {
            $name_user = $this->pl->txt('loginname') . ' ' . $arr_usr_data[$this->usr_id]->getPartCertUserName();
        }

        $result_table = new ilParticipationCertificateSingleResultTableGUI($this->usr_id);
        $table = $result_table->getTableForRepresentation();

        $table_html = '<div class="dhbw_part_cert_res">';
        $table_html .= '<h2 class="dhbw-part-cert-heading">' . $this->pl->txt('result_for ') . ' ' . htmlspecialchars((string) $name_user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
        $table_html .= $renderer->render($table->withRequest($DIC->http()->request()));
        $table_html .= '</div>';

        $this->tpl->setContent($table_html);
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
     * @param string $parameter
     * @return string[]
     */
    private function excludeURLParameters(string $parameter): array
    {
        return explode('_', $parameter);
    }
}