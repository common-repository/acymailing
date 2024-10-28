<?php


namespace Javanile\Imap2;

class BodyStructure
{

    const TYPETEXT = 0;                 const TYPEMULTIPART = 1;            const TYPEMESSAGE = 2;             const TYPEAPPLICATION = 3;          const TYPEAUDIO = 4;                const TYPEIMAGE = 5;                const TYPEVIDEO = 6;                const TYPEMODEL = 7;                const TYPEOTHER = 8;            
    const ENC7BIT = 0;                  const ENC8BIT = 1;                  const ENCBINARY = 2;                const ENCBASE64 = 3;                const ENCQUOTEDPRINTABLE = 4;       const ENCOTHER = 5;             
    protected static $body_types = [
        self::TYPETEXT => "TEXT",
        self::TYPEMULTIPART => "MULTIPART",
        self::TYPEMESSAGE => "MESSAGE",
        self::TYPEAPPLICATION => "APPLICATION",
        self::TYPEAUDIO => "AUDIO",
        self::TYPEIMAGE => "IMAGE",
        self::TYPEVIDEO => "VIDEO",
        self::TYPEMODEL => "MODEL",
        self::TYPEOTHER => "X-UNKNOWN"
    ];

    protected static $body_encodings = [
        self::ENC7BIT => "7BIT",
        self::ENC8BIT => "8BIT",
        self::ENCBINARY => "BINARY",
        self::ENCBASE64 => "BASE64",
        self::ENCQUOTEDPRINTABLE => "QUOTED-PRINTABLE",
        self::ENCOTHER => "X-UNKNOWN"
    ];

    public static function fromMessage($message)
    {
        return self::extractBodyStructure($message->bodystructure);
    }

    protected static function extractBodyStructure($structure)
    {

        if ( is_null($structure) )
            return null;

        if ( ! $length = count($structure) )
            return null;

        $body = (object)[
            "type" => self::TYPEOTHER,
            "encoding" => self::ENC7BIT,
            "ifsubtype" => 0,
            "subtype" => null,
            "ifdescription" => 0,
            "description" => null,
            "ifid" => 0,
            "id" => null,
            "lines" => null,
            "bytes" => null,
            "ifdisposition" => 0,
            "disposition" => null,
            "ifdparameters" => 0,
            "dparameters" => null,
            "ifparameters" => 0,
            "parameters" => null
        ];

        if ( is_array($structure[0]) ) {

            $body->type = self::TYPEMULTIPART;

            $index = 0;
            $parts = [];

            while( is_array($structure[$index]) )
                $parts[] = self::extractBodyStructure( $structure[$index++] );

            if ( $body->subtype = strtoupper($structure[$index++]) )
                $body->ifsubtype = 1;

            if ( $index < $length ) {
                if ( count( $body->parameters = self::extractParameters($structure[$index++], []) ) )
                    $body->ifparameters = 1;
                else
                    $body->parameters = (object)[];
            }

            if ( $index < $length ) {
                if ( is_array($disposition = $structure[$index++]) ) {
                    $body->disposition = $disposition[0];
                    $body->ifdisposition = 1;

                    if ( count( $body->dparameters = self::extractParameters($disposition[1], []) ) )
                        $body->ifdparameters = 1;
                    else {
                        $body->dparameters = null;
                    }
                }
            }

            if ( $index < $length ) {
                ++$index;
            }

            while( $index < $length ) {
                ++$index;
            }

            $body->parts = $parts;

        }
        else {

            if ( ! $length ) return (object)[];

            $body->type = self::TYPEOTHER;
            $body->encoding = self::ENCOTHER;

            if ( ($type = array_search(strtoupper($structure[0]), self::$body_types)) !== false )
                $body->type = $type;

            if ( ($encoding = array_search(strtoupper($structure[5]), self::$body_encodings)) !== false )
                $body->encoding = $encoding;

            if ( $body->subtype = strtoupper($structure[1]) )
                $body->ifsubtype = 1;

            $body->ifdescription = 0;
            if ( ! empty($structure[4]) ) {
                $body->ifdescription = 1;
                $body->description = $structure[4];
            }

            $body->ifid = 0;
            if ( ! empty($structure[3]) ) {
                $body->ifid = 1;
                $body->id = $structure[3];
            }

            $body->bytes = intval($structure[6]);

            $index = 7;

            switch ( $body->type ) {

                case self::TYPEMESSAGE:
                    if ( strcmp($body->subtype, "RFC822") ) break;

                    ++$index;

                    $body->parts[] = self::extractBodyStructure( $structure[$index++] );


                case self::TYPETEXT:
                    $body->lines = intval($structure[$index++]);
                    break;

                default:
                    break;

            }

            if ( $index < $length )
                ++$index;

            if ( $index < $length ) {
                if ( is_array($disposition = $structure[$index++]) ) {
                    $body->disposition = $disposition[0];
                    $body->ifdisposition = 1;

                    if ( count( $body->dparameters = self::extractParameters($disposition[1], []) ) )
                        $body->ifdparameters = 1;
                    else {
                        $body->dparameters = null;
                    }
                }
            }

            if ( $index < $length ) {
                ++$index;
            }

            if ( $index < $length ) {
                ++$index;
            }

            while( $index < $length ) {
                ++$index;
            }

            if ( count( $body->parameters = self::extractParameters($structure[2], []) ) )
                $body->ifparameters = 1;
            else
                $body->parameters = (object)[];

        }

        if ( is_null($body->description) ) unset($body->description);
        if ( is_null($body->id) ) unset($body->id);
        if ( is_null($body->disposition) ) unset($body->disposition);
        if ( is_null($body->dparameters) ) unset($body->dparameters);
        if ( is_null($body->parameters) ) unset($body->parameters);

        if ( ! $body->bytes ) unset($body->bytes);
        if ( ! $body->lines ) unset($body->lines);

        return $body;

    }

    protected static function extractParameters($attributes, $parameters)
    {
        if (empty($attributes)) {
            return [];
        }

        $attribute = null;

        foreach ($attributes as $value) {
            if (empty($attribute)) {
                $attribute = [
                    'attribute' => $value,
                    'value' => null,
                ];
            } else {
                $attribute['value'] = $value;
                $parameters[] = (object) $attribute;
                $attribute = null;
            }
        }

        return $parameters;
    }

}
