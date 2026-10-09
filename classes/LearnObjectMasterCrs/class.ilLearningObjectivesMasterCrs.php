<?php
class ilLearningObjectivesMasterCrs
{

    /**
     * @return ilLearningObjectiveMasterCrs[]
     */
    public static function getData(int $crsId, array $arr_usr_ids = []): array
    {
        global $DIC;

        $ilDB = $DIC->database();
        $result = $ilDB->query(self::getSQL($crsId, $arr_usr_ids));

        $lo_master_data = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $lo_master = new ilLearningObjectiveMasterCrs();
            $lo_master->setLoMasterObjectiveTitle($row['lo_master_objective_title']);
            $lo_master->setLoMasterUsrId($row['lo_master_usr_id']);

            $lo_master_data[$row['lo_master_usr_id']][] = $lo_master;
        }

        return $lo_master_data;
    }

    /**
     * @param int   $crsId
     * @param array $arr_usr_ids
     * @return string
     */
    protected static function getSQL(int $crsId, array $arr_usr_ids = []): string
    {
        global $DIC;

        $ilDB = $DIC->database();

        $select = "SELECT 
					DISTINCT 
					crso.title as lo_master_objective_title,
					crs_memb.usr_id as lo_master_usr_id
					from crs_objectives as crso
					inner join object_data as crs_obj on crs_obj.obj_id = crso.crs_id
					inner join object_reference as crs_ref on crs_ref.obj_id = crs_obj.obj_id
					inner join loc_settings on loc_settings.obj_id = crs_obj.obj_id and loc_settings.itest > 0
					inner join obj_members as crs_memb on crs_memb.obj_id = crs_obj.obj_id
					inner join alo_crs_config as alp_crs on alp_crs.course_obj_id = crs_obj.obj_id
					WHERE crso.crs_id = " . $ilDB->quote($crsId, 'integer') . " AND "
					. $ilDB->in('crs_memb.usr_id', $arr_usr_ids, false, 'integer');

        return $select;
    }
}