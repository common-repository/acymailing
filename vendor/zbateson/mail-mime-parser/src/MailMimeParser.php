<?php

namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Parser\MessageParser;

class MailMimeParser
{
    public const DEFAULT_CHARSET = 'UTF-8';

    protected static $di = null;

    protected $messageParser;

    public static function getDependencyContainer()
    {
        return static::$di;
    }

    public static function configureDependencyContainer(array $providers = [])
    {
        static::$di = new Container();
        $di = static::$di;
        foreach ($providers as $provider) {
            $di->register($provider);
        }
    }

    public static function setDependencyContainer(?Container $di = null)
    {
        static::$di = $di;
    }

    public function __construct()
    {
        if (static::$di === null) {
            static::configureDependencyContainer();
        }
        $di = static::$di;
        $this->messageParser = $di[\ZBateson\MailMimeParser\Parser\MessageParser::class];
    }

    public function parse($resource, $attached)
    {
        $stream = Utils::streamFor(
            $resource,
            ['metadata' => ['mmp-detached-stream' => ($attached !== true)]]
        );
        if (!$stream->isSeekable()) {
            $stream = new CachingStream($stream);
        }
        return $this->messageParser->parse($stream);
    }
}
