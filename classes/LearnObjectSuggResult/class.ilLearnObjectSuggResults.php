<?php
class ilLearnObjectSuggResults {

    /**
     * @param int   $course_obj_id
     * @param array $arr_usr_ids
     * @return array
     */
	public static function getData(int $course_obj_id, array $arr_usr_ids = []): array
    {
		global $DIC;

		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($course_obj_id, $arr_usr_ids));
		$reached_percentage_data = [];

        while ($row = $ilDB->fetchAssoc($result)) {
			$reached_percentage = new ilLearnObjectSuggResult();
			$reached_percentage->setUsrId((int) $row['usr_id']);
            $reached_percentage->setPointsAsPercentage((int) $row['points_as_percentage']);
            $reached_percentage->setPointsAsPercentageAsString((int) $row['points_as_percentage']."%");
            $reached_percentage->setObjectiveAsPercentage((int) $row['objective_as_percentage']);
            $reached_percentage->setObjectiveAsFractionString((string) $row['objective_as_fraction_string']);
			$reached_percentage->setLimitPercentage((int) $row['limit_perc']);
			$reached_percentage_data[(int) $row['usr_id']] = $reached_percentage;
		}

		return $reached_percentage_data;
	}

    /**
     * @param int   $course_obj_id
     * @param array $arr_usr_ids
     * @return string
     */
	protected static function getSQL(int $course_obj_id, array $arr_usr_ids = []): string
    {
		ilLearnObjectFinalTestStates::createTemporaryTableLearnObjectFinalTest(
            $course_obj_id,
            $arr_usr_ids,
            'tmp_lo_fin_test'
        );

        $select = "SELECT round((SUM(objectives_sug_percentage) / SUM(suggested)),0) as points_as_percentage,
					usr_id, 
					round(avg(tst_req_percentage),0) as limit_perc,
					round((SUM(objectives_sug_completed) / SUM(suggested)) * 100,0)  as objective_as_percentage,
					CONCAT(SUM(objectives_sug_completed),'/',SUM(suggested)) as objective_as_fraction_string
					from tmp_lo_fin_test
					group by usr_id";

		return $select;
	}
}