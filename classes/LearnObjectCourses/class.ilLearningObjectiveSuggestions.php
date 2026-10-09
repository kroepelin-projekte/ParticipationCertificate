<?php
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

class ilLearningObjectiveSuggestions {

	/**
	 * @return ilLearningObjectivesMasterCrs[]
	 */
	public static function getData(array $arr_usr_ids = []): array
    {
		global $DIC;

		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($arr_usr_ids));
		$sug_data = [];
		while ($row = $ilDB->fetchAssoc($result)) {
            /** @var ilLearningObjectivesMasterCrs $l_o_sugg */
			$l_o_sugg = new ilLearningObjectivesMasterCrs();
			$l_o_sugg->setMasterCrsTitle($row['sugg_master_crs_title']);
			$l_o_sugg->setMasterCrsObjId($row['sugg_master_crs_obj_id']);
			$l_o_sugg->setSuggObjectiveTitle($row['sugg_objective_title']);
			$l_o_sugg->setSuggObjectiveId($row['sugg_objective_id']);
			$l_o_sugg->setSuggForUserId($row['sugg_for_user']);

			$sug_data[$row['sugg_for_user']] = $l_o_sugg;
		}

		return $sug_data;
	}

    /**
     * @param array $arr_usr_ids
     * @return string
     */
	protected static function getSQL(array $arr_usr_ids = []): string
    {
		global $DIC;

		$ilDB = $DIC->database();

		$select = "SELECT 
					crs_obj.title as sugg_master_crs_title,
					crs_obj.obj_id as sugg_master_crs_obj_id,
					crso.title as sugg_objective_title,
					sugg.objective_id as sugg_objective_id,
					sugg.user_id as sugg_for_user,
					loc_user_results.result_perc AS result_perc,
					loc_user_results.limit_perc AS limit_perc
					FROM " . LearningObjectiveSuggestion::TABLE_NAME . " as sugg
					inner join crs_objectives as crso on crso.crs_id = sugg.course_obj_id and crso.objective_id = sugg.objective_id
					inner join object_data as crs_obj on crs_obj.obj_id = crso.crs_id
					 LEFT JOIN loc_user_results ON loc_user_results.objective_id = crso.objective_id and learn_objective_crs.master_crs_objective_id
        AND loc_user_results.user_id =  sugg.user_id
	                where " . $ilDB->in('sugg.user_id', $arr_usr_ids, false, 'integer');

		return $select;
	}

	/**
	 * @param array  $arr_usr_ids
	 * @param string $table_name
	 */
	public static function createTemporaryTableLearnObjectSugg(array $arr_usr_ids = array(), string $table_name = 'tmp_lo_sugg'): void
    {
		global $DIC;
		$ilDB = $DIC->database();

		$sql = "CREATE Temporary Table IF NOT Exists $table_name (" . self::getSql($arr_usr_ids) . ")";

		$ilDB->query($sql);
	}
}