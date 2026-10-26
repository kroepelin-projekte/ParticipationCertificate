<?php

use ILIAS\Data\Factory;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\URLBuilder;
use ILIAS\Data\URI;
use ILIAS\UI\URLBuilderToken;
use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Implementation\Component\Table\Data;

/**
 * Class ilParticipationCertificateResultTableNewGUI
 */
#[AllowDynamicProperties]
class ilParticipationCertificateResultTableGUI
{
    public const string IDENTIFIER = 'ilpartusr';

    public const string GREEN_PROGRESS = 'ilCourseObjectiveProgressBarCompleted';

    public const string ORANGE_PROGRESS = 'progress-bar-warning';

    public const string RED_PROGRESS = 'ilCourseObjectiveProgressBarFailed';

    public const string NO_PROGRESS = 'ilCourseObjectiveProgressBarNeutral';

    private ilParticipationCertificateAccess $cert_access;

    protected Factory $df;

    protected DateFormat $current_user_date_format;

    protected URLBuilderToken $action_parameter_token;

    protected URLBuilderToken $row_id_token;

    protected ilParticipationCertificatePlugin $pl;

    protected ilTabsGUI $tabs;

    protected ilCtrl $ctrl;

    private int $ref_id;

    private ?string $firstname = null;

    private ?string $lastname = null;

    /**
     * @var ilParticipationCertificateResultGUI
     */
    protected ?object $parent_obj;

    protected array $filter = array();

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
        $this->ref_id = $_GET['ref_id'];

        $this->cert_access = new ilParticipationCertificateAccess($_GET['ref_id']);
        $this->usr_ids = $this->cert_access->getUserIdsOfGroup();
        $this->range = [ null, null ];
        $this->order = null;

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

        $actions = $this->getActions((bool) $this->ementoring);
        $request = $DIC->http()->request();

        $data_retrieval = $this->getDataRetrieval();
        $columns = $this->getColumsForRepresentation();

        if ($this->cert_access->hasCurrentUserWriteAccess()) {
            $table = $this->ui_factory->table()->data($data_retrieval, '', $columns)
                          ->withActions($actions)
                          ->withRequest($DIC->http()->request());

        } else {
            $table = $this->ui_factory->table()->data($data_retrieval, '', $columns)
                          ->withRequest($DIC->http()->request());
        }

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
     * @return DataRetrieval
     */
    private function getDataRetrieval(): I\DataRetrieval
    {
        $data_retrieval = new class (
            $this->ementoring,
            $this->pl,
            $this->usr_ids,
            $this->range,
            $this->order,
            $this->ref_id,
            $this->getRecords(...),
            $this->buildProgressBar(...),
            $this->firstname,
            $this->lastname
        ) implements DataRetrieval {
            private bool $ementoring;
            private ilParticipationCertificatePlugin $pl;
            private array $usr_ids;
            private array $range;
            private mixed $order;
            private int $ref_id;
            private ?string $firstname;
            private ?string $lastname;

            private Closure $records;
            private Closure $progress_bar_builder;

            /**
             * @param bool                             $ementoring
             * @param ilParticipationCertificatePlugin $pl
             * @param array                            $usr_ids
             * @param array                            $range
             * @param mixed                            $order
             * @param int                              $ref_id
             * @param Closure                          $records
             * @param Closure                          $progress_bar_builder
             * @param string|null                      $firstname
             * @param string|null                      $lastname
             */
            public function __construct(
                bool $ementoring,
                ilParticipationCertificatePlugin $pl,
                array $usr_ids,
                array $range,
                mixed $order,
                int $ref_id,
                Closure $records,
                Closure $progress_bar_builder,
                ?string $firstname,
                ?string $lastname
            ) {
                $this->ementoring = $ementoring;
                $this->pl = $pl;
                $this->usr_ids = $usr_ids;
                $this->range = $range;
                $this->order = $order;
                $this->ref_id = $ref_id;
                $this->firstname = $firstname;
                $this->lastname = $lastname;
                $this->records = $records;
                $this->progress_bar_builder = $progress_bar_builder;
            }

            /**
             * Delegates to ilParticipationCertificateResultTableGUI::buildProgressBar()
             * @param int $a_perc_result
             * @param int $a_perc_limit
             * @return string
             */
            private function buildProgressBar(
                int $a_perc_result,
                int $a_perc_limit
            ): string {
                return ($this->progress_bar_builder)($a_perc_result, $a_perc_limit);
            }

            /**
             * @param DataRowBuilder $row_builder
             * @param array          $visible_column_ids
             * @param Range          $range
             * @param Order          $order
             * @param mixed          $additional_viewcontrol_data
             * @param mixed          $filter_data
             * @param mixed          $additional_parameters
             * @return Generator
             */
            public function getRows(
                I\DataRowBuilder $row_builder,
                array $visible_column_ids,
                Range $range,
                Order $order,
                mixed $additional_viewcontrol_data,
                mixed $filter_data,
                mixed $additional_parameters
            ): \Generator {
                $data = ($this->records)($range, $order);

                $ementoring_is_active = false;
                if($this->ementoring) {
                    $ementoring_is_active = true;
                }

                foreach ($data as $key => $record) {
                    yield $row_builder->buildDataRow($record['usr_id'] . '_' . $ementoring_is_active, $record);
                }
            }

            /**
             * @param mixed $additional_viewcontrol_data
             * @param mixed $filter_data
             * @param mixed $additional_parameters
             * @return int|null
             */
            public function getTotalRowCount(
                mixed $additional_viewcontrol_data,
                mixed $filter_data,
                mixed $additional_parameters
            ): ?int {
                return count(($this->records)());
            }
        };
        return $data_retrieval;
    }

    /**
     * @param Range|null $range
     * @param Order|null $order
     * @return array
     */
    public function getRecords(?Range $range = null, ?Order $order = null): array
    {
        $arr_usr_data = ilPartCertUsersData::getData($this->pl, $this->usr_ids, $this->range[1], $this->range[0], $this->order, '');
        $course_obj_id = ilObject::_lookupObjectId((int) $this->ref_id);

        $arr_usr_data = $this->excludeUserIdIfFiltered($arr_usr_data);
        $arr_initial_test_states = ilCrsInitialTestStates::getData($this->usr_ids);
        $arr_learn_reached_percentages = ilLearnObjectSuggResults::getData($course_obj_id, $this->usr_ids);
        $arr_final_tests = ilLearnObjectFinalTestStates::getDataByCourseObjId($course_obj_id, $this->usr_ids);
        $arr_new_iass_states = ilIassStatesMulti::getData($this->usr_ids, $this->ref_id);
        $arr_excercise_states = ilExcerciseStates::getData($this->usr_ids, $this->ref_id);

        $rows = array();
        foreach ($arr_usr_data as $usr_id=>$user_data) {
            $row = array();
            $row['usr_id'] = $usr_id;
            $row['loginname'] = $user_data->getPartCertUserName();

            $row['firstname'] = '';
            if ($user_data->getPartCertFirstname()  != NULL) {
                $row['firstname'] = $user_data->getPartCertFirstname();
            }

            $row['lastname'] = '';
            if ($user_data->getPartCertLastname() != NULL) {
                $row['lastname'] = $user_data->getPartCertLastname();
            }

            $row['initial_test_finished'] = $this->pl->txt('no');
            if (key_exists($usr_id, $arr_initial_test_states) && is_object($arr_initial_test_states[$usr_id])) {
                $row['initial_test_finished'] = $arr_initial_test_states[$usr_id]->getCrsitestItestSubmitted();
                if ($row['initial_test_finished'] == 1) {
                    $row['initial_test_finished'] = $this->pl->txt('yes');
                } else {
                    $row['initial_test_finished'] = $this->pl->txt('no');
                }
            }

            if ((key_exists($usr_id, $arr_learn_reached_percentages)) && (is_object($arr_learn_reached_percentages[$usr_id]))) {
                $learn_suggestion_results = ilLearnObjectSuggResults::getData($course_obj_id, $this->usr_ids);
                $learn_suggest_results = $learn_suggestion_results[$usr_id]->getAveragePercentage(ilParticipationCertificateConfig::getConfig('calculation_type_processing_state_suggested_objectives', $this->ref_id), true);
                $row['result_qualifing_tests'] = $learn_suggest_results;
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

                                $loc_test_percentage = $rec->getLocftestPercentage();
                                $percentage_text = ($loc_test_percentage !== null) ? round($loc_test_percentage, 0) . '%' : '0%';

                                /**
                                 * @var ilLearnObjectFinalTestState $rec
                                 */
                                $rec_array[$rec->getLocftestObjectiveId()] .= '- ' . $percentage_text .
                                    ' ' . $rec->getLocftestObjectiveTitle() . '<br/>';
                            }
                        }
                    }
                }

                $array_results = $rec_array;
                $row['results_qualifing_tests'] = implode('<br/><br/>', $array_results);

            } else {
                $row['results_qualifing_tests'] = $this->pl->txt('no_tests');
            }

            $count_passed = 0;
            $count_tests = 0;
            if (key_exists($usr_id, $arr_new_iass_states) && is_array($arr_new_iass_states[$usr_id])) {
                foreach ($arr_new_iass_states[$usr_id] as $item) {
                    $count_passed = $count_passed + $item->getPassed();
                    $count_tests = $count_tests + $item->getTotal();
                }
            }

            $sessions = ParticipationCertificateHelper::getSessions(ParticipationCertificateHelper::getGroupRefId((int) $this->ref_id));
            $count_tests = $count_tests + count($sessions);
            foreach ($sessions as $session) {
                $event_participants = new ilEventParticipants($session['obj_id']);
                if ($event_participants->hasParticipated($usr_id)) {
                    $count_passed++;
                }
            }

            if($count_tests > 0) {
                $percentage = $count_passed / $count_tests * 100;
                switch ($count_tests) {
                    case 1:
                        if ($count_passed == 1) {
                            $row['eMentoring_finished'] = ilUtil::img('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/images/passed.svg');
                        } else {
                            $row['eMentoring_finished'] = ilUtil::img('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/images/failed.svg');
                        }
                        break;
                    default:
                        $row['eMentoring_finished'] = $count_passed . '/' . $count_tests;
                        break;
                }
            } else {
                $row['eMentoring_finished'] = ilUtil::img('./' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY . '/templates/images/not_attempted.svg');
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
     * @param array $user_data
     * @return array
     */
    private function excludeUserIdIfFiltered(array $user_data): array
    {
        foreach($user_data as $user_id => $user) {
            if ((!empty($this->firstname) && $user->getPartCertFirstname() !== $this->firstname) ||
                (!empty($this->lastname) && $user->getPartCertLastname() !== $this->lastname)
            ) {
                unset($user_data[$user_id]);

                $this->usr_ids = array_values(array_filter($this->usr_ids, function($value) use ($user_id) {
                    return $value !== $user_id;
                }));
            }
        }
        return $user_data;
    }

    /**
     * @return array
     */
    public function getSelectableColumns(): array
    {
        $cols = [];
        $write_access = $this->cert_access->hasCurrentUserWriteAccess();
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

    /**
     * @return array
     */
    public function getColumsForRepresentation(): array
    {
        $write_access = $this->cert_access->hasCurrentUserWriteAccess();

        $factory = $this->ui_factory;

        if ($write_access) {
            $cols['loginname'] = $factory->table()->column()
                                   ->text($this->pl->txt('loginname'))
                                   ->withIsSortable(true)
                                   ->withIsOptional(true);
        }
        $cols['firstname'] = $factory->table()->column()
                               ->text($this->pl->txt('cols_firstname'))
                               ->withIsSortable($write_access);
        $cols['lastname'] = $factory->table()->column()
                              ->text($this->pl->txt('cols_lastname'))
                              ->withIsSortable($write_access);
        $cols['initial_test_finished'] = $factory->table()->column()
                                           ->text($this->pl->txt('cols_initial_test_finished'))
                                           ->withIsSortable(false);
        $cols['result_qualifing_tests'] = $factory->table()->column()
                                            ->text($this->pl->txt('cols_result_qualifying'))
                                            ->withIsSortable(false);
        $cols['results_qualifing_tests'] = $factory->table()->column()
                                             ->text($this->pl->txt('cols_results_qualifying'))
                                             ->withIsSortable(false)
                                             ->withIsOptional(true, false);
        if ($this->ementoring or $write_access) {
            $cols['eMentoring_finished'] = $factory->table()->column()
                                             ->text($this->pl->txt('cols_eMentoring_finished'))
                                             ->withIsSortable(false)
                                             ->withIsOptional(true, $this->ementoring);
            $cols['eMentoring_homework'] = $factory->table()->column()
                                             ->text($this->pl->txt('cols_eMentoring_homework'))
                                             ->withIsSortable(false)
                                             ->withIsOptional(true, $this->ementoring);
            $cols['eMentoring_percentage'] = $factory->table()->column()
                                               ->text($this->pl->txt('cols_eMentoring_percentage'))
                                               ->withIsSortable(false)
                                               ->withIsOptional(true, $this->ementoring);
        }
        return $cols;
    }

    /**
     * @throws ilCtrlException
     */
    private function getActions(bool $ementoring_is_active): array
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

        if ($ementoring_is_active) {
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

        $actions['print_without_ementorining'] = $f->table()->action()->single(
            $this->pl->txt('list_print_without'),
            $url_builder->withParameter($this->action_parameter_token, 'print_without_ementorining'),
            $this->row_id_token
        );

        if($this->cert_access->hasCurrentUserWriteAccess()) {
            $actions['show_all_results'] = $f->table()->action()->single(
                $this->pl->txt('list_overview'),
                $url_builder->withParameter($this->action_parameter_token, 'show_all_results'),
                $this->row_id_token
            );
        }

        $actions['print_selected_without_ementorining'] = $f->table()->action()->multi(
            $this->pl->txt('list_print_without'),
            $url_builder->withParameter($this->action_parameter_token, 'print_selected_without_ementorining'),
            $this->row_id_token
        );

        if($this->cert_access->hasCurrentUserWriteAccess()) {
            $actions['show_selected_all_results'] = $f->table()->action()->multi(
                $this->pl->txt('list_overview'),
                $url_builder->withParameter($this->action_parameter_token, 'show_selected_all_results'),
                $this->row_id_token
            );
        }

        if ($this->cert_access->hasCurrentUserWriteAccess()) {
            $actions['adjust_results'] = $f->table()->action()->single(
                $this->pl->txt('list_results'),
                $url_builder->withParameter($this->action_parameter_token, 'adjust_results'),
                $this->row_id_token
            );
        }
        return $actions;
    }

    /**
     * @param int $a_perc_result
     * @param int $a_perc_limit
     * @return string
     * @throws DateMalformedStringException
     */
    public function buildProgressBar(int $a_perc_result, int $a_perc_limit): string
    {
        $group_ref_id = filter_input(INPUT_GET, 'ref_id');

        $start = ilParticipationCertificateConfig::getConfig('period_start', $group_ref_id);
        $end = ilParticipationCertificateConfig::getConfig('period_end', $group_ref_id);

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
                } elseif ($current <= $end) {
                    // End not reached
                    if ($a_perc_result >= ($perc_limit - 30)) {
                        // In time or already farer
                        $css_class = self::GREEN_PROGRESS;
                    } elseif ($a_perc_result >= ($perc_limit - 40)) {
                        //
                        $css_class = self::ORANGE_PROGRESS;
                    } else {
                        // Not in time
                        $css_class = self::RED_PROGRESS;

                        if ($a_perc_result === 0) {
                            $css_class .= ' percent-0';
                        }
                    }
                } else {
                    // End reached
                    $css_class = self::RED_PROGRESS;

                    if ($a_perc_result === 0) {
                        $css_class .= ' percent-0';
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

                if ($a_perc_result === 0 ){
                    $css_class .= ' percent-0';
                }
            }
        }

        if (is_float($perc_limit)) {
            $perc_limit = (int) round($perc_limit);
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

        $input_firstname = $ui->input()->field()->text('Firstname');
        $input_lastname = $ui->input()->field()->text('Lastname');

        $action = $DIC->ctrl()->getLinkTargetByClass(
            ilParticipationCertificateResultGUI::class,
            'applyFilter',
            '',
            false
        );

        $filter = $DIC->uiService()->filter()->standard(
            'filter-results',
            $action,
            [
                'firstname' => $input_firstname,
                'lastname' => $input_lastname,
            ],
            [true, true],
            true,
            true,
        );

        return $filter;
    }
}
