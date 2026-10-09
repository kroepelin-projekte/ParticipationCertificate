<?php
class ilLearnObjectFinalTestState {
    protected ?string $locftest_learn_objective_title;

    protected ?int $locftest_usr_id;

    protected ?int $locftest_crs_obj_id = null;

    protected ?int $locftest_master_crs_id = null;

    protected ?string $locftest_master_crs_title;

    protected ?string $locftest_crs_title;

    protected ?int $locftest_objective_id = null;

    protected ?string $locftest_objective_title;

    protected ?int $locftest_master_objective_id = null;

    protected ?int $locftest_test_ref_id = null;

    protected ?int $locftest_test_obj_id = null;

    protected ?string $locftest_test_title;

    protected ?int $locftest_percentage = null;

    protected ?int $locftest_qpls_required_percentage = null;

    protected bool $objectives_all_completed;

    protected bool $objectives_sug_completed;

    protected bool $objectives_suggested;

    /**
     * @return int|null
     */
    public function getLocftestMasterObjectiveId(): ?int
    {
	    return $this->locftest_master_objective_id;
    }

    /**
     * @param int|null $locftest_master_objective_id
     * @return void
     */
    public function setLocftestMasterObjectiveId ( ?int $locftest_master_objective_id ): void
    {
    	$this->locftest_master_objective_id = $locftest_master_objective_id;
    }

    /**
     * @return string|null
     */
    public function getLocftestLearnObjectiveTitle() : ?string
    {
        return $this->locftest_learn_objective_title;
    }

    /**
     * @param string|null $locftest_learn_objective_title
     * @return void
     */
    public function setLocftestLearnObjectiveTitle(?string $locftest_learn_objective_title): void
    {
        $this->locftest_learn_objective_title = $locftest_learn_objective_title;
    }

    /**
     * @return int|null
     */
	public function getLocftestUsrId(): ?int
    {
		return $this->locftest_usr_id;
	}

    /**
     * @param int|null $locftest_usr_id
     * @return void
     */
	public function setLocftestUsrId(?int $locftest_usr_id): void
    {
		$this->locftest_usr_id = $locftest_usr_id;
	}

    /**
     * @return int|null
     */
	public function getLocftestCrsObjId(): ?int
    {
		return $this->locftest_crs_obj_id;
	}

    /**
     * @param int|null $locftest_crs_obj_id
     * @return void
     */
	public function setLocftestCrsObjId(?int $locftest_crs_obj_id): void
    {
		$this->locftest_crs_obj_id = $locftest_crs_obj_id;
	}

    /**
     * @return string|null
     */
	public function getLocftestCrsTitle(): ?string
    {
		return $this->locftest_crs_title;
	}

    /**
     * @param string|null $locftest_crs_title
     * @return void
     */
	public function setLocftestCrsTitle(?string $locftest_crs_title): void
    {
		$this->locftest_crs_title = $locftest_crs_title;
	}

    /**
     * @return int|null
     */
	public function getLocftestObjectiveId(): ?int
    {
		return $this->locftest_objective_id;
	}

    /**
     * @param int|null $locftest_objective_id
     * @return void
     */
	public function setLocftestObjectiveId(?int $locftest_objective_id): void
    {
		$this->locftest_objective_id = $locftest_objective_id;
	}

    /**
     * @return string|null
     */
	public function getLocftestObjectiveTitle(): ?string
    {
		return $this->locftest_objective_title;
	}

    /**
     * @param string|null $locftest_objective_title
     * @return void
     */
	public function setLocftestObjectiveTitle(?string $locftest_objective_title): void
    {
		$this->locftest_objective_title = $locftest_objective_title;
	}

    /**
     * @return int|null
     */
	public function getLocftestMasterCrsId(): ?int
    {
        return $this->locftest_master_crs_id;
    }

    /**
     * @param int|null $locftest_master_crs_id
     * @return void
     */
	public function setLocftestMasterCrsId(?int $locftest_master_crs_id): void
    {
        $this->locftest_master_crs_id = $locftest_master_crs_id;
    }

    /**
     * @return string|null
     */
	public function getLocftestMasterCrsTitle() : ?string
    {
	    return $this->locftest_master_crs_title;
    }

    /**
     * @param string|null $locftest_master_crs_title
     * @return void
     */
	public function setLocftestMasterCrsTitle(?string $locftest_master_crs_title): void
    {
        $this->locftest_master_crs_title = $locftest_master_crs_title;
    }

    /**
     * @return int|null
     */
	public function getLocftestTestRefId(): ?int
    {
		return $this->locftest_test_ref_id;
    }

    /**
     * @param int|null $locftest_test_ref_id
     * @return void
     */
	public function setLocftestTestRefId(?int $locftest_test_ref_id): void
    {
			$this->locftest_test_ref_id = $locftest_test_ref_id;
	}

    /**
     * @return int|null
     */
	public function getLocftestTestObjId(): ?int
    {
		return $this->locftest_test_obj_id;
	}

    /**
     * @param int|null $locftest_test_obj_id
     * @return void
     */
	public function setLocftestTestObjId(?int $locftest_test_obj_id): void
    {
		$this->locftest_test_obj_id = $locftest_test_obj_id;
	}

    /**
     * @return string|null
     */
	public function getLocftestTestTitle(): ?string
    {
		return $this->locftest_test_title;
	}

    /**
     * @param string|null $locftest_test_title
     * @return void
     */
	public function setLocftestTestTitle(?string $locftest_test_title): void
    {
		$this->locftest_test_title = $locftest_test_title;
	}

    /**
     * @return int|null
     */
	public function getLocftestPercentage(): ?int
    {
		return $this->locftest_percentage;
	}

    /**
     * @param int|null $locftest_percentage
     * @return void
     */
	public function setLocftestPercentage(?int $locftest_percentage): void
    {
		$this->locftest_percentage = $locftest_percentage;
	}

    /**
     * @return int|null
     */
	public function getLocftestQplsRequiredPercentage(): ?int
    {
		return $this->locftest_qpls_required_percentage;
	}

    /**
     * @param int|null $locftest_qpls_required_percentage
     * @return void
     */
	public function setLocftestQplsRequiredPercentage(?int $locftest_qpls_required_percentage): void
    {
		$this->locftest_qpls_required_percentage = $locftest_qpls_required_percentage;
	}

    /**
     * @return bool
     */
	public function isObjectivesAllCompleted(): bool
    {
		return $this->objectives_all_completed;
	}

    /**
     * @param bool $objectives_all_completed
     * @return void
     */
	public function setObjectivesAllCompleted(bool $objectives_all_completed): void
    {
		$this->objectives_all_completed = $objectives_all_completed;
	}

    /**
     * @return bool
     */
	public function isObjectivesSugCompleted(): bool
    {
		return $this->objectives_sug_completed;
	}

    /**
     * @param bool $objectives_sug_completed
     * @return void
     */
	public function setObjectivesSugCompleted(bool $objectives_sug_completed): void
    {
		$this->objectives_sug_completed = $objectives_sug_completed;
	}

    /**
     * @return bool
     */
	public function getObjectivesSuggested(): bool
    {
		return $this->objectives_suggested;
	}

    /**
     * @param bool $objectives_suggested
     * @return void
     */
	public function setObjectivesSuggested(bool $objectives_suggested): void
    {
		$this->objectives_suggested = $objectives_suggested;
	}
}
