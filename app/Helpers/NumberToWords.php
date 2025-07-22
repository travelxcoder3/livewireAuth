<?php

namespace App\Helpers;

class NumberToWords
{
    public static function convert($number)
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        return ucfirst($formatter->format($number));
    }
}
