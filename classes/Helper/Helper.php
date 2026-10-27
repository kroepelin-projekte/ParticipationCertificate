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
        $refinery = $DIC->refinery();

        $transformation = match ($type) {
            'int'          => $refinery->kindlyTo()->int(),
            'string[]'     => $refinery->to()->listOf($refinery->kindlyTo()->string()),
            'int[]'        => $refinery->to()->listOf($refinery->kindlyTo()->int()),
            'dict_string'  => $refinery->to()->dictOf($refinery->kindlyTo()->string()),
            default        => $refinery->kindlyTo()->string(),
        };

        return $query->has($param)
            ? $query->retrieve($param, $transformation)
            : null;
    }
}