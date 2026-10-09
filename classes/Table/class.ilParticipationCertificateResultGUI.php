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
    public const string CMD_CONTENT = 'content';

    public const string CMD_OVERVIEW = 'overview';

    public const string CMD_PRINT_PDF = 'printpdf';

    public const string CMD_PRINT_SELECTED_WITHOUTE_MENTORING = 'printSelectedWithouteMentoring';

    public const string CMD_PRINT_SELECTED = 'printSelected';

    public const string CMD_INIT_TABLE = 'initTable';

    public const string CMD_EXPORT_EXCEL = 'exportExcel';

    public const string CMD_EXPORT_CSV = 'exportCSV';

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
    protected int $course_ref_id;

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
        $this->course_ref_id = (int) $refId;
        $this->lng = $DIC->language();
	    $this->cert_access = new ilParticipationCertificateAccess($refId);
	    
        $ementoring = ilParticipationCertificateConfig::getConfig('enable_ementoring', $this->course_ref_id);
        if ($ementoring === NULL) {
            $this->ementoring = true;
        } else {
            $this->ementoring = boolval($ementoring);
        }
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, ['ref_id', 'group_id']);
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        global $DIC;

        $cert_access = new ilParticipationCertificateAccess($this->course_ref_id);
        if (!$cert_access->hasCurrentUserWriteAccess()) {
            $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_permission'), true);
            $DIC->ctrl()->redirectToURL('login.php');
        }
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case strtolower(ilParticipationCertificateResultModificationGUI::class):
                $il_participation_certificate_result_modification_gui = new ilParticipationCertificateResultModificationGUI();
                $ret1 = $this->ctrl->forwardCommand($il_participation_certificate_result_modification_gui);
                break;
            case strtolower(ilParticipationCertificateGUI::class):
                $il_participation_certificate_gui = new ilParticipationCertificateGUI();
                $ret2 = $this->ctrl->forwardCommand($il_participation_certificate_gui);
                $this->tabs->activateTab(self::CMD_OVERVIEW);
                break;
            case strtolower(ilparticipationcertificatesingleresultgui::class):
                $il_participation_certificate_result_overview_gui = new ilParticipationCertificateSingleResultGUI();
                $ret3 = $this->ctrl->forwardCommand($il_participation_certificate_result_overview_gui);
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

            // TODO remove it
            $this->tpl->getStandardTemplate();
        }
        $this->initHeader();

        $ui = $DIC->ui()->factory();

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $this->tpl->setOnScreenMessage('info',$this->pl->txt('print_all_info'),true);

            if ($this->ementoring) {
                $this->ctrl->setParameter($this, 'ementor', true);
                $toolbar_button = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_is_ementoring'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbar_button);

                $this->ctrl->setParameter($this, 'ementor', false);
                $toolbar_button = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_no_ementoring'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbar_button);
            } else {
                $this->ctrl->setParameter($this, 'ementor', false);
                $toolbar_button = $ui->button()->standard(
                    $this->pl->txt('header_btn_print'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbar_button);
            }

            if (!empty($_GET['filter_firstname'])) {
                $this->ctrl->setParameter($this, 'filter_firstname', $_GET['filter_firstname']);
            }

            if (!empty($_GET['filter_lastname'])) {
                $this->ctrl->setParameter($this, 'filter_lastname', $_GET['filter_lastname']);
            }

            $toolbar_button = $ui->button()->standard(
                $this->pl->txt('excel_export'),
                $this->ctrl->getLinkTarget($this, self::CMD_EXPORT_EXCEL)
            );
            $this->toolbar->addComponent($toolbar_button);

            $toolbar_button = $ui->button()->standard(
                $this->pl->txt('csv_export'),
                $this->ctrl->getLinkTarget($this, $this::CMD_EXPORT_CSV)
            );
            $this->toolbar->addComponent($toolbar_button);
        }

        $target_ref = 0;
        if ($this->cert_access->isSelfPrintEnabled() and !$this->cert_access->hasCurrentUserPrintAccess()) {
			$global_config_sets = ilParticipationCertificateConfig::where(['config_type'=>3, 'global_config_id' => 0 ])->orderBy('order_by')->get();
            foreach ($global_config_sets as $config) {
				if ($config->getConfigKey() == 'true_name_helper') {
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

        $table_html = $this->initTable((int) $this->course_ref_id);
        $this->tpl->setContent($table_html);

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
        /*$result_table = new ilParticipationCertificateResultTableGUI();

        $filter_firstname = '';
        $is_filter_active = false;
        $firstname = $this->fetchUrlParameter('filter_firstname', FILTER_DEFAULT);
        if (!empty($firstname)) {
            $filter_firstname = $firstname;
            $is_filter_active = true;
        }

        $filter_lastname = '';
        $lastname = $this->fetchUrlParameter('filter_lastname', FILTER_DEFAULT);
        if (!empty($lastname)) {
            $filter_lastname = $lastname;
            $is_filter_active = true;
        }

        if ($is_filter_active) {
            $result_table->setFilter($filter_firstname, $filter_lastname);
        }

        $data = $result_table->records();

        $excel = new ilExcel();
        $excel->addSheet('TEST'
            ?: $this->lng->txt('export'));
        $row = 1;

        ob_start();
        $this->fillMetaExcel($excel, $row);

        // #14813
        $pre = $row;
        $this->fillHeaderExcel($excel, $row, $result_table);
        if ($pre == $row) {
            $row++;
        }

        foreach ($data as $set) {
            $this->fillRowExcel($excel, $row, $set);
            $row++; // #14760
        }
        ob_end_clean();

        $filename = 'export';
        $excel->sendToClient($filename);*/
    }

    private function exportCSV()
    {
        /*$result_table = new ilParticipationCertificateResultTableGUI();

        $filter_firstname = '';
        $is_filter_active = false;
        $firstname = $this->fetchUrlParameter('filter_firstname', FILTER_DEFAULT);
        if (!empty($firstname)) {
            $filter_firstname = $firstname;
            $is_filter_active = true;
        }

        $filter_lastname = '';
        $lastname = $this->fetchUrlParameter('filter_lastname', FILTER_DEFAULT);
        if (!empty($lastname)) {
            $filter_lastname = $lastname;
            $is_filter_active = true;
        }

        if ($is_filter_active) {
            $result_table->setFilter($filter_firstname, $filter_lastname);
        }

        $data = $result_table->records();
        $csv = new ilCSVWriter();
        $csv->setSeparator(';');

        ob_start();
        $this->fillHeaderCSV($csv, $result_table);
        foreach ($data as $set) {
            $this->fillRowCSV($csv, $set);
        }
        ob_end_clean();

        $filename = 'export.csv';
        header('Content-type: text/comma-separated-values');
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
        header('Pragma: public');
        echo $csv->getCSVString();
        exit();*/
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
        
        $selectable_columns = $resultTable->getColumsForRepresentation();
        foreach ($selectable_columns as $column) {
            $this->columns[] = [
                'text' => $column->getTitle()
                ];
        }

        $col = 0;
        foreach ($this->columns as $column) {
            $title = strip_tags($column['text']);
            if ($title) {
                $a_excel->setCell($a_row, $col++, $title);
            }
        }
        $a_excel->setBold('A' . $a_row . ':' . $a_excel->getColumnCoord($col - 1) . $a_row);
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

        $selectable_columns = $resultTable->getColumsForRepresentation();
        foreach ($selectable_columns as $column) {
            $this->columns[] = [
                'text' => $column->getTitle()
            ];
        }

        foreach ($this->columns as $column) {
            $title = strip_tags($column['text']);
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
        $course_object = ilObjectFactory::getInstanceByRefId($this->course_ref_id);

        $this->tpl->setTitle($course_object->getTitle());
        $this->tpl->setDescription($course_object->getDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon($course_object->getId()));

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

        $result_table = new ilParticipationCertificateResultTableGUI();
        
        $filter_html = '';
        $filter_firstname = '';
        $filter_lastname = '';

        if ($this->cert_access->hasCurrentUserWriteAccess()) {
            $filter = $result_table->buildFilter();
            $filter_data = $DIC->uiService()->filter()->getData($filter);

            if (!empty($filter_data)) {
                $filter_firstname = $filter_data['firstname'];
                $filter_lastname = $filter_data['lastname'];
            }
            $filter_html .= $renderer->render($filter);
        }

        $table = $result_table->getTableForRepresentation(
            $filter_firstname,
            $filter_lastname
        );

        $table_html = $renderer->render($table->withRequest($DIC->http()->request()));

        return $filter_html . $table_html;
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
                    $single_result_gui = new ilParticipationCertificateSingleResultGUI();
                    $single_result_gui->display();
                    break;

                case 'show_selected_all_results':
                    $user_ids = [];

                    $config_entries = $_GET['config_entry'];
                    /*if (!empty($config_entries)) {
                        if ($config_entries[0] === 'ALL_OBJECTS') {
                            $resultTable = new ilParticipationCertificateResultTableGUI();
                            $data = $resultTable->records();
                            $user_ids = array_column($data, 'usr_id');
                        } else {
                            $user_ids = $this->excludeUserIdsFromUrlParameters($config_entries);
                        }
                    }

                    new ilParticipationCertificateMultipleResultGUI($user_ids, $this->course_ref_id);*/
                    break;
                case 'adjust_results':
                    $result_modification_gui = new ilParticipationCertificateResultModificationGUI();
                    $result_modification_gui->display();
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
     * @param array $config_entries
     * @return array
     */
    private function excludeUserIdsFromUrlParameters(array $config_entries): array
    {
        $user_ids = [];
        if (!empty($config_entries)) {
            foreach ($config_entries as $config_entry) {
                $user_id = explode('_', $config_entry)[0];
                $user_ids[] = $user_id;
            }
        }
        return $user_ids;
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
                $url_parameters = $this->excludeURLParameters($_GET['config_entry'][0]);
                $user_id = $url_parameters[0];
                $ementor = (bool) $url_parameters[1];
                $usr_id[] = $user_id;
            } else {
                if ($_GET['ementor'] == 'true') {
			        $ementor = true;
		         }
                $cert_access = new ilParticipationCertificateAccess($this->course_ref_id);
                $user_ids = $cert_access->getUserIdsOfGroup();
                if (empty($usr_id)) {
                    $usr_id = $user_ids;
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

            $twig_parser = new ilParticipationCertificateTwigParser(
                $this->course_ref_id,
                $usr_id,
                $ementor,
                false
            );

            $twig_parser->parseData(
                false,
                false,
                $this->course_ref_id
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
            /*$config_entries = $_GET['config_entry'];

            if (empty($config_entries)) {
                $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }

            $usr_ids = [];

            if ($config_entries[0] === 'ALL_OBJECTS') {
                $result_table = new ilParticipationCertificateResultTableGUI();
                $data = $result_table->records();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($config_entries as $entry) {
                    $url_parameters = $this->excludeURLParameters($entry);
                    $user_id = $url_parameters[0];
                    $usr_ids[] = $user_id;
                }
            }

            $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_ids);
            $usr_ids = $this->excludeUserIfDataMissing($usr_ids, $arr_usr_data);

            if(empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('all_user_data_missing'));
            }

            $twig_parser = new ilParticipationCertificateTwigParser(
                $this->course_ref_id,
                (array) $usr_ids,
                true,
                false
            );

            $twig_parser->parseData(
                false,
                false,
                $this->course_ref_id
            );*/
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
           /* $config_entries = $_GET['config_entry'];

            if (empty($config_entries)) {
                $this->tpl->setOnScreenMessage('failure',$this->lng->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }
            $usr_ids = [];

            if ($config_entries[0] === 'ALL_OBJECTS') {
                $result_table = new ilParticipationCertificateResultTableGUI();
                $data = $result_table->records();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($config_entries as $entry) {
                    $url_parameters = $this->excludeURLParameters($entry);
                    $user_id = $url_parameters[0];
                    $usr_ids[] = $user_id;
                }
            }

            $arr_usr_data = ilPartCertUsersData::getData($this->pl, $usr_ids);
            $usr_ids = $this->excludeUserIfDataMissing($usr_ids, $arr_usr_data);

            if(empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('all_user_data_missing'));
            }

            $twig_parser = new ilParticipationCertificateTwigParser(
                $this->course_ref_id,
                $usr_ids,
                false,
                false
            );

            $twig_parser->parseData(
                false,
                false,
                $this->course_ref_id
            );*/
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

        /*$result_table = new ilParticipationCertificateResultTableGUI();
        $filter = $result_table->buildFilter();
        $filter_data = $DIC->uiService()->filter()->getData($filter);

        if (!empty($filter_data['firstname'])) {
            $this->ctrl->setParameterByClass(self::class, 'filter_firstname', $filter_data['firstname']);
        }*/

        if (!empty($filter_data['lastname'])) {
            $this->ctrl->setParameterByClass(self::class, 'filter_lastname', $filter_data['lastname']);
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
