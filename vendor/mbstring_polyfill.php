<?php
defined('MB_CASE_UPPER') || define('MB_CASE_UPPER', 0);
defined('MB_CASE_LOWER') || define('MB_CASE_LOWER', 1);
defined('MB_CASE_TITLE') || define('MB_CASE_TITLE', 2);
defined('MB_CASE_FOLD') || define('MB_CASE_FOLD', 3);

if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding($encoding = null) {
        static $internal = 'UTF-8';
        if ($encoding !== null) $internal = $encoding;
        return $internal;
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) { return strlen($string); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null) {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $encoding = null) { return strpos($haystack, $needle, $offset); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null) { return strtolower($string); }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null) { return strtoupper($string); }
}
if (!function_exists('mb_substr_count')) {
    function mb_substr_count($haystack, $needle, $encoding = null) { return substr_count($haystack, $needle); }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($string, $to_encoding, $from_encoding = null) {
        if ($from_encoding === null) $from_encoding = mb_internal_encoding();
        return iconv($from_encoding, $to_encoding . '//TRANSLIT//IGNORE', $string);
    }
}
if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding($string, $encoding = null) { return $string !== false; }
}
if (!function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($string, $encodings = null, $strict = false) { return 'UTF-8'; }
}
if (!function_exists('mb_list_encodings')) {
    function mb_list_encodings() { return ['UTF-8', 'ASCII', 'ISO-8859-1', 'Windows-1252']; }
}
if (!function_exists('mb_http_output')) {
    function mb_http_output($encoding = null) {
        static $http = 'UTF-8';
        if ($encoding !== null) $http = $encoding;
        return $http;
    }
}
if (!function_exists('mb_regex_encoding')) {
    function mb_regex_encoding($encoding = null) { return mb_internal_encoding($encoding); }
}
if (!function_exists('mb_ereg_match')) {
    function mb_ereg_match($pattern, $string, $option = null) { return preg_match('/' . $pattern . '/', $string); }
}
if (!function_exists('mb_ereg_replace')) {
    function mb_ereg_replace($pattern, $replacement, $string, $option = null) { return preg_replace('/' . $pattern . '/', $replacement, $string); }
}
if (!function_exists('mb_split')) {
    function mb_split($pattern, $string, $limit = -1) { return preg_split('/' . $pattern . '/', $string, $limit); }
}
if (!function_exists('mb_send_mail')) {
    function mb_send_mail($to, $subject, $message, $headers = null, $params = null) { return mail($to, $subject, $message, $headers, $params); }
}
if (!function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) { return stripos($haystack, $needle, $offset); }
}
if (!function_exists('mb_strripos')) {
    function mb_strripos($haystack, $needle, $offset = 0, $encoding = null) { return strripos($haystack, $needle, $offset); }
}
if (!function_exists('mb_strrpos')) {
    function mb_strrpos($haystack, $needle, $offset = 0, $encoding = null) { return strrpos($haystack, $needle, $offset); }
}
if (!function_exists('mb_stristr')) {
    function mb_stristr($haystack, $needle, $before_needle = false, $encoding = null) { return stristr($haystack, $needle, $before_needle); }
}
if (!function_exists('mb_strstr')) {
    function mb_strstr($haystack, $needle, $before_needle = false, $encoding = null) { return strstr($haystack, $needle, $before_needle); }
}
if (!function_exists('mb_strrichr')) {
    function mb_strrichr($haystack, $needle, $before_needle = false, $encoding = null) {
        $pos = strripos($haystack, $needle);
        if ($pos === false) return false;
        if ($before_needle) return substr($haystack, 0, $pos);
        return substr($haystack, $pos);
    }
}
if (!function_exists('mb_detect_order')) {
    function mb_detect_order($encoding = null) {
        $default = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
        if ($encoding !== null) return true;
        return $default;
    }
}
if (!function_exists('mb_substitute_character')) {
    function mb_substitute_character($substitute_character = null) {
        static $char = 0xFFFD;
        if ($substitute_character !== null) $char = $substitute_character;
        return $char;
    }
}
if (!function_exists('mb_encode_numericentity')) {
    function mb_encode_numericentity($string, $convmap, $encoding = null, $is_hex = false) {
        $mapping = [];
        for ($i = 0; $i < count($convmap); $i += 4) {
            $start = $convmap[$i];
            $end = $convmap[$i+1];
            $offset = $convmap[$i+2];
            $add = $convmap[$i+3];
            for ($cp = $start; $cp <= $end; $cp++) {
                $mapping[$cp] = $cp + $offset + $add;
            }
        }
        $result = '';
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($string[$i]);
            if ($ord < 128) {
                $result .= $string[$i];
            } else {
                $result .= '&#' . $ord . ';';
            }
        }
        return $result;
    }
}
if (!function_exists('mb_eregi')) {
    function mb_eregi($pattern, $string, &$registers = null) {
        if (preg_match('/' . $pattern . '/i', $string, $matches)) {
            $registers = $matches;
            return 1;
        }
        return false;
    }
}
if (!function_exists('mb_eregi_replace')) {
    function mb_eregi_replace($pattern, $replacement, $string, $option = null) {
        return preg_replace('/' . $pattern . '/i', $replacement, $string);
    }
}
if (!function_exists('mb_ereg')) {
    function mb_ereg($pattern, $string, &$registers = null) {
        if (preg_match('/' . $pattern . '/', $string, $matches)) {
            $registers = $matches;
            return mb_strlen($matches[0]);
        }
        return false;
    }
}
if (!function_exists('mb_output_handler')) {
    function mb_output_handler($contents, $status) { return $contents; }
}
if (!function_exists('mb_decode_numericentity')) {
    function mb_decode_numericentity($string, $convmap, $encoding = null) {
        return preg_replace('/\&\#(\d+)\;/', '&#$1;', $string);
    }
}
if (!function_exists('mb_strwidth')) {
    function mb_strwidth($string, $encoding = null) { return strlen($string); }
}
if (!function_exists('mb_convert_case')) {
    function mb_convert_case($string, $mode = null, $encoding = null) {
        if ($mode === MB_CASE_UPPER) return strtoupper($string);
        if ($mode === MB_CASE_LOWER) return strtolower($string);
        if ($mode === MB_CASE_TITLE) return ucwords(strtolower($string));
        if ($mode === MB_CASE_FOLD) return strtolower($string);
        return $string;
    }
}

