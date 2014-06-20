<?php

namespace Model\Filter;

use Zend\Filter\AbstractFilter;

class Translit extends AbstractFilter
{
    const TR_NO_SLASHES = 0;
    const TR_ALLOW_SLASHES = 1;
    const TR_ENCODE = 0;
    const TR_DECODE = 1;

    protected static $_translitSmall = array(
        // RUSSIAN
        "а" => "a",  "б" => "b",  "в" => "v",  "г" => "g",   "д" => "d",   "е" => "e",  "ё" => "yo",
        "ж" => "zh", "з" => "z",  "и" => "i",  "й" => "j", "к" => "k",  "л" => "l",  "м" => "m",   "н" => "n",   "о" => "o",  "п" => "p",
        "р" => "r",  "с" => "s",  "т" => "t",  "у" => "u",  "ф" => "f",  "х" => "kh",  "ц" => "c",   "ч" => "ch",  "ш" => "sh", "щ" => "shh",
        "ъ" => "''",  "ы" => "y",  "ь" => "'",  "э" => "e'", "ю" => "yu", "я" => "ya",
        // UKRAINE
        "є" => "e'",  "і" => "i",  "ї" => "yi",  "ґ" => "g",  "ў" => "u",
    );
    protected static $_translitBig = array(
        // RUSSIAN
        "А" => "A",  "Б" => "B",  "В" => "V",  "Г" => "G",  "Д" => "D",  "Е" => "E",  "З" => "Z",  "И" => "I",  "Й" => "J",
        "К" => "K",  "Л" => "L",  "М" => "M",  "Н" => "N",  "О" => "O",  "П" => "P",   "Р" => "R",   "С" => "S",  "Т" => "T",
        "У" => "U",  "Ф" => "F",  "Ц" => "C",  "Ъ" => "''",  "Ы" => "Y",  "Ь" => "'",  "Э" => "E'",
        // UKRAINE
        "Є" => "E'",  "І" => "I",  "Ґ" => "G",  "Ў" => "U",
    );
    protected static $_translitMixed = array(
        // RUSSIAN
        "Ё" => "Yo", "Ж" => "Zh",  "Х" => "Kh", "Ч" => "Ch", "Ш" => "Sh", "Щ" => "Shh", "Ю" => "Yu", "Я" => "Ya",
        // UKRAINE
        "Ї" => "Yi",
    );

    public function filter($value)
    {
        return self::text($value);
    }

    /**
     * Преобразует строку в транслит (URI валидный)
     *
     * @param string $string строка для преобразования
     * @param int $allow_slashes разрешены ли слеши
     * @return string
     */
    public static function url($string, $allow_slashes = self::TR_NO_SLASHES)
    {
        $string = preg_replace('#([^\s]+)\'#usi', '\1', $string);
        $string = preg_replace('#[\s+\-\:\;\'\"]#usi', ' ', $string);

        $slash = "";
        if ($allow_slashes) {
            $slash = '\/';
        }

        static $LettersFrom = 'а б в г д е з и к л м н о п р с т у ф ы э й х ё';
        static $LettersTo   = 'a b v g d e z i k l m n o p r s t u f y e j x e';
        //static $Consonant = 'бвгджзйклмнпрстфхцчшщ';
        static $Vowel = 'аеёиоуыэюя';
        static $BiLetters = array(
            "ж" => "zh", "ц"=>"ts", "ч" => "ch",
            "ш" => "sh", "щ" => "sch", "ю" => "yu", "я" => "ya",
        );

        $string = preg_replace('/[_\s\.,?!\[\](){}]+/', '-', $string);
        $string = preg_replace("/-{2,}/", "--", $string);
        $string = preg_replace("/_-+_/", "--", $string);
        $string = preg_replace('/[_\-]+$/', '', $string);

        $string = mb_strtolower($string, 'UTF-8');

        //here we replace ъ/ь
        $string = preg_replace("/(ь|ъ)([".$Vowel."])/", "j\\2", $string);
        $string = preg_replace("/(ь|ъ)/", "", $string);

        //transliterating
        $string = str_replace(explode(' ', $LettersFrom),  explode(' ', $LettersTo), $string);
        $string = str_replace(array_keys($BiLetters), array_values($BiLetters), $string);

        $string = preg_replace("/j{2,}/", "j", $string);
        $string = preg_replace('/[^' . $slash . '0-9a-z_\-]+/', "-", $string);

        $string = preg_replace('/^[_\-]+/', '', $string);
        $string = preg_replace('/[_\-]+$/', '', $string);
        $string = preg_replace('/[\_\-]+$/', '-', $string);

        return $string;
    }

    public static function text($string)
    {
        foreach (self::$_translitMixed as $from => $to) {
            while (($pos = mb_strpos($string, $from, 0, 'UTF-8')) !== false) {
                $tempTo = $to;
                $len = mb_strlen($string, 'UTF-8');
                $left = '';
                $right = '';
                if ($pos < $len - 1) {
                    $next = mb_substr($string, $pos+1, 1, 'UTF-8');
                    if (array_key_exists($next, self::$_translitBig) || array_key_exists($next, self::$_translitMixed)) {
                        $tempTo = mb_strtoupper($tempTo);
                    }
                    $right = mb_substr($string, $pos+1, $len, 'UTF-8');
                }
                if ($pos > 0) {
                    $prev = mb_substr($string, $pos-1, 1, 'UTF-8');
                    if (array_key_exists($prev, self::$_translitBig) || array_key_exists($prev, self::$_translitMixed)) {
                        $tempTo = mb_strtoupper($tempTo);
                    }
                    $left = mb_substr($string, 0, $pos, 'UTF-8');
                }
                $string = $left . $tempTo . $right;
            }
        }
        $string = strtr($string, self::$_translitBig);
        $string = strtr($string, self::$_translitSmall);

        return $string;
    }
}
