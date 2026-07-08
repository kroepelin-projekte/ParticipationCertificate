<?php
class LearnObjectivesCoursesQuery {

	const DEFAULT_TMP_TABLE_NAME = 'tmp_learn_objectives_courses';

	public function getSQL($courseObjId): string
    {
        global $DIC;

        $ilDB = $DIC->database();

        return "SELECT  crs_objectives.crs_id as master_crs_id, 
		master_crs_obj.title as master_crs_title,
		crs_objectives.title as learn_objective_title,
		crs_objectives.objective_id as master_crs_objective_id, 
		crs_objectives.title as master_crs_objective_title, 
		crs_objectives.position as master_crs_objective_position, 
        objective_crsr.obj_id as learn_objective_crsr_obj_id,
        objective_crs_obj.title as learn_objective_crs_title,
        objective_crs_obj.obj_id as learn_objective_crs_obj_id
		from crs_objectives
        inner join object_data as master_crs_obj on master_crs_obj.obj_id = crs_objectives.crs_id
		inner join crs_objective_lm as objective_crsr on objective_crsr.objective_id = crs_objectives.objective_id and objective_crsr.type = 'crsr'
		inner join container_reference as real_objective_crs on real_objective_crs.obj_id = objective_crsr.obj_id
        inner join object_data as objective_crs_obj on objective_crs_obj.obj_id = real_objective_crs.target_obj_id 
        WHERE crs_objectives.crs_id = " . $ilDB->quote($courseObjId, 'integer');
	}

	/**
	 * @param string $table_name
	 */
	public function createTemporaryTable(string $table_name =  self::DEFAULT_TMP_TABLE_NAME): void
    {
		global $DIC;
		$ilDB = $DIC->database();

		$sql = "CREATE Temporary Table IF NOT Exists $table_name  (INDEX cob (crs_id, objective_id)) (".$this->getSQL().")";

		//echo $sql;

		$ilDB->query($sql);
	}
}