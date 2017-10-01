<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * String starts with
 * @param  string $haystack
 * @param  string $needle
 * @return boolean
 */
if (!function_exists('_startsWith')) {
    function _startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}
/**
 * String ends with
 * @param  string $haystack
 * @param  string $needle
 * @return boolean
 */
if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}
/**
 * Check if there is html in string
 */
if (!function_exists('is_html')) {
    function is_html($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }
}
/**
 * Get string after specific charcter/word
 * @param  string $string    string from where to get
 * @param  substring $substring search for
 * @return string
 */
function strafter($string, $substring)
{
    $pos = strpos($string, $substring);
    if ($pos === false) {
        return $string;
    } else {
        return (substr($string, $pos + strlen($substring)));
    }
}
/**
 * Get string before specific charcter/word
 * @param  string $string    string from where to get
 * @param  substring $substring search for
 * @return string
 */
function strbefore($string, $substring)
{
    $pos = strpos($string, $substring);
    if ($pos === false) {
        return $string;
    } else {
        return (substr($string, 0, $pos));
    }
}
/**
 * Is internet connection open
 * @param  string  $domain
 * @return boolean
 */
function is_connected($domain = 'www.perfexcrm.com')
{
    $connected = @fsockopen($domain, 80);
    //website, port  (try 80 or 443)
    if ($connected) {
        $is_conn = true; //action when connected
        fclose($connected);
    } else {
        $is_conn = false; //action in connection failure
    }

    return $is_conn;
}
/**
 * Replace Last Occurence of a String in a String
 * @since  Version 1.0.1
 * @param  string $search  string to be replaced
 * @param  string $replace replace with
 * @param  string $subject [the string to search
 * @return string
 */
function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}
/**
 * Get string bettween words
 * @param  string $string the string to get from
 * @param  string $start  where to start
 * @param  string $end    where to end
 * @return string formatted string
 */
function get_string_between($string, $start, $end)
{
    $string = ' ' . $string;
    $ini    = strpos($string, $start);
    if ($ini == 0) {
        return '';
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;

    return substr($string, $ini, $len);
}
/**
 * Format datetime to time ago with specific hours mins and seconds
 * @param  datetime $lastreply
 * @param  string $from      Optional
 * @return mixed
 */
function time_ago_specific($date, $from = "now")
{
    $datetime   = strtotime($from);
    $date2      = strtotime("" . $date);
    $holdtotsec = $datetime - $date2;
    $holdtotmin = ($datetime - $date2) / 60;
    $holdtothr  = ($datetime - $date2) / 3600;
    $holdtotday = intval(($datetime - $date2) / 86400);
    $str        = '';
    if (0 < $holdtotday) {
        $str .= $holdtotday . "d ";
    }
    $holdhr = intval($holdtothr - $holdtotday * 24);
    $str .= $holdhr . "h ";
    $holdmr = intval($holdtotmin - ($holdhr * 60 + $holdtotday * 1440));
    $str .= $holdmr . "m";

    return $str;
}
/**
 * Format seconds to quantity
 * @param  mixed  $sec      total seconds
 * @return [integer]
 */

function sec2qty($sec)
{
    $seconds = $sec / 3600;

    $qty = round($seconds, 2);

    $hookData = do_action('sec2qty_formatted',array('seconds'=>$sec,'qty'=>$qty));

    return $hookData['qty'];
}

/**
 * @deprecated
 * Format seconds to hours/minutes or seconds
 * @param  mixed $seconds
 * @return mixed
 */
function format_seconds($seconds)
{
    $minutes = $seconds / 60;
    $hours   = $minutes / 60;
    if ($minutes >= 60) {
        return round($hours, 2) . ' ' . _l('hours');
    } elseif ($seconds > 60) {
        return round($minutes, 2) . ' ' . _l('minutes');
    } else {
        return $seconds . ' ' . _l('seconds');
    }
}
/**
 * Format seconds to H:I:S
 * @param  integer $seconds         mixed
 * @param  boolean $include_seconds
 * @return string
 */
function seconds_to_time_format($seconds = 0, $include_seconds = false)
{
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds - ($hours * 3600)) / 60);
    $secs = floor($seconds % 60);

    $hours = ($hours < 10) ? "0" . $hours : $hours;
    $mins = ($mins < 10) ? "0" . $mins : $mins;
    $secs = ($secs < 10) ? "0" . $secs : $secs;
    $sprintF = $include_seconds == true ? '%02d:%02d:%02d' : '%02d:%02d';

    return sprintf($sprintF, $hours, $mins, $secs);
}
/**
 * Converts hours to minutes timestamp
 * @param  mixed $hours     total hours in format HH:MM or HH.MMM
 * @return int
 */
function hours_to_seconds_format($hours){
    if(strpos($hours,'.') !== FALSE){
        $hours = str_replace('.', ':', $hours);
    }
    $tmp = explode(':',$hours);
    $hours = $tmp[0];
    $minutesFromHour = isset($tmp[1]) ? $tmp[1] : 0;
    return $hours*3600 + $minutesFromHour*60;
}

/*
 * ip_in_range.php - Function to determine if an IP is located in a
 *                   specific range as specified via several alternative
 *                   formats.
 *
 * Network ranges can be specified as:
 * 1. Wildcard format:     1.2.3.*
 * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
 *
 * Return value BOOLEAN : ip_in_range($ip, $range);
 *
 * Copyright 2008: Paul Gregg <pgregg@pgregg.com>
 * 10 January 2008
 * Version: 1.2
 *
 * Source website: http://www.pgregg.com/projects/php/ip_in_range/
 * Version 1.2
 *
 * This software is Donationware - if you feel you have benefited from
 * the use of this tool then please consider a donation. The value of
 * which is entirely left up to your discretion.
 * http://www.pgregg.com/donate/
 *
 * Please do not remove this header, or source attibution from this file.
 */

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
    list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
      $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);

            return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
        } else {
            // $netmask is a CIDR size block
      // fix the range argument
      $x = explode('.', $range);
            while (count($x)<4) {
                $x[] = '0';
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b, empty($c)?'0':$c, empty($d)?'0':$d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);

      # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
      #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

      # Strategy 2 - Use math to create it
      $wildcard_dec = pow(2, (32-$netmask)) - 1;
            $netmask_dec = ~ $wildcard_dec;

            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
    if (strpos($range, '*') !==false) { // a.b.*.* format
      // Just convert to A-B format by setting * to 0 for A and 255 for B
      $lower = str_replace('*', '0', $range);
        $upper = str_replace('*', '255', $range);
        $range = "$lower-$upper";
    }

        if (strpos($range, '-')!==false) { // A-B format
      list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float) sprintf("%u", ip2long($lower));
            $upper_dec = (float) sprintf("%u", ip2long($upper));
            $ip_dec = (float) sprintf("%u", ip2long($ip));

            return (($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec));
        }

        echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';

        return false;
    }
}
if(!function_exists('in_array_multidimensional')){

    function in_array_multidimensional($array, $key, $val) {
        foreach ($array as $item)
            if (isset($item[$key]) && $item[$key] == $val)
                return true;
        return false;
    }
}
if(!function_exists('in_object_multidimensional')){

    function in_object_multidimensional($object, $key, $val) {
        foreach ($object as $item)
            if (isset($item->{$key}) && $item->{$key} == $val)
                return true;
        return false;
    }

}
/**
 *
 * @param  $array - data
 * @param  $key - value you want to pluck from array
 *
 * @return plucked array only with key data
 */
if(!function_exists('array_pluck')){
  function array_pluck($array, $key) {
      return array_map(function($v) use ($key)  {
        return is_object($v) ? $v->$key : $v[$key];
    }, $array);
  }
}
