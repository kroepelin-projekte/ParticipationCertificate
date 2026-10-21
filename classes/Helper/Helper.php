<?php

class Helper
{
    /**
     * @param string $param
     * @param int    $type
     * @return mixed
     */
    public static function fetchUrlParameter(string $param, string $type): mixed
    {
        global $DIC;

        $query = $DIC->http()->wrapper()->query();

        $transformation = null;
        if($type === 'string') {
            $transformation = $DIC->refinery()->kindlyTo()->string();
        } elseif($type === 'int') {
            $transformation = $DIC->refinery()->kindlyTo()->int();
        }

        if ($transformation === null) {
            $transformation = $DIC->refinery()->kindlyTo()->string();
        }

        $parameter = $query->has($param)
            ? $query->retrieve($param, $transformation)
            : null;

        return $parameter;
    }
}