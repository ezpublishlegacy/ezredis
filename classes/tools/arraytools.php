<?php

class ArrayTools
{
    /**
     * retrieve all spaces and replace by one space
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
     * @todo  comments this part code
     * @thought It s a shit code.
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
            if (preg_match('/"([^"]+)"/', $value)) {
                $args[] = str_replace('"', '', trim($value));
            } elseif (preg_match('/"/', $value)) {
                $stringConcat .= $value. " ";
                if ($doubleQuote) {
                    $args[]       = str_replace('"', '', trim($stringConcat));
                    $doubleQuote  = false;
                    // reinitialize variable
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
