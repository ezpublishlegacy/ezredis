<?php

class ArrayTools
{
    /**
     * @param  string                   $query [description]
     * @date   2015-01-24T00:18:32+0100
     */
    public static function explodeStringInArray($query)
    {
        $query    = preg_replace('!\s+!', ' ', $query);
        return explode(" ", $query);
    }

    /**
     * @param  string                   $query [description]
     * @date   2015-01-24T00:42:45+0100
     */
    public static function explodeComplexStringInArray($query)
    {
        $subQuery     = explode(" ", $query);
        $doubleQuote  = false;
        $args         = array();
        $stringConcat = "";
        foreach ($subQuery as $key => $value) {
            $value = trim($value);
            if (empty($value)) {
                continue;
            }
            if (preg_match('/"/', $value)) {
                $stringConcat .= $value. " ";
                if ($doubleQuote) {
                    $args[]       = str_replace('"', '', trim($stringConcat));
                    $doubleQuote  = false;
                    // reinitialize varia
                    $stringConcat = "";
                } else {
                    $doubleQuote = true;
                }
            } elseif ($doubleQuote) {
                $stringConcat .= $value . " ";
            } else {
                $args[] = $value;
            }
        }
        return $args;
    }
}
