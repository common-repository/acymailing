<?php


namespace Javanile\Imap2\Roundcube;
class MessageHeaderSorter
{
    private $uids = array();


    function set_index($index)
    {
        $index = array_flip($index);

        $this->uids = $index;
    }

    function sort_headers(&$headers)
    {
        uksort($headers, array($this, "compare_uids"));
    }

    function compare_uids($a, $b)
    {
        $posa = isset($this->uids[$a]) ? intval($this->uids[$a]) : -1;
        $posb = isset($this->uids[$b]) ? intval($this->uids[$b]) : -1;

        return $posa - $posb;
    }
}
