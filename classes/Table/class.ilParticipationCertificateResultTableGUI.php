<?php

use ILIAS\Data\Factory;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\UI\Implementation\Component\Table\Data;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Component\Input\Container\Filter\Standard;

/**
 * Class ilParticipationCertificateResultTableNewGUI
 */
class ilParticipationCertificateResultTableGUI implements I\DataRetrieval
{
    CONST IDENTIFIER = 'ilpartusr';
    const GREEN_PROGRESS = "ilCourseObjectiveProgressBarCompleted";
    const ORANGE_PROGRESS = "progress-bar-warning";
    const RED_PROGRESS = "ilCourseObjectiveProgressBarFailed";
    const NO_PROGRESS = "ilCourseObjectiveProgressBarNeutral";

    protected Factory $df;
    protected DateFormat $current_user_date_format;

    protected URLBuilderToken $action_parameter_token;

    protected URLBuilderToken $row_id_token;

    protected ilParticipationCertificatePlugin $pl;

    protected ilTabsGUI $tabs;

    protected ilCtrl $ctrl;

    private int $refId;

    private ?string $firstname = null;

    private ?string $lastname = null;

    /**
     * @var ilParticipationCertificateResultGUI
     */
    protected ?object $parent_obj;

    protected array $filter = array();
    protected array $custom_export_formats = array();
    protected array $custom_export_generators = array();

    protected array $usr_ids;
    protected ?string $ementoring = null;

    private \ILIAS\UI\Factory $ui_factory;

    public function __construct()
    {
        global $DIC;

        $this->ui_factory = $DIC->ui()->factory();
        $this->df = new Factory();
        $this->current_user_date_format = $this->df->dateFormat()->withTime24(
            $DIC->user()->getDateFormat()
        );
        $this->pl = ilParticipationCertificatePlugin::getInstance();

        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->pl = ilParticipationCertificatePlugin::getInstance();
        $this->refId = $_GET['ref_id'];

        $cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);
        $this->usr_ids = $cert_access->getUserIdsOfGroup();

        $ementoring = ilParticipationCertificateConfig::getConfig('enable_ementoring', $_GET['ref_id']);
        if ($ementoring === NULL) {
            $ementoring = true;
        } else {
            $ementoring = boolval($ementoring);
        }
        $this->ementoring = $ementoring;
    }

    /**
     * @throws ilCtrlException
     */
    public function getTableForRepresentation(
        ?string $firstname = null,
        ?string $lastname = null
    ): I\Data {
        global $DIC;

        $this->setFilter($firstname, $lastname);
        $actions = $this->getActions();
        $request = $DIC->http()->request();
        $table = $this->ui_factory->table()->data(
            '',
            $this->getColumsForRepresentation(),
            $this
        )->withActions($actions)->withRequest($request);



        /*$f = $DIC->ui()->factory();
        $refinery = $DIC->refinery();
        $query = $DIC->http()->wrapper()->query();


        /*if ($query->has($this->action_parameter_token->getName())) {*/
        /*if (!empty($_GET['config_action'])) {
            $action = $query->retrieve('config_action', $refinery->to()->string());

            dd($action);
            $ids = $query->retrieve($this->row_id_token->getName(), $refinery->custom()->transformation(fn($v) => $v));
            $listing = $f->listing()->characteristicValue()->text([
                'table_action' => $action,
                'id' => print_r($ids, true),
            ]);

            dd($listing);

            /** take care of the async-call; 'delete'-action asks for it.
            if ($action === 'delete') {
                $items = [];
                foreach ($ids as $id) {
                    $items[] = $f->modal()->interruptiveItem()->keyValue($id, $row_id_token->getName(), $id);
                }
                echo($r->renderAsync([
                    $f->modal()->interruptive(
                        'Deletion',
                        'You are about to delete items!',
                        '#'
                    )->withAffectedItems($items)
                      ->withAdditionalOnLoadCode(static fn($id): string => "console.log('ASYNC JS');")
                ]));
                exit();
            }
            if ($action === 'info') {
                echo(
                    $r->render($f->messageBox()->info('an info message: <br><li>' . implode('<li>', $ids)))
                    . '<script data-replace-marker="script">console.log("ASYNC JS, too");</script>'
                );
                exit();
            }

            /** otherwise, we want the table and the results below
            $out[] = $f->divider()->horizontal();
            $out[] = $listing;
        }*/







        return $table;

    }

    /**
     * @param string|null $firstname
     * @param string|null $lastname
     * @return void
     */
    public function setFilter(
        ?string $firstname = null,
        ?string $lastname = null
    ): void {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    /**
     * @param I\DataRowBuilder $row_builder
     * @param array            $visible_column_ids
     * @param Range            $range
     * @param Order            $order
     * @param array|null       $filter_data
     * @param array|null       $additional_parameters
     * @return Generator
     */
    public function getRows(
        I\DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        $data = $this->doSelect($order, $range);

        $ementoringIsActive = false;
        if($this->ementoring) {
            $ementoringIsActive = true;
        }

        foreach ($data as $key => $record) {
            yield $row_builder->buildDataRow($record['usr_id'] . '_' . $ementoringIsActive, $record);
        }
    }

    public function getSelectableColumns(): array
    {
        $cols = [];
        $cert_access = new ilParticipationCertificateAccess($_GET["ref_id"]);
        $write_access = $cert_access->hasCurrentUserWriteAccess();
        $cols['loginname'] = array(
            'txt' => $this->pl->txt('loginname'),
            'default' => $write_access,
            'width' => 'auto',
            'sort_field' => 'loginname'
        );
        $cols['firstname'] = array(
            'txt' => $this->pl->txt('cols_firstname'),
            'default' => true,
            'width' => 'auto',
            'sort_field' => 'firstname'
        );
        $cols['lastname'] = array(
            'txt' => $this->pl->txt('cols_lastname'),
            'default' => true,
            'width' => 'auto',
            'sort_field' => 'lastname'
        );
        $cols['initial_test_finished'] = array(
            'txt' => $this->pl->txt('cols_initial_test_finished'),
            'default' => true,
            'width' => 'auto',
            'sort_field' => 'initial_test_finished'
        );
        $cols['result_qualifing_tests'] = array(
            'txt' => $this->pl->txt('cols_result_qualifying'),
            'default' => true,
            'width' => 'auto',
            'sort_field' => 'result_qualifing_tests'
        );
        $cols['results_qualifing_tests'] = array(
            'txt' => $this->pl->txt('cols_results_qualifying'),
            'default' => false,
            'width' => 'auto',
            'sort_field' => 'result_qualifing_tests'
        );
        $cols['eMentoring_finished'] = array(
            'txt' => $this->pl->txt('cols_eMentoring_finished'),
            'default' => $this->ementoring,
            'width' => 'auto',
            'sort_field' => 'eMentoring_finished'
        );
        $cols['eMentoring_homework'] = array(
            'txt' => $this->pl->txt('cols_eMentoring_homework'),
            'default' => $this->ementoring,
            'width' => 'auto',
            'sort_field' => 'eMentoring_homework'
        );
        $cols['eMentoring_percentage'] = array(
            'txt' => $this->pl->txt('cols_eMentoring_percentage'),
            'default' => $this->ementoring,
            'width' => 'auto',
            'sort_field' => 'eMentoring_percentage'
        );

        return $cols;
    }

    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return count($this->records());
    }

    protected function doSelect(Order $order, Range $range): array
    {
        $sql_order_part = $order->join('ORDER BY', fn(...$o) => implode(' ', $o));
        $sql_range_part = sprintf('LIMIT %2$s OFFSET %1$s', ...$range->unpack());
        return array_map(
            fn($rec) => array_merge($rec, ['sql_order' => $sql_order_part, 'sql_range' => $sql_range_part]),
            $this->records()
        );
    }

    /**
     * @return array
     */
    protected function getColumsForRepresentation(): array
    {
        $columns = $this->getSelectableColumns();

        $f = $this->ui_factory;

        return  [
            'loginname' => $f->table()->column()
                                      ->text($columns['loginname']['txt'])
                                      ->withIsSortable(false),
            'firstname' => $f->table()->column()
                                      ->text($columns['firstname']['txt'])
                                      ->withIsSortable(false),
            'lastname' => $f->table()->column()
                                      ->text($columns['lastname']['txt'])
                                      ->withIsSortable(false),
            'initial_test_finished' => $f->table()->column()
                                                  ->text($columns['initial_test_finished']['txt'])
                                                  ->withIsSortable(false),
            'result_qualifing_tests' => $f->table()->column()
                                                   ->text($columns['result_qualifing_tests']['txt'])
                                                   ->withIsSortable(false),
            'results_qualifing_tests' => $f->table()->column()
                                                    ->text($columns['results_qualifing_tests']['txt'])
                                                    ->withIsSortable(false),
            'eMentoring_finished' => $f->table()->column()
                                                ->text($columns['eMentoring_finished']['txt'])
                                                ->withIsSortable(false),
            'eMentoring_homework' => $f->table()->column()
                                                ->text($columns['eMentoring_homework']['txt'])
                                                ->withIsSortable(false),
            'eMentoring_percentage' => $f->table()->column()
                                                  ->text($columns['eMentoring_percentage']['txt'])
                                                  ->withIsSortable(false),
        ];
    }

    public function records(): array
    {
        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids);

        $arr_usr_data = $this->excludeUserIdIfFiltered($arr_usr_data);
        $arr_initial_test_states = ilCrsInitialTestStates::getData($this->usr_ids);
        $arr_learn_reached_percentages = ilLearnObjectSuggResults::getData($this->usr_ids);
        $arr_final_tests = ilLearnObjectFinalTestStates::getData($this->usr_ids);
        $arr_new_iass_states = ilIassStatesMulti::getData($this->usr_ids, $this->refId);
        $arr_xali_states = xaliStates::getData($this->usr_ids, $this->refId);
        $arr_excercise_states = ilExcerciseStates::getData($this->usr_ids, $this->refId);

        $rows = array();
        foreach ($this->usr_ids as $usr_id) {
            $row = array();
            $row['usr_id'] = $usr_id;
            $row['loginname'] = $arr_usr_data[$usr_id]->getPartCertUserName();
            if ($arr_usr_data[$usr_id]->getPartCertFirstname()  != NULL) {
                $row['firstname'] = $arr_usr_data[$usr_id]->getPartCertFirstname();
            } else {
                $row['firstname'] = '';
            }
            if ($arr_usr_data[$usr_id]->getPartCertLastname() != NULL) {
                $row['lastname'] = $arr_usr_data[$usr_id]->getPartCertLastname();
            } else {
                $row['lastname'] = '';
            }

            if (key_exists($usr_id, $arr_initial_test_states) && is_object($arr_initial_test_states[$usr_id])) {
                $row['initial_test_finished'] = $arr_initial_test_states[$usr_id]->getCrsitestItestSubmitted();
                if ($row['initial_test_finished'] == 1) {
                    $row['initial_test_finished'] = $this->pl->txt("yes");
                } else {
                    $row['initial_test_finished'] = $this->pl->txt("no");
                }
            } else {
                $row['initial_test_finished'] = $this->pl->txt("no");
            }
            if ((key_exists($usr_id, $arr_learn_reached_percentages)) && (is_object($arr_learn_reached_percentages[$usr_id]))) {
                $row['result_qualifing_tests'] = $this->buildProgressBar($arr_learn_reached_percentages[$usr_id]->getAveragePercentage(ilParticipationCertificateConfig::getConfig('calculation_type_processing_state_suggested_objectives',$_GET['ref_id'])
                ), $arr_learn_reached_percentages[$usr_id]->getLimitPercentage());

            } else {
                $row['result_qualifing_tests'] = $this->buildProgressBar(0,0);
            }

            $rec_array = [];
            $array_results = [];

            if (key_exists($usr_id, $arr_final_tests) && (is_array($arr_final_tests[$usr_id]))) {

                foreach ($arr_final_tests[$usr_id] as $usr_objectives) {

                    if (is_array($usr_objectives)) {
                        foreach ($usr_objectives as $rec) {

                            if ($rec->getObjectivesSuggested()) {

                                if (!array_key_exists($rec->getLocftestObjectiveId(), $rec_array)) {
                                    $rec_array[$rec->getLocftestObjectiveId()] = $rec->getLocftestLearnObjectiveTitle() . '<br/>';
                                }

                                $locTestPercentage = $rec->getLocftestPercentage();
                                $percentageText = ($locTestPercentage !== null) ? round($locTestPercentage, 0) . '%' : '0%';

                                /**
                                 * @var ilLearnObjectFinalTestState $rec
                                 */
                                $rec_array[$rec->getLocftestObjectiveId()] .= '- ' . $percentageText .
                                    ' ' . $rec->getLocftestObjectiveTitle() . '<br/>';
                            }
                        }
                    }
                }

                $array_results = $rec_array;
                $row['results_qualifing_tests'] = implode('<br/><br/>', $array_results);

            } else {
                $row['results_qualifing_tests'] = $this->pl->txt("no_tests");
            }

            $countPassed = 0;
            $countTests = 0;
            if (key_exists($usr_id, $arr_new_iass_states) && is_array($arr_new_iass_states[$usr_id])) {
                foreach ($arr_new_iass_states[$usr_id] as $item) {
                    $countPassed = $countPassed + $item->getPassed();
                    $countTests = $countTests + $item->getTotal();
                }
            }

            if (key_exists($usr_id, $arr_xali_states) && is_object($arr_xali_states[$usr_id])) {
                $countPassed = $countPassed + $arr_xali_states[$usr_id]->getPassed();
                $countTests = $countTests + $arr_xali_states[$usr_id]->getTotal();
            }

            if($countTests > 0) {
                $percentage = $countPassed / $countTests * 100;
                switch ($countTests) {
                    case 1:
                        if ($countPassed == 1) {
                            $row['eMentoring_finished'] = ilUtil::img($this->pl->getImagePath("passed.svg"));
                        } else {
                            $row['eMentoring_finished'] = ilUtil::img($this->pl->getImagePath("failed.svg"));
                        }
                        break;
                    default:
                        $row['eMentoring_finished'] = $countPassed . "/" . $countTests;
                        break;
                }
            } else {
                $row['eMentoring_finished'] = ilUtil::img($this->pl->getImagePath("not_attempted.svg"));
            }

            if (key_exists($usr_id, $arr_excercise_states) && is_object($arr_excercise_states[$usr_id])) {
                $row['eMentoring_homework'] = $arr_excercise_states[$usr_id]->getPassed();
                $row['eMentoring_percentage'] = $this->buildProgressBar($arr_excercise_states[$usr_id]->getPassedPercentage(),0);
            } else {
                $row['eMentoring_homework'] = 0;
                $row['eMentoring_percentage'] = $this->buildProgressBar(0,0);
            }

            $rows[] = $row;
        }
        return $rows;

    }

    /**
     * @param array $userData
     * @return array
     */
    private function excludeUserIdIfFiltered(array $userData): array
    {
        foreach($userData as $userId => $user) {
            if ((!empty($this->firstname) && $user->getPartCertFirstname() !== $this->firstname) ||
                (!empty($this->lastname) && $user->getPartCertLastname() !== $this->lastname)
            ) {
                unset($userData[$userId]);

                $this->usr_ids = array_values(array_filter($this->usr_ids, function($value) use ($userId) {
                    return $value !== $userId;
                }));
            }
        }
        return $userData;
    }

    /**
     * @throws ilCtrlException
     */
    private function getActions(): array
    {
        global $DIC;

        $f = $DIC->ui()->factory();
        $uri = $this->buildURI();
        $url_builder = new URLBuilder($uri);
        [$url_builder, $this->action_parameter_token, $this->row_id_token] =
            $url_builder->acquireParameters(
                ['config'],
                'action',
                'entry'
            );

        $actions = [
            
            'print_without_ementorining' => $f->table()->action()->single(
                $this->pl->txt('list_print_without'),
                $url_builder->withParameter($this->action_parameter_token, 'print_without_ementorining'),
                $this->row_id_token
            ),
            'print_selected_without_ementorining' => $f->table()->action()->multi(
                $this->pl->txt('list_print_without'),
                $url_builder->withParameter($this->action_parameter_token, 'print_selected_without_ementorining'),
                $this->row_id_token
            ),
           
        ];
        
        if ($this->ementoring) {
            $actions['print_with_ementorining'] = $f->table()->action()->single(
                $this->pl->txt('list_print_with'),
                $url_builder->withParameter($this->action_parameter_token, 'print_with_ementorining'),
                $this->row_id_token
            );
            $actions['print_selected_with_ementorining'] = $f->table()->action()->multi(
                $this->pl->txt('list_print_with'),
                $url_builder->withParameter($this->action_parameter_token, 'print_selected_with_ementorining'),
                $this->row_id_token
            );
        }
        
        $cert_access = new ilParticipationCertificateAccess($this->refId);
        if ($cert_access->hasCurrentUserAdminAccess()) {
           $actions['adjust_results'] = $f->table()->action()->single(
               $this->pl->txt('list_results'),
               $url_builder->withParameter($this->action_parameter_token, 'adjust_results'),
               $this->row_id_token
           );
        }

        // if mentees shoud see results, change the check to Read instead of Special
        if ($cert_access->hasCurrentUserSpecialAccess()) {
           $actions['show_all_results'] = $f->table()->action()->single(
                $this->pl->txt('list_overview'),
                $url_builder->withParameter($this->action_parameter_token, 'show_all_results'),
                $this->row_id_token
           );
           $actions['show_selected_all_results'] = $f->table()->action()->multi(
                $this->pl->txt('list_overview'),
                $url_builder->withParameter($this->action_parameter_token, 'show_selected_all_results'),
                $this->row_id_token
            );
        }

        return $actions;
    }

    protected function buildProgressBar(int $a_perc_result, int $a_perc_limit): string
    {
        $groupRefId = filter_input(INPUT_GET, 'ref_id');

        $start = ilParticipationCertificateConfig::getConfig('period_start', $groupRefId);
        $end = ilParticipationCertificateConfig::getConfig('period_end', $groupRefId);

        if ($start !== NULL && $end !== NULL) {
            // Period set
            $start = new DateTime($start);
            $end = new DateTime($end);
            $current = new DateTime();

            if ($current >= $start) {
                if ($current <= $end) {
                    // Running
                    $rest_days = $end->diff($current)->days;
                    $total_days = max(1, $end->diff($start)->days);
                    $perc_limit = (100 - ($rest_days / $total_days * 100));
                } else {
                    // Ended
                    $perc_limit = 100;
                }

                if ($a_perc_result >= 90) {
                    // 90% reached
                    $css_class = self::GREEN_PROGRESS;
                } else {
                    // <90%
                    if ($current <= $end) {
                        // End not reached
                        if ($a_perc_result >= ($perc_limit - 30)) {
                            // In time or already farer
                            $css_class = self::GREEN_PROGRESS;
                        } else {
                            if ($a_perc_result >= ($perc_limit - 40)) {
                                //
                                $css_class = self::ORANGE_PROGRESS;
                            } else {
                                // Not in time
                                $css_class = self::RED_PROGRESS;
                            }
                        }
                    } else {
                        // End reached
                        $css_class = self::RED_PROGRESS;
                    }
                }

                if ($perc_limit < 30) {
                    //
                    $perc_limit = 30;
                }
            } else {
                // Not started
                $perc_limit = 1;
                $css_class = self::NO_PROGRESS;
            }
        } else {
            // No period set
            $perc_limit = NULL;

            if ($a_perc_result >= 80) {
                // 80% reached
                $css_class = self::GREEN_PROGRESS;
            } else {
                // <80%
                $css_class = self::RED_PROGRESS;
            }
        }
        return ilContainerObjectiveGUI::renderProgressBar($a_perc_result, $perc_limit, $css_class);
    }

    /**
     * @return URI
     * @throws ilCtrlException
     */
    private function buildURI(): URI {
        global $DIC;

        return new URI(
            ILIAS_HTTP_PATH . '/' . $DIC->ctrl()->getLinkTargetByClass(
                \ilParticipationCertificateResultGUI::class,
                ilParticipationCertificateConfigGUI::CMD_ACTION
            )
        );
    }

    /**
     * @return Standard
     * @throws ilCtrlException
     */
    public function buildFilter(): Standard
    {
        global $DIC;

        $ui = $DIC->ui()->factory();

        $inputFirstname = $ui->input()->field()->text('Firstname');
        $inputLastname = $ui->input()->field()->text('Lastname');

        $action = $DIC->ctrl()->getLinkTargetByClass(
            ilParticipationCertificateResultGUI::class,
            'applyFilter',
            "",
            false
        );

        $filter = $DIC->uiService()->filter()->standard(
            'filter-results',
            $action,
            [
                'firstname' => $inputFirstname,
                'lastname' => $inputLastname,
            ],
            [true, true],
            true,
            true,
        );

        return $filter;
    }
}
