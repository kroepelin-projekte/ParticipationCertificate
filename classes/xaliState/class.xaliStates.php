<?php
class xaliStates
{
    /**
     * @return xaliState[]
     */
    public static function getData(array $arr_usr_ids, int $group_ref_id): array
    {
        global $DIC;

        $items = $DIC->repositoryTree()->getChildIds($group_ref_id);
        $data = [];

        $arr_xali_status = [];
        // init array, set present and total to 0 for every user
        foreach ($arr_usr_ids as $usr_id) {
            $arr_xali_status[$usr_id]['present'] = 0;
            $arr_xali_status[$usr_id]['total'] = 0;
        }
        foreach ($items as $ref_id) {
            if (ilObject::_lookupType($ref_id, true) != 'xali') {
                continue;
            }
            $object_id = ilObject::_lookupObjectId($ref_id);
            /**
             * @var xaliChecklist $checklist
             */
            $total_checklist = xaliChecklist::where(['obj_id' => $object_id])->count();

            foreach ($arr_usr_ids as $usr_id) {
                $xaliUserStatus = xaliUserStatus::getInstance($usr_id, $object_id);
                $arr_xali_status[$usr_id]['present'] = (int) $arr_xali_status[$usr_id]['present'] + $xaliUserStatus->getAttendanceStatuses(xaliChecklistEntry::STATUS_PRESENT);
                $arr_xali_status[$usr_id]['total'] = $arr_xali_status[$usr_id]['total'] + 
                    $xaliUserStatus->getAttendanceStatuses(xaliChecklistEntry::STATUS_ABSENT_UNEXCUSED) + 
                    $xaliUserStatus->getAttendanceStatuses(xaliChecklistEntry::STATUS_ABSENT_EXCUSED) + 
                    $xaliUserStatus->getAttendanceStatuses(xaliChecklistEntry::STATUS_PRESENT);
                
            }
        }


        foreach ($arr_xali_status as $usr_id => $xali_state) {
            $state = new xaliState();
            $state->setUsrId($usr_id);
            $state->setPassed($xali_state['present']);
            $state->setTotal($xali_state['total']);
            $data[$usr_id] = $state;
        }
        return $data;
    }
}
