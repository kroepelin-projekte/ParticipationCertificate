<?php

/**
 * Class ilParticipationCertificateResultModificationGUI
 *
 * @ilCtrl_isCalledBy ilParticipationCertificateResultModificationGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilParticipationCertificateResultModificationGUI: ilParticipationCertificateResultGUI
 */
class ilParticipationCertificateResultModificationGUI
{

    const CMD_DISPLAY = 'display';
    const IDENTIFIER = 'usr_id';
    public ilTabsGUI $tabs;
    protected ilTemplate|ilGlobalTemplateInterface $tpl;
    protected ilCtrl|ilCtrlInterface $ctrl;
    protected ilParticipationCertificatePlugin $pl;
    protected ilToolbarGUI $toolbar;
    protected int $courseRefId;
    protected ?ilObject $learnGroup;
    protected array $usr_ids;
    protected mixed $usr_id;
    /**
     * @var ilPartCertUserData[]
     */
    protected array $arr_usr_data;
    /**
     * @var ilCrsInitialTestState[]
     */
    protected array $arr_initial_test_states;
    /**
     * @var ilLearnObjectSuggResult[]
     */
    protected array $arr_learn_reached_percentages;
    /**
     * @var ilIassState[]
     */
    protected array $arr_iass_states;
    /**
     * @var ilExcerciseState[]
     */
    protected array $arr_excercise_states;
    /**
     * @var ilLearnObjectFinalTestState[][]
     */
    protected array $arr_FinalTestsStates;
    /**
     * @var ilLearnObjectFinalTestState[][]
     */
    protected array $array_obj_ids;

    private $dic;

    /**
     * @throws ilCtrlException
     * @throws ilObjectNotFoundException
     * @throws ilDatabaseException
     */
    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
        $this->toolbar = $DIC->toolbar();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilParticipationCertificatePlugin::getInstance();

        $this->courseRefId = $this->fetchUrlParameter('ref_id', FILTER_SANITIZE_NUMBER_INT);
        $this->learnGroup = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, 'ementor');
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'usr_id');
        $cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);
        $this->usr_ids = $cert_access->getUserIdsOfGroup();

        $usr_id = $_GET[self::IDENTIFIER];

        if(empty($usr_id)) {
            $urlParameters = $this->excludeURLParameters($_GET['config_entry'][0]);
            $usr_id = (int) $urlParameters[0];
        }
        $this->usr_id = $usr_id;

        $courseObjId = ilObject::_lookupObjectId((int) $this->courseRefId);

        $this->arr_usr_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
        $this->arr_initial_test_states = ilCrsInitialTestStates::getData($this->usr_ids);
        $this->arr_learn_reached_percentages = ilLearnObjectSuggResults::getData($courseObjId, $this->usr_ids);
        $this->arr_iass_states = ilIassStates::getData($this->usr_ids);
        $this->arr_excercise_states = ilExcerciseStates::getData($this->usr_ids, $_GET['ref_id']);
        /*$this->arr_FinalTestsStates = ilLearnObjectFinalTestStates::getData($this->usr_ids);*/
        $this->arr_FinalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($courseObjId, $this->usr_ids);
        /*$this->array_obj_ids = ilLearnObjectFinalTestStates::getData($this->usr_ids);*/
        $this->array_obj_ids = $this->arr_FinalTestsStates;

        $this->ctrl->setParameterByClass(ilParticipationCertificateResultModificationGUI::class, 'edited', true);
        $this->ctrl->setParameterByClass(ilParticipationCertificateResultModificationGUI::class, 'ementor', true);
        $this->ctrl->setParameterByClass(ilParticipationCertificateResultModificationGUI::class, 'usr_id', $this->usr_id);
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        $nextClass = $this->ctrl->getNextClass();
        switch ($nextClass) {
            case strtolower(ilParticipationCertificateResultGUI::class):
                $ilParticipationCertificateresultGUI = new ilParticipationCertificateResultGUI();
                $this->ctrl->forwardCommand($ilParticipationCertificateresultGUI);
                break;
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
        global $DIC;

        $cert_access = new ilParticipationCertificateAccess($this->courseRefId);
        if ($cert_access->hasCurrentUserWriteAccess()) {
            $renderer = $this->dic->ui()->renderer();
            $this->tpl->loadStandardTemplate();
            $this->initHeader();

            $form = $this->initForm();

            $printButtonText = $this->pl->txt('print');
            $printButtonTextJs = json_encode($printButtonText);
            $DIC->ui()->mainTemplate()->addOnLoadCode(<<<JS
              $('#ilContentContainer #il_center_col form .il-standard-form-cmd button.btn').text($printButtonTextJs)
            JS);

            $this->tpl->setContent($renderer->render($form));
            if (method_exists($this->tpl, 'printToStdout')) {
                $this->tpl->printToStdout();
            } else {
                $this->tpl->show();
            }

        } else {
            $this->ctrl->redirect(new ilParticipationCertificateResultGUI(), 'content');
        }
    }

    public function initHeader(): void
    {
        $this->tpl->setTitle($this->learnGroup->getTitle());
        $this->tpl->setDescription($this->learnGroup->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($this->learnGroup->getId()));

        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'ref_id');
        $this->tabs->setBackTarget($this->pl->txt('header_btn_back'), $this->ctrl->getLinkTargetByClass(array(
            ilUIPluginRouterGUI::class,
            ilParticipationCertificateResultGUI::class
        ), ilParticipationCertificateResultGUI::CMD_CONTENT));
    }

    /**
     * @throws ilCtrlException
     */
    public function initForm()
    {
        $ui = $this->dic->ui()->factory();

        $usr_id = $_GET[self::IDENTIFIER];

        if(empty($usr_id)) {
            $urlParameters = $this->excludeURLParameters($_GET['config_entry'][0]);
            $usr_id = (int) $urlParameters[0];
        }

        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);
        $name_user = $arr_usr_data[$usr_id]->getPartCertFirstname() . ' ' . $arr_usr_data[$usr_id]->getPartCertLastname();

        $form_data = $this->getFormData();

        $inputFields['initial'] = $ui->input()->field()->text(
            $this->pl->txt('mod_initial')
        )->withValue((string) $form_data['initial'] ?? '');

        $inputFields['mod_resultstest'] = $ui->input()->field()->text(
            $this->pl->txt('mod_resultstest')
        )->withValue((string) $form_data['resultstest'] ?? '');

        $inputFields['conf'] = $ui->input()->field()->text(
            $this->pl->txt('mod_conf')
        )->withValue((string) $form_data['conf'] ?? '');

        $inputFields['homework'] = $ui->input()->field()->text(
            $this->pl->txt('mod_homework')
        )->withValue((string) $form_data['homework'] ?? '');

        if ($form_data['ementoring']) {
            $inputFields['ementoring'] = $ui->input()->field()->checkbox(
                $this->pl->txt('add_ementoring'),
                $this->pl->txt('add_ementoring_additional')
            )->withValue(true);
        }

        $section = $ui->input()->field()->section(
            $inputFields,
            sprintf($this->pl->txt('mod_section'), $name_user)
        );
        
        $formAction = $this->ctrl->getFormActionByClass(
            self::class,
            ilParticipationCertificateResultGUI::CMD_PRINT_PDF,
            $this->pl->txt('list_print')
        );

        //Step 2: Define the form and attach the section.
         $form = $ui->input()->container()->form()->standard(
             $formAction,
             ['config' => $section]
         );
         return $form;
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        $array = [];
        if (key_exists($this->usr_id, $this->arr_initial_test_states) && is_object($this->arr_initial_test_states[$this->usr_id])) {
            $array['initial'] = $this->arr_initial_test_states[$this->usr_id]->getCrsitestItestSubmitted();
        } else {
            $array['initial'] = 0;
        }
        if (key_exists($this->usr_id, $this->arr_learn_reached_percentages) && is_object($this->arr_learn_reached_percentages[$this->usr_id])) {
            $array['resultstest'] = $this->arr_learn_reached_percentages[$this->usr_id]->getAveragePercentage(ilParticipationCertificateConfig::getConfig('calculation_type_processing_state_suggested_objectives', $_GET['ref_id']));
        } else {
            $array['resultstest'] = 0;
        }
        if (key_exists($this->usr_id, $this->arr_iass_states) && is_object($this->arr_iass_states[$this->usr_id])) {
            $array['conf'] = $this->arr_iass_states[$this->usr_id]->getPassed();
        } else {
            $array['conf'] = 0;
        }
        if (key_exists($this->usr_id, $this->arr_excercise_states) && is_object($this->arr_excercise_states[$this->usr_id])) {
            $array['homework'] = $this->arr_excercise_states[$this->usr_id]->getPassedPercentage();
        } else {
            $array['homework'] = 0;
        }

        $ementor = ilParticipationCertificateConfig::getConfig('enable_ementoring', $this->courseRefId);
        if ($ementor === NULL) {
            $ementor = true;
        } else {
            $ementor = boolval($ementor);
        }
        $array['ementoring'] = $ementor;
        
        return $array;
    }

    /**
     * @throws ilCtrlException
     */
    public function printPDF(): void
    {
        global $DIC;

        $form = $this->initForm();

        $form  = $form ->withRequest($DIC->http()->request());
        $data = $form->getData()['config'];

        $array = [
            $data['initial'],
            $data['mod_resultstest'],
            $data['conf'],
            $data['homework']
        ];
        
        $edited = $_GET['edited'];
        $usr_id[] = $this->usr_id;

        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_id);
        $user_data = new ilPartCertUserData();
        if(!$user_data->checkIfUserDataFilled(
            $arr_usr_data[$usr_id[0]]->getPartCertSalutation(),
            $arr_usr_data[$usr_id[0]]->getPartCertFirstname(),
            $arr_usr_data[$usr_id[0]]->getPartCertLastname()
        )) {
            $this->redirectWithError(self::CMD_DISPLAY, $this->pl->txt('user_data_missing'));
        }

        $twigParser = new ilParticipationCertificateTwigParser(
            $this->courseRefId,
            $usr_id,
            boolval($data['ementoring'] ?? ''),
            $edited,
            $array
        );

        $twigParser->parseData(
            false,
            false,
            $this->courseRefId
        );
    }

    /**
     * @param string $cmd
     * @param string $msg
     * @return void
     * @throws ilCtrlException
     */
    private function redirectWithError(string $cmd, string $msg): void
    {
        $this->tpl->setOnScreenMessage('failure', $msg, true);
        $this->ctrl->redirect($this, $cmd);
    }

    /**
     * @param string $parameter
     * @return string[]
     */
    private function excludeURLParameters(string $parameter): array
    {
        return explode('_', $parameter);
    }

    protected function fetchUrlParameter(string $param, int $filter): ?int
    {
        return filter_input(INPUT_GET, $param, $filter);
    }
}
