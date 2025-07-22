<?php

namespace App\Helpers;

class NumberToWords
{
    public static function convert($number)
    {
       $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        echo $formatter->format(123); // one hundred twenty-three

    }
}
