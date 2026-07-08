<?php
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Score\LearningObjectiveScore;
use SRAG\ILIAS\Plugins\LearningObjectiveSuggestions\Suggestion\LearningObjectiveSuggestion;

class NewLearningObjectiveScores {

	public static function getData(int $usr_id, int $courseObjId): array
    {
		global $DIC;
		$ilDB = $DIC->database();
		$result = $ilDB->query(self::getSQL($usr_id, $courseObjId));
		$scores = array();

		while ($row = $ilDB->fetchAssoc($result)) {
			$score = new NewLearningObjectiveScore();
			$score->setCourseObjId($row['course_obj_id']);
			$score->setUserId($row['user_id']);
			$score->setScore($row['score']);
			$score->setObjectiveId($row['objective_id']);
			$score->setTitle($row['title']);
			$scores[] = $score;
		}

		return $scores;
	}


	protected static function getSQL(int $usr_id, int $crsObjId): string
    {
		global $DIC;
		$ilDB = $DIC->database();
        $select = "select scores.*, crs_objectives.*, sort from " . LearningObjectiveScore::TABLE_NAME . " as scores
					inner join crs_objectives on scores.objective_id = crs_objectives.objective_id 
					left join " . LearningObjectiveSuggestion::TABLE_NAME . " as suggestion on 
					crs_objectives.objective_id = suggestion.objective_id AND scores.user_id = suggestion.user_id 
					where scores.user_id = " . $ilDB->quote($usr_id, "integer") . " AND scores.course_obj_id = " . $ilDB->quote($crsObjId, "integer") . "
					order by scores.course_obj_id DESC, coalesce(suggestion.sort, (SELECT MAX(sugg.sort)+1 FROM " .
                    LearningObjectiveSuggestion::TABLE_NAME . " as sugg)) ASC, crs_objectives.position ASC";

		return $select;
	}
}
