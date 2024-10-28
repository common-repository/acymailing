<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\GenericConsumer;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;

class GenericReceivedConsumer extends GenericConsumer
{
    protected $partName;

    public function __construct(ConsumerService $consumerService, HeaderPartFactory $partFactory, string $partName)
    {
        parent::__construct($consumerService, $partFactory);
        $this->partName = $partName;
    }

    protected function getPartName() : string
    {
        return $this->partName;
    }

    protected function getSubConsumers() : array
    {
        return [$this->consumerService->getCommentConsumer()];
    }

    protected function isStartToken(string $token) : bool
    {
        $pattern = '/^' . \preg_quote($this->getPartName(), '/') . '$/i';
        return (\preg_match($pattern, $token) === 1);
    }

    protected function isEndToken(string $token) : bool
    {
        return (\preg_match('/^(by|via|with|id|for|;)$/i', $token) === 1);
    }

    protected function getTokenSeparators() : array
    {
        return [
            '\s+',
            '(\A\s*|\s+)(?i)' . \preg_quote($this->getPartName(), '/') . '(?-i)(?=\s+)'
        ];
    }

    protected function processParts(array $parts) : array
    {
        $strValue = '';
        $ret = [];
        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            if ($part instanceof CommentPart) {
                $ret[] = $part;
                continue;    // getValue() is empty anyway, but for clarity...
            }
            $strValue .= $part->getValue();
        }
        \array_unshift($ret, $this->partFactory->newReceivedPart($this->getPartName(), $strValue));
        return $ret;
    }
}
