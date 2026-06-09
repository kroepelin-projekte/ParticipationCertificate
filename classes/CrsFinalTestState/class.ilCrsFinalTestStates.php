<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;

class ilCrsFinalTestStates {

    /**
     * @param array $arr_usr_ids
     * @param int   $crsRefId crsRefId (defaults to 0=
     *
     * @return ilCrsFinalTestStates[]
     */
    public static function getData(array $arr_usr_ids = array(), int $crsRefId = 0): array
    {
        global $DIC;
        $ilDB = $DIC->database();
        $result = $ilDB->query(self::getSQL($arr_usr_ids));
        $crsitst_data = array();
        while ($row = $ilDB->fetchAssoc($result)) {
            $crsitst_state = new ilCrsFinalTestState();
            $crsitst_state->setCrsqtestUsrId($row['crsqtest_usr_id']);
            $crsitst_state->setCrsqtestCrsObjId($row['crsqtest_crs_obj_id']);
            $crsitst_state->setCrsqtestCrsRefId($row['crsqtest_crs_ref_id']);
            $crsitst_state->setCrsqtestCrsTitle($row['crsqtest_crs_title']);
            $crsitst_state->setCrsqtestQtestRefId($row['crsqtest_qtest_ref_id']);
            $crsitst_state->setCrsqtestQtestObjId($row['crsqtest_qtest_obj_id']);
            $crsitst_state->setCrsqtestQtestTitle($row['crsqtest_qtest_title']);
            $crsitst_state->setCrsqtestQtestTries($row['crsqtest_qtest_tries']);
            $crsitst_state->setCrsqtestQtestSubmitted($row['crsqtest_qtest_submitted']);

            if (( $crsRefId == 0 ) || ( $crsRefId == (int) $row['crsqtest_crs_ref_id'] )) {
                $crsitst_data[$row['crsqtest_usr_id']] = $crsitst_state;
            }
        }

        return $crsitst_data;
    }

    protected static function getSQL(array $arr_usr_ids = array()): string
    {
        global $DIC;
        $ilDB = $DIC->database();
        $course_configs = new ConfigProvider();
        $arr_malok_ids = $course_configs->getCourseRefIds();
        $select = "SELECT test_act.user_fi as crsqtest_usr_id,
					crs_obj.obj_id as crsqtest_crs_obj_id,
					crs_obj.title as crsqtest_crs_title,
					crs_ref.ref_id as crsqtest_crs_ref_id,
					qtest_ref.ref_id as crsqtest_qtest_ref_id,
					qtest_obj.obj_id as crsqtest_qtest_obj_id,
					qtest_obj.title as  crsqtest_qtest_title,
					test_act.tries as crsqtest_qtest_tries,
					test_act.submitted as crsqtest_qtest_submitted
					FROM 
					loc_settings
					inner join object_data as crs_obj on crs_obj.obj_id =  loc_settings.obj_id
					inner join object_reference as crs_ref on crs_ref.obj_id = crs_obj.obj_id
					inner join object_reference as qtest_ref on qtest_ref.ref_id = loc_settings.qtest
					inner join object_data as qtest_obj on qtest_obj.obj_id = qtest_ref.obj_id
					inner join tst_tests as test on test.obj_fi = qtest_obj.obj_id
					inner join tst_active as test_act on test_act.test_fi = test.test_id
					where loc_settings.qtest is not null AND " . $ilDB->in('crs_ref.ref_id', $arr_malok_ids, false, 'integer') . " AND " . $ilDB->in('test_act.user_fi', $arr_usr_ids, false, 'integer');

        return $select;
    }
}

?>
