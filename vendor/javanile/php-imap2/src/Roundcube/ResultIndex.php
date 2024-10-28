<?php


namespace Javanile\Imap2\Roundcube;

use Javanile\Imap2\Offset;
use Javanile\Imap2\rcube_imap_generic;

class ResultIndex
{
    public $incomplete = false;

    protected $raw_data;
    protected $mailbox;
    protected $meta   = array();
    protected $params = array();
    protected $order  = 'ASC';

    const SEPARATOR_ELEMENT = ' ';


    public function __construct($mailbox = null, $data = null, $order = null)
    {
        $this->mailbox = $mailbox;
        $this->order   = $order == 'DESC' ? 'DESC' : 'ASC';
        $this->init($data);
    }

    public function init($data = null)
    {
        $this->meta = array();

        $data = explode('*', (string)$data);

        for ($i=0, $len=count($data); $i<$len; $i++) {
            $data_item = &$data[$i];
            if (preg_match('/^ SORT/i', $data_item)) {
                $this->raw_data = '';
                $data_item = substr($data_item, 5);
                break;
            }
            else if (preg_match('/^ (E?SEARCH)/i', $data_item, $m)) {
                $this->raw_data = '';
                $data_item = substr($data_item, strlen($m[0]));

                if (strtoupper($m[1]) == 'ESEARCH') {
                    $data_item = trim($data_item);
                    if (preg_match('/\(MODSEQ ([0-9]+)\)$/i', $data_item, $m)) {
                        $data_item = substr($data_item, 0, -strlen($m[0]));
                        $this->params['MODSEQ'] = $m[1];
                    }
                    if (preg_match('/^\(TAG ["a-z0-9]+\)\s*/i', $data_item, $m)) {
                        $data_item = substr($data_item, strlen($m[0]));
                    }
                    $data_item = preg_replace('/^UID\s*/i', '', $data_item);

                    while (preg_match('/^([a-z]+) ([0-9:,]+)\s*/i', $data_item, $m)) {
                        $param = strtoupper($m[1]);
                        $value = $m[2];

                        $this->params[$param] = $value;
                        $data_item = substr($data_item, strlen($m[0]));

                        if (in_array($param, array('COUNT', 'MIN', 'MAX'))) {
                            $this->meta[strtolower($param)] = (int) $value;
                        }
                    }

                    if (isset($this->params['ALL'])) {
                        $data_item = implode(self::SEPARATOR_ELEMENT,
                            ImapClient::uncompressMessageSet($this->params['ALL']));
                    }
                }

                break;
            }

            unset($data[$i]);
        }

        $data = array_filter($data);

        if (empty($data)) {
            return;
        }

        $data = array_shift($data);
        $data = trim($data);
        $data = preg_replace('/[\r\n]/', '', $data);
        $data = preg_replace('/\s+/', ' ', $data);

        $this->raw_data = $data;
    }

    public function is_error()
    {
        return $this->raw_data === null;
    }

    public function is_empty()
    {
        return empty($this->raw_data);
    }

    public function count()
    {
        if (isset($this->meta['count']) && $this->meta['count'] !== null)
            return $this->meta['count'];

        if (empty($this->raw_data)) {
            $this->meta['count']  = 0;
            $this->meta['length'] = 0;
        }
        else {
            $this->meta['count'] = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT);
        }

        return $this->meta['count'];
    }

    public function count_messages()
    {
        return $this->count();
    }

    public function max()
    {
        if (!isset($this->meta['max'])) {
            $this->meta['max'] = (int) @max($this->get());
        }

        return $this->meta['max'];
    }

    public function min()
    {
        if (!isset($this->meta['min'])) {
            $this->meta['min'] = (int) @min($this->get());
        }

        return $this->meta['min'];
    }

    public function slice($offset, $length)
    {
        $data = $this->get();
        $data = array_slice($data, $offset, $length);

        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }

    public function filter($ids = array())
    {
        $data = $this->get();
        $data = array_intersect($data, $ids);

        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }

    public function revert()
    {
        $this->order = $this->order == 'ASC' ? 'DESC' : 'ASC';

        if (empty($this->raw_data)) {
            return;
        }

        $data = $this->get();
        $data = array_reverse($data);
        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $data);

        $this->meta['pos'] = array();
    }

    public function exists($msgid, $get_index = false)
    {
        if (empty($this->raw_data)) {
            return false;
        }

        $msgid = (int) $msgid;
        $begin = implode('|', array('^', preg_quote(self::SEPARATOR_ELEMENT, '/')));
        $end   = implode('|', array('$', preg_quote(self::SEPARATOR_ELEMENT, '/')));

        if (preg_match("/($begin)$msgid($end)/", $this->raw_data, $m,
            $get_index ? PREG_OFFSET_CAPTURE : null)
        ) {
            if ($get_index) {
                $idx = 0;
                if ($m[0][1]) {
                    $idx = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT, 0, $m[0][1]);
                }
                $this->meta['pos'][$idx] = (int)$m[0][1];

                return $idx;
            }

            return true;
        }

        return false;
    }

    public function get()
    {
        if (empty($this->raw_data)) {
            return array();
        }

        return explode(self::SEPARATOR_ELEMENT, $this->raw_data);
    }

    public function get_compressed()
    {
        if (empty($this->raw_data)) {
            return '';
        }

        return rcube_imap_generic::compressMessageSet($this->get());
    }

    public function get_element($index)
    {
        $count = $this->count();

        if (!$count) {
            return null;
        }

        if ($index === 0 || $index === '0' || $index === 'FIRST') {
            $pos = strpos($this->raw_data, self::SEPARATOR_ELEMENT);
            if ($pos === false)
                $result = (int) $this->raw_data;
            else
                $result = (int) substr($this->raw_data, 0, $pos);

            return $result;
        }

        if ($index === 'LAST' || $index == $count-1) {
            $pos = strrpos($this->raw_data, self::SEPARATOR_ELEMENT);
            if ($pos === false)
                $result = (int) $this->raw_data;
            else
                $result = (int) substr($this->raw_data, $pos);

            return $result;
        }

        if (!empty($this->meta['pos'])) {
            if (isset($this->meta['pos'][$index]))
                $pos = $this->meta['pos'][$index];
            else if (isset($this->meta['pos'][$index-1]))
                $pos = strpos($this->raw_data, self::SEPARATOR_ELEMENT,
                    $this->meta['pos'][$index-1] + 1);
            else if (isset($this->meta['pos'][$index+1]))
                $pos = strrpos($this->raw_data, self::SEPARATOR_ELEMENT,
                    $this->meta['pos'][$index+1] - $this->length() - 1);

            if (isset($pos) && preg_match('/([0-9]+)/', $this->raw_data, $m, null, $pos)) {
                return (int) $m[1];
            }
        }

        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);

        return $data[$index];
    }

    public function get_parameters($param=null)
    {
        $params = $this->params;
        $params['MAILBOX'] = $this->mailbox;
        $params['ORDER']   = $this->order;

        if ($param !== null) {
            return $params[$param];
        }

        return $params;
    }

    protected function length()
    {
        if (!isset($this->meta['length'])) {
            $this->meta['length'] = strlen($this->raw_data);
        }

        return $this->meta['length'];
    }
}
