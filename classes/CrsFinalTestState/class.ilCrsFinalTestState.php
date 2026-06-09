<?php

class ilCrsFinalTestState {
	protected int $crsqtest_usr_id;
	protected int $crsqtest_crs_obj_id;
	protected int $crsqtest_crs_ref_id;
	protected string $crsqtest_crs_title;
	protected int $crsqtest_qtest_ref_id;
	protected int $crsqtest_qtest_obj_id;
	protected string $crsqtest_qtest_title;
	protected int $crsqtest_qtest_tries;
	protected int $crsqtest_qtest_submitted;

	public function getCrsqtestUsrId(): int
    {
		return $this->crsqtest_usr_id;
	}

	public function setCrsqtestUsrId(int $crsqtest_usr_id): void
    {
		$this->crsqtest_usr_id = $crsqtest_usr_id;
	}

	public function getCrsqtestCrsObjId(): int
    {
		return $this->crsqtest_crs_obj_id;
	}

	public function setCrsqtestCrsObjId(int $crsqtest_crs_obj_id): void
    {
		$this->crsqtest_crs_obj_id = $crsqtest_crs_obj_id;
	}

	public function getCrsqtestCrsRefId():int {
		return $this->crsqtest_crs_ref_id;
	}

	public function setCrsqtestCrsRefId(int $crsqtest_crs_ref_id): void
    {
		$this->crsqtest_crs_ref_id = $crsqtest_crs_ref_id;
	}

	public function getCrsqtestCrsTitle(): string
    {
		return $this->crsqtest_crs_title;
	}

	public function setCrsqtestCrsTitle(string $crsqtest_crs_title): void
    {
		$this->crsqtest_crs_title = $crsqtest_crs_title;
	}

	public function getCrsqtestQtestRefId(): int
    {
		return $this->crsqtest_qtest_ref_id;
	}

	public function setCrsqtestQtestRefId(int $crsqtest_itest_ref_id): void
    {
		$this->crsqtest_qtest_ref_id = $crsqtest_itest_ref_id;
	}

	public function getCrsqtestQtestObjId(): int
    {
		return $this->crsqtest_qtest_obj_id;
	}

	public function setCrsqtestQtestObjId(int $crsqtest_itest_obj_id): void
    {
		$this->crsqtest_qtest_obj_id = $crsqtest_itest_obj_id;
	}

	public function getCrsqtestQtestTitle(): string
    {
		return $this->crsqtest_qtest_title;
	}

	public function setCrsqtestQtestTitle(string $crsqtest_itest_title): void
    {
		$this->crsqtest_qtest_title = $crsqtest_itest_title;
	}

	public function getCrsqtestQtestTries(): int
    {
		return $this->crsqtest_qtest_tries;
	}

	public function setCrsqtestQtestTries(int $crsqtest_itest_tries): void
    {
		$this->crsqtest_qtest_tries = $crsqtest_itest_tries;
	}

	public function getCrsqtestQtestSubmitted(): int
    {
		return $this->crsqtest_qtest_submitted;
	}

	public function setCrsqtestQtestSubmitted(int $crsqtest_itest_submitted): void
    {
		$this->crsqtest_qtest_submitted = $crsqtest_itest_submitted;
	}
}
?>