<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\ConfigProvider;

class ilCrsFinalTestStates {

    /**
     * @param array $userIds
     * @param int   $crsRefId crsRefId
     *
     * @return ilCrsFinalTestStates[]
     */
    public static function getData(array $userIds, int $crsRefId): array
    {
        global $DIC;
        $ilDB = $DIC->database();
        $result = $ilDB->query(self::getSQL($userIds));
        $crsFinalTestData = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            if ($crsRefId == (int) $row['crsqtest_crs_ref_id']) {
                $crsFinalTestData[$row['crsqtest_usr_id']] = $row;
            }
        }

        return $crsFinalTestData;
    }

    /**
     * @param array $userIds
     * @return string
     */
    protected static function getSQL(array $userIds): string
    {
        global $DIC;
        $ilDB = $DIC->database();
        $course_configs = new ConfigProvider();
        $masterLokIds = $course_configs->getCourseRefIds();
        $select = "SELECT test_act.user_fi as crsqtest_usr_id,
					crs_obj.obj_id as crsqtest_crs_obj_id,
					crs_obj.title as crsqtest_crs_title,
					crs_ref.ref_id as crsqtest_crs_ref_id,
					qtest_ref.ref_id as crsqtest_qtest_ref_id,
					qtest_obj.obj_id as crsqtest_qtest_obj_id,
					qtest_obj.title as  crsqtest_qtest_title,
					test_act.tries as crsqtest_qtest_tries,
					test_act.submitted as crsqtest_qtest_submitted,
                    test_act.active_id as test_active_id
					FROM 
					loc_settings
					inner join object_data as crs_obj on crs_obj.obj_id =  loc_settings.obj_id
					inner join object_reference as crs_ref on crs_ref.obj_id = crs_obj.obj_id
					inner join object_reference as qtest_ref on qtest_ref.ref_id = loc_settings.qtest
					inner join object_data as qtest_obj on qtest_obj.obj_id = qtest_ref.obj_id
					inner join tst_tests as test on test.obj_fi = qtest_obj.obj_id
					inner join tst_active as test_act on test_act.test_fi = test.test_id
					where loc_settings.qtest is not null AND " . $ilDB->in('crs_ref.ref_id', $masterLokIds, false, 'integer') . " AND " . $ilDB->in('test_act.user_fi', $userIds, false, 'integer');

        return $select;
    }
}

?>
