<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

class getLearnSuggs {

	/**
	 * @return ilLearningObjectivesMasterCrs[]
	 */
	public static function getData(int $usr_id, int $courseObjId): array
    {
		global $DIC;
		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($usr_id, $courseObjId));
		$learn_sugg = array();
		while ($row = $ilDB->fetchAssoc($result)) {
			$suggs = new getLearnSugg();
			$suggs->setSuggObjectiveId($row['sugg_objective_id']);
			$suggs->setSuggForUser($row['sugg_for_user']);
			$suggs->setSuggObjTitle($row['sugg_objective_title']);

			$learn_sugg[] = $suggs;
		}

		return $learn_sugg;
	}


	protected static function getSQL(int $usr_id, int $courseObjId): string
    {
		global $DIC;
		$ilDB = $DIC->database();
		$select = "SELECT 
					crs_obj.title as sugg_master_crs_title,
					crs_obj.obj_id as sugg_master_crs_obj_id,
					crso.title as sugg_objective_title,
					sugg.objective_id as sugg_objective_id,
					sugg.user_id as sugg_for_user
					FROM " . LearningObjectiveSuggestion::TABLE_NAME . " as sugg
					inner join crs_objectives as crso on crso.crs_id = sugg.course_obj_id and crso.objective_id = sugg.objective_id
					inner join object_data as crs_obj on crs_obj.obj_id = crso.crs_id
	                where sugg.user_id =" . $ilDB->quote($usr_id, "integer") .
                    " AND sugg.course_obj_id = ". $ilDB->quote($courseObjId, "integer");

		return $select;
	}
}