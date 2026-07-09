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
     * @param int  $userId
     * @param int  $courseRefId
     * @return string|null
     */
    #[NoReturn]
    public static function getNotRecommendedLearningObjectivesForCertificate(
        int $userId,
        int $courseRefId
    ): string|null {
        $learningObjectives = self::getTrackingToolLearningObjectives(
            $userId,
            $courseRefId
        );

        $completed = 0;
        $total = 0;

        foreach ($learningObjectives as $learningObjective) {
            if (!empty($learningObjective['suggested'])) {
                continue;
            }

            foreach ($learningObjective['courses'] as $learningObjectiveCourse) {

                if ($learningObjectiveCourse['test_percentage'] !== null && $learningObjectiveCourse['test_percentage'] >= $learningObjectiveCourse['test_required_percentage']) {
                    $completed++;
                }
                $total++;
            }
        }
        return $total > 0 ? $completed . '/' . $total : '0/0';
    }

    /**
     * @param int         $userId
     * @param bool        $printIsAsynchronous
     * @param string|null $refId
     * @return array
     */
    public static function getTrackingToolLearningObjectives(
        int $userId,
        ?string $refId = null
    ): array {
        $trackingLearningObjectives = [];

        if (empty($refId)) {
            return $trackingLearningObjectives;
        }

        $courseObjId = ilObjCourse::_lookupObjectId($refId);
        $sorted = self::sortByScore($userId, $courseObjId);
        $finalTestsStates = ilLearnObjectFinalTestStates::getDataByCourseObjId($courseObjId, [$userId]);

        $requiredPercentages = [];
        foreach ($finalTestsStates as $key => $finalTestState) {
            foreach ($finalTestState as $k => $value) {
                $masterCrsId = $value[0]->getLocftestMasterCrsId();
                $dataFinalTest = self::getDataFinalTest($courseObjId);

                $tst = null;
                if (!empty($dataFinalTest['qtest'])) {
                    $tst = new ilObjTest($dataFinalTest['qtest'], true);
                }

                if ($tst instanceof ilObjTest) {
                    $schema = $tst->getMarkSchema();
                    foreach ($schema->getMarkSteps() as $mark) {
                        if ($mark->getPassed()) {
                            $requiredPercentages[$masterCrsId] = (int) $mark->getMinimumLevel();
                            break;
                        }
                    }
                }

                if (empty($requiredPercentages)) {
                    $requiredPercentages[$masterCrsId] = 60;
                }
            }
        }

        $learningObjectives = [];
        if (count($finalTestsStates)) {
            $learningObjectives = self::getLearningObjectives($sorted, $finalTestsStates[$userId]);
        }

        if( !empty($finalTestsStates[$userId])) {
            $trackingToolData = self::getTrackingToolData($finalTestsStates, $userId);

            $trackingLearningObjectives = self::storeCoursesInLearningObjectives(
                $learningObjectives,
                $trackingToolData,
                $requiredPercentages
            );
        }
        return $trackingLearningObjectives;
    }

    /**
     * @param array $finalTestsStates
     * @param int   $userId
     * @return array
     */
    private static function getTrackingToolData(array $finalTestsStates, int $userId): array
    {
        $trackingToolData = [];
        $processed = [];

        foreach ($finalTestsStates[$userId] as $finalTests) {
            foreach ($finalTests as $key => $value) {
                /** @var ilLearnObjectFinalTestState $value */
                if ($value->getLocftestCrsObjId()) {
                    // check if data already exists
                    $crsObjId = $value->getLocftestCrsObjId();
                    $crsObjectiveId = $value->getLocftestObjectiveId();
                    if (isset($processed[$crsObjId])) {
                        if (isset($processed[$crsObjId][$crsObjectiveId])) {
                            continue;
                        }
                    }

                    $trackingToolData[$value->getLocftestCrsObjId()][] = [
                        'master_crs_id' => $value->getLocftestMasterCrsId(),
                        'title' => $value->getLocftestObjectiveTitle(),
                        'test_percentage' => $value->getLocftestPercentage(),
                        'test_required_percentage' => $value->getLocftestQplsRequiredPercentage(),
                        'what_is' => 1
                    ];
                    $processed[$crsObjId][$crsObjectiveId] = $userId;
                }
            }
        }
        return $trackingToolData;
    }

    /**
     * @param array $learningObjectives
     * @param array $trackingToolData
     * @param array $requiredPercentages
     * @return array
     */
    private static function storeCoursesInLearningObjectives(
        array $learningObjectives,
        array $trackingToolData,
        array $requiredPercentages
    ): array {
        foreach ($learningObjectives as $key => $learningObjective) {
            foreach ($trackingToolData as $k => $data) {

                if ($key === $k) {
                    $completed = 0;
                    foreach ($data as $course) {
                        if ($course['test_percentage'] !== null && $course['test_percentage'] >= '60') {
                            $completed++;
                        }
                    }
                    $learningObjectives[$key]['required_percentage'] = $requiredPercentages[$learningObjective['obj_id']];
                    $learningObjectives[$key]['courses'] = $data;
                    $learningObjectives[$key]['count_completed_courses'] = $completed;
                }
            }
        }
        return $learningObjectives;
    }

    /**
     * @param array $sorted
     * @param array $finalTestsStatesUser
     * @return array
     */
    private static function getLearningObjectives(array $sorted, array $finalTestsStatesUser): array
    {
        $learningObjectives = [];
        foreach ($sorted as $sort_key => $sort_arr) {

            if (array_key_exists($sort_key, $finalTestsStatesUser)) {
                /** @var ilLearnObjectFinalTestState $finalTestsState */
                $finalTestsStates_course = $finalTestsStatesUser[$sort_key];

                foreach ($finalTestsStates_course as $finalTestsState) {
                    $learningObjectives[$finalTestsState->getLocftestCrsObjId()] = array(
                        'txt' => $finalTestsState->getLocftestLearnObjectiveTitle(),
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

        return $learningObjectives;
    }

    /**
     * @param int $userId
     * @param int $courseObjId
     * @return array
     */
    private static function sortByScore(int $userId,int $courseObjId): array
    {
        $scores = NewLearningObjectiveScores::getData($userId, $courseObjId);
        $weights = getFineWeights::getData($courseObjId);
        $suggs = getLearnSuggs::getData($userId, $courseObjId);

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

                    $weightRough = self::getWeightRough(
                        (int) $score->getCourseObjId(),
                        (int) $score->getObjectiveId(),
                        $userId
                    );

                    if ($weightRough > 0) {
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
     * @param int $courseId
     * @param int $learningObjectiveId
     * @param int $userId
     * @return int|string
     */
    private static function getWeightRough(
        int $courseId,
        int $learningObjectiveId,
        int $userId
    ) {
        global $DIC;

        $calculation = new CalculateScoresAndSuggestions(
            $DIC->database(),
            new ConfigProvider(),
            new Log()
        );


        $course = new LearningObjectiveCourse(new ilObjCourse($courseId, false));
        $learningObjective = $calculation->getLearningObjective($course, $learningObjectiveId);

        $user = new User(new ilObjUser($userId));
        $config = new CourseConfigProvider($course);
        $studyProgramQuery = new StudyProgramQuery($config);
        $studyProgram = $studyProgramQuery->getByUser($user);

        return $config->getWeightRough($learningObjective, $studyProgram);
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