<?php

class ilExcerciseStates
{
    public static function getData(
        array $arr_usr_ids,
        int $group_ref_id
    ): array {
        global $DIC;

        $ilDB = $DIC->database();
        $result = $ilDB->query(self::getSQL($arr_usr_ids));
        $exerc_data = array();

        $items = $DIC->repositoryTree()->getChildIds($group_ref_id);

        $counted_exercises = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            if (!in_array($row['exc_ref_id'], $items)) {
                continue;
            }
            $usr_id = (int) $row['exc_usr_id'];
            // an exercise linked twice into the container counts once
            if (isset($counted_exercises[$usr_id][$row['exc_obj_id']])) {
                continue;
            }
            $counted_exercises[$usr_id][$row['exc_obj_id']] = true;

            // sum over all exercises; title/obj/ref stay those of the first exercise found (not used by callers)
            if (!isset($exerc_data[$usr_id])) {
                $exerc_state = new ilExcerciseState();
                $exerc_state->setUsrId($usr_id);
                $exerc_state->setExcObjId((int) $row['exc_obj_id']);
                $exerc_state->setExcObjTitle((string) $row['exc_obj_title']);
                $exerc_state->setExcRefId((int) $row['exc_ref_id']);
                $exerc_state->setPassed(0);
                $exerc_state->setTotal(0);
                $exerc_data[$usr_id] = $exerc_state;
            }
            $exerc_state = $exerc_data[$usr_id];
            $exerc_state->setPassed($exerc_state->getPassed() + (int) $row['exc_passed']);
            $exerc_state->setTotal($exerc_state->getTotal() + (int) $row['exc_total']);
        }

        foreach ($exerc_data as $exerc_state) {
            $total = $exerc_state->getTotal();
            $exerc_state->setPassedPercentage($total > 0 ? (int) round($exerc_state->getPassed() / $total * 100) : 0);
        }

        return $exerc_data;
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
					exerc.usr_id as exc_usr_id,
					exc_obj.title as exc_obj_title,
					exc_ass.exc_id as exc_obj_id,
					exc_ref.ref_id as  exc_ref_id,
					COUNT(CASE WHEN exerc.status = 'passed' THEN exerc.status END) as exc_passed,
					COUNT(exerc.status) as exc_total,
					COALESCE(round(( COUNT(CASE WHEN exerc.status = 'passed' THEN exerc.status END)/COUNT(exerc.status) * 100 ),0),0) as exc_passed_percentage
					FROM 
					exc_mem_ass_status as exerc
					inner join exc_assignment as exc_ass on exc_ass.id = exerc.ass_id
					inner join object_data as exc_obj on exc_obj.obj_id = exc_ass.exc_id
					inner join object_reference as exc_ref on exc_ref.obj_id = exc_ass.exc_id
					where  " . $ilDB->in('exerc.usr_id', $arr_usr_ids, false, 'integer') . "
					group by 
					exerc.usr_id,
					exc_ass.exc_id,
					exc_ref.ref_id,
					exc_obj.title";

        return $select;
    }
}
