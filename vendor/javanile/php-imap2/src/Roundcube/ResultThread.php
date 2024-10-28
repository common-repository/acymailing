<?php


namespace Javanile\Imap2\Roundcube;

use Javanile\Imap2\Offset;
use Javanile\Imap2\rcube_imap_generic;
use Javanile\Imap2\rcube_result_index;

class ResultThread
{
    public $incomplete = false;

    protected $raw_data;
    protected $mailbox;
    protected $meta = array();
    protected $order = 'ASC';

    const SEPARATOR_ELEMENT = ' ';
    const SEPARATOR_ITEM    = '~';
    const SEPARATOR_LEVEL   = ':';


    public function __construct($mailbox = null, $data = null)
    {
        $this->mailbox = $mailbox;
        $this->init($data);
    }

    public function init($data = null)
    {
        $this->meta = array();

        $data = explode('*', (string)$data);

        for ($i=0, $len=count($data); $i<$len; $i++) {
            if (preg_match('/^ THREAD/i', $data[$i])) {
                $this->raw_data = '';
                $data[$i] = substr($data[$i], 7);
                break;
            }

            unset($data[$i]);
        }

        if (empty($data)) {
            return;
        }

        $data = array_shift($data);
        $data = trim($data);
        $data = preg_replace('/[\r\n]/', '', $data);
        $data = preg_replace('/\s+/', ' ', $data);

        $this->raw_data = $this->parse_thread($data);
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
            $this->meta['count'] = 0;
        }
        else {
            $this->meta['count'] = 1 + substr_count($this->raw_data, self::SEPARATOR_ELEMENT);
        }

        if (!$this->meta['count'])
            $this->meta['messages'] = 0;

        return $this->meta['count'];
    }

    public function count_messages()
    {
        if ($this->meta['messages'] !== null)
            return $this->meta['messages'];

        if (empty($this->raw_data)) {
            $this->meta['messages'] = 0;
        }
        else {
            $this->meta['messages'] = 1
                + substr_count($this->raw_data, self::SEPARATOR_ELEMENT)
                + substr_count($this->raw_data, self::SEPARATOR_ITEM);
        }

        if ($this->meta['messages'] == 0 || $this->meta['messages'] == 1)
            $this->meta['count'] = $this->meta['messages'];

        return $this->meta['messages'];
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
        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);
        $data = array_slice($data, $offset, $length);

        $this->meta          = array();
        $this->meta['count'] = count($data);
        $this->raw_data      = implode(self::SEPARATOR_ELEMENT, $data);
    }

    public function filter($roots)
    {
        $datalen = strlen($this->raw_data);
        $roots   = array_flip($roots);
        $result  = '';
        $start   = 0;

        $this->meta          = array();
        $this->meta['count'] = 0;

        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))
        ) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;

            if ($npos = strpos($elem, self::SEPARATOR_ITEM)) {
                $root = (int) substr($elem, 0, $npos);
            }
            else {
                $root = $elem;
            }

            if (isset($roots[$root])) {
                $this->meta['count']++;
                $result .= self::SEPARATOR_ELEMENT . $elem;
            }
        }

        $this->raw_data = ltrim($result, self::SEPARATOR_ELEMENT);
    }

    public function revert()
    {
        $this->order = $this->order == 'ASC' ? 'DESC' : 'ASC';

        if (empty($this->raw_data)) {
            return;
        }

        $data = explode(self::SEPARATOR_ELEMENT, $this->raw_data);
        $data = array_reverse($data);
        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $data);

        $this->meta['pos'] = array();
    }

    public function exists($msgid, $get_index = false)
    {
        $msgid = (int) $msgid;
        $begin = implode('|', array(
            '^',
            preg_quote(self::SEPARATOR_ELEMENT, '/'),
            preg_quote(self::SEPARATOR_LEVEL, '/'),
        ));
        $end = implode('|', array(
            '$',
            preg_quote(self::SEPARATOR_ELEMENT, '/'),
            preg_quote(self::SEPARATOR_ITEM, '/'),
        ));

        if (preg_match("/($begin)$msgid($end)/", $this->raw_data, $m,
            $get_index ? PREG_OFFSET_CAPTURE : null)
        ) {
            if ($get_index) {
                $idx = 0;
                if ($m[0][1]) {
                    $idx = substr_count($this->raw_data, self::SEPARATOR_ELEMENT, 0, $m[0][1]+1)
                        + substr_count($this->raw_data, self::SEPARATOR_ITEM, 0, $m[0][1]+1);
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

        $regexp = '/(' . preg_quote(self::SEPARATOR_ELEMENT, '/')
            . '|' . preg_quote(self::SEPARATOR_ITEM, '/') . '[0-9]+' . preg_quote(self::SEPARATOR_LEVEL, '/')
            .')/';

        return preg_split($regexp, $this->raw_data);
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
            preg_match('/^([0-9]+)/', $this->raw_data, $m);
            $result = (int) $m[1];
            return $result;
        }

        if ($index === 'LAST' || $index == $count-1) {
            preg_match('/([0-9]+)$/', $this->raw_data, $m);
            $result = (int) $m[1];
            return $result;
        }

        if (!empty($this->meta['pos'])) {
            $element = preg_quote(self::SEPARATOR_ELEMENT, '/');
            $item    = preg_quote(self::SEPARATOR_ITEM, '/') . '[0-9]+' . preg_quote(self::SEPARATOR_LEVEL, '/') .'?';
            $regexp  = '(' . $element . '|' . $item . ')';

            if (isset($this->meta['pos'][$index])) {
                if (preg_match('/([0-9]+)/', $this->raw_data, $m, null, $this->meta['pos'][$index]))
                    $result = $m[1];
            }
            else if (isset($this->meta['pos'][$index-1])) {
                $data = substr($this->raw_data, $this->meta['pos'][$index-1]+1, 50);
                $data = preg_replace('/^[0-9]+/', '', $data); // remove UID at $index position
                $data = preg_replace("/^$regexp/", '', $data); // remove separator
                if (preg_match('/^([0-9]+)/', $data, $m))
                    $result = $m[1];
            }
            else if (isset($this->meta['pos'][$index+1])) {
                $pos  = max(0, $this->meta['pos'][$index+1] - 50);
                $len  = min(50, $this->meta['pos'][$index+1]);
                $data = substr($this->raw_data, $pos, $len);
                $data = preg_replace("/$regexp\$/", '', $data); // remove separator

                if (preg_match('/([0-9]+)$/', $data, $m))
                    $result = $m[1];
            }

            if (isset($result)) {
                return (int) $result;
            }
        }

        $data = $this->get();

        return $data[$index];
    }

    public function get_parameters($param=null)
    {
        $params = array();
        $params['MAILBOX'] = $this->mailbox;
        $params['ORDER']   = $this->order;

        if ($param !== null) {
            return $params[$param];
        }

        return $params;
    }

    public function sort($index)
    {
        $this->sort_order = $index->get_parameters('ORDER');

        if (empty($this->raw_data)) {
            return;
        }

        if ($index->count() != $this->count_messages()) {
            $index->filter($this->get());
        }

        $result  = array_fill_keys($index->get(), null);
        $datalen = strlen($this->raw_data);
        $start   = 0;


        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))
        ) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;

            $items = explode(self::SEPARATOR_ITEM, $elem);
            $root  = (int) array_shift($items);

            if ($root) {
                $result[$root] = $root;
                foreach ($items as $item) {
                    list($lv, $id) = explode(self::SEPARATOR_LEVEL, $item);
                    $result[$id] = $root;
                }
            }
        }

        $result = array_filter($result); // make sure there are no nulls
        $result = array_unique($result);

        $result = array_fill_keys($result, null);
        $start = 0;

        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))
        ) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $start = $pos + 1;

            $npos = strpos($elem, self::SEPARATOR_ITEM);
            $root = (int) ($npos ? substr($elem, 0, $npos) : $elem);

            $result[$root] = $elem;
        }

        $this->raw_data = implode(self::SEPARATOR_ELEMENT, $result);
    }

    public function get_tree()
    {
        $datalen = strlen($this->raw_data);
        $result  = array();
        $start   = 0;

        while (($pos = @strpos($this->raw_data, self::SEPARATOR_ELEMENT, $start))
            || ($start < $datalen && ($pos = $datalen))
        ) {
            $len   = $pos - $start;
            $elem  = substr($this->raw_data, $start, $len);
            $items = explode(self::SEPARATOR_ITEM, $elem);
            $result[array_shift($items)] = $this->build_thread($items);
            $start = $pos + 1;
        }

        return $result;
    }

    public function get_thread_data()
    {
        $data     = $this->get_tree();
        $depth    = array();
        $children = array();

        $this->build_thread_data($data, $depth, $children);

        return array($depth, $children);
    }

    protected function build_thread_data($data, &$depth, &$children, $level = 0)
    {
        foreach ((array)$data as $key => $val) {
            $empty          = empty($val) || !is_array($val);
            $children[$key] = !$empty;
            $depth[$key]    = $level;
            if (!$empty) {
                $this->build_thread_data($val, $depth, $children, $level + 1);
            }
        }
    }

    protected function build_thread($items, $level = 1, &$pos = 0)
    {
        $result = array();

        for ($len=count($items); $pos < $len; $pos++) {
            list($lv, $id) = explode(self::SEPARATOR_LEVEL, $items[$pos]);
            if ($level == $lv) {
                $pos++;
                $result[$id] = $this->build_thread($items, $level+1, $pos);
            }
            else {
                $pos--;
                break;
            }
        }

        return $result;
    }

    protected function parse_thread($str, $begin = 0, $end = 0, $depth = 0)
    {
        $node = '';
        if (!$end) {
            $end = strlen($str);
        }


        if ($str[$begin] != '(') {
            $stop      = $begin + strcspn($str, '()', $begin, $end - $begin);
            $messages  = explode(' ', trim(substr($str, $begin, $stop - $begin)));

            if (empty($messages)) {
                return $node;
            }

            foreach ($messages as $msg) {
                if ($msg) {
                    $node .= ($depth ? self::SEPARATOR_ITEM.$depth.self::SEPARATOR_LEVEL : '').$msg;
                    $this->meta['messages']++;
                    $depth++;
                }
            }

            if ($stop < $end) {
                $node .= $this->parse_thread($str, $stop, $end, $depth);
            }
        }
        else {
            $off = $begin;
            while ($off < $end) {
                $start = $off;
                $off++;
                $n = 1;
                while ($n > 0) {
                    $p = strpos($str, ')', $off);
                    if ($p === false) {
                        return $node;
                    }
                    $p1 = strpos($str, '(', $off);
                    if ($p1 !== false && $p1 < $p) {
                        $off = $p1 + 1;
                        $n++;
                    }
                    else {
                        $off = $p + 1;
                        $n--;
                    }
                }

                $thread = $this->parse_thread($str, $start + 1, $off - 1, $depth);
                if ($thread) {
                    if (!$depth) {
                        if ($node) {
                            $node .= self::SEPARATOR_ELEMENT;
                        }
                    }
                    $node .= $thread;
                }
            }
        }

        return $node;
    }
}
