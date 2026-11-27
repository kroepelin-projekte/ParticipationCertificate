<?php

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;
use JetBrains\PhpStorm\NoReturn;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;
use  SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

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

    public const string CMD_ACTION = 'action';

    public const string CMD_APPLY_FILTER = 'applyFilter';

    public const string CMD_RESET_FILTER = 'resetFilter';

    /**
     * Commands that may be called via the request (exact case).
     * Everything else falls back to CMD_CONTENT.
     */
    private const array COMMANDS = [
        self::CMD_CONTENT,
        self::CMD_ACTION,
        self::CMD_PRINT_PDF,
        self::CMD_EXPORT_EXCEL,
        self::CMD_EXPORT_CSV,
        self::CMD_APPLY_FILTER,
        self::CMD_RESET_FILTER,
    ];

    /** @var array<string, string> export column key => title */
    private array $columns = [];

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

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->course_ref_id = (int) (ilParticipationCertificateHelper::fetchUrlParameter('ref_id', 'string') ?? 0);

        if ($this->course_ref_id <= 0) {
            $this->redirectNoPermission();
        }

        $this->toolbar = $DIC->toolbar();
        $this->tabs = $DIC->tabs();
        $this->pl = ilParticipationCertificatePlugin::getInstance();
	    $this->cert_access = new ilParticipationCertificateAccess($this->course_ref_id);
	    
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
            $this->redirectNoPermission();
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
                    default:
                        if (!in_array($cmd, self::COMMANDS, true)) {
                            $cmd = self::CMD_CONTENT;
                        }
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

        $this->tpl->addCss('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/css/participation-certificate.css');

        $this->tpl->loadStandardTemplate();
        $this->initHeader();

        $ui = $DIC->ui()->factory();

        if ($this->cert_access->hasCurrentUserPrintAccess()) {
            $this->tpl->setOnScreenMessage('info',$this->pl->txt('print_all_info'),true);

            if ($this->ementoring) {
                $this->ctrl->setParameter($this, 'ementor', true);
                $toolbar_button = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_is_eMentoring'),
                    $this->ctrl->getLinkTarget($this, $this::CMD_PRINT_PDF)
                );
                $this->toolbar->addComponent($toolbar_button);

                $this->ctrl->setParameter($this, 'ementor', false);
                $toolbar_button = $ui->button()->standard(
                    $this->pl->txt('header_btn_print_no_eMentoring'),
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

            $filter_firstname = ilParticipationCertificateHelper::fetchUrlParameter('filter_firstname', 'string');
            if (!empty($filter_firstname)) {
                $this->ctrl->setParameter($this, 'filter_firstname', $filter_firstname);
            }

            $filter_lastname = ilParticipationCertificateHelper::fetchUrlParameter('filter_lastname', 'string');
            if (!empty($filter_lastname)) {
                $this->ctrl->setParameter($this, 'filter_lastname', $filter_lastname);
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

        if ($this->cert_access->isSelfPrintEnabled() && !$this->cert_access->hasCurrentUserPrintAccess()) {
            $this->tpl->setOnScreenMessage('failure',$this->pl->txt('noname_noprint'), true);
            $msgurl = ' <a href="ilias.php?baseClass=ilDashboardGUI&cmd=jumpToProfile">' . $this->pl->txt('helper_name') . '</a>';

            $msgadd = $this->pl->txt('helper_action_pre') . $msgurl . $this->pl->txt('helper_action_post');
            $this->tpl->setOnScreenMessage('info',$msgadd, true);
            //Variants sendQuestion, send Info or unified Failure (with some codechange). two same not possible
        }

        $table_html = $this->initTable();
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
        $result_table = new ilParticipationCertificateResultTableGUI();

        $filter_firstname = '';
        $is_filter_active = false;
        $firstname = ilParticipationCertificateHelper::fetchUrlParameter('filter_firstname', 'string');
        if (!empty($firstname)) {
            $filter_firstname = $firstname;
            $is_filter_active = true;
        }

        $filter_lastname = '';
        $lastname = ilParticipationCertificateHelper::fetchUrlParameter('filter_lastname', 'string');
        if (!empty($lastname)) {
            $filter_lastname = $lastname;
            $is_filter_active = true;
        }

        if ($is_filter_active) {
            $result_table->setFilter($filter_firstname, $filter_lastname);
        }

        $data = $result_table->getRecords();

        $excel = new ilExcel();
        $excel->addSheet($this->pl->txt('tbl_overview_results'));
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
        $excel->sendToClient($filename);
    }

    /**
     * @return void
     */
    #[NoReturn]
    private function exportCSV()
    {
        $result_table = new ilParticipationCertificateResultTableGUI();

        $filter_firstname = '';
        $is_filter_active = false;
        $firstname = ilParticipationCertificateHelper::fetchUrlParameter('filter_firstname', 'string');
        if (!empty($firstname)) {
            $filter_firstname = $firstname;
            $is_filter_active = true;
        }

        $filter_lastname = '';
        $lastname = ilParticipationCertificateHelper::fetchUrlParameter('filter_lastname', 'string');
        if (!empty($lastname)) {
            $filter_lastname = $lastname;
            $is_filter_active = true;
        }

        if ($is_filter_active) {
            $result_table->setFilter($filter_firstname, $filter_lastname);
        }

        $data = $result_table->getRecords();
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
    protected function fillHeaderExcel(ilExcel $a_excel, int &$a_row, ilParticipationCertificateResultTableGUI $resultTable): void
    {
        $this->initExportColumns($resultTable);

        $col = 0;
        foreach ($this->columns as $title) {
            $a_excel->setCell($a_row, $col++, $title);
        }
        $a_excel->setBold('A' . $a_row . ':' . $a_excel->getColumnCoord($col - 1) . $a_row);
    }

    /**
     * Header and rows are built from the same column keys, so every value stays under its title
     * (records may lack keys, e.g. suggestions_generated_at, or contain keys without a column).
     */
    private function initExportColumns(ilParticipationCertificateResultTableGUI $result_table): void
    {
        $this->columns = [];
        foreach ($result_table->getColumsForRepresentation() as $key => $column) {
            $this->columns[$key] = strip_tags($column->getTitle());
        }
    }

    /**
     * Record value as plain export text: date formatted, list joined, HTML (e.g. progress bars) removed.
     */
    private function exportValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $value = preg_replace('#<br\s*/?>#i', "\n", (string) $value);
        // table records are HTML-escaped for the web table
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // progress bar markup leaves template placeholders and indentation behind
        $value = preg_replace('/\{[A-Z0-9_]+\}/', '', $value);
        $lines = array_filter(
            // \x{00A0}: &nbsp; from the markup
            array_map(static fn(string $line): string => trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $line)), explode("\n", $value)),
            static fn(string $line): bool => $line !== ''
        );

        return implode("\n", $lines);
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
        foreach (array_keys($this->columns) as $key) {
            $a_excel->setCell($a_row, $col++, $this->exportValue($a_set[$key] ?? ''));
        }
    }

    /**
     * CSV Version of Fill Header.
     *
     * @param	ilCSVWriter $a_csv current file
     */
    protected function fillHeaderCSV(ilCSVWriter $a_csv, ilParticipationCertificateResultTableGUI $resultTable): void
    {
        $this->initExportColumns($resultTable);

        foreach ($this->columns as $title) {
            $a_csv->addColumn($this->neutralizeCsvFormula($title));
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
        foreach (array_keys($this->columns) as $key) {
            $a_csv->addColumn($this->neutralizeCsvFormula($this->exportValue($a_set[$key] ?? '')));
        }
        $a_csv->addRow();
    }

    /**
     * CSV/formula injection: spreadsheet apps execute cells starting with = + - @ (or TAB/CR).
     * Names come from user-editable profile fields, so such text cells get a leading apostrophe.
     * Plain numbers (e.g. "-5") are left untouched.
     */
    private function neutralizeCsvFormula(string $value): string
    {
        if ($value !== '' && strpbrk($value[0], "=+-@\t\r") !== false && !is_numeric($value)) {
            return "'" . $value;
        }
        return $value;
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
        $ref_id = ilParticipationCertificateHelper::fetchUrlParameter('ref_id', 'string');

        $this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', (int) $ref_id);
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
        $action = ilParticipationCertificateHelper::fetchUrlParameter('config_action', 'string');

        if (!empty($action)) {
            switch ($action) {
                case 'print_with_ementorining':
		        $this->printPdf(true);
		        break;

                case 'print_without_ementorining':
                    $this->printPdf(false);
                    break;

                case 'show_all_results':
                    $config_entry = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

                    if (!empty($config_entry)) {
                        $usrId = explode('_', $config_entry[0])[0];

                        if ($this->restrictToCourseMembers([$usrId]) === []) {
                            $this->redirectWithError(self::CMD_CONTENT, $this->lng->txt('no_permission'));
                        }
                        $this->ctrl->setParameterByClass(ilParticipationCertificateResultGUI::class, 'usr_id', $usrId);
                    }
                    $single_result_gui = new ilParticipationCertificateSingleResultGUI();
                    $single_result_gui->display();
                    break;

                case 'show_selected_all_results':
                    $user_ids = [];

                    $config_entries = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

                    if (!empty($config_entries)) {
                        if ($config_entries[0] === 'ALL_OBJECTS') {
                            $resultTable = new ilParticipationCertificateResultTableGUI();
                            $data = $resultTable->getRecords();
                            $user_ids = array_column($data, 'usr_id');
                        } else {
                            $user_ids = $this->excludeUserIdsFromUrlParameters($config_entries);
                        }
                    }

                    $user_ids = $this->restrictToCourseMembers($user_ids);

                    new ilParticipationCertificateMultipleResultGUI($user_ids, $this->course_ref_id);
                    break;
                case 'adjust_results':
                    $result_modification_gui = new ilParticipationCertificateResultModificationGUI();
                    $result_modification_gui->display();
                    break;

                case 'print_selected_with_ementorining':
                    $this->printSelected();
                    break;

                case 'print_selected_without_ementorining':
                    $this->printSelectedWithouteMentoring();
                    break;

                case 'suggestions_regenerate':
                    $this->suggestionsRegenerate();
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

            $config_entry = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

            if (!empty($config_entry)) {
                $url_parameters = $this->excludeURLParameters($config_entry[0]);
                $user_id = $url_parameters[0];
                $ementor = (bool) $url_parameters[1];
                $usr_id = $this->restrictToCourseMembers([$user_id]);
                if (empty($usr_id)) {
                    $this->redirectWithError(self::CMD_CONTENT, $this->lng->txt('no_permission'));
                }
            } else {
                $ementor_url_parameter = ilParticipationCertificateHelper::fetchUrlParameter('ementor', 'string');
                if ($ementor_url_parameter == 'true') {
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
            $this->redirectNoPermission();
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
            $config_entries = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

            if (empty($config_entries)) {
                $this->tpl->setOnScreenMessage('failure',$this->pl->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }

            $usr_ids = [];

            if ($config_entries[0] === 'ALL_OBJECTS') {
                $result_table = new ilParticipationCertificateResultTableGUI();
                $data = $result_table->getRecords();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($config_entries as $entry) {
                    $url_parameters = $this->excludeURLParameters($entry);
                    $user_id = $url_parameters[0];
                    $usr_ids[] = $user_id;
                }
            }

            $usr_ids = $this->restrictToCourseMembers($usr_ids);
            if (empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->lng->txt('no_permission'));
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
            );
        } else {
            $this->redirectNoPermission();
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
            $config_entries = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

            if (empty($config_entries)) {
                $this->tpl->setOnScreenMessage('failure',$this->pl->txt('no_records_selected'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }
            $usr_ids = [];

            if ($config_entries[0] === 'ALL_OBJECTS') {
                $result_table = new ilParticipationCertificateResultTableGUI();
                $data = $result_table->getRecords();
                $usr_ids = array_column($data, 'usr_id');
            } else {
                foreach ($config_entries as $entry) {
                    $url_parameters = $this->excludeURLParameters($entry);
                    $user_id = $url_parameters[0];
                    $usr_ids[] = $user_id;
                }
            }

            $usr_ids = $this->restrictToCourseMembers($usr_ids);
            if (empty($usr_ids)) {
                $this->redirectWithError(self::CMD_CONTENT, $this->lng->txt('no_permission'));
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
            );
        } else {
            $this->redirectNoPermission();
        }
    }

    private function suggestionsRegenerate(): void
    {
        global $DIC;

        $config_entries = ilParticipationCertificateHelper::fetchUrlParameter('config_entry', 'string[]');

        if (empty($config_entries) || empty($config_entries[0])) {
            $this->redirectWithError(self::CMD_CONTENT, $this->pl->txt('no_records_selected'));
        }

        $url_parameters = $this->excludeURLParameters($config_entries[0]);

        $member_ids = $this->restrictToCourseMembers([(int) $url_parameters[0]]);
        if ($member_ids === []) {
            $this->redirectWithError(self::CMD_CONTENT, $this->lng->txt('no_permission'));
        }
        $user_id = $member_ids[0];

        $obj_id = ilObject::_lookupObjectId($this->course_ref_id);
        $settings = ilLOSettings::getInstanceByObjId($obj_id);
        $initial_test_ref_id = $settings->getInitialTest();

        if (empty($initial_test_ref_id)) {
            $this->tpl->setOnScreenMessage('failure', $this->pl->txt('initial_test_missing') . ':' . $this->course_ref_id, true);
            $this->ctrl->redirect($this, self::CMD_CONTENT);
        }

        $test = new ilObjTest($initial_test_ref_id, true);

        $test_ref_ids = ilObject::_getAllReferences($test->getId());
        $learning_objective_suggestion = new LearningObjectiveSuggestion();
        $active_participants = [];
        if ($test->participantDataExist()) {
            $active_participants_list = $test->getActiveParticipantList();
            $active_participants = $this->removeInactiveUsers($active_participants_list->getAllUserIds());
        }

        $learning_suggestion_exists = $learning_objective_suggestion->suggestionExists(
            $user_id,
            $obj_id
        );

        if (!empty($active_participants) && in_array($user_id, $active_participants)) {
            if ($learning_suggestion_exists) {
                $this->tpl->setOnScreenMessage('failure', $this->pl->txt('learning_suggestions_exist'), true);
                $this->ctrl->redirect($this, self::CMD_CONTENT);
            }
        } else {
            $this->tpl->setOnScreenMessage('failure', $this->pl->txt('no_active_participant'), true);
            $this->ctrl->redirect($this, self::CMD_CONTENT);
        }

        $parent_found = false;
        $parent = 0;
        foreach ($test_ref_ids as $test_ref_id) {
            $parent = $DIC->repositoryTree()->getParentId($test_ref_id);

            if ($parent === $this->course_ref_id) {
                $parent_found = true;
                break;
            }
        }

        $screen_message_type = 'failure';
        $screen_message = $this->pl->txt('learning_suggestions_not_generated');
        if ($parent_found && ilObject::_lookupType($parent, true) === 'crs') {
            $course = new LearningObjectiveCourse(new ilObjCourse($parent, true));

            if (!$course->getIsCalculationInactive()) {
                $user = new User(new ilObjUser($user_id));
                $learning_objective_suggestion_plugin = ilLearningObjectiveSuggestionsPlugin::getInstance();
                if ($learning_objective_suggestion_plugin->startCalculation($course, $user)) {
                    $screen_message_type = 'success';
                    $screen_message = $this->pl->txt('learning_suggestions_generated');
                    $learning_objective_suggestion_plugin->sendSuggestions($course, $user);
                }
            }
        }

        $this->tpl->setOnScreenMessage($screen_message_type, $screen_message, true);
        $this->ctrl->redirect($this, self::CMD_CONTENT);
    }

    /**
     * @param array $user_ids
     * @return array
     */
    private function removeInactiveUsers(array $user_ids): array
    {
        foreach ($user_ids as $key => $user_id) {
            if (!ilObjUser::_lookupActive($user_id)) {
                unset($user_ids[$key]);
            }
        }
        return array_values($user_ids);
    }

    /**
     * @throws ilCtrlException
     */
    public function applyFilter(): void
    {
        global $DIC;

        $result_table = new ilParticipationCertificateResultTableGUI();
        $filter = $result_table->buildFilter();
        $filter_data = $DIC->uiService()->filter()->getData($filter);

        if (!empty($filter_data['firstname'])) {
            $this->ctrl->setParameterByClass(self::class, 'filter_firstname', $filter_data['firstname']);
        }

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
    /**
     * No permission: back to the course/group (or the dashboard without ref_id)
     * with an error message. Never redirects to this GUI itself, to avoid a loop.
     *
     * @throws ilCtrlException
     */
    private function redirectNoPermission(): void
    {
        $this->tpl->setOnScreenMessage('failure', $this->lng->txt('no_permission'), true);

        if ($this->course_ref_id > 0) {
            $this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', $this->course_ref_id);
            $this->ctrl->redirectByClass(ilRepositoryGUI::class);
        }

        $this->ctrl->redirectByClass(ilDashboardGUI::class);
    }

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
            if (!isset($arr_usr_data[$id])) {
                unset($usr_id[$key]);
                continue;
            }

            if(!$user_data->checkIfUserDataFilled(
                $arr_usr_data[$id]->getPartCertFirstname(),
                $arr_usr_data[$id]->getPartCertLastname()
            )) {
                unset($usr_id[$key]);
            }
        }
        return array_values($usr_id);
    }

    /**
     * Keeps only ids of members of the current course/group, as returned by
     * ilParticipationCertificateAccess::getUserIdsOfGroup(). User ids come from the
     * request (config_entry), so they must never be trusted as-is.
     *
     * @param array $user_ids
     * @return int[]
     */
    private function restrictToCourseMembers(array $user_ids): array
    {
        return $this->cert_access->filterUserIdsOfGroup($user_ids);
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
