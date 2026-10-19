<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Calculation\CalculateScoresAndSuggestions;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;
use JetBrains\PhpStorm\NoReturn;

class TrackingTool
{
    /**
     * @param int $user_id
     * @param int $course_ref_id
     * @return string|null
     */
    #[NoReturn]
    public static function getNotRecommendedLearningObjectivesForCertificate(
        int $user_id,
        int $course_ref_id
    ): string|null {
        $learning_objectives = self::getTrackingToolLearningObjectives(
            $user_id,
            $course_ref_id
        );

        $completed = 0;
        $total = 0;

        foreach ($learning_objectives as $learning_objective) {
            if (!empty($learning_objective['suggested'])) {
                continue;
            }

            if (!empty($learning_objective['courses'])) {
                foreach ($learning_objective['courses'] as $learning_objectiveCourse) {

                    if ($learning_objectiveCourse['test_percentage'] !== null && $learning_objectiveCourse['test_percentage'] >= $learning_objectiveCourse['test_required_percentage']) {
                        $completed++;
                    }
                    $total++;
                }
            }
        }
        return $total > 0 ? $completed . '/' . $total : '0/0';
    }

    /**
     * @param int         $user_id
     * @param bool        $printIsAsynchronous
     * @param string|null $ref_id
     * @return array
     */
    public static function getTrackingToolLearningObjectives(
        int $user_id,
        ?string $ref_id = null
    ): array {
        $tracking_learning_objectives = [];

        if (empty($ref_id)) {
            return $tracking_learning_objectives;
        }

        $course_obj_id = ilObjCourse::_lookupObjectId($ref_id);
        $sorted = self::sortByScore($user_id, $course_obj_id);
        $final_tests_states = ilLearnObjectFinalTestStates::getDataByCourseObjId($course_obj_id, [$user_id]);

        $required_percentages = [];
        foreach ($final_tests_states as $key => $final_test_state) {
            foreach ($final_test_state as $k => $value) {
                $master_crs_id = $value[0]->getLocftestMasterCrsId();
                $data_final_test = self::getDataFinalTest($course_obj_id);

                $tst = null;
                if (!empty($data_final_test['qtest'])) {
                    $tst = new ilObjTest($data_final_test['qtest'], true);
                }

                if ($tst instanceof ilObjTest) {
                    $schema = $tst->getMarkSchema();
                    foreach ($schema->getMarkSteps() as $mark) {
                        if ($mark->getPassed()) {
                            $required_percentages[$master_crs_id] = (int) $mark->getMinimumLevel();
                            break;
                        }
                    }
                }

                if (empty($required_percentages)) {
                    $required_percentages[$master_crs_id] = 60;
                }
            }
        }

        $learning_objectives = [];
        if (count($final_tests_states)) {
            $learning_objectives = self::getLearningObjectives($sorted, $final_tests_states[$user_id]);
        }

        if( !empty($final_tests_states[$user_id])) {
            $tracking_tool_data = self::getTrackingToolData($final_tests_states, $user_id);

            $tracking_learning_objectives = self::storeCoursesInLearningObjectives(
                $learning_objectives,
                $tracking_tool_data,
                $required_percentages
            );
        }
        return $tracking_learning_objectives;
    }

    /**
     * @param array $final_tests_states
     * @param int   $user_id
     * @return array
     */
    private static function getTrackingToolData(array $final_tests_states, int $user_id): array
    {
        $tracking_tool_data = [];
        $processed = [];

        foreach ($final_tests_states[$user_id] as $final_tests) {
            foreach ($final_tests as $key => $value) {
                /** @var ilLearnObjectFinalTestState $value */
                if ($value->getLocftestCrsObjId()) {
                    // check if data already exists
                    $crs_obj_id = $value->getLocftestCrsObjId();
                    $crs_objectiv_id = $value->getLocftestObjectiveId();
                    if (isset($processed[$crs_obj_id])) {
                        if (isset($processed[$crs_obj_id][$crs_objectiv_id])) {
                            continue;
                        }
                    }

                    $tracking_tool_data[$value->getLocftestCrsObjId()][] = [
                        'master_crs_id' => $value->getLocftestMasterCrsId(),
                        'title' => $value->getLocftestObjectiveTitle(),
                        'test_percentage' => $value->getLocftestPercentage(),
                        'test_required_percentage' => $value->getLocftestQplsRequiredPercentage(),
                        'what_is' => 1
                    ];
                    $processed[$crs_obj_id][$crs_objectiv_id] = $user_id;
                }
            }
        }
        return $tracking_tool_data;
    }

    /**
     * @param array $learning_objectives
     * @param array $tracking_tool_data
     * @param array $required_percentages
     * @return array
     */
    private static function storeCoursesInLearningObjectives(
        array $learning_objectives,
        array $tracking_tool_data,
        array $required_percentages
    ): array {
        foreach ($learning_objectives as $key => $learning_objective) {
            foreach ($tracking_tool_data as $k => $data) {

                if ($key === $k) {
                    $completed = 0;
                    foreach ($data as $course) {
                        if ($course['test_percentage'] !== null && $course['test_percentage'] >= '60') {
                            $completed++;
                        }
                    }
                    $learning_objectives[$key]['required_percentage'] = $required_percentages[$learning_objective['obj_id']];
                    $learning_objectives[$key]['courses'] = $data;
                    $learning_objectives[$key]['count_completed_courses'] = $completed;
                }
            }
        }
        return $learning_objectives;
    }

    /**
     * @param array $sorted
     * @param array $final_tests_states_user
     * @return array
     */
    private static function getLearningObjectives(array $sorted, array $final_tests_states_user): array
    {
        $learning_objectives = [];
        foreach ($sorted as $sort_key => $sort_arr) {

            if (array_key_exists($sort_key, $final_tests_states_user)) {
                /** @var ilLearnObjectFinalTestState $final_tests_state */
                $final_tests_states_course = $final_tests_states_user[$sort_key];

                foreach ($final_tests_states_course as $final_tests_state) {
                    $learning_objectives[$final_tests_state->getLocftestCrsObjId()] = array(
                        'txt' => $final_tests_state->getLocftestLearnObjectiveTitle(),
                        'obj_id' => $sort_arr['obj_id'],
                        'objective_id' => $sort_arr['objective_id'],
                        'default' => true,
                        'score' => $sort_arr['score'],
                        'width' => 'auto',
                        'weight' => $sort_arr['weight'],
                        'suggested' => $sort_arr['suggested'],
                    );
                }
            }
        }
        return $learning_objectives;
    }

    /**
     * @param int $user_id
     * @param int $course_obj_id
     * @return array
     */
    private static function sortByScore(int $user_id, int $course_obj_id): array
    {
        $scores = NewLearningObjectiveScores::getData($user_id, $course_obj_id);
        $weights = getFineWeights::getData($course_obj_id);
        $suggs = getLearnSuggs::getData($user_id, $course_obj_id);

        $sorting = [];

        foreach ($scores as $score) {

            $fine = 1;
            /**
             * @var NewLearningObjectiveScore $score
             */
            if (key_exists('weight_fine_' . $score->getObjectiveId(), $weights)) {
                $fine = $weights['weight_fine_' . $score->getObjectiveId()];
            }

            $suggested = false;
            foreach ($suggs as $sugg) {
                /**
                 * @var getLearnSugg $sugg
                 */

                if ($score->getObjectiveId() == $sugg->getSuggObjectiveId()) {

                    $weight_rough = self::getWeightRough(
                        (int) $score->getCourseObjId(),
                        (int) $score->getObjectiveId(),
                        $user_id
                    );

                    if ($weight_rough > 0) {
                        $suggested = true;
                        break;
                    }
                }
            }
            $sorting[$score->getObjectiveId()] = [
                'title' => $score->getTitle(),
                'score' => $score->getScore(),
                'obj_id' => $score->getCourseObjId(),
                'objective_id' => $score->getObjectiveId(),
                'weight' => $fine,
                'suggested' => $suggested
            ];
        }
        return $sorting;
    }

    /**
     * @param int $course_id
     * @param int $learning_objective_id
     * @param int $user_id
     * @return int|string
     */
    private static function getWeightRough(
        int $course_id,
        int $learning_objective_id,
        int $user_id
    ) {
        global $DIC;

        $calculation = new CalculateScoresAndSuggestions(
            $DIC->database(),
            new ConfigProvider(),
            new Log()
        );


        $course = new LearningObjectiveCourse(new ilObjCourse($course_id, false));
        $learning_objective = $calculation->getLearningObjective($course, $learning_objective_id);

        $user = new User(new ilObjUser($user_id));
        $config = new CourseConfigProvider($course);
        $study_program_query = new StudyProgramQuery($config);
        $study_program = $study_program_query->getByUser($user);

        return $config->getWeightRough($learning_objective, $study_program);
    }

    /**
     * @param int $objId
     * @return array
     */
    public static function getDataFinalTest(int $objId): array
    {
        global $DIC;

        $ilDB = $DIC->database();

        $result = $ilDB->queryF(
            "SELECT * FROM loc_settings
              WHERE obj_id = %s AND qtest IS NOT NULL",
            ['integer'],
            [$objId]
        );

        $data = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $data = $row;
        }
        return $data;
    }
}