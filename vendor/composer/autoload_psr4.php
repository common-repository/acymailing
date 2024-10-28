<?php


$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'ZBateson\\StreamDecorators\\' => array($vendorDir . '/zbateson/stream-decorators/src'),
    'ZBateson\\MbWrapper\\' => array($vendorDir . '/zbateson/mb-wrapper/src'),
    'ZBateson\\MailMimeParser\\' => array($vendorDir . '/zbateson/mail-mime-parser/src'),
    'Symfony\\Polyfill\\Mbstring\\' => array($vendorDir . '/symfony/polyfill-mbstring'),
    'Symfony\\Polyfill\\Iconv\\' => array($vendorDir . '/symfony/polyfill-iconv'),
    'Symfony\\' => array($baseDir . '/'),
    'Sabberworm\\' => array($baseDir . '/'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-factory/src', $vendorDir . '/psr/http-message/src'),
    'Psr\\Container\\' => array($vendorDir . '/psr/container/src'),
    'Pelago\\' => array($baseDir . '/'),
    'Javanile\\Imap2\\' => array($vendorDir . '/javanile/php-imap2/src'),
    'GuzzleHttp\\Psr7\\' => array($vendorDir . '/guzzlehttp/psr7/src'),
    'AcyMailing\\' => array($baseDir . '/'),
    'AcyMailerPhp\\' => array($baseDir . '/'),
);
