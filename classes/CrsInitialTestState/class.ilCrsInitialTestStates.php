<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;

class ilCrsInitialTestStates {

	/**
	 * @param array $arr_usr_ids
	 * @param int $crs_ref_id (defaults to 0)
  	 *
	 * @return ilCrsInitialTestState[]
	 */
	public static function getData(array $arr_usr_ids = array(), int $crs_ref_id = 0): array
    {
		global $DIC;

		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($arr_usr_ids));
		$crsitst_data = array();

		while ($row = $ilDB->fetchAssoc($result)) {
			$crsitst_state = new ilCrsInitialTestState();
			$crsitst_state->setCrsitestUsrId($row['crsitest_usr_id']);
			$crsitst_state->setCrsitestCrsObjId($row['crsitest_crs_obj_id']);
			$crsitst_state->setCrsitestCrsRefId($row['crsitest_crs_ref_id']);
			$crsitst_state->setCrsitestCrsTitle($row['crsitest_crs_title']);
			$crsitst_state->setCrsitestItestRefId($row['crsitest_itest_ref_id']);
			$crsitst_state->setCrsitestItestObjId($row['crsitest_itest_obj_id']);
			$crsitst_state->setCrsitestItestTitle($row['crsitest_itest_title']);
			$crsitst_state->setCrsitestItestTries($row['crsitest_itest_tries']);
			$crsitst_state->setCrsitestItestSubmitted($row['crsitest_itest_submitted']);

			if (( $crs_ref_id == 0 ) || ( $crs_ref_id == (int) $row['crsitest_crs_ref_id'] )) {
				$crsitst_data[$row['crsitest_usr_id']] = $crsitst_state;
			}
		}

		return $crsitst_data;
	}

	/**
	 * @param array $arr_usr_ids
	 * @return string
	 */
	protected static function getSQL(array $arr_usr_ids = array()): string
    {
		global $DIC;

		$ilDB = $DIC->database();
		$course_configs = new ConfigProvider();
	    $arr_malok_ids = $course_configs->getCourseRefIds();

		$select = "SELECT test_act.user_fi as crsitest_usr_id,
					crs_obj.obj_id as crsitest_crs_obj_id,
					crs_obj.title as crsitest_crs_title,
					crs_ref.ref_id as crsitest_crs_ref_id,
					itest_ref.ref_id as crsitest_itest_ref_id,
					itest_obj.obj_id as crsitest_itest_obj_id,
					itest_obj.title as  crsitest_itest_title,
					test_act.tries as crsitest_itest_tries,
					test_act.submitted as crsitest_itest_submitted
					FROM 
					loc_settings
					inner join object_data as crs_obj on crs_obj.obj_id =  loc_settings.obj_id
					inner join object_reference as crs_ref on crs_ref.obj_id = crs_obj.obj_id
					inner join object_reference as itest_ref on itest_ref.ref_id = loc_settings.itest
					inner join object_data as itest_obj on itest_obj.obj_id = itest_ref.obj_id
					inner join tst_tests as test on test.obj_fi = itest_obj.obj_id
					inner join tst_active as test_act on test_act.test_fi = test.test_id
					where loc_settings.itest is not null AND " . $ilDB->in('crs_ref.ref_id', $arr_malok_ids, false, 'integer') . " AND " . $ilDB->in('test_act.user_fi', $arr_usr_ids, false, 'integer');

		return $select;
	}
}

?>
