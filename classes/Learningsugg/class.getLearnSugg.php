<?php
class getLearnSugg
{
    private string $sugg_obj_title;

    private int $sugg_objective_id;

    private int $sugg_for_user;

    /**
     * @return string
     */
    public function getSuggObjTitle(): string
    {
        return $this->sugg_obj_title;
    }

    /**
     * @param string $sugg_obj_title
     * @return void
     */
    public function setSuggObjTitle(string $sugg_obj_title): void
    {
        $this->sugg_obj_title = $sugg_obj_title;
    }

    /**
     * @return int
     */
    public function getSuggObjectiveId(): int
    {
        return $this->sugg_objective_id;
    }

    /**
     * @param int $sugg_objective_id
     * @return void
     */
    public function setSuggObjectiveId(int $sugg_objective_id)
    {
        $this->sugg_objective_id = $sugg_objective_id;
    }

    /**
     * @return int
     */
    public function getSuggForUser(): int
    {
        return $this->sugg_for_user;
    }

    /**
     * @param int $sugg_for_user
     * @return void
     */
    public function setSuggForUser(int $sugg_for_user)
    {
        $this->sugg_for_user = $sugg_for_user;
    }
}