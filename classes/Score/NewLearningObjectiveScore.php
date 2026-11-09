<?php
class NewLearningObjectiveScore
{
    protected int $course_obj_id;

    protected int $user_id;

    protected int $score;

    protected int $objective_id;

    protected string $title;

    /**
     * @return int
     */
    public function getCourseObjId(): int
    {
        return $this->course_obj_id;
    }

    /**
     * @param int $course_obj_id
     * @return void
     */
    public function setCourseObjId(int $course_obj_id): void
    {
        $this->course_obj_id = $course_obj_id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     * @return void
     */
    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getObjectiveId(): int
    {
        return $this->objective_id;
    }

    /**
     * @param int $objective_id
     * @return void
     */
    public function setObjectiveId(int $objective_id): void
    {
        $this->objective_id = $objective_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}