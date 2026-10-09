<?php

class ilIassStatesMulti {

    /**
     * @param array $arr_usr_ids
     * @param int   $group_ref_id
     * @return ilIassStateMulti[]
     */
	public static function getData(array $arr_usr_ids, int $group_ref_id): array
    {
		global $DIC;

		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($arr_usr_ids));
		$iass_data = array();

		$items = $DIC->repositoryTree()->getChildIds($group_ref_id);

		while ($row = $ilDB->fetchAssoc($result)) {
		    if(!in_array($row['iass_ref_id'], $items)) {
		        continue;
		    }

			$iass_state = new ilIassStateMulti();
			$iass_state->setUsrId($row['iass_usr_id']);
			$iass_state->setIassObjTitle($row['iass_obj_title']);
			$iass_state->setIassObjId($row['iass_obj_id']);
			$iass_state->setIassRefId($row['iass_ref_id']);
			$iass_state->setPassed($row['iass_passed']);
			$iass_state->setTotal($row['iass_total']);
			$iass_state->setPassedPercentage($row['iass_passed_percentage']);

			$iass_data[$row['iass_usr_id']][] = $iass_state;
		}
		return $iass_data;
	}


	/**
	 * @param array $arr_usr_ids
	 *
	 * @return string
	 */
	protected static function getSQL(array $arr_usr_ids = []): string
    {
		global $DIC;

		$ilDB = $DIC->database();

		$select = "SELECT 
					iass.usr_id as iass_usr_id,
					iass_obj.title as iass_obj_title,
					iass_obj.obj_id as iass_obj_id,
					iass_ref.ref_id as  iass_ref_id,
					COUNT(CASE WHEN iass.learning_progress = 2 THEN learning_progress END) as iass_passed,
					COUNT(iass.learning_progress) as iass_total,
					COALESCE(round(( COUNT(CASE WHEN iass.learning_progress = 2 THEN iass.learning_progress END)/COUNT(iass.learning_progress) * 100 ),0),0) as iass_passed_percentage
					FROM 
					iass_members as iass
					inner join object_data as iass_obj on iass_obj.obj_id = iass.obj_id
					inner join object_reference as iass_ref on iass_ref.obj_id = iass_obj.obj_id
					where  " . $ilDB->in('iass.usr_id', $arr_usr_ids, false, 'integer') . "
					group by 
					iass.usr_id,
					iass_obj.obj_id,
					iass_ref.ref_id,
					iass_obj.title
					";

		return $select;
	}
}