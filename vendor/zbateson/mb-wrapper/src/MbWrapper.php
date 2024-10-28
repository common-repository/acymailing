<?php

namespace ZBateson\MbWrapper;

class MbWrapper
{
    public static $mbAliases = [
        'CP850' => 'CP850',
        'GB2312' => 'GB18030',
        'SJIS2004' => 'SJIS-2004',
        'ANSIX341968' => 'ASCII',
        'ANSIX341986' => 'ASCII',
        'ARABIC' => 'ISO-8859-6',
        'ASMO708' => 'ISO-8859-6',
        'BIG5' => 'BIG-5',
        'BIG5TW' => 'BIG-5',
        'CESU8' => 'UTF-8',
        'CHINESE' => 'GB18030',
        'CP367' => 'ASCII',
        'CP819' => 'ISO-8859-1',
        'CP1251' => 'WINDOWS-1251',
        'CP1252' => 'WINDOWS-1252',
        'CP1254' => 'WINDOWS-1254',
        'CP1255' => 'ISO-8859-8',
        'CSASCII' => 'ASCII',
        'CSBIG5' => 'BIG-5',
        'CSIBM866' => 'CP866',
        'CSISO2022JP' => 'ISO-2022-JP',
        'CSISO2022KR' => 'ISO-2022-KR',
        'CSISO58GB231280' => 'GB18030',
        'CSISOLATIN1' => 'ISO-8859-1',
        'CSISOLATIN2' => 'ISO-8859-2',
        'CSISOLATIN3' => 'ISO-8859-3',
        'CSISOLATIN4' => 'ISO-8859-4',
        'CSISOLATIN5' => 'ISO-8859-9',
        'CSISOLATIN6' => 'ISO-8859-10',
        'CSISOLATINARABIC' => 'ISO-8859-6',
        'CSISOLATINCYRILLIC' => 'ISO-8859-5',
        'CSISOLATINGREEK' => 'ISO-8859-7',
        'CSISOLATINHEBREW' => 'ISO-8859-8',
        'CSKOI8R' => 'KOI8-R',
        'CSPC850MULTILINGUAL' => 'CP850',
        'CSSHIFTJIS' => 'SJIS',
        'CYRILLIC' => 'ISO-8859-5',
        'ECMA114' => 'ISO-8859-6',
        'ECMA118' => 'ISO-8859-7',
        'ELOT928' => 'ISO-8859-7',
        'EUCCN' => 'GB18030',
        'EUCGB2312CN' => 'GB18030',
        'GB180302000' => 'GB18030',
        'GB23121980' => 'GB18030',
        'GB231280' => 'GB18030',
        'GBK' => 'CP936',
        'GREEK8' => 'ISO-8859-7',
        'GREEK' => 'ISO-8859-7',
        'HEBREW' => 'ISO-8859-8',
        'HZGB2312' => 'HZ',
        'HZGB' => 'HZ',
        'IBM367' => 'ASCII',
        'IBM819' => 'ISO-8859-1',
        'IBM850' => 'CP850',
        'IBM866' => 'CP866',
        'ISO2022JP2004' => 'ISO-2022-JP-2004',
        'ISO646IRV1991' => 'ASCII',
        'ISO646US' => 'ASCII',
        'ISO8859' => 'ISO-8859-1',
        'ISO8859101992' => 'ISO-8859-10',
        'ISO885911987' => 'ISO-8859-1',
        'ISO8859141998' => 'ISO-8859-14',
        'ISO8859162001' => 'ISO-8859-16',
        'ISO885921987' => 'ISO-8859-2',
        'ISO885931988' => 'ISO-8859-3',
        'ISO885941988' => 'ISO-8859-4',
        'ISO885951988' => 'ISO-8859-5',
        'ISO885961987' => 'ISO-8859-6',
        'ISO885971987' => 'ISO-8859-7',
        'ISO885981988' => 'ISO-8859-8',
        'ISO88598I' => 'ISO-8859-8',
        'ISO885991989' => 'ISO-8859-9',
        'ISOCELTIC' => 'ISO-8859-14',
        'ISOIR100' => 'ISO-8859-1',
        'ISOIR101' => 'ISO-8859-2',
        'ISOIR109' => 'ISO-8859-3',
        'ISOIR110' => 'ISO-8859-4',
        'ISOIR126' => 'ISO-8859-7',
        'ISOIR127' => 'ISO-8859-6',
        'ISOIR138' => 'ISO-8859-8',
        'ISOIR144' => 'ISO-8859-5',
        'ISOIR148' => 'ISO-8859-9',
        'ISOIR157' => 'ISO-8859-10',
        'ISOIR199' => 'ISO-8859-14',
        'ISOIR226' => 'ISO-8859-16',
        'ISOIR58' => 'GB18030',
        'ISOIR6' => 'ASCII',
        'KOI8R' => 'KOI8-R',
        'KOREAN' => 'EUC-KR',
        'KSC56011987' => 'EUC-KR',
        'KSC5601' => 'EUC-KR',
        'KSX1001' => 'EUC-KR',
        'L1' => 'ISO-8859-1',
        'L2' => 'ISO-8859-2',
        'L3' => 'ISO-8859-3',
        'L4' => 'ISO-8859-4',
        'L5' => 'ISO-8859-9',
        'L6' => 'ISO-8859-10',
        'L8' => 'ISO-8859-14',
        'L10' => 'ISO-8859-16',
        'LATIN' => 'ISO-8859-1',
        'LATIN1' => 'ISO-8859-1',
        'LATIN2' => 'ISO-8859-2',
        'LATIN3' => 'ISO-8859-3',
        'LATIN4' => 'ISO-8859-4',
        'LATIN5' => 'ISO-8859-9',
        'LATIN6' => 'ISO-8859-10',
        'LATIN8' => 'ISO-8859-14',
        'LATIN10' => 'ISO-8859-16',
        'MS932' => 'CP932',
        'ms936' => 'CP936',
        'MS950' => 'CP950',
        'MSKANJI' => 'CP932',
        'SHIFTJIS2004' => 'SJIS',
        'SHIFTJIS' => 'SJIS',
        'UJIS' => 'EUC-JP',
        'UNICODE11UTF7' => 'UTF-7',
        'US' => 'ASCII',
        'USASCII' => 'ASCII',
        'WE8MSWIN1252' => 'WINDOWS-1252',
        'WINDOWS1251' => 'WINDOWS-1251',
        'WINDOWS1252' => 'WINDOWS-1252',
        'WINDOWS1254' => 'WINDOWS-1254',
        'WINDOWS1255' => 'ISO-8859-8',
        '0' => 'WINDOWS-1252',
        '128' => 'SJIS',
        '129' => 'EUC-KR',
        '134' => 'GB18030',
        '136' => 'BIG-5',
        '161' => 'WINDOWS-1253',
        '162' => 'WINDOWS-1254',
        '177' => 'WINDOWS-1255',
        '178' => 'WINDOWS-1256',
        '186' => 'WINDOWS-1257',
        '204' => 'WINDOWS-1251',
        '222' => 'WINDOWS-874',
        '238' => 'WINDOWS-1250',
        '646' => 'ASCII',
        '850' => 'CP850',
        '866' => 'CP866',
        '932' => 'CP932',
        '936' => 'CP936',
        '950' => 'CP950',
        '1251' => 'WINDOWS-1251',
        '1252' => 'WINDOWS-1252',
        '1254' => 'WINDOWS-1254',
        '1255' => 'ISO-8859-8',
        '8859' => 'ISO-8859-1',
    ];

    public static $iconvAliases = [
        'CESU8' => 'UTF8',
        'CP154' => 'PT154',
        'CPGR' => 'CP869',
        'CPIS' => 'CP861',
        'CSHPROMAN8' => 'ROMAN8',
        'CSIBM037' => 'CP037',
        'CSIBM1026' => 'CP1026',
        'CSIBM424' => 'CP424',
        'CSIBM500' => 'CP500',
        'CSIBM860' => 'CP860',
        'CSIBM861' => 'CP861',
        'CSIBM863' => 'CP863',
        'CSIBM864' => 'CP864',
        'CSIBM865' => 'CP865',
        'CSIBM869' => 'CP869',
        'CSPC775BALTIC' => 'CP775',
        'CSPC862LATINHEBREW' => 'CP862',
        'CSPC8CODEPAGE437' => 'CP437',
        'CSPTCP154' => 'PT154',
        'CYRILLICASIAN' => 'PT154',
        'EBCDICCPBE' => 'CP500',
        'EBCDICCPCA' => 'CP037',
        'EBCDICCPCH' => 'CP500',
        'EBCDICCPHE' => 'CP424',
        'EBCDICCPNL' => 'CP037',
        'EBCDICCPUS' => 'CP037',
        'EBCDICCPWT' => 'CP037',
        'HKSCS' => 'BIG5HKSCS',
        'HPROMAN8' => 'ROMAN8',
        'IBM037' => 'CP037',
        'IBM039' => 'CP037',
        'IBM424' => 'CP424',
        'IBM437' => 'CP437',
        'IBM500' => 'CP500',
        'IBM775' => 'CP775',
        'IBM860' => 'CP860',
        'IBM861' => 'CP861',
        'IBM862' => 'CP862',
        'IBM863' => 'CP863',
        'IBM864' => 'CP864',
        'IBM865' => 'CP865',
        'IBM869' => 'CP869',
        'IBM1026' => 'CP1026',
        'IBM1140' => 'CP1140',
        'ISO2022JP2' => 'ISO2022JP2',
        'ISO8859112001' => 'ISO885911',
        'ISO885911' => 'ISO885911',
        'ISOIR166' => 'TIS620',
        'JOHAB' => 'CP1361',
        'MACCYRILLIC' => 'MACCYRILLIC',
        'MS1361' => 'CP1361',
        'MS949' => 'CP949',
        'PTCP154' => 'PT154',
        'R8' => 'ROMAN8',
        'ROMAN8' => 'ROMAN8',
        'THAI' => 'ISO885911',
        'TIS6200' => 'TIS620',
        'TIS62025290' => 'TIS620',
        'TIS62025291' => 'TIS620',
        'TIS620' => 'TIS620',
        'UHC' => 'CP949',
        'WINDOWS1250' => 'CP1250',
        'WINDOWS1253' => 'CP1253',
        'WINDOWS1256' => 'CP1256',
        'WINDOWS1257' => 'CP1257',
        'WINDOWS1258' => 'CP1258',
        '037' => 'CP037',
        '424' => 'CP424',
        '437' => 'CP437',
        '500' => 'CP500',
        '775' => 'CP775',
        '860' => 'CP860',
        '861' => 'CP861',
        '862' => 'CP862',
        '863' => 'CP863',
        '864' => 'CP864',
        '865' => 'CP865',
        '869' => 'CP869',
        '949' => 'CP949',
        '1026' => 'CP1026',
        '1140' => 'CP1140',
        '1250' => 'CP1250',
        '1253' => 'CP1253',
        '1256' => 'CP1256',
        '1257' => 'CP1257',
        '1258' => 'CP1258',
    ];

    protected $mappedMbCharsets = [
        'UTF8' => 'UTF-8',
        'USASCII' => 'US-ASCII',
        'ISO88591' => 'ISO-8859-1',
    ];

    private static $mbListedEncodings;

    public function __construct()
    {
        if (self::$mbListedEncodings === null) {
            $cs = \mb_list_encodings();
            $keys = $this->getNormalizedCharset($cs);
            self::$mbListedEncodings = \array_combine($keys, $cs);
        }
    }

    private function getNormalizedCharset($charset)
    {
        $upper = null;
        if (\is_array($charset)) {
            $upper = \array_map('strtoupper', $charset);
        } else {
            $upper = \strtoupper($charset);
        }
        return \preg_replace('/[^A-Z0-9]+/', '', $upper);
    }

    public function convert(string $str, string $fromCharset, string $toCharset) : string
    {

        $from = $this->getMbCharset($fromCharset);
        $to = $this->getMbCharset($toCharset);

        if ($str !== '') {
            if ($from !== false && $to === false) {
                $str = \mb_convert_encoding($str, 'UTF-8', $from);
                return \iconv('UTF-8', $this->getIconvAlias($toCharset) . '//TRANSLIT//IGNORE', $str);
            } elseif ($from === false && $to !== false) {
                $str = \iconv($this->getIconvAlias($fromCharset), 'UTF-8//TRANSLIT//IGNORE', $str);
                return \mb_convert_encoding($str, $to, 'UTF-8');
            } elseif ($from !== false && $to !== false) {
                return \mb_convert_encoding($str, $to, $from);
            }
            return \iconv(
                $this->getIconvAlias($fromCharset),
                $this->getIconvAlias($toCharset) . '//TRANSLIT//IGNORE',
                $str
            );
        }
        return $str;
    }

    public function checkEncoding(string $str, string $charset) : bool
    {
        $mb = $this->getMbCharset($charset);
        if ($mb !== false) {
            return \mb_check_encoding($str, $mb);
        }
        $ic = $this->getIconvAlias($charset);
        return (@\iconv($ic, $ic, $str) !== false);
    }

    public function getLength(string $str, string $charset) : int
    {
        $mb = $this->getMbCharset($charset);
        if ($mb !== false) {
            return \mb_strlen($str, $mb);
        }
        return \iconv_strlen($str, $this->getIconvAlias($charset) . '//TRANSLIT//IGNORE');
    }

    public function getSubstr(string $str, string $charset, int $start, ?int $length = null) : string
    {
        $mb = $this->getMbCharset($charset);
        if ($mb !== false) {
            return \mb_substr($str, $start, $length, $mb);
        }
        $ic = $this->getIconvAlias($charset);
        if ($ic === 'CP1258') {
            $str = $this->convert($str, $ic, 'UTF-8');
            return $this->convert($this->getSubstr($str, 'UTF-8', $start, $length), 'UTF-8', $ic);
        }
        if ($length === null) {
            $length = \iconv_strlen($str, $ic . '//TRANSLIT//IGNORE');
        }
        return \iconv_substr($str, $start, $length, $ic . '//TRANSLIT//IGNORE');
    }

    private function getMbCharset(string $cs)
    {
        $normalized = $this->getNormalizedCharset($cs);
        if (\array_key_exists($normalized, self::$mbListedEncodings)) {
            return self::$mbListedEncodings[$normalized];
        } elseif (\array_key_exists($normalized, self::$mbAliases)) {
            return self::$mbAliases[$normalized];
        }
        return false;
    }

    private function getIconvAlias(string $cs) : string
    {
        $normalized = $this->getNormalizedCharset($cs);
        if (\array_key_exists($normalized, self::$iconvAliases)) {
            return static::$iconvAliases[$normalized];
        }
        return $cs;
    }
}
