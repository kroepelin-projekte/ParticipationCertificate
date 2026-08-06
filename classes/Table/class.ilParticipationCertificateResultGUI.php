<?php

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;

/**
 * Class ilParticipationCertificateResultGUI
 * @ilCtrl_isCalledBy ilParticipationCertificateResultGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilParticipationCertificateResultGUI: ilParticipationCertificateSingleResultGUI, ilParticipationCertificateGUI, ilParticipationCertificateResultModificationGUI
 */
class ilParticipationCertificateResultGUI
{
    const CMD_CONTENT = 'content';

    const CMD_OVERVIEW = 'overview';

    const CMD_PRINT_PDF = 'printpdf';

    const CMD_PRINT_SELECTED_WITHOUTE_MENTORING = 'printSelectedWithouteMentoring';

    const CMD_PRINT_SELECTED = 'printSelected';

    const CMD_INIT_TABLE = 'initTable';

    const CMD_EXPORT_EXCEL = 'exportExcel';

    const CMD_EXPORT_CSV = 'exportCSV';

    /**
     * @var array|array[]
     */
    private array $columns;

    private ilParticipationCertificateAccess $cert_access;

    protected ilTemplate|ilGlobalTemplateInterface $tpl;

    protected ilCtrl|ilCtrlInterface $ctrl;

    protected ilTabsGUI $tabs;

    protected ilToolbarGUI $toolbar;

    protected ilParticipationCertificatePlugin $pl;
    protected int $courseRefId;

    protected ilLanguage $lng;

    private bool $ementoring;

    /**
     * @throws ilObjectNotFoundException
     * @throws ilCtrlException
     * @throws ilDatabaseException
     */
    public function __construct()
    {
        global $DIC;

        $refId = $this->fetchUrlParameter('ref_id', FILTER_DEFAULT);
        $this->toolbar = $DIC->toolbar();
        $this->tabs = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->courseRefId = (int) $refId;
        $this->lng = $DIC->language();
	    $this->cert_access = new ilParticipationCertificateAccess($refId);
	    
        $ementoring = ilParticipationCertificateConfig::getConfig('enable_ementoring', $this->courseRefId);
        if ($ementoring === NULL) {
            $ementoring = true;
        } else {
            $ementoring = boolval($ementoring);
        }
        $this->ementoring = $ementoring;
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, ['ref_id', 'group_id']);
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        global $DIC;

        $cert_access = new ilParticipationCertificateAccess($this->courseRefId);
        if (!$cert_access->hasCurrentUserWriteAccess()) {
            $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_permission'), true);
            $DIC->ctrl()->redirectToURL('login.php');
        }
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(ilParticipationCertificateResultModificationGUI::class):
                $ilparticipationcertificateresultmodificationgui = new ilParticipationCertificateResultModificationGUI();
                $ret1 = $this->ctrl->forwardCommand($ilparticipationcertificateresultmodificationgui);
                break;
            case strtolower(ilParticipationCertificateGUI::class):
                $ilParticipationCertificateGUI = new ilParticipationCertificateGUI();
                $ret2 = $this->ctrl->forwardCommand($ilParticipationCertificateGUI);
                $this->tabs->activateTab(self::CMD_OVERVIEW);
                break;
            case strtolower(ilparticipationcertificatesingleresultgui::class):
                $ilparticipationcertificateresultoverviewgui = new ilParticipationCertificateSingleResultGUI();
                $ret3 = $this->ctrl->forwardCommand($ilparticipationcertificateresultoverviewgui);
                break;
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_CONTENT);
                $this->tabs->activateTab(self::CMD_OVERVIEW);

                switch ($cmd) {
                    case ilParticipationCertificateMultipleResultGUI::CMD_SHOW_ALL_RESULTS:
                        $this->ctrl->forwardCommand(new ilParticipationCertificateMultipleResultGUI());
                        break;
                    case self::CMD_PRINT_PDF:
                    case self::CMD_PRINT_SELECTED:
                    case self::CMD_PRINT_SELECTED_WITHOUTE_MENTORING:
                        $this->{$cmd}();
                        break;
                    default:
                        $this->{$cmd}();
                        break;
                }
                break;
        }
    }

    /**
     * @throws ilTemplateException
     * @throws arException
     * @throws ilCtrlException
     * @throws Exception
     */
    public function content(): void
    {
        global $DIC;

        global $rbacreview;

        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');

        if (method_exists($this->tpl, 'loadStandardTemplate')) {
            $this->tpl->loadStandardTemplate();
        } else {
            $this->tpl->getStandardTemplate();
        }
        $this->initHeader();

        $ui = $DIC->ui()->factory();

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $this->tpl->setOnScreenMessage('info',$this->pl->txt('print_all_info'),true);

            if ($this->ementoring) {
                $this->ctrl->setParameter($this, 'ementor', true);
                $toolbarButton = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_is_ementoring'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbarButton);

                $this->ctrl->setParameter($this, 'ementor', false);
                $toolbarButton = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_no_ementoring'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbarButton);
            } else {
                $this->ctrl->setParameter($this, 'ementor', false);
                $toolbarButton = $ui->button()->standard(
                    $this->pl->txt('header_btn_print'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbarButton);
            }

            if (!empty($_GET['filter_firstname'])) {
                $this->ctrl->setParameter($this, 'filter_firstname', $_GET['filter_firstname']);
            }

            if (!empty($_GET['filter_lastname'])) {
                $this->ctrl->setParameter($this, 'filter_lastname', $_GET['filter_lastname']);
            }

            $toolbarButton = $ui->button()->standard(
                $this->pl->txt('excel_export'),
                $this->ctrl->getLinkTarget($this, self::CMD_EXPORT_EXCEL)
            );
            $this->toolbar->addComponent($toolbarButton);

            $toolbarButton = $ui->button()->standard(
                $this->pl->txt('csv_export'),
                $this->ctrl->getLinkTarget($this, $this::CMD_EXPORT_CSV)
            );
            $this->toolbar->addComponent($toolbarButton);
        }

        $target_ref = 0;
        if ($this->cert_access->isSelfPrintEnabled() and !$this->cert_access->hasCurrentUserPrintAccess()) {
			$global_config_sets = ilParticipationCertificateConfig::where(array("config_type"=>3, "global_config_id" => 0 ))->orderBy('order_by')->get();
            foreach ($global_config_sets as $config) {
				if ($config->getConfigKey() == "true_name_helper") {
					$target_ref=$config->getConfigValue();
				}
			}
            $this->tpl->setOnScreenMessage('failure',$this->pl->txt('noname_noprint'), true);
	    if (is_numeric($target_ref) and ($target_ref > 0) and (ilObject::_lookupType(ilObject::_lookupObjectId($target_ref),false) == 'xudf')) {
                $msgurl = ' <a href="ilias.php?baseClass=ilObjPluginDispatchGUI&cmd=forward&ref_id=' . $target_ref . '">' .  $this->pl->txt('helper_name') . '</a>';
	    } else {
		$msgurl = ' <a href="ilias.php?baseClass=ilDashboardGUI&cmd=jumpToProfile">' . $this->pl->txt('helper_name') . '</a>';
	    }
            $msgadd= $this->pl->txt('helper_action_pre') . $msgurl . $this->pl->txt('helper_action_post');
            $this->tpl->setOnScreenMessage('info',$msgadd, true);
				//Variants sendQuestion, send Info or unified Failure (with some codechange). two same not possible
            
        }

        $tableHtml = $this->initTable((int) $this->courseRefId);
        $this->tpl->setContent($tableHtml);

        if (method_exists($this->tpl, 'printToStdout')) {
            $this->tpl->printToStdout();

        } else {
            $this->tpl->show();
        }
    }

    /**
     * @return void
     */
    private function exportExcel()
    {
        $resultTable = new ilParticipationCertificateResultTableGUI();

        $filterFirstname = '';
        $isFilterActive = false;
        $firstname = $this->fetchUrlParameter('filter_firstname', FILTER_DEFAULT);
        if (!empty($firstname)) {
            $filterFirstname = $firstname;
            $isFilterActive = true;
        }

        $filterLastname = '';
        $lastname = $this->fetchUrlParameter('filter_lastname', FILTER_DEFAULT);
        if (!empty($lastname)) {
            $filterLastname = $lastname;
            $isFilterActive = true;
        }

        if ($isFilterActive) {
            $resultTable->setFilter($filterFirstname, $filterLastname);
        }

        $data = $resultTable->records();

        $excel = new ilExcel();
        $excel->addSheet('TEST'
            ?: $this->lng->txt("export"));
        $row = 1;

        ob_start();
        $this->fillMetaExcel($excel, $row);

        // #14813
        $pre = $row;
        $this->fillHeaderExcel($excel, $row, $resultTable);
        if ($pre == $row) {
            $row++;
        }

        foreach ($data as $set) {
            $this->fillRowExcel($excel, $row, $set);
            $row++; // #14760
        }
        ob_end_clean();

        $filename = "export";
        $excel->sendToClient($filename);
    }

    private function exportCSV()
    {
        $resultTable = new ilParticipationCertificateResultTableGUI();

        $filterFirstname = '';
        $isFilterActive = false;
        $firstname = $this->fetchUrlParameter('filter_firstname', FILTER_DEFAULT);
        if (!empty($firstname)) {
            $filterFirstname = $firstname;
            $isFilterActive = true;
        }

        $filterLastname = '';
        $lastname = $this->fetchUrlParameter('filter_lastname', FILTER_DEFAULT);
        if (!empty($lastname)) {
            $filterLastname = $lastname;
            $isFilterActive = true;
        }

        if ($isFilterActive) {
            $resultTable->setFilter($filterFirstname, $filterLastname);
        }

        $data = $resultTable->records();
        $csv = new ilCSVWriter();
        $csv->setSeparator(";");

        ob_start();
        //$this->fillMetaCSV($csv);
        $this->fillHeaderCSV($csv, $resultTable);
        foreach ($data as $set) {
            $this->fillRowCSV($csv, $set);
        }
        ob_end_clean();

        $filename = "export.csv";
        header("Content-type: text/comma-separated-values");
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        echo $csv->getCSVString();
        exit();
    }

    protected function fillMetaExcel(ilExcel $a_excel, int &$a_row): void
    {
    }

    /**
     * Excel Version of Fill Header.
     *
     * @param	ilExcel	$a_excel excel wrapper
     * @param	int		$a_row   row counter
     */
    protected function fillHeaderExcel(ilExcel $a_excel, int &$a_row, $resultTable): void
    {
        $this->columns = [
            [
                'text' => 'invisible'
            ]
        ];
        
        $selectableColumns = $resultTable->getColumsForRepresentation();
        foreach ($selectableColumns as $column) {
            $this->columns[] = [
                'text' => $column->getTitle()
                ];
        }

        $col = 0;
        foreach ($this->columns as $column) {
            $title = strip_tags($column["text"]);
            if ($title) {
                $a_excel->setCell($a_row, $col++, $title);
            }
        }
        $a_excel->setBold("A" . $a_row . ":" . $a_excel->getColumnCoord($col - 1) . $a_row);
    }

    /**
     * Excel Version of Fill Row.
     *
     * @param	ilExcel $a_excel excel wrapper
     * @param	int     $a_row   row counter
     * @param	array   $a_set   data array
     */
    protected function fillRowExcel(ilExcel $a_excel, int &$a_row, array $a_set): void
    {
        $col = 0;

        foreach ($a_set as $key => $value) {

            if ($key !== 'usr_id') {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $a_excel->setCell($a_row, $col++, $value);
            }

        }
    }

    /**
     * CSV Version of Fill Header.
     *
     * @param	ilCSVWriter $a_csv current file
     */
    protected function fillHeaderCSV(ilCSVWriter $a_csv, $resultTable): void
    {
        $this->columns = [
            [
                'text' => 'invisible'
            ]
        ];

        $selectableColumns = $resultTable->getColumsForRepresentation();
        foreach ($selectableColumns as $column) {
            $this->columns[] = [
                'text' => $column->getTitle()
            ];
        }

        foreach ($this->columns as $column) {
            $title = strip_tags($column["text"]);
            if ($title) {
                $a_csv->addColumn($title);
            }
        }
        $a_csv->addRow();
    }

    /**
     * CSV Version of Fill Row.
     *
     * @param	ilCSVWriter $a_csv current file
     * @param	array       $a_set data array
     */
    protected function fillRowCSV(ilCSVWriter $a_csv, array $a_set): void
    {
        foreach ($a_set as $key => $value) {
            if ($key !== 'usr_id') {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $a_csv->addColumn(strip_tags($value));
            }
        }
        $a_csv->addRow();
    }

    /**
     * @throws ilCtrlException
     */
    public function initHeader(): void
    {
        $courseObject = ilObjectFactory::getInstanceByRefId($this->courseRefId);

        $this->tpl->setTitle($courseObject->getTitle());
        $this->tpl->setDescription($courseObject->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($courseObject->getId()));

        $this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', (int)$_GET['ref_id']);
        $this->tabs->setBackTarget($this->pl->txt('header_btn_back'), $this->ctrl->getLinkTargetByClass(array(
            ilRepositoryGUI::class
        )));
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, ['ref_id', 'group_id']);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateGUI::class, 'ref_id');

        $this->tabs->addTab(self::CMD_OVERVIEW, $this->pl->txt('header_overview'),
            $this->ctrl->getLinkTargetByClass(self::class, self::CMD_CONTENT));
        
        if ($this->cert_access->hasCurrentUserAdminAccess()) {
            $this->tabs->addTab(ilParticipationCertificateGUI::TAB_CONFIG, $this->pl->txt('header_config'),
                $this->ctrl->getLinkTargetByClass(ilParticipationCertificateGUI::class,
                    ilParticipationCertificateGUI::CMD_CONFIG));
        }
        $this->tabs->activateTab(self::CMD_OVERVIEW);
    }

    /**
     * @throws ilCtrlException
     */
    protected function initTable(): string
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        $resultTable = new ilParticipationCertificateResultTableGUI();
        
        $filterHtml = '';
        $filterFirstname = '';
        $filterLastname = '';

        if ($this->cert_access->hasCurrentUserWriteAccess()) {
            $filter = $resultTable->buildFilter();
            $filterData = $DIC->uiService()->filter()->getData($filter);

            if (!empty($filterData)) {
                $filterFirstname = $filterData['firstname'];
                $filterLastname = $filterData['lastname'];
            }
            $filterHtml .= $renderer->render($filter);
        }

        $table = $resultTable->getTableForRepresentation(
            $filterFirstname,
            $filterLastname
        );

        $tableHtml = $renderer->render($table->withRequest($DIC->http()->request()));

        return $filterHtml . $tableHtml;
    }

    /**
     * @throws ilCtrlException
     * @throws ilTemplateException
     */
    public function action(): void
    {
        $action = $_GET['config_action'];

        if (!empty($action)) {
            switch ($action) {
                case 'print_with_ementorining':
		    $this->printPdf(true);
		    break;
                case 'print_without_ementorining':
                    $this->printPdf(false);
                    break;

                case 'show_all_results':
                    if (!empty($_GET['config_entry'])) {
                        $usrId = explode('_', $_GET['config_entry'][0])[0];
                        $this->ctrl->setParameterByClass(ilParticipationCertificateResultGUI::class, 'usr_id', $usrId);
                    }
                    $singleResultGui = new ilParticipationCertificateSingleResultGUI();
                    $singleResultGui->display();
                    break;

                case 'show_selected_all_results':
                    $userIds = [];

                    $configEntries = $_GET['config_entry'];
                    if (!empty($configEntries)) {
                        if ($configEntries[0] === 'ALL_OBJECTS') {
                            $resultTable = new ilParticipationCertificateResultTableGUI();
                            $data = $resultTable->records();
                            $userIds = array_column($data, 'usr_id');
                        } else {
                            $userIds = $this->excludeUserIdsFromUrlParameters($configEntries);
                        }
                    }

                    new ilParticipationCertificateMultipleResultGUI($userIds, $this->courseRefId);
                    break;
                case 'adjust_results':
                    $resultModificationGui = new ilParticipationCertificateResultModificationGUI();
                    $resultModificationGui->display();
                    break;

                case 'print_selected_with_ementorining':
                    $this->printSelected();
                    break;

                case 'print_selected_without_ementorining':
                    $this->printSelectedWithoutEmentoring();
                    break;
            }
        }
    }

    /**
     * @param array $configEntries
     * @return array
     */
    private function excludeUserIdsFromUrlParameters(array $configEntries): array
    {
        $userIds = [];
        if (!empty($configEntries)) {
            foreach ($configEntries as $configEntry) {
                $usrId = explode('_', $configEntry)[0];
                $userIds[] = $usrId;
            }
        }
        return $userIds;
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    public function printPdf(?bool $ementoring = null): void
    {
        global $DIC;

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $ementor = false;
            $usr_id = [];
            if (!empty($_GET['config_entry'])) {
                $urlParameters = $this->excludeURLParameters($_GET['config_entry'][0]);
                $userId = $urlParameters[0];
                $ementor = (bool) $urlParameters[1];
                $usr_id[] = $userId;
            } else {
                if ($_GET['ementor'] == 'true') {
			        $ementor = true;
		         }
                $cert_access = new ilParticipationCertificateAccess($this->courseRefId);
                $userIds = $cert_access->getUserIdsOfGroup();
                if (empty($usr_id)) {
                    $usr_id = $userIds;
                }
            }

            if ($ementoring !== null) {
                $ementor = $ementoring;
            }
		
            if (!empty($usr_id)) {
                $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_id);
                $usr_id = $this->excludeUserIfDataMissing($usr_id, $arr_usr_data);
            }

            // Redirect if selected user's data or all users' data are missing
            if (empty($usr_id)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('user_data_missing'));
            }

            $twigParser = new ilParticipationCertificateTwigParser(
                $this->courseRefId,
                $usr_id,
                $ementor,
                false
            );

            $twigParser->parseData(
                false,
                false,
                $this->courseRefId
            );
        } else {
            $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_permission'), true);
            $DIC->ctrl()->redirectToURL('login.php');
        }
    }

    /**
     * @throws arException
     * @throws SyntaxError
     * @throws ilCtrlException
     * @throws LoaderError
     * @throws ilDateTimeException
     */
    public function printSelected(): void
    {
        global $DIC;

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $configEntries = $_GET['config_entry'];

            if (empty($configEntries)) {
                $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }

            $usr_ids = [];

            if ($configEntries[0] === 'ALL_OBJECTS') {
                $resultTable = new ilParticipationCertificateResultTableGUI();
                $data = $resultTable->records();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($configEntries as $entry) {
                    $urlParameters = $this->excludeURLParameters($entry);
                    $userId = $urlParameters[0];
                    $usr_ids[] = $userId;
                }
            }

            $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_ids);
            $usr_ids = $this->excludeUserIfDataMissing($usr_ids, $arr_usr_data);

            if(empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('all_user_data_missing'));
            }

            $twigParser = new ilParticipationCertificateTwigParser(
                $this->courseRefId,
                (array) $usr_ids,
                true,
                false
            );

            $twigParser->parseData(
                false,
                false,
                $this->courseRefId
            );
        } else {
            $DIC->ctrl()->redirectToURL('login.php');
        }
    }

    /**
     * @throws arException
     * @throws ilCtrlException
     * @throws SyntaxError
     * @throws ilDateTimeException
     * @throws LoaderError
     * @throws Exception
     */
    public function printSelectedWithouteMentoring(): void
    {
        global $DIC;

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $configEntries = $_GET['config_entry'];

            if (empty($configEntries)) {
                $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }
            $usr_ids = [];

            if ($configEntries[0] === 'ALL_OBJECTS') {
                $resultTable = new ilParticipationCertificateResultTableGUI();
                $data = $resultTable->records();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($configEntries as $entry) {
                    $urlParameters = $this->excludeURLParameters($entry);
                    $userId = $urlParameters[0];
                    $usr_ids[] = $userId;
                }
            }

            $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_ids);
            $usr_ids = $this->excludeUserIfDataMissing($usr_ids, $arr_usr_data);

            if(empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('all_user_data_missing'));
            }

            $twigParser = new ilParticipationCertificateTwigParser(
                $this->courseRefId,
                $usr_ids,
                false,
                false
            );

            $twigParser->parseData(
                false,
                false,
                $this->courseRefId
            );
        } else {
            $DIC->ctrl()->redirectToURL('login.php');
        }
    }

    /**
     * @throws ilCtrlException
     */
    public function applyFilter(): void
    {
        global $DIC;

        $resultTable = new ilParticipationCertificateResultTableGUI();
        $filter = $resultTable->buildFilter();
        $filterData = $DIC->uiService()->filter()->getData($filter);

        if (!empty($filterData['firstname'])) {
            $this->ctrl->setParameterByClass(self::class, 'filter_firstname', $filterData['firstname']);
        }

        if (!empty($filterData['lastname'])) {
            $this->ctrl->setParameterByClass(self::class, 'filter_lastname', $filterData['lastname']);
        }
        $this->ctrl->redirect($this, self::CMD_CONTENT);
    }

    /**
     * @throws ilCtrlException
     */
    public function resetFilter(): void
    {
        $this->ctrl->redirect($this, self::CMD_CONTENT);
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
     * @param array $usr_id
     * @param array $arr_usr_data
     * @return array
     */
    private function excludeUserIfDataMissing(array $usr_id, array $arr_usr_data): array
    {
        $user_data = new ilPartCertUserData();
        foreach ($usr_id as $key => $id) {
            if(!$user_data->checkIfUserDataFilled(
                $arr_usr_data[$id]->getPartCertSalutation(),
                $arr_usr_data[$id]->getPartCertFirstname(),
                $arr_usr_data[$id]->getPartCertLastname()
            )) {
                unset($usr_id[$key]);
            }
        }
        return array_values($usr_id);
    }

    /**
     * @param string $parameter
     * @return string[]
     */
    private function excludeURLParameters(string $parameter): array
    {
        return explode('_', $parameter);
    }

    /**
     * @param string $param
     * @param int    $filter
     * @return mixed
     */
    protected function fetchUrlParameter(string $param, int $filter): mixed
    {
        return filter_input(INPUT_GET, $param, $filter);
    }
}
