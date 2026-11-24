<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\StudyProgramQuery;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Calculation\CalculateScoresAndSuggestions;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Log\Log;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\LearningObjective\LearningObjectiveCourse;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\User\User;

class TrackingTool
{
    /**
     * Not suggested learning objectives as "completed/total", course data loaded once for all users.
     * @param int[] $user_ids
     * @return array<int, string> usr_id => "completed/total"
     */
    public static function getNotRecommendedLearningObjectivesForCertificates(
        array $user_ids,
        int $course_ref_id
    ): array {
        $user_ids = array_map('intval', $user_ids);
        $course_obj_id = ilObjCourse::_lookupObjectId($course_ref_id);
        $final_tests_states = ilLearnObjectFinalTestStates::getDataByCourseObjId($course_obj_id, $user_ids);
        $weights = null;

        $results = [];
        foreach ($user_ids as $user_id) {
            $learning_objectives = [];
            if (!empty($final_tests_states[$user_id])) {
                $weights ??= getFineWeights::getData($course_obj_id);
                $learning_objectives = self::buildTrackingToolLearningObjectives(
                    $user_id,
                    $course_obj_id,
                    $final_tests_states[$user_id],
                    [], // counting uses test_required_percentage of the state, not the course pass level
                    $weights
                );
            }
            $results[$user_id] = self::countNotRecommendedCompleted($learning_objectives);
        }
        return $results;
    }

    private static function countNotRecommendedCompleted(array $learning_objectives): string
    {
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
     * @param string|null $ref_id
     * @return array
     */
    public static function getTrackingToolLearningObjectives(
        int $user_id,
        ?string $ref_id = null
    ): array {
        if (empty($ref_id)) {
            return [];
        }

        $course_obj_id = ilObjCourse::_lookupObjectId((int) $ref_id);
        $final_tests_states = ilLearnObjectFinalTestStates::getDataByCourseObjId($course_obj_id, [$user_id]);
        if (empty($final_tests_states[$user_id])) {
            return [];
        }

        return self::buildTrackingToolLearningObjectives(
            $user_id,
            $course_obj_id,
            $final_tests_states[$user_id],
            self::getRequiredPercentages($final_tests_states[$user_id], self::getFinalTestPassLevel($course_obj_id)),
            getFineWeights::getData($course_obj_id)
        );
    }

    /**
     * @param array $final_tests_states_user master_crs_objective_id => ilLearnObjectFinalTestState[]
     */
    private static function buildTrackingToolLearningObjectives(
        int $user_id,
        int $course_obj_id,
        array $final_tests_states_user,
        array $required_percentages,
        array $weights
    ): array {
        $learning_objectives = self::getLearningObjectives(
            self::sortByScore($user_id, $course_obj_id, $weights),
            $final_tests_states_user
        );

        return self::storeCoursesInLearningObjectives(
            $learning_objectives,
            self::getTrackingToolData([$user_id => $final_tests_states_user], $user_id),
            $required_percentages
        );
    }

    /**
     * @return array<int, int> master_crs_id => required percentage
     */
    private static function getRequiredPercentages(array $final_tests_states_user, ?int $pass_level): array
    {
        // keeps the former quirk: without pass level only the first master course gets the default 60
        $required_percentages = [];
        foreach ($final_tests_states_user as $value) {
            $master_crs_id = $value[0]->getLocftestMasterCrsId();
            if ($pass_level !== null) {
                $required_percentages[$master_crs_id] = $pass_level;
            }
            if (empty($required_percentages)) {
                $required_percentages[$master_crs_id] = 60;
            }
        }
        return $required_percentages;
    }

    /**
     * Minimum level of the first passed mark of the course's qualifying test, null if there is none.
     */
    private static function getFinalTestPassLevel(int $course_obj_id): ?int
    {
        $data_final_test = self::getDataFinalTest($course_obj_id);
        if (empty($data_final_test['qtest'])) {
            return null;
        }

        $tst = new ilObjTest($data_final_test['qtest'], true);
        foreach ($tst->getMarkSchema()->getMarkSteps() as $mark) {
            if ($mark->getPassed()) {
                return (int) $mark->getMinimumLevel();
            }
        }
        return null;
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
                    $learning_objectives[$key]['required_percentage'] = $required_percentages[$learning_objective['obj_id']] ?? null;
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
    private static function sortByScore(int $user_id, int $course_obj_id, array $weights): array
    {
        $scores = NewLearningObjectiveScores::getData($user_id, $course_obj_id);
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
