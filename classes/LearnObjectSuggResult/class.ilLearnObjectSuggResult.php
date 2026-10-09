<?php
class ilLearnObjectSuggResult
{
    public const int CALC_TYPE_BY_POINTS = 1;

    public const int CALC_TYPE_BY_COMPLETED_OBJECTIVE = 2;

    public const int CALC_TYPE_HIGHEST_VALUE = 3;

    protected int $usr_id;

    protected int $points_as_percentage;

    protected string $points_as_percentage_as_string;

    protected int $objective_as_percentage;

    protected string $objective_as_fraction_string;

    protected int $limit_percentage;

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

    /**
     * @return int
     */
    public function getLimitPercentage(): int
    {
        return $this->limit_percentage;
    }

    /**
     * @param $limit_percentage
     * @return void
     */
    public function setLimitPercentage($limit_percentage): void
    {
        $this->limit_percentage = $limit_percentage;
    }

    /**
     * @return int
     */
    public function getPointsAsPercentage(): int
    {
        return $this->points_as_percentage;
    }

    /**
     * @param int $points_as_percentage
     * @return void
     */
    public function setPointsAsPercentage(int $points_as_percentage): void
    {
        $this->points_as_percentage = $points_as_percentage;
    }

    /**
     * @return string
     */
    public function getPointsAsPercentageAsString(): string
    {
        return $this->points_as_percentage_as_string;
    }

    /**
     * @param string $points_as_percentage_as_string
     * @return void
     */
    public function setPointsAsPercentageAsString(string $points_as_percentage_as_string): void
    {
        $this->points_as_percentage_as_string = $points_as_percentage_as_string;
    }

    /**
     * @return int
     */
    public function getObjectiveAsPercentage(): int
    {
        return $this->objective_as_percentage;
    }

    /**
     * @param int $objective_as_percentage
     * @return void
     */
    public function setObjectiveAsPercentage(int $objective_as_percentage): void
    {
        $this->objective_as_percentage = $objective_as_percentage;
    }

    /**
     * @return string
     */
    public function getObjectiveAsFractionString(): string
    {
        return $this->objective_as_fraction_string;
    }

    /**
     * @param string $objective_as_fraction_string
     * @return void
     */
    public function setObjectiveAsFractionString(string $objective_as_fraction_string): void
    {
        $this->objective_as_fraction_string = $objective_as_fraction_string;
    }

    /**
     * @param $average_type
     * @param $as_string
     * @return int|string
     */
    public function getAveragePercentage(
        int $average_type = self::CALC_TYPE_BY_POINTS,
        bool $as_string = false
    ): int|string {

        switch ($average_type) {
            case self::CALC_TYPE_BY_COMPLETED_OBJECTIVE:
                if ($as_string) {
                    return $this->objective_as_fraction_string;
                }
                return $this->objective_as_percentage;
                break;
            case self::CALC_TYPE_HIGHEST_VALUE:
                if ($as_string) {
                    return $this->points_as_percentage > $this->objective_as_percentage ? $this->points_as_percentage_as_string : $this->objective_as_fraction_string;
                }
                return max($this->points_as_percentage, $this->objective_as_percentage);

                break;
            default:
                if ($as_string) {
                    return $this->points_as_percentage_as_string;
                }
                return $this->points_as_percentage;
                break;
        }
    }
}
