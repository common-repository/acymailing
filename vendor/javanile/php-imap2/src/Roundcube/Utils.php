<?php


namespace Javanile\Imap2\Roundcube;

use Javanile\Imap2\DateTime;
use Javanile\Imap2\DateTimeZone;
use Javanile\Imap2\Exception;
use Javanile\Imap2\rcube;
use Javanile\Imap2\rcube_charset;
use Javanile\Imap2\rcube_string_replacer;

class Utils
{
    const INPUT_GET    = 1;
    const INPUT_POST   = 2;
    const INPUT_COOKIE = 4;
    const INPUT_GP     = 3; // GET + POST
    const INPUT_GPC    = 7; // GET + POST + COOKIE


    public static function setcookie($name, $value, $exp = 0, $http_only = true)
    {
        if (headers_sent()) {
            return;
        }

        $cookie = session_get_cookie_params();
        $secure = $cookie['secure'] || self::https_check();

        setcookie($name, $value, $exp, $cookie['path'], $cookie['domain'], $secure, $http_only);
    }

    public static function check_email($email, $dns_check=true)
    {
        if (preg_match('/\p{Cc}/u', $email)) {
            return false;
        }

        if (strlen($email) > 254) {
            return false;
        }

        $pos = strrpos($email, '@');
        if (!$pos) {
            return false;
        }

        $domain_part = substr($email, $pos + 1);
        $local_part  = substr($email, 0, $pos);

        if (substr($local_part,0,1) == '"') {
            $local_quoted = preg_replace('/\\\\(\\\\|\")/','', substr($local_part, 1, -1));
            if (preg_match('/\\\\|"/', $local_quoted)) {
                return false;
            }
        }
        else if (preg_match('/(^\.|\.\.|\.$)/', $local_part)
            || preg_match('/[\\ ",:;<>@]/', $local_part)
        ) {
            return false;
        }

        if (preg_match('/^\[((IPv6:[0-9a-f:.]+)|([0-9.]+))\]$/i', $domain_part, $matches)) {
            return self::check_ip(preg_replace('/^IPv6:/i', '', $matches[1])); // valid IPv4 or IPv6 address
        }
        else {
            $domain_array = explode('.', $domain_part);
            if (count($domain_array) < 2) {
                return false;
            }

            foreach ($domain_array as $part) {
                if (!preg_match('/^((xn--)?([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]))$/', $part)) {
                    return false;
                }
            }

            $last_part = array_pop($domain_array);
            if (strpos($last_part, 'xn--') !== 0 && preg_match('/[^a-zA-Z]/', $last_part)) {
                return false;
            }

            $rcube = rcube::get_instance();

            if (!$dns_check || !function_exists('checkdnsrr') || !$rcube->config->get('email_dns_check')) {
                return true;
            }

            foreach (array('A', 'MX', 'CNAME', 'AAAA') as $type) {
                if (checkdnsrr($domain_part, $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function check_ip($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function rep_specialchars_output($str, $enctype = '', $mode = '', $newlines = true)
    {
        static $html_encode_arr = false;
        static $js_rep_table    = false;
        static $xml_rep_table   = false;

        if (!is_string($str)) {
            $str = strval($str);
        }

        if ($enctype == 'html') {
            if (!$html_encode_arr) {
                $html_encode_arr = get_html_translation_table(HTML_SPECIALCHARS);
                unset($html_encode_arr['?']);
            }

            $encode_arr = $html_encode_arr;

            if ($mode == 'remove') {
                $str = strip_tags($str);
            }
            else if ($mode != 'strict') {
                $ltpos = strpos($str, '<');
                if ($ltpos !== false && strpos($str, '>', $ltpos) !== false) {
                    unset($encode_arr['"']);
                    unset($encode_arr['<']);
                    unset($encode_arr['>']);
                    unset($encode_arr['&']);
                }
            }

            $out = strtr($str, $encode_arr);

            return $newlines ? nl2br($out) : $out;
        }

        if ($js_rep_table === false) {
            $js_rep_table = $xml_rep_table = array();
            $xml_rep_table['&'] = '&amp;';

            for ($c=160; $c<256; $c++) {
                $xml_rep_table[chr($c)] = "&#$c;";
            }

            $xml_rep_table['"'] = '&quot;';
            $js_rep_table['"']  = '\\"';
            $js_rep_table["'"]  = "\\'";
            $js_rep_table["\\"] = "\\\\";
            $js_rep_table[chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('A8'))] = '&#8232;';
            $js_rep_table[chr(hexdec('E2')).chr(hexdec('80')).chr(hexdec('A9'))] = '&#8233;';
        }

        if ($enctype == 'js') {
            return preg_replace(array("/\r?\n/", "/\r/", '/<\\//'), array('\n', '\n', '<\\/'), strtr($str, $js_rep_table));
        }

        if ($enctype == 'text') {
            return str_replace("\r\n", "\n", $mode == 'remove' ? strip_tags($str) : $str);
        }

        if ($enctype == 'url') {
            return rawurlencode($str);
        }

        if ($enctype == 'xml') {
            return strtr($str, $xml_rep_table);
        }

        return $str;
    }

    public static function get_input_value($fname, $source, $allow_html = false, $charset = null)
    {
        $value = null;

        if (($source & self::INPUT_GET) && isset($_GET[$fname])) {
            $value = $_GET[$fname];
        }

        if (($source & self::INPUT_POST) && isset($_POST[$fname])) {
            $value = $_POST[$fname];
        }

        if (($source & self::INPUT_COOKIE) && isset($_COOKIE[$fname])) {
            $value = $_COOKIE[$fname];
        }

        return self::parse_input_value($value, $allow_html, $charset);
    }

    public static function parse_input_value($value, $allow_html = false, $charset = null)
    {
        global $OUTPUT;

        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $idx => $val) {
                $value[$idx] = self::parse_input_value($val, $allow_html, $charset);
            }
            return $value;
        }

        if (!$allow_html) {
            $value = strip_tags($value);
        }

        $output_charset = is_object($OUTPUT) ? $OUTPUT->get_charset() : null;

        if ($output_charset == 'UTF-8') {
            $value = rcube_charset::clean($value);
        }

        if ($charset && $output_charset) {
            $value = rcube_charset::convert($value, $output_charset, $charset);
        }

        return $value;
    }

    public static function request2param($mode = null, $ignore = 'task|action', $allow_html = false)
    {
        $out = array();
        $src = $mode == self::INPUT_GET ? $_GET : ($mode == self::INPUT_POST ? $_POST : $_REQUEST);

        foreach (array_keys($src) as $key) {
            $fname = $key[0] == '_' ? substr($key, 1) : $key;
            if ($ignore && !preg_match('/^(' . $ignore . ')$/', $fname)) {
                $out[$fname] = self::get_input_value($key, $mode, $allow_html);
            }
        }

        return $out;
    }

    public static function html_identifier($str, $encode = false)
    {
        if ($encode) {
            return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
        }

        return asciiwords($str, true, '_');
    }

    public static function mod_css_styles($source, $container_id, $allow_remote = false, $prefix = '')
    {
        $last_pos     = 0;
        $replacements = new rcube_string_replacer;

        $source   = self::xss_entity_decode($source);
        $stripped = preg_replace('/[^a-z\(:;]/i', '', $source);
        $evilexpr = 'expression|behavior|javascript:|import[^a]' . (!$allow_remote ? '|url\((?!data:image)' : '');

        if (preg_match("/$evilexpr/i", $stripped)) {
            return '/* evil! */';
        }

        $strict_url_regexp = '!url\s*\(\s*["\']?(https?:)//[a-z0-9/._+-]+["\']?\s*\)!Uims';

        while (($pos = strpos($source, '{', $last_pos)) && ($pos2 = strpos($source, '}', $pos))) {
            $nested = strpos($source, '{', $pos+1);
            if ($nested && $nested < $pos2)  // when dealing with nested blocks (e.g. @media), take the inner one
                $pos = $nested;
            $length = $pos2 - $pos - 1;
            $styles = substr($source, $pos+1, $length);

            $styles = preg_replace('/position[^a-z]*:[\s\r\n]*fixed/i', 'position: absolute', $styles);

            if ($allow_remote) {
                $a_styles = preg_split('/;[\r\n]*/', $styles, -1, PREG_SPLIT_NO_EMPTY);

                for ($i=0, $len=count($a_styles); $i < $len; $i++) {
                    $line     = $a_styles[$i];
                    $stripped = preg_replace('/[^a-z\(:;]/i', '', $line);

                    if (stripos($stripped, 'url(data:image')) {
                        $a_styles[$i] .= ';' . $a_styles[$i+1];
                        unset($a_styles[$i+1]);
                    }
                    else if (stripos($stripped, 'url(') && !preg_match($strict_url_regexp, $line)) {
                        $a_styles = array('/* evil! */');
                        break;
                    }
                }

                $styles = implode(";\n", $a_styles);
            }

            $key      = $replacements->add($styles);
            $repl     = $replacements->get_replacement($key);
            $source   = substr_replace($source, $repl, $pos+1, $length);
            $last_pos = $pos2 - ($length - strlen($repl));
        }

        $source = preg_replace('/(^\s*<\!--)|(-->\s*$)/m', '', $source);

        if ($container_id || $prefix) {
            $regexp   = '/(^\s*|,\s*|\}\s*|\{\s*)((?!##str):?[a-z0-9\._#\*\[][a-z0-9\._:\(\)#=~ \[\]"\|\>\+\$\^-]*)/im';
            $callback = function($matches) use ($container_id, $prefix) {
                $replace = $matches[2];

                if (stripos($replace, ':root') === 0) {
                    $replace = substr($replace, 5);
                }

                if ($prefix) {
                    $replace = str_replace(array('.', '#'), array(".$prefix", "#$prefix"), $replace);
                }

                if ($container_id) {
                    $replace = "#$container_id " . $replace;
                }

                $replace = preg_replace('/\s+/', ' ', $replace);

                return str_replace($matches[2], $replace, $matches[0]);
            };

            $source = preg_replace_callback($regexp, $callback, $source);
        }

        if ($container_id) {
            $regexp = '/#' . preg_quote($container_id, '/') . '\s+body/i';
            $source = preg_replace($regexp, "#$container_id", $source);
        }

        $source = $replacements->resolve($source);

        return $source;
    }

    public static function file2class($mimetype, $filename)
    {
        $mimetype = strtolower($mimetype);
        $filename = strtolower($filename);

        list($primary, $secondary) = explode('/', $mimetype);

        $classes = array($primary ?: 'unknown');

        if ($secondary) {
            $classes[] = $secondary;
        }

        if (preg_match('/\.([a-z0-9]+)$/', $filename, $m)) {
            if (!in_array($m[1], $classes)) {
                $classes[] = $m[1];
            }
        }

        return implode(' ', $classes);
    }

    public static function xss_entity_decode($content)
    {
        $callback = function($matches) { return chr(hexdec($matches[1])); };

        $out = html_entity_decode(html_entity_decode($content));
        $out = trim(preg_replace('/(^<!--|-->$)/', '', trim($out)));
        $out = preg_replace_callback('/\\\([0-9a-f]{2,6})\s*/i', $callback, $out);
        $out = preg_replace('/\\\([^0-9a-f])/i', '\\1', $out);
        $out = preg_replace('#/\*.*\*/#Ums', '', $out);
        $out = strip_tags($out);

        return $out;
    }

    public static function mem_check($need)
    {
        $mem_limit = parse_bytes(ini_get('memory_limit'));
        $memory    = function_exists('memory_get_usage') ? memory_get_usage() : 16*1024*1024; // safe value: 16MB

        return $mem_limit > 0 && $memory + $need > $mem_limit ? false : true;
    }

    public static function https_check($port = null, $use_https = true)
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'
            && in_array($_SERVER['REMOTE_ADDR'], rcube::get_instance()->config->get('proxy_whitelist', array()))
        ) {
            return true;
        }

        if ($port && $_SERVER['SERVER_PORT'] == $port) {
            return true;
        }

        if ($use_https && rcube::get_instance()->config->get('use_https')) {
            return true;
        }

        return false;
    }

    public static function parse_host($name, $host = '')
    {
        if (!is_string($name)) {
            return $name;
        }

        $n = self::server_name();
        $t = preg_replace('/^[^.]+\.(?![^.]+$)/', '', $n);
        $d = preg_replace('/^[^.]+\.(?![^.]+$)/', '', self::server_name('HTTP_HOST'));
        $h = $_SESSION['storage_host'] ?: $host;
        $z = preg_replace('/^[^.]+\.(?![^.]+$)/', '', $h);
        if (strpos($name, '%s') !== false) {
            $user_email = self::idn_to_ascii(self::get_input_value('_user', self::INPUT_POST));
            $matches    = preg_match('/(.*)@([a-z0-9\.\-\[\]\:]+)/i', $user_email, $s);
            if ($matches < 1 || filter_var($s[1]."@".$s[2], FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }
        }

        return str_replace(array('%n', '%t', '%d', '%h', '%z', '%s'), array($n, $t, $d, $h, $z, $s[2]), $name);
    }

    public static function server_name($type = null, $strip_port = true)
    {
        $name     = $_SERVER[$type ?: 'SERVER_NAME'];
        $rcube    = rcube::get_instance();
        $patterns = (array) $rcube->config->get('trusted_host_patterns');

        if ($strip_port) {
            $name = preg_replace('/:\d+$/', '', $name);
        }

        if (empty($patterns) || in_array_nocase($name, $patterns)) {
            return $name;
        }

        if (!empty($name)) {
            foreach ($patterns as $pattern) {
                if (preg_match("/$pattern/", $name)) {
                    return $name;
                }
            }

            $rcube->raise_error(array('file' => __FILE__, 'line' => __LINE__,
                'message' => "Specified host is not trusted. Using 'localhost'."), true, false);
        }

        return 'localhost';
    }

    public static function remote_ip()
    {
        $address = $_SERVER['REMOTE_ADDR'];

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $remote_ip[] = 'X-Real-IP: ' . $_SERVER['HTTP_X_REAL_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remote_ip[] = 'X-Forwarded-For: ' . $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($remote_ip)) {
            $address .= '(' . implode(',', $remote_ip) . ')';
        }

        return $address;
    }

    public static function remote_addr()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($_SERVER['HTTP_X_REAL_IP'])) {
            $proxy_whitelist = rcube::get_instance()->config->get('proxy_whitelist', array());
            if (in_array($_SERVER['REMOTE_ADDR'], $proxy_whitelist)) {
                if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    foreach (array_reverse(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $forwarded_ip) {
                        $forwarded_ip = trim($forwarded_ip);
                        if (!in_array($forwarded_ip, $proxy_whitelist)) {
                            return $forwarded_ip;
                        }
                    }
                }

                if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                    return $_SERVER['HTTP_X_REAL_IP'];
                }
            }
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    public static function request_header($name)
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $key     = strtoupper($name);
        }
        else {
            $headers = $_SERVER;
            $key     = 'HTTP_' . strtoupper(strtr($name, '-', '_'));
        }

        if (!empty($headers)) {
            $headers = array_change_key_case($headers, CASE_UPPER);

            return $headers[$key];
        }
    }

    public static function explode_quoted_string($delimiter, $string)
    {
        $result = array();
        $strlen = strlen($string);

        for ($q=$p=$i=0; $i < $strlen; $i++) {
            if ($string[$i] == "\"" && $string[$i-1] != "\\") {
                $q = $q ? false : true;
            }
            else if (!$q && preg_match("/$delimiter/", $string[$i])) {
                $result[] = substr($string, $p, $i - $p);
                $p = $i + 1;
            }
        }

        $result[] = (string) substr($string, $p);

        return $result;
    }

    public static function strtotime($date, $timezone = null)
    {
        $date   = self::clean_datestr($date);
        $tzname = $timezone ? ' ' . $timezone->getName() : '';

        if (is_numeric($date)) {
            return (int) $date;
        }

        if (strlen($date) > 128) {
            $date = substr($date, 0, 128);
        }

        while (($ts = @strtotime($date . $tzname)) === false || $ts < 0) {
            if (($pos = strrpos($date, ' ')) === false) {
                break;
            }

            $date = rtrim(substr($date, 0, $pos));
        }

        return (int) $ts;
    }

    public static function anytodatetime($date, $timezone = null)
    {
        if ($date instanceof DateTime) {
            return $date;
        }

        $dt   = false;
        $date = self::clean_datestr($date);

        if (!empty($date)) {
            try {
                $_date = preg_match('/^[0-9]+$/', $date) ? "@$date" : $date;
                $dt    = $timezone ? new DateTime($_date, $timezone) : new DateTime($_date);
            }
            catch (Exception $e) {
            }
        }

        if (!$dt && ($timestamp = self::strtotime($date, $timezone))) {
            try {
                $dt = new DateTime("@".$timestamp);
                if ($timezone) {
                    $dt->setTimezone($timezone);
                }
            }
            catch (Exception $e) {
            }
        }

        return $dt;
    }

    public static function clean_datestr($date)
    {
        $date = trim($date);

        if (preg_match('/^([12][90]\d\d)([01]\d)([0123]\d)$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', intval($m[1]), intval($m[2]), intval($m[3]));
        }

        $date = preg_replace(
            array(
                '/\(.*\)/',                                 // remove RFC comments
                '/GMT\s*([+-][0-9]+)/',                     // support non-standard "GMTXXXX" literal
                '/[^a-z0-9\x20\x09:\/\.+-]/i',              // remove any invalid characters
                '/\s*(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*/i',   // remove weekday names
            ),
            array(
                '',
                '\\1',
                '',
                '',
            ), $date);

        $date = trim($date);

        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})(\s.*)?$/', $date, $m)) {
            $mdy   = $m[2] > 12 && $m[1] <= 12;
            $day   = $mdy ? $m[2] : $m[1];
            $month = $mdy ? $m[1] : $m[2];
            $date  = sprintf('%04d-%02d-%02d%s', $m[3], $month, $day, $m[4] ?: ' 00:00:00');
        }
        else if (preg_match('/^(\d{4})\.(\d{1,2})\.(\d{1,2})(\s.*)?$/', $date, $m)) {
            $date  = sprintf('%04d-%02d-%02d%s', $m[1], $m[2], $m[3], $m[4] ?: ' 00:00:00');
        }

        return $date;
    }

    public static function format_datestr($date, $format)
    {
        $format_items = preg_split('/[.-\/\\\\]/', $format);
        $date_items   = preg_split('/[.-\/\\\\]/', $date);
        $iso_format   = '%04d-%02d-%02d';

        if (count($format_items) == 3 && count($date_items) == 3) {
            if ($format_items[0] == 'Y') {
                $date = sprintf($iso_format, $date_items[0], $date_items[1], $date_items[2]);
            }
            else if (strpos('dj', $format_items[0]) !== false) {
                $date = sprintf($iso_format, $date_items[2], $date_items[1], $date_items[0]);
            }
            else if (strpos('mn', $format_items[0]) !== false) {
                $date = sprintf($iso_format, $date_items[2], $date_items[0], $date_items[1]);
            }
        }

        return $date;
    }

    public static function idn_to_ascii($str)
    {
        return self::idn_convert($str, true);
    }

    public static function idn_to_utf8($str)
    {
        return self::idn_convert($str, false);
    }

    public static function idn_convert($input, $is_utf = false)
    {
        if ($at = strpos($input, '@')) {
            $user   = substr($input, 0, $at);
            $domain = substr($input, $at + 1);
        }
        else {
            $user   = '';
            $domain = $input;
        }

        $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : null;
        $options = 0;


        if ($is_utf) {
            if (preg_match('/[^\x20-\x7E]/', $domain)) {
                $options = defined('IDNA_NONTRANSITIONAL_TO_ASCII') ? IDNA_NONTRANSITIONAL_TO_ASCII : 0;
                $domain  = idn_to_ascii($domain, $options, $variant);
            }
        }
        else if (preg_match('/(^|\.)xn--/i', $domain)) {
            $options = defined('IDNA_NONTRANSITIONAL_TO_UNICODE') ? IDNA_NONTRANSITIONAL_TO_UNICODE : 0;
            $domain  = idn_to_utf8($domain, $options, $variant);
        }

        if ($domain === false) {
            return '';
        }

        return $at ? $user . '@' . $domain : $domain;
    }

    public static function tokenize_string($str, $minlen = 2)
    {
        $expr = array('/[\s;,"\'\/+-]+/ui', '/(\d)[-.\s]+(\d)/u');
        $repl = array(' ', '\\1\\2');

        if ($minlen > 1) {
            $minlen--;
            $expr[] = "/(^|\s+)\w{1,$minlen}(\s+|$)/u";
            $repl[] = ' ';
        }

        return array_filter(explode(" ", preg_replace($expr, $repl, $str)));
    }

    public static function normalize_string($str, $as_array = false, $minlen = 2)
    {
        $str = preg_replace('/('
            . '\xF0[\x90-\xBF][\x80-\xBF]{2}'
            . '|[\xF1-\xF3][\x80-\xBF]{3}'
            . '|\xF4[\x80-\x8F][\x80-\xBF]{2}'
            . ')/', '?', $str);

        $arr = self::tokenize_string($str, $minlen);

        if (utf8_encode(utf8_decode($str)) == $str) {
            preg_match_all('/./u', 'äâàåáãæçéêëèïîìíñöôòøõóüûùúýÿ', $keys);
            preg_match_all('/./',  'aaaaaaaceeeeiiiinoooooouuuuyy', $values);

            $mapping = array_combine($keys[0], $values[0]);
            $mapping = array_merge($mapping, array('ß' => 'ss', 'ae' => 'a', 'oe' => 'o', 'ue' => 'u'));
        }
        else if (rcube_charset::convert(rcube_charset::convert($str, 'UTF-8', 'ISO-8859-2'), 'ISO-8859-2', 'UTF-8') == $str) {
            preg_match_all('/./u', 'ąáâäćçčéęëěíîłľĺńňóôöŕřśšşťţůúűüźžżý', $keys);
            preg_match_all('/./',  'aaaaccceeeeiilllnnooorrsssttuuuuzzzy', $values);

            $mapping = array_combine($keys[0], $values[0]);
            $mapping = array_merge($mapping, array('ß' => 'ss', 'ae' => 'a', 'oe' => 'o', 'ue' => 'u'));
        }

        foreach ($arr as $i => $part) {
            $part = mb_strtolower($part);

            if (!empty($mapping)) {
                $part = strtr($part, $mapping);
            }

            $arr[$i] = $part;
        }

        return $as_array ? $arr : implode(' ', $arr);
    }

    public static function words_match($haystack, $needle)
    {
        $a_needle  = self::tokenize_string($needle, 1);
        $_haystack = implode(' ', self::tokenize_string($haystack, 1));
        $valid     = strlen($_haystack) > 0;
        $hits      = 0;

        foreach ($a_needle as $w) {
            if ($valid) {
                if (stripos($_haystack, $w) !== false) {
                    $hits++;
                }
            }
            else if (stripos($haystack, $w) !== false) {
                $hits++;
            }
        }

        return $hits >= count($a_needle);
    }

    public static function get_opt($aliases = array())
    {
        $args = array();
        $bool = array();

        foreach ($aliases as $key => $alias) {
            if ($pos = strpos($alias, ':')) {
                $aliases[$key] = substr($alias, 0, $pos);
                $bool[] = $key;
                $bool[] = $aliases[$key];
            }
        }

        for ($i=1; $i < count($_SERVER['argv']); $i++) {
            $arg   = $_SERVER['argv'][$i];
            $value = true;
            $key   = null;

            if ($arg[0] == '-') {
                $key = preg_replace('/^-+/', '', $arg);
                $sp  = strpos($arg, '=');

                if ($sp > 0) {
                    $key   = substr($key, 0, $sp - 2);
                    $value = substr($arg, $sp+1);
                }
                else if (in_array($key, $bool)) {
                    $value = true;
                }
                else if (strlen($_SERVER['argv'][$i+1]) && $_SERVER['argv'][$i+1][0] != '-') {
                    $value = $_SERVER['argv'][++$i];
                }

                $args[$key] = is_string($value) ? preg_replace(array('/^["\']/', '/["\']$/'), '', $value) : $value;
            }
            else {
                $args[] = $arg;
            }

            if ($alias = $aliases[$key]) {
                $args[$alias] = $args[$key];
            }
        }

        return $args;
    }

    public static function prompt_silent($prompt = "Password:")
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript  = sys_get_temp_dir() . 'prompt_password.vbs';
            $vbcontent = 'wscript.echo(InputBox("' . addslashes($prompt) . '", "", "password here"))';
            #file_put_contents($vbscript, $vbcontent);

            $command  = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);

            return $password;
        }

        $command = "/usr/bin/env bash -c 'echo OK'";

        if (rtrim(shell_exec($command)) !== 'OK') {
            echo $prompt;
            $pass = trim(fgets(STDIN));
            echo chr(8)."\r" . $prompt . str_repeat("*", strlen($pass))."\n";

            return $pass;
        }

        $command  = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($prompt) . "\" mypassword && echo \$mypassword'";
        $password = rtrim(shell_exec($command));
        echo "\n";

        return $password;
    }

    public static function get_boolean($str)
    {
        $str = strtolower($str);

        return !in_array($str, array('false', '0', 'no', 'off', 'nein', ''), true);
    }

    public static function is_absolute_path($path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return (bool) preg_match('!^[a-z]:[\\\\/]!i', $path);
        }

        return $path[0] == '/';
    }

    public static function resolve_url($url)
    {
        if (!preg_match('|^https?://|', $url)) {
            $schema       = 'http';
            $default_port = 80;

            if (self::https_check()) {
                $schema       = 'https';
                $default_port = 443;
            }

            $prefix = $schema . '://' . preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
            if ($_SERVER['SERVER_PORT'] != $default_port && $_SERVER['SERVER_PORT'] != 80) {
                $prefix .= ':' . $_SERVER['SERVER_PORT'];
            }

            $url = $prefix . ($url[0] == '/' ? '' : '/') . $url;
        }

        return $url;
    }

    public static function random_bytes($length, $raw = false)
    {
        $hextab  = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $tabsize = strlen($hextab);

        if ($raw && function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (!$raw && function_exists('random_int')) {
            $result = '';
            while ($length-- > 0) {
                $result .= $hextab[random_int(0, $tabsize - 1)];
            }

            return $result;
        }

        $random = openssl_random_pseudo_bytes($length);

        if ($random === false && $length > 0) {
            throw new Exception("Failed to get random bytes");
        }

        if (!$raw) {
            for ($x = 0; $x < $length; $x++) {
                $random[$x] = $hextab[ord($random[$x]) % $tabsize];
            }
        }

        return $random;
    }

    public static function bin2ascii($input)
    {
        $hextab = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $result = '';

        for ($x = 0; $x < strlen($input); $x++) {
            $result .= $hextab[ord($input[$x]) % 62];
        }

        return $result;
    }

    public static function date_format($format = null)
    {
        if (empty($format)) {
            $format = 'd-M-Y H:i:s O';
        }

        if (strpos($format, 'u') !== false) {
            $dt  = number_format(microtime(true), 6, '.', '');
            $dt .=  '.' . date_default_timezone_get();

            if ($date = date_create_from_format('U.u.e', $dt)) {
                return $date->format($format);
            }
        }

        return date($format);
    }

    public static function parse_socket_options(&$options, $host = null)
    {
        if (empty($host) || empty($options)) {
            return;
        }

        $host_url = parse_url($host);
        if (isset($host_url['host'])) {
            $host = $host_url['host'];
        }

        if ($host && array_key_exists($host, $options)) {
            $options = $options[$host];
        }
    }

    public static function max_upload_size()
    {
        $max_filesize = parse_bytes(ini_get('upload_max_filesize'));
        $max_postsize = parse_bytes(ini_get('post_max_size'));

        if ($max_postsize && $max_postsize < $max_filesize) {
            $max_filesize = $max_postsize;
        }

        return $max_filesize;
    }

    public static function preg_error($error = array(), $terminate = false)
    {
        if (($preg_error = preg_last_error()) != PREG_NO_ERROR) {
            $errstr = "PCRE Error: $preg_error.";

            if ($preg_error == PREG_BACKTRACK_LIMIT_ERROR) {
                $errstr .= " Consider raising pcre.backtrack_limit!";
            }
            if ($preg_error == PREG_RECURSION_LIMIT_ERROR) {
                $errstr .= " Consider raising pcre.recursion_limit!";
            }

            $error = array_merge(array('code' => 620, 'line' => __LINE__, 'file' => __FILE__), $error);

            if (!empty($error['message'])) {
                $error['message'] .= ' ' . $errstr;
            }
            else {
                $error['message'] = $errstr;
            }

            rcube::raise_error($error, true, $terminate);

            return true;
        }

        return false;
    }

    public static function temp_filename($file_name, $unique = true, $create = true)
    {
        $temp_dir = rcube::get_instance()->config->get('temp_dir');

        if (!is_writable($temp_dir)) {
            $temp_dir = sys_get_temp_dir();
        }

        $temp_file = $unique ? str_replace('.', '', uniqid($file_name, true)) : $file_name;
        $temp_path = unslashify($temp_dir) . '/' . RCUBE_TEMP_FILE_PREFIX . $temp_file;

        if ($unique && file_exists($temp_path)) {
            return self::temp_filename($file_name, $unique, $create);
        }

        if ($create) {
            touch($temp_path);
        }

        return $temp_path;
    }
}
