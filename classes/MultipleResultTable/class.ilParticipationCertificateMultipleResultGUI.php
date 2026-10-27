<?php
/**
 * @ilCtrl_isCalledBy ilParticipationCertificateMultipleResultGUI: ilUIPluginRouterGUI
 */
class ilParticipationCertificateMultipleResultGUI
{
    public const string CMD_SHOW_ALL_RESULTS = 'show_all_results';


    protected ilTabsGUI $tabs;

    protected ilTemplate|ilGlobalTemplateInterface $tpl;

    protected ilCtrl|ilCtrlInterface $ctrl;

    protected ilParticipationCertificatePlugin $pl;

    protected ilToolbarGUI $toolbar;

    /**
     * @var ilParticipationCertificateSingleResultTableGUI[]
     */
    protected array $tables = [];

    /**
     * @var int[]
     */
    protected ?array $usr_ids;

    /**
     * @var int
     */
    protected mixed $ref_id;

    protected ilObject|null|ilObjGroup $learn_group;

    /**
     * @throws ilObjectNotFoundException
     * @throws ilCtrlException
     * @throws ilDatabaseException|ilTemplateException
     */
    public function __construct(?array $userIds = null, ?string $refId = null)
    {
        global $DIC;

        $this->toolbar = $DIC->toolbar();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilParticipationCertificatePlugin::getInstance();

        if ($refId !== null) {
            $this->ref_id = (int) $refId;
        } else {
            $this->ref_id = (int) $_GET['ref_id'];
        }

        $this->learn_group = ilObjectFactory::getInstanceByRefId($this->ref_id);

        if ($userIds !== null) {
            $this->usr_ids = $userIds;
        } else {
            $this->usr_ids = filter_input(INPUT_POST, 'record_ids', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
        }

        if (!is_array($this->usr_ids) || count($this->usr_ids) === 0) {
            $this->tpl->setOnScreenMessage('failure',$this->pl->txt('no_records_selected'), true);
            $this->ctrl->redirectByClass(ilParticipationCertificateResultGUI::class, ilParticipationCertificateResultGUI::CMD_CONTENT);
        };

        $this->ctrl->saveParameterByClass(ilParticipationCertificateUIHookGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');

        $this->show_all_results();
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd(self::CMD_SHOW_ALL_RESULTS);

        if ($cmd == self::CMD_SHOW_ALL_RESULTS) {
            $this->{$cmd}();
        }
    }

    /**
     * @throws ilTemplateException
     */
    private function show_all_results(): void
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        $this->tpl->loadStandardTemplate();

        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');
        $this->tpl->addJavaScript('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/js/participation-certificate.js');

        $this->initHeader();
        $this->initTables();

        $html = '';
        foreach ($this->tables as $usr_id => $table) {
            $arr_usr_data = ilPartCertUsersData::getData($this->pl, [$usr_id]);
            $name_user = $arr_usr_data[$usr_id]->getPartCertFirstname() . ' ' . $arr_usr_data[$usr_id]->getPartCertLastname();
            if ($name_user == ' ') {
                $name_user = $this->pl->txt('loginname') . ' ' . $arr_usr_data[$usr_id]->getPartCertUserName();
            }

            $html .= '<div class="dhbw_part_cert_res">';
            $html .= '<h2 class="dhbw-part-cert-heading">' . $this->pl->txt('result_for ') . ' ' . $name_user . '</h2>';
            $html .= $renderer->render($table->withRequest($DIC->http()->request()));
            $html .= '</div>';
        }

        $this->tpl->setContent($html);
        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();
        } else {
            $this->tpl->show();
        }
    }

    /**
     * @return void
     * @throws ilCtrlException
     */
    protected function initHeader(): void
    {
        $this->tpl->setTitle($this->learn_group->getTitle());
        $this->tpl->setDescription($this->learn_group->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($this->learn_group->getId()));
        $this->tabs->setBackTarget($this->pl->txt('header_btn_back'), $this->ctrl->getLinkTargetByClass(ilParticipationCertificateResultGUI::class, ilParticipationCertificateResultGUI::CMD_CONTENT));
    }

    /**
     * @return void
     * @throws ilCtrlException
     * @throws ilException
     */
    protected function initTables(): void
    {
        $this->ctrl->saveParameterByClass(ilParticipationCertificateMultipleResultGUI::class, 'ref_id');
        foreach ($this->usr_ids as $usr_id) {
            $result_table = new ilParticipationCertificateSingleResultTableGUI($usr_id);
            $table = $result_table->getTableForRepresentation();

            $this->tables[$usr_id] = $table;
        }
    }
}
