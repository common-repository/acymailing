<?php


namespace Javanile\Imap2\Roundcube;

class MessageHeader
{
    public $id;

    public $uid;

    public $subject;

    public $from;

    public $to;

    public $cc;

    public $replyto;

    public $in_reply_to;

    public $date;

    public $messageID;

    public $size;

    public $encoding;

    public $charset;

    public $ctype;

    public $timestamp;

    public $bodystructure;

    public $internaldate;

    public $references;

    public $priority;

    public $mdn_to;

    public $folder;

    public $others = array();

    public $flags = array();

    private $obj_headers = array(
        'date'      => 'date',
        'from'      => 'from',
        'to'        => 'to',
        'subject'   => 'subject',
        'reply-to'  => 'replyto',
        'cc'        => 'cc',
        'bcc'       => 'bcc',
        'mbox'      => 'folder',
        'folder'    => 'folder',
        'content-transfer-encoding' => 'encoding',
        'in-reply-to'               => 'in_reply_to',
        'content-type'              => 'ctype',
        'charset'                   => 'charset',
        'references'                => 'references',
        'return-receipt-to'         => 'mdn_to',
        'disposition-notification-to' => 'mdn_to',
        'x-confirm-reading-to'      => 'mdn_to',
        'message-id'                => 'messageID',
        'x-priority'                => 'priority',
    );

    public function get($name, $decode = true)
    {
        $name = strtolower($name);

        if (isset($this->obj_headers[$name])) {
            $value = $this->{$this->obj_headers[$name]};
        }
        else {
            $value = $this->others[$name];
        }

        if ($decode) {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $val         = Mime::decode_header($val, $this->charset);
                    $value[$key] = Charset::clean($val);
                }
            }
            else {
                $value = Mime::decode_header($value, $this->charset);
                $value = Charset::clean($value);
            }
        }

        return $value;
    }

    public function set($name, $value)
    {
        $name = strtolower($name);

        if (isset($this->obj_headers[$name])) {
            $this->{$this->obj_headers[$name]} = $value;
        }
        else {
            $this->others[$name] = $value;
        }
    }


    public static function from_array($arr)
    {
        $obj = new MessageHeader;
        foreach ($arr as $k => $v)
            $obj->set($k, $v);

        return $obj;
    }
}

