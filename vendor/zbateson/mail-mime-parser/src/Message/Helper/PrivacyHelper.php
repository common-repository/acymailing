<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\Message\Factory\IMimePartFactory;
use ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory;
use ZBateson\MailMimeParser\Message\IMessagePart;

class PrivacyHelper extends AbstractHelper
{
    private $genericHelper;

    private $multipartHelper;

    public function __construct(
        IMimePartFactory $mimePartFactory,
        IUUEncodedPartFactory $uuEncodedPartFactory,
        GenericHelper $genericHelper,
        MultipartHelper $multipartHelper
    ) {
        parent::__construct($mimePartFactory, $uuEncodedPartFactory);
        $this->genericHelper = $genericHelper;
        $this->multipartHelper = $multipartHelper;
    }

    public function setMessageAsMultipartSigned(IMessage $message, $micalg, $protocol)
    {
        if (\strcasecmp($message->getContentType(), 'multipart/signed') !== 0) {
            $this->multipartHelper->enforceMime($message);
            $messagePart = $this->mimePartFactory->newInstance();
            $this->genericHelper->movePartContentAndChildren($message, $messagePart);
            $message->addChild($messagePart);
            $boundary = $this->multipartHelper->getUniqueBoundary('multipart/signed');
            $message->setRawHeader(
                HeaderConsts::CONTENT_TYPE,
                "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
            );
        }
        $this->overwrite8bitContentEncoding($message);
        $this->setSignature($message, 'Empty');
    }

    public function setSignature(IMessage $message, $body)
    {
        $signedPart = $message->getSignaturePart();
        if ($signedPart === null) {
            $signedPart = $this->mimePartFactory->newInstance();
            $message->addChild($signedPart);
        }
        $signedPart->setRawHeader(
            HeaderConsts::CONTENT_TYPE,
            $message->getHeaderParameter(HeaderConsts::CONTENT_TYPE, 'protocol')
        );
        $signedPart->setContent($body);
    }

    public function overwrite8bitContentEncoding(IMessage $message)
    {
        $parts = $message->getAllParts(function(IMessagePart $part) {
            return \strcasecmp($part->getContentTransferEncoding(), '8bit') === 0;
        });
        foreach ($parts as $part) {
            $contentType = \strtolower($part->getContentType());
            $part->setRawHeader(
                HeaderConsts::CONTENT_TRANSFER_ENCODING,
                ($contentType === 'text/plain' || $contentType === 'text/html') ?
                'quoted-printable' :
                'base64'
            );
        }
    }

    public function getSignedMessageStream(IMessage $message)
    {
        $child = $message->getChild(0);
        if ($child !== null) {
            return $child->getStream();
        }
        return null;
    }

    public function getSignedMessageAsString(IMessage $message)
    {
        $stream = $this->getSignedMessageStream($message);
        if ($stream !== null) {
            return \preg_replace(
                '/\r\n|\r|\n/',
                "\r\n",
                $stream->getContents()
            );
        }
        return null;
    }
}
