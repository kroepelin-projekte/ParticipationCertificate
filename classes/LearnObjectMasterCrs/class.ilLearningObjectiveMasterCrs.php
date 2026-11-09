<?php
class ilLearningObjectiveMasterCrs {
	protected string $lo_master_objective_title;

	protected int $lo_master_usr_id;

    /**
     * @return string
     */
	public function getLoMasterObjectiveTitle(): string
    {
		return $this->lo_master_objective_title;
	}

    /**
     * @param string $lo_master_objective_title
     * @return void
     */
	public function setLoMasterObjectiveTitle(string $lo_master_objective_title): void
    {
		$this->lo_master_objective_title = $lo_master_objective_title;
	}

    /**
     * @return int
     */
	public function getLoMasterUsrId(): int
    {
		return $this->lo_master_usr_id;
	}

    /**
     * @param int $lo_master_usr_id
     * @return void
     */
	public function setLoMasterUsrId(int $lo_master_usr_id): void
    {
		$this->lo_master_usr_id = $lo_master_usr_id;
	}
}