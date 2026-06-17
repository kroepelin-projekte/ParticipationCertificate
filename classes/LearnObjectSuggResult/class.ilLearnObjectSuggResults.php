<?php
class ilLearnObjectSuggResults {

	public static function getData(int $courseObjId, array $arr_usr_ids = array()): array
    {
		global $DIC;
		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($courseObjId, $arr_usr_ids));
		$reached_percentage_data = array();
		while ($row = $ilDB->fetchAssoc($result)) {
			$reached_percentage = new ilLearnObjectSuggResult();
			$reached_percentage->setUsrId((int)$row['usr_id']);
            $reached_percentage->setPointsAsPercentage((int)$row['points_as_percentage']);
            $reached_percentage->setPointsAsPercentageAsString((int)$row['points_as_percentage']."%");
            $reached_percentage->setObjectiveAsPercentage((int)$row['objective_as_percentage']);
            $reached_percentage->setObjectiveAsFractionString((string)$row['objective_as_fraction_string']);
			$reached_percentage->setLimitPercentage((int)$row['limit_perc']);
			$reached_percentage_data[(int)$row['usr_id']] = $reached_percentage;
		}

		return $reached_percentage_data;
	}

	protected static function getSQL(int $courseObjId, array $arr_usr_ids = array()): string
    {
		ilLearnObjectFinalTestStates::createTemporaryTableLearnObjectFinalTest($courseObjId,$arr_usr_ids, 'tmp_lo_fin_test');

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