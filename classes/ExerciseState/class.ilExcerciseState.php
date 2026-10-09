<?php
class ilExcerciseState {

	protected string $exc_obj_title;

	protected int $exc_obj_id;

	protected int $exc_ref_id;

	protected int $total;

	protected int $passed;

	protected int $passed_percentage;

	protected int $usr_id;

    /**
     * @return string
     */
	public function getExcObjTitle(): string
    {
		return $this->exc_obj_title;
	}

    /**
     * @param string $exc_obj_title
     */
	public function setExcObjTitle(string $exc_obj_title): void
    {
		$this->exc_obj_title = $exc_obj_title;
	}

    /**
     * @return int
     */
	public function getExcObjId(): int
    {
		return $this->exc_obj_id;
	}

    /**
     * @param int $exc_obj_id
     * @return void
     */
	public function setExcObjId(int $exc_obj_id): void
    {
		$this->exc_obj_id = $exc_obj_id;
	}

    /**
     * @return int
     */
	public function getExcRefId(): int
    {
		return $this->exc_ref_id;
	}

    /**
     * @param int $exc_ref_id
     * @return void
     */
	public function setExcRefId(int $exc_ref_id): void
    {
		$this->exc_ref_id = $exc_ref_id;
	}

    /**
     * @return int
     */
	public function getTotal(): int
    {
		return $this->total;
	}

    /**
     * @param int $total
     * @return void
     */
	public function setTotal(int $total): void
    {
		$this->total = $total;
	}

    /**
     * @return int
     */
	public function getPassed(): int
    {
		return $this->passed;
	}

    /**
     * @param int $passed
     * @return void
     */
	public function setPassed(int $passed): void
    {
		$this->passed = $passed;
	}

    /**
     * @return int
     */
	public function getPassedPercentage(): int
    {
		return $this->passed_percentage;
	}

    /**
     * @param int $passed_percentage
     * @return void
     */
	public function setPassedPercentage(int $passed_percentage): void
    {
		$this->passed_percentage = $passed_percentage;
	}

    /**
     * @return int
     */
	public function getUsrId(): int
    {
		return $this->usr_id;
	}

    /**
     * @param int $usr_id
     * @return void
     */
	public function setUsrId(int $usr_id): void
    {
		$this->usr_id = $usr_id;
	}
}