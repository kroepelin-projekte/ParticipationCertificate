<?php
class xaliState {

    protected int $total;

    protected int $passed;

    protected int $usr_id;

    protected int $passed_percentage;

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