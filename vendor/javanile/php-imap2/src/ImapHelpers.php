<?php


namespace Javanile\Imap2;

class ImapHelpers
{
    public static function idToUid($imap, $messageNums)
    {
        $client = $imap->getClient();

        $messages = $client->fetch($imap->getMailboxName(), $messageNums, false, ['UID']);

        $uid = [];
        foreach ($messages as $message) {
            $uid[] = $message->uid;
        }

        return implode(',', $uid);
    }

    public static function uidToId($imap, $messageUid)
    {
        $client = $imap->getClient();

        $messages = $client->fetch($imap->getMailboxName(), $messageUid, true, ['UID']);

        $id = [];
        foreach ($messages as $message) {
            $id[] = $message->id;
        }

        return implode(',', $id);
    }
}
