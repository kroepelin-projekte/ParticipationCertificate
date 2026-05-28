<?php
class LearnObjectivesFinalTestsQuery
{

    const DEFAULT_TMP_TABLE_NAME = 'tmp_learn_objectives_final_tests';


    public function getSQL(): string
    {

        $this->createTemporaryTableReqPercentage();

        return "SELECT 
    crs_objectives.crs_id,
    object_crs.title AS crs_title,
    crs_objectives.objective_id AS crs_objective_id,
    crs_objectives.title AS crs_objective_title,
    crs_objectives.position AS crs_objective_position,
    objective_tst.obj_id AS tst_obj_id,
    objective_tst.ref_id AS tst_ref_id,
    object_tst.title AS tst_title,
    tmp_req_percentage.percentage AS tst_req_percentage
FROM
    crs_objectives
        LEFT JOIN
    object_data AS object_crs ON object_crs.obj_id = crs_objectives.crs_id
        LEFT JOIN
    crs_objective_lm AS objective_tst ON objective_tst.objective_id = crs_objectives.objective_id
        AND objective_tst.type = 'tst'
        LEFT JOIN
    object_data AS object_tst ON object_tst.obj_id = objective_tst.obj_id
        LEFT JOIN
    tmp_req_percentage ON tmp_req_percentage.container_id =  crs_objectives.crs_id and tmp_req_percentage.objective_id = crs_objectives.objective_id
";
    }

    public function createTemporaryTable(string $table_name = self::DEFAULT_TMP_TABLE_NAME): void
    {
        global $DIC;
        $ilDB = $DIC->database();


        $sql = "CREATE Temporary Table IF NOT Exists $table_name  (INDEX cob (crs_id, crs_objective_id)) (" . $this->getSQL() . ")";
        //echo $sql."; ";
        $ilDB->query($sql);
    }

    private function createTemporaryTableReqPercentage(): void
    {
        global $DIC;
        $ilDB = $DIC->database();

        $sql = "CREATE Temporary Table IF NOT Exists tmp_req_percentage  (INDEX obi (objective_id)) (" . $this->getSqlReqPercentage() . ")";

        //echo $sql . "; ";
        $ilDB->query($sql);
    }


    private function getSqlReqPercentage(): string
    {
        return "SELECT objective_id, course_id as container_id, limit_perc as percentage
             from loc_user_results
             where type=".ilLOUserResults::TYPE_QUALIFIED."
             Group by objective_id,course_id, limit_perc";

    }
}