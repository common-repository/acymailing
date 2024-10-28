<?php


namespace Javanile\Imap2;

class Timeout
{
    protected static $timeout;

    public static function set($timeoutType, $timeout = -1)
    {
        if ($timeout == -1) {
            return self::get($timeoutType);
        }

        self::$timeout[$timeoutType] = $timeout;

        return true;
    }

    public static function get($timeoutType)
    {
        return self::$timeout[$timeoutType];
    }
}
