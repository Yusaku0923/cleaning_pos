<?php
namespace App\Services;

class Utility
{
    public static function convertTagFormat($tag) {
        $formated_tag = '';
        if ($tag >= 10000) {
            $formated_tag = substr((string)$tag, 0, 2);
        } else {
            $formated_tag = substr((string)$tag, 0, 1);
        }
        if ($tag % 1000 < 10) {
            $formated_tag .= '-00' . (string)$tag % 1000;
        } else if ($tag % 1000 < 100) {
            $formated_tag .= '-0' . (string)$tag % 1000;
        } else if ($tag % 1000 < 1000) {
            $formated_tag .= '-' . (string)$tag % 1000;
        }

        return $formated_tag;
    }
}