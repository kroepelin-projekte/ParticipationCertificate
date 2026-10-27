<?php

use ILIAS\UI\Component\Table as I;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;

/**
 * Class ilParticipationCertificateSingleResultTableGUI
 */
#[AllowDynamicProperties]
class ilParticipationCertificateSingleResultTableGUI
{
    public const string IDENTIFIER = 'usr_id';

    protected ilTabsGUI $tabs;

    protected ilCtrl $ctrl;

    protected ?object $parent_obj;

    protected ilParticipationCertificatePlugin $pl;

    protected array $filter = [];

    protected int $usr_id;

    protected array $marks = [];

    protected array $usr_ids;

    /**
     * @var ilLearningObjectivesMasterCrs[]
     */
    protected array $sugg;

    private int $course_obj_id;

    private \ILIAS\UI\Factory $ui_factory;

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    public function __construct(int $usr_id)
    {
        global $DIC;

        $this->ui_factory = $DIC->ui()->factory();
        $this->ctrl = $DIC->ctrl();
        $this->tabs = $DIC->tabs();
        $this->pl = ilParticipationCertificatePlugin::getInstance();

        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultModificationGUI::class, [ 'ref_id', 'group_id' ]);
        $this->ctrl->saveParameterByClass(ilParticipationCertificateResultGUI::class, 'usr_id');

        $ref_id = Helper::fetchUrlParameter('ref_id', 'string');
        $this->course_obj_id = ilObject::_lookupObjId($ref_id);

        $this->cert_access = new ilParticipationCertificateAccess($ref_id);
        $this->usr_ids = $this->cert_access->getUserIdsOfGroup();
        $this->usr_id = $usr_id;
        $this->sugg = getLearnSuggs::getData($usr_id, $this->course_obj_id);
    }

    /**
     * @throws ilCtrlException
     */
    public function getTableForRepresentation(): I\Data
    {
        global $DIC;

        $columns_for_representation = $this->getColumsForRepresentation();
        $columns = $this->getColumns();

        $data_retrieval = $this->getDataRetrieval($columns);

        $table = $this->ui_factory->table()->data($data_retrieval, '', $columns_for_representation)
                                  ->withRequest($DIC->http()->request());

        return $table;

    }

    /**
     * @param array $columns
     * @return DataRetrieval
     */
    private function getDataRetrieval(array $columns): I\DataRetrieval
    {
        $data_retrieval = new class (
            $this->sugg,
            $columns,
            $this->getRecords(...)
        ) implements DataRetrieval {

            public const string SUCCESSFUL_PROGRESS_CSS_CLASS = 'ilCourseObjectiveProgressBarCompleted';

            public const string NON_SUCCESSFUL_PROGRESS_CSS_CLASS = 'ilCourseObjectiveProgressBarNeutral';

            public const string FAILED_PROGRESS_CSS_CLASS = 'ilCourseObjectiveProgressBarFailed';

            private Closure $records;

            private $suggestions;

            private array $columns;

            private string $color;

            private string $unsugg_color;

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
                $suggestions,
                array $columns,
                Closure $records
            ) {
                $this->suggestions = $suggestions;
                $this->columns = $columns;
                $this->records = $records;

                $config = ilParticipationCertificateConfig::where(array(
                    'config_key' => 'color',
                    'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
                ))->first();

                $this->color = $config->getConfigValue();

                $unsugg_config = ilParticipationCertificateConfig::where(array(
                    'config_key' => 'unsugg_color',
                    'config_type' => ilParticipationCertificateConfig::CONFIG_SET_TYPE_GLOBAL,
                ))->first();

                $this->unsugg_color = '909090';
                if (is_Object($unsugg_config)) {
                    $this->unsugg_color = $unsugg_config->getConfigValue();
                }
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

                foreach ($data as $key => $record) {
                    $row = [];

                    foreach ($visible_column_ids as $column_id) {
                        $field = str_replace('crs_', '', $column_id);
                        $value = $record[$field] ?? '';

                        if (is_array($value)) {
                            $exploded_value = explode('%', $value[0]  ?? '');
                            $value = $this->buildProgressBar($exploded_value, $value[1], $value[2]);
                        }

                        $background_color = '#FFFFFF';
                        if ($column_id !== 'hidden') {

                            $objective_id = $this->columns[str_replace('crs_', '', $column_id)]['objective_id'];
                            if ($this->searchForId($objective_id, $this->suggestions)) {
                                $background_color = '#' . $this->color;
                            } else {
                                $background_color = '#' . $this->unsugg_color;
                            }
                        }
                        $row[$column_id] = '<span class="td-span" style="background-color: ' . $background_color . ';' . '">' . $value . '</div>' ?? '';
                    }

                    yield $row_builder->buildDataRow(
                        'result_' . $key,
                        $row
                    );
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

            /**
             * @param          $points
             * @param int|null $required_percent
             * @param int|null $tries
             * @return string
             */
            private function buildProgressBar($points, ?int $required_percent, ?int $tries): string
            {
                $points = $points[0];
                $tooltip_id = 'prg_';

                $maximum_possible_amount_of_points = 100;
                $current_amount_of_points = (int)$points;

                if ($maximum_possible_amount_of_points > 0) {
                    $current_percent = (int)($current_amount_of_points * 100 / $maximum_possible_amount_of_points);
                } else {
                    $current_percent = 0;
                }

                //required to dodge bug in ilContainerObjectiveGUI::renderProgressBar
                if ($required_percent == 0) {
                    $required_percent = null;
                }

                if ($current_percent >= $required_percent) {
                    $css_class = self::SUCCESSFUL_PROGRESS_CSS_CLASS;
                } elseif ($tries == NULL) {
                    $css_class = self::NON_SUCCESSFUL_PROGRESS_CSS_CLASS;
                } else {
                    $css_class = self::FAILED_PROGRESS_CSS_CLASS;

                    if ($current_percent === 0) {
                        $css_class .= ' percent-0';
                    }
                }

                if (is_float($required_percent)) {
                    $required_percent = (int) round($required_percent);
                }

                return \ilContainerObjectiveGUI::renderProgressBar(
                    $current_percent,
                    $required_percent,
                    $css_class,
                    '',
                    NULL,
                    $tooltip_id,
                    ''
                );
            }

            private function searchForId(int $id, array $array): bool
            {
                foreach ($array as $key => $val) {
                    if ($val->getSuggObjectiveId() == $id) {
                        return true;
                    }
                }

                return false;
            }
        };
        return $data_retrieval;
    }

    /**
     * @param Range|null $range
     * @param Order|null $order
     * @return array
     */
    public function getRecords(
        ?Range $range = null,
        ?Order $order = null
    ): array {
        $arr_FinalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($this->course_obj_id, [$this->usr_id]);
        $usr_id = $this->usr_id;
        $rec_array = [];
        $processed = [];

        if (count($arr_FinalTestsStates)) {

            $row_key = [];
            //build_row_key_s
            foreach ($arr_FinalTestsStates[$usr_id] as $rec) {
                /**
                 * @var ilLearnObjectFinalTestState $rec;
                 */
                $row_key[$rec[0]->getLocftestCrsObjId()] = 0;
            }

            foreach ($arr_FinalTestsStates[$usr_id] as $rec_final_tests) {
                /**
                 * @var ilLearnObjectFinalTestState $rec_final_test;
                 */

                foreach($rec_final_tests as $key => $value) {
                    $locfTestCrsObjId = $value->getLocftestCrsObjId();

                    if ($locfTestCrsObjId) {
                        // check if data already exists
                        $crs_obj_id = $locfTestCrsObjId;
                        $crs_objective_id = $value->getLocftestObjectiveId();
                        if (isset($processed[$crs_obj_id])) {
                            if (isset($processed[$crs_obj_id][$crs_objective_id])) {
                                continue;
                            }
                        }

                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId] = $value->getLocftestObjectiveTitle();

                        $row_key[$locfTestCrsObjId] += 1;
                        //second line - array progressbar
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][0] = $value->getLocftestPercentage();
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][1] = $value->getLocftestQplsRequiredPercentage();
                        $rec_array[$row_key[$locfTestCrsObjId]][$locfTestCrsObjId][2] = 1;

                        $row_key[$locfTestCrsObjId] += 1;

                        $processed[$crs_obj_id][$crs_objective_id] = $usr_id;
                    }
                }
            }
        }
        return $rec_array;
    }

    /**
     * @return array
     */
    public function getColumsForRepresentation(): array
    {
        $write_access = $this->cert_access->hasCurrentUserWriteAccess();

        $factory = $this->ui_factory;

        $columns = $this->getColumns();

        $cols = [];
        $cols['hidden'] = $factory->table()->column()
                                     ->text('')
                                     ->withIsOptional(false)
                                     ->withIsSortable(false);

        foreach($columns as $obj_id => $column) {
            $cols['crs_' . $obj_id] = $factory->table()->column()
                                            ->text($column['txt'])
                                            ->withIsOptional(true)
                                            ->withIsSortable(0);

        }

        return $cols;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        $cols = [];

        $finalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($this->course_obj_id, [$this->usr_id ]);
        $sorted = $this->sortScores();
        $i = 0;

        if (count($finalTestsStates)) {
            foreach ($sorted as $sort_key => $sort_arr) {
                if (array_key_exists($sort_key, $finalTestsStates[$this->usr_id])) {
                    /** @var ilLearnObjectFinalTestState $finalTestsState */

                    /** @var ilLearnObjectFinalTestState $finalTestsState */
                    $finalTestsStates_course = $finalTestsStates[$this->usr_id][$sort_key];

                    foreach ($finalTestsStates_course as $finalTestsState) {
                        $cols[$finalTestsState->getLocftestCrsObjId()] = [
                            'txt' => $finalTestsState->getLocftestLearnObjectiveTitle(),
                            'obj_id' => $sort_arr['obj_id'],
                            'objective_id' => $sort_arr['objective_id'],
                            'default' => true,
                            'width' => 'auto',
                        ];
                    }

                }

                $i = $i + 1;

                if ($i == 10000) {
                    break;
                }
            }
        }
        return $cols;
    }

    /**
     * @return array
     */
    private function sortScores(): array
    {
        //First sort scores
        $scores = NewLearningObjectiveScores::getData($this->usr_id, $this->course_obj_id);
        //if the scores are equal, sort because of the weight value
        $weights = getFineWeights::getData($this->course_obj_id);
        $newWeights = $weights;

        $sorting = [];

        foreach ($scores as $score) {

            /**
             * @var NewLearningObjectiveScore $score
             */
            if (key_exists('weight_fine_'.$score->getObjectiveId(),$newWeights)) {
                $fine = $newWeights['weight_fine_'.$score->getObjectiveId()];
            } else {
                //fallback
                $fine = 1;
            }

            $sorting[$score->getObjectiveId()] = [
                'title' => $score->getTitle(),
                'score' => $score->getScore(),
                'obj_id' => $score->getCourseObjId(),
                'objective_id' => $score->getObjectiveId(),
                'weight' => $fine
            ];
        }
        return $sorting;
    }
}
