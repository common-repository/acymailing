<?php


namespace Javanile\Imap2\Roundcube;

use Javanile\Imap2\rcube;
use Javanile\Imap2\rcube_mime_decode;

class Mime
{
    private static $default_charset;


    function __construct($default_charset = null)
    {
        self::$default_charset = $default_charset;
    }

    public static function get_charset()
    {
        if (self::$default_charset) {
            return self::$default_charset;
        }

        if ($charset = IMAP2_CHARSET) {
            return $charset;
        }

        return IMAP2_CHARSET;
    }

    public static function parse_message($raw_body)
    {
        $conf = array(
            'include_bodies'  => true,
            'decode_bodies'   => true,
            'decode_headers'  => false,
            'default_charset' => self::get_charset(),
        );

        $mime = new rcube_mime_decode($conf);

        return $mime->decode($raw_body);
    }

    static function decode_address_list($input, $max = null, $decode = true, $fallback = null, $addronly = false)
    {
        if (is_array($input)) {
            $input = implode(', ', $input);
        }

        $a   = self::parse_address_list($input, $decode, $fallback);
        $out = array();
        $j   = 0;

        $special_chars = '[\(\)\<\>\\\.\[\]@,;:"]';

        if (!is_array($a)) {
            return $out;
        }

        foreach ($a as $val) {
            $j++;
            $address = trim($val['address']);

            if ($addronly) {
                $out[$j] = $address;
            }
            else {
                $name = trim($val['name']);
                if ($name && $address && $name != $address)
                    $string = sprintf('%s <%s>', preg_match("/$special_chars/", $name) ? '"'.addcslashes($name, '"').'"' : $name, $address);
                else if ($address)
                    $string = $address;
                else if ($name)
                    $string = $name;

                $out[$j] = array('name' => $name, 'mailto' => $address, 'string' => $string);
            }

            if ($max && $j==$max)
                break;
        }

        return $out;
    }

    public static function decode_header($input, $fallback = null)
    {
        $str = self::decode_mime_string((string)$input, $fallback);

        return $str;
    }

    public static function decode_mime_string($input, $fallback = null)
    {
        $default_charset = $fallback ?: self::get_charset();

        $input = preg_replace("/\?=\s+=\?/", '?==?', $input);

        $re = '/=\?([^?]+)\?([BbQq])\?([^\n]*?)\?=/';

        if (preg_match_all($re, $input, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $tmp   = array();
            $out   = '';
            $start = 0;

            foreach ($matches as $idx => $m) {
                $pos      = $m[0][1];
                $charset  = $m[1][0];
                $encoding = $m[2][0];
                $text     = $m[3][0];
                $length   = strlen($m[0][0]);

                if ($start != $pos) {
                    $substr = substr($input, $start, $pos-$start);
                    $out   .= Charset::convert($substr, $default_charset);
                    $start  = $pos;
                }
                $start += $length;


                $tmp[] = $text;
                if ($next_match = $matches[$idx+1]) {
                    if ($next_match[0][1] == $start
                        && $next_match[1][0] == $charset
                        && $next_match[2][0] == $encoding
                    ) {
                        continue;
                    }
                }

                $count = count($tmp);
                $text  = '';

                if ($encoding == 'B' || $encoding == 'b') {
                    $rest  = '';
                    for ($i=0; $i<$count; $i++) {
                        $chunk  = $rest . $tmp[$i];
                        $length = strlen($chunk);
                        if ($length % 4) {
                            $length = floor($length / 4) * 4;
                            $rest   = substr($chunk, $length);
                            $chunk  = substr($chunk, 0, $length);
                        }

                        $text .= base64_decode($chunk);
                    }
                }
                else { //if ($encoding == 'Q' || $encoding == 'q') {
                    for ($i=0; $i<$count; $i++)
                        $text .= $tmp[$i];

                    $text = str_replace('_', ' ', $text);
                    $text = quoted_printable_decode($text);
                }

                $out .= Charset::convert($text, $charset);
                $tmp = array();
            }

            if ($start != strlen($input)) {
                $out .= Charset::convert(substr($input, $start), $default_charset);
            }

            return $out;
        }

        return Charset::convert($input, $default_charset);
    }

    public static function decode($input, $encoding = '7bit')
    {
        switch (strtolower($encoding)) {
            case 'quoted-printable':
                return quoted_printable_decode($input);
            case 'base64':
                return base64_decode($input);
            case 'x-uuencode':
            case 'x-uue':
            case 'uue':
            case 'uuencode':
                return convert_uudecode($input);
            case '7bit':
            default:
                return $input;
        }
    }

    public static function parse_headers($headers)
    {
        $a_headers = array();
        $headers   = preg_replace('/\r?\n(\t| )+/', ' ', $headers);
        $lines     = explode("\n", $headers);
        $count     = count($lines);

        for ($i=0; $i<$count; $i++) {
            if ($p = strpos($lines[$i], ': ')) {
                $field = strtolower(substr($lines[$i], 0, $p));
                $value = trim(substr($lines[$i], $p+1));
                if (!empty($value)) {
                    $a_headers[$field] = $value;
                }
            }
        }

        return $a_headers;
    }

    private static function parse_address_list($str, $decode = true, $fallback = null)
    {
        $str = preg_replace('/\r?\n(\s|\t)?/', ' ', $str);

        $str = self::explode_header_string(',;', $str, true);
        $result = array();

        $email_rx = '(\S+|("\s*(?:[^"\f\n\r\t\v\b\s]+\s*)+"))@\S+';

        foreach ($str as $key => $val) {
            $name    = '';
            $address = '';
            $val     = trim($val);

            if (preg_match('/(.*)<('.$email_rx.')>$/', $val, $m)) {
                $address = $m[2];
                $name    = trim($m[1]);
            }
            else if (preg_match('/^('.$email_rx.')$/', $val, $m)) {
                $address = $m[1];
                $name    = '';
            }
            else if (preg_match('/(\s*<MAILER-DAEMON>)$/', $val, $m)) {
                $address = 'MAILER-DAEMON';
                $name    = substr($val, 0, -strlen($m[1]));
            }
            else if (preg_match('/('.$email_rx.')/', $val, $m)) {
                $name = $m[1];
            }
            else {
                $name = $val;
            }

            if ($name) {
                if ($name[0] == '"' && $name[strlen($name)-1] == '"') {
                    $name = substr($name, 1, -1);
                    $name = stripslashes($name);
                }
                if ($decode) {
                    $name = self::decode_header($name, $fallback);
                    if ($name[0] == '"' && $name[strlen($name)-1] == '"') {
                        $name = substr($name, 1, -1);
                    }
                }
            }

            if (!$address && $name) {
                $address = $name;
                $name    = '';
            }

            if ($address) {
                $address      = self::fix_email($address);
                $result[$key] = array('name' => $name, 'address' => $address);
            }
        }

        return $result;
    }

    public static function explode_header_string($separator, $str, $remove_comments = false)
    {
        $length  = strlen($str);
        $result  = array();
        $quoted  = false;
        $comment = 0;
        $out     = '';

        for ($i=0; $i<$length; $i++) {
            if ($quoted) {
                if ($str[$i] == '"') {
                    $quoted = false;
                }
                else if ($str[$i] == "\\") {
                    if ($comment <= 0) {
                        $out .= "\\";
                    }
                    $i++;
                }
            }
            else if ($comment > 0) {
                if ($str[$i] == ')') {
                    $comment--;
                }
                else if ($str[$i] == '(') {
                    $comment++;
                }
                else if ($str[$i] == "\\") {
                    $i++;
                }
                continue;
            }
            else if (strpos($separator, $str[$i]) !== false) {
                if ($out) {
                    $result[] = $out;
                }
                $out = '';
                continue;
            }
            else if ($str[$i] == '"') {
                $quoted = true;
            }
            else if ($remove_comments && $str[$i] == '(') {
                $comment++;
            }

            if ($comment <= 0) {
                $out .= $str[$i];
            }
        }

        if ($out && $comment <= 0) {
            $result[] = $out;
        }

        return $result;
    }

    public static function unfold_flowed($text, $mark = null, $delsp = false)
    {
        $text    = preg_split('/\r?\n/', $text);
        $last    = -1;
        $q_level = 0;
        $marks   = array();

        foreach ($text as $idx => $line) {
            if ($q = strspn($line, '>')) {
                $line = substr($line, $q);
                if ($line[0] === ' ') $line = substr($line, 1);

                if ($q == $q_level
                    && isset($text[$last]) && $text[$last][strlen($text[$last])-1] == ' '
                    && !preg_match('/^>+ {0,1}$/', $text[$last])
                ) {
                    if ($delsp) {
                        $text[$last] = substr($text[$last], 0, -1);
                    }
                    $text[$last] .= $line;
                    unset($text[$idx]);

                    if ($mark) {
                        $marks[$last] = true;
                    }
                }
                else {
                    $last = $idx;
                }
            }
            else {
                if ($line == '-- ') {
                    $last = $idx;
                }
                else {
                    if ($line[0] === ' ') $line = substr($line, 1);

                    if (isset($text[$last]) && $line && !$q_level
                        && $text[$last] != '-- '
                        && $text[$last][strlen($text[$last])-1] == ' '
                    ) {
                        if ($delsp) {
                            $text[$last] = substr($text[$last], 0, -1);
                        }
                        $text[$last] .= $line;
                        unset($text[$idx]);

                        if ($mark) {
                            $marks[$last] = true;
                        }
                    }
                    else {
                        $text[$idx] = $line;
                        $last = $idx;
                    }
                }
            }
            $q_level = $q;
        }

        if (!empty($marks)) {
            foreach (array_keys($marks) as $mk) {
                $text[$mk] = $mark . $text[$mk];
            }
        }

        return implode("\r\n", $text);
    }

    public static function format_flowed($text, $length = 72, $charset=null)
    {
        $text = preg_split('/\r?\n/', $text);

        foreach ($text as $idx => $line) {
            if ($line != '-- ') {
                if ($level = strspn($line, '>')) {
                    $line = substr($line, $level);
                    $line = rtrim($line, ' ');
                    if ($line[0] === ' ') $line = substr($line, 1);

                    $prefix = str_repeat('>', $level) . ' ';
                    $line   = $prefix . self::wordwrap($line, $length - $level - 2, " \r\n$prefix", false, $charset);
                }
                else if ($line) {
                    $line = self::wordwrap(rtrim($line), $length - 2, " \r\n", false, $charset);
                    $line = preg_replace('/(^|\r\n)(From| |>)/', '\\1 \\2', $line);
                }

                $text[$idx] = $line;
            }
        }

        return implode("\r\n", $text);
    }

    public static function wordwrap($string, $width=75, $break="\n", $cut=false, $charset=null, $wrap_quoted=true)
    {

        if ($charset && $charset != IMAP2_CHARSET) {
            mb_internal_encoding($charset);
        }

        $string       = str_replace("\r\n", "\n", $string);
        $separator    = "\n"; // must be 1 character length
        $result       = array();

        while (($stringLength = mb_strlen($string)) > 0) {
            $breakPos = mb_strpos($string, $separator, 0);

            if ($wrap_quoted && $string[0] == '>') {
                if ($breakPos === $stringLength - 1 || $breakPos === false) {
                    $subString = $string;
                    $cutLength = null;
                }
                else {
                    $subString = mb_substr($string, 0, $breakPos);
                    $cutLength = $breakPos + 1;
                }
            }
            else if ($breakPos !== false && $breakPos < $width) {
                if ($breakPos === $stringLength - 1) {
                    $subString = $string;
                    $cutLength = null;
                }
                else {
                    $subString = mb_substr($string, 0, $breakPos);
                    $cutLength = $breakPos + 1;
                }
            }
            else {
                $subString = mb_substr($string, 0, $width);

                if ($breakPos === false && $subString === $string) {
                    $cutLength = null;
                }
                else {
                    $nextChar = mb_substr($string, $width, 1);

                    if ($nextChar === ' ' || $nextChar === $separator) {
                        $afterNextChar = mb_substr($string, $width + 1, 1);

                        if ($afterNextChar === false || $afterNextChar === '') {
                            $subString .= $nextChar;
                        }

                        $cutLength = mb_strlen($subString) + 1;
                    }
                    else {
                        $spacePos = mb_strrpos($subString, ' ', 0);

                        if ($spacePos !== false) {
                            $subString = mb_substr($subString, 0, $spacePos);
                            $cutLength = $spacePos + 1;
                        }
                        else if ($cut === false) {
                            $spacePos = mb_strpos($string, ' ', 0);

                            if ($spacePos !== false && ($breakPos === false || $spacePos < $breakPos)) {
                                $subString = mb_substr($string, 0, $spacePos);
                                $cutLength = $spacePos + 1;
                            }
                            else if ($breakPos === false) {
                                $subString = $string;
                                $cutLength = null;
                            }
                            else {
                                $subString = mb_substr($string, 0, $breakPos);
                                $cutLength = $breakPos + 1;
                            }
                        }
                        else {
                            $cutLength = $width;
                        }
                    }
                }
            }

            $result[] = $subString;

            if ($cutLength !== null) {
                $string = mb_substr($string, $cutLength, ($stringLength - $cutLength));
            }
            else {
                break;
            }
        }

        if ($charset && $charset != IMAP2_CHARSET) {
            mb_internal_encoding(IMAP2_CHARSET);
        }

        return implode($break, $result);
    }

    public static function file_content_type($path, $name, $failover = 'application/octet-stream', $is_stream = false, $skip_suffix = false)
    {
        static $mime_ext = array();

        $mime_type = null;
        $config    = rcube::get_instance()->config;

        if (!$skip_suffix && empty($mime_ext)) {
            foreach ($config->resolve_paths('mimetypes.php') as $fpath) {
                $mime_ext = array_merge($mime_ext, (array) @include($fpath));
            }
        }

        if (!$skip_suffix && is_array($mime_ext) && $name) {
            if ($suffix = substr($name, strrpos($name, '.')+1)) {
                $mime_type = $mime_ext[strtolower($suffix)];
            }
        }

        if (!$mime_type && function_exists('finfo_open')) {
            $mime_magic = $config->get('mime_magic');
            if ($mime_magic) {
                $finfo = finfo_open(FILEINFO_MIME, $mime_magic);
            }
            else {
                $finfo = finfo_open(FILEINFO_MIME);
            }

            if ($finfo) {
                $func      = $is_stream ? 'finfo_buffer' : 'finfo_file';
                $mime_type = $func($finfo, $path, FILEINFO_MIME_TYPE);
                finfo_close($finfo);
            }
        }

        if (!$mime_type && !$is_stream && function_exists('mime_content_type')) {
            $mime_type = @mime_content_type($path);
        }

        if (!$mime_type) {
            $mime_type = $failover;
        }

        return $mime_type;
    }

    public static function get_mime_extensions($mimetype = null)
    {
        static $mime_types, $mime_extensions;

        if (is_array($mime_types)) {
            return $mimetype ? $mime_types[$mimetype] : $mime_extensions;
        }

        $file_paths = array();

        if ($mime_types = rcube::get_instance()->config->get('mime_types')) {
            $file_paths[] = $mime_types;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $file_paths[] = 'C:/xampp/apache/conf/mime.types.';
        }
        else {
            $file_paths[] = '/etc/mime.types';
            $file_paths[] = '/etc/httpd/mime.types';
            $file_paths[] = '/etc/httpd2/mime.types';
            $file_paths[] = '/etc/apache/mime.types';
            $file_paths[] = '/etc/apache2/mime.types';
            $file_paths[] = '/etc/nginx/mime.types';
            $file_paths[] = '/usr/local/etc/httpd/conf/mime.types';
            $file_paths[] = '/usr/local/etc/apache/conf/mime.types';
            $file_paths[] = '/usr/local/etc/apache24/mime.types';
        }

        foreach ($file_paths as $fp) {
            if (@is_readable($fp)) {
                $lines = file($fp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                break;
            }
        }

        $mime_types = $mime_extensions = array();
        $regex = "/([\w\+\-\.\/]+)\s+([\w\s]+)/i";
        foreach ((array)$lines as $line) {
            if ($line[0] == '#' || !preg_match($regex, $line, $matches))
                continue;

            $mime = $matches[1];
            foreach (explode(' ', $matches[2]) as $ext) {
                $ext = trim($ext);
                $mime_types[$mime][] = $ext;
                $mime_extensions[$ext] = $mime;
            }
        }

        if (empty($mime_types)) {
            foreach (rcube::get_instance()->config->resolve_paths('mimetypes.php') as $fpath) {
                $mime_extensions = array_merge($mime_extensions, (array) @include($fpath));
            }

            foreach ($mime_extensions as $ext => $mime) {
                $mime_types[$mime][] = $ext;
            }
        }

        $aliases = array(
            'image/gif'      => array('gif'),
            'image/png'      => array('png'),
            'image/x-png'    => array('png'),
            'image/jpeg'     => array('jpg', 'jpeg', 'jpe'),
            'image/jpg'      => array('jpg', 'jpeg', 'jpe'),
            'image/pjpeg'    => array('jpg', 'jpeg', 'jpe'),
            'image/tiff'     => array('tif'),
            'image/bmp'      => array('bmp'),
            'image/x-ms-bmp' => array('bmp'),
            'message/rfc822' => array('eml'),
            'text/x-mail'    => array('eml'),
        );

        foreach ($aliases as $mime => $exts) {
            $mime_types[$mime] = array_unique(array_merge((array) $mime_types[$mime], $exts));

            foreach ($exts as $ext) {
                if (!isset($mime_extensions[$ext])) {
                    $mime_extensions[$ext] = $mime;
                }
            }
        }

        return $mimetype ? $mime_types[$mimetype] : $mime_extensions;
    }

    public static function image_content_type($data)
    {
        $type = 'jpeg';
        if      (preg_match('/^\x89\x50\x4E\x47/', $data)) $type = 'png';
        else if (preg_match('/^\x47\x49\x46\x38/', $data)) $type = 'gif';
        else if (preg_match('/^\x00\x00\x01\x00/', $data)) $type = 'ico';

        return 'image/' . $type;
    }

    public static function fix_email($email)
    {
        $parts = Utils::explode_quoted_string('@', $email);
        foreach ($parts as $idx => $part) {
            if ($part[0] == '"' && preg_match('/^"([a-zA-Z0-9._+=-]+)"$/', $part, $m)) {
                $parts[$idx] = $m[1];
            }
        }

        return implode('@', $parts);
    }
}
