<?php

use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Config\CourseConfig;

class getFineWeights {

	public static function getData(int $courseObjId): array
    {
		global $DIC;
		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($courseObjId));
		$weight = array();
		$weights = new getFineWeight();

		while ($row = $ilDB->fetchAssoc($result)) {
			// just read out the objectives and values from the cfg_key
			if (str_starts_with($row['cfg_key'],'weight_fine_')) {
				$weight[$row['cfg_key']] = $row['value'];
			}
			// the complete switch clause may be obsolete
			// this seems to have been broken for a while, since it trelies on 10 fixed objective-IDs
			
			switch ($row['cfg_key']) {
				case 'weight_fine_90':
					$weights->setWeightFine90($row['value']);
					break;
				case 'weight_fine_91':
					$weights->setWeightFine91($row['value']);
					break;
				case 'weight_fine_92':
					$weights->setWeightFine92($row['value']);
					break;
				case 'weight_fine_93':
					$weights->setWeightFine93($row['value']);
					break;
				case 'weight_fine_94':
					$weights->setWeightFine94($row['value']);
					break;
				case 'weight_fine_95':
					$weights->setWeightFine95($row['value']);
					break;
				case 'weight_fine_96':
					$weights->setWeightFine96($row['value']);
					break;
				case 'weight_fine_97':
					$weights->setWeightFine97($row['value']);
					break;
				case 'weight_fine_98':
					$weights->setWeightFine98($row['value']);
					break;
				case 'weight_fine_99':
					$weights->setWeightFine99($row['value']);
					break;
			}
		}

		return $weight;
	}

	protected static function getSQL($courseObjId): string
    {
        global $DIC;

        $ilDB = $DIC->database();

		$select = "select * from " . CourseConfig::TABLE_NAME . " 
		WHERE course_obj_id = " . $ilDB->quote($courseObjId, "integer");

		return $select;
	}
}
