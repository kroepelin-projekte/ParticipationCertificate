<?php
class ilLearnObjectFinalTestStates
{
    /**
     * @param int   $courseObjId
     * @param array $userIds
     * @return array
     */
    public static function getDataByCourseObjId(int $courseObjId, array $userIds = array()): array
    {
        global $DIC;
        $ilDB = $DIC->database();
        $result = $ilDB->query(self::getSQLByMasterCourseObjId($courseObjId, $userIds));
        $locftst_data = array();

        while ($row = $ilDB->fetchAssoc($result)) {

            $locftst_state = new ilLearnObjectFinalTestState();
            $locftst_state->setLocftestUsrId($row['usr_id']);
            $locftst_state->setLocftestCrsObjId($row['learn_objective_crs_obj_id']);
            $locftst_state->setLocftestLearnObjectiveTitle($row['learn_objective_title']);
            $locftst_state->setLocftestCrsTitle($row['learn_objective_crs_title']);
            $locftst_state->setLocftestMasterObjectiveId($row['master_crs_objective_id']);
            $locftst_state->setLocftestObjectiveId($row['crs_objective_id']);
            $locftst_state->setLocftestObjectiveTitle($row['crs_objective_title']);
            $locftst_state->setLocftestTestObjId($row['tst_obj_id']);
            $locftst_state->setLocftestTestRefId($row['tst_ref_id']);
            $locftst_state->setLocftestTestTitle($row['tst_title']);
            $locftst_state->setLocftestPercentage($row['usr_percentage']);
            $locftst_state->setObjectivesAllCompleted($row['objectives_all_completed']);
            $locftst_state->setObjectivesSugCompleted($row['objectives_sug_completed']);
            $locftst_state->setObjectivesSuggested($row['suggested']);
            $locftst_state->setLocftestQplsRequiredPercentage($row['tst_req_percentage']);
            $locftst_state->setLocftestMasterCrsId($row['master_crs_id']);
            $locftst_state->setLocftestMasterCrsTitle($row['master_crs_title']);

            $locftst_data[$row['usr_id']][$row['master_crs_objective_id']][] = $locftst_state;
        }

        return $locftst_data;
    }

    /**
     * @param array $userIds
     * @param int   $courseObjId
     * @return string
     */
    protected static function getSQLByMasterCourseObjId(int $courseObjId, array $userIds = array()): string
    {
        global $DIC;

        $ilDB = $DIC->database();

        $learn_objectives_sugg_courses_query = new LearnObjectivesSuggCoursesQuery();
        $learn_objectives_sugg_courses_query->createTemporaryTable(LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_1");
        $learn_objectives_sugg_courses_query->createTemporaryTable(LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_2");
        $learn_objectives_sugg_courses_query->createTemporaryTable(LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_3");


        $learn_objectives_courses_query = new LearnObjectivesCoursesQuery();
        $learn_objectives_final_tests_query = new LearnObjectivesFinalTestsQuery();
        $learn_objectives_final_tests_query->createTemporaryTable();

        $select = "SELECT
					learn_objective_crs.master_crs_id,
					learn_objective_crs.master_crs_title,
					learn_objective_crs.master_crs_objective_id,
					learn_objective_crs.learn_objective_title,
					learn_objective_crs.learn_objective_crs_title,
       				learn_objective_crs.learn_objective_crs_obj_id,
					final_tests.crs_objective_id,
					final_tests.crs_objective_title,
					final_tests.tst_title,
					final_tests.tst_obj_id,
					final_tests.tst_ref_id,
					final_tests.tst_req_percentage,
					crs_memb.usr_id as usr_id,
					loc_user_results.result_perc as usr_percentage,
					
    				CASE WHEN loc_user_results.result_perc >= final_tests.tst_req_percentage then 1 else 0 end as objectives_all_completed,
    				
    				CASE WHEN exists (SELECT   * from ".LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_1
 where objective_id = learn_objective_crs.master_crs_objective_id AND  user_id = crs_memb.usr_id)  AND loc_user_results.result_perc >= final_tests.tst_req_percentage then 1 else 0 end as objectives_sug_completed,
 
    				CASE WHEN exists (SELECT   * from ".LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_2
 where objective_id = learn_objective_crs.master_crs_objective_id AND  user_id = crs_memb.usr_id)  then loc_user_results.result_perc else 0 end as objectives_sug_percentage,
    				
    				CASE WHEN exists (SELECT   * from ".LearnObjectivesSuggCoursesQuery::DEFAULT_TMP_TABLE_NAME."_3
 where objective_id = learn_objective_crs.master_crs_objective_id AND  user_id = crs_memb.usr_id)  then 1 else 0 end as suggested
 
                    FROM 
                    
                    (".$learn_objectives_courses_query->getSQL($courseObjId).") as learn_objective_crs
                    
                    INNER JOIN (SELECT * from ".LearnObjectivesFinalTestsQuery::DEFAULT_TMP_TABLE_NAME.") as final_tests on final_tests.crs_id = learn_objective_crs.learn_objective_crs_obj_id
                    
                    INNER JOIN obj_members as crs_memb on ".$ilDB->in('crs_memb.usr_id', $userIds, false, 'integer')." and crs_memb.obj_id = learn_objective_crs.master_crs_id
                    
                    LEFT JOIN loc_user_results
                        ON loc_user_results.course_id = learn_objective_crs.master_crs_id
                        AND loc_user_results.user_id = crs_memb.usr_id AND ".$ilDB->in('loc_user_results.user_id', $userIds, false, 'integer')."
                        AND loc_user_results.objective_id = learn_objective_crs.master_crs_objective_id
                        AND loc_user_results.type = ".ilLOUserResults::TYPE_QUALIFIED."
                        
			        WHERE learn_objective_crs.master_crs_id = " . $ilDB->quote($courseObjId, 'integer')  . "
			        ORDER BY learn_objective_crs.master_crs_objective_position, final_tests.crs_objective_position";

        return $select;
    }

	protected static function createTemporaryTableTestMaxResult(string $table_name = 'tmp_test_max_result'): void
    {
		global $DIC;
		$ilDB = $DIC->database();


		$sql = "CREATE Temporary Table IF NOT Exists $table_name  (INDEX af (active_fi)) (SELECT max(points) as points, active_fi, maxpoints FROM tst_pass_result group by active_fi, maxpoints)";
		$ilDB->query($sql);
	}

	public static function createTemporaryTableLearnObjectFinalTest(int $courseObjId, array $arr_usr_ids = array(), string $table_name = 'tmp_lo_fin_test'): void
    {
		global $DIC;
		$ilDB = $DIC->database();

        $ilDB->query("DROP TEMPORARY TABLE IF EXISTS $table_name");
		$sql = "CREATE Temporary Table IF NOT Exists $table_name (" . self::getSQLByMasterCourseObjId($courseObjId, $arr_usr_ids) . ")";

		$ilDB->query($sql);
	}
}
