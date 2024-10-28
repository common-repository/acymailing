<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Libraries\acymController;

class FrontservicesController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        acym_setNoTemplate();

        $this->publicFrontTasks = [
            'sendinblue',
        ];
    }

    public function listing()
    {
        exit;
    }

    public function sendinblue()
    {
        $securityKey = acym_getVar('string', 'seckey');
        if (empty($securityKey) || $securityKey !== $this->config->get('sendinblue_webhooks_seckey')) exit;

        $mailerMethod = $this->config->get('mailer_method');
        if (!in_array($mailerMethod, ['brevo-smtp', 'sendinblue'])) exit;

        $data = acym_getJsonData();
        if (empty($data['email'])) exit;

        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($data['email']);
        if (empty($user)) exit;

        $action = empty($data['event']) ? 'brevo' : $data['event'];

        $mailId = 0;
        if (!empty($data['campaign name']) && strpos($data['campaign name'], 'AcyMailing Mail ') === 0) {
            $mailId = preg_replace('#^AcyMailing Mail (\d+) \(.*$#Uis', '$1', $data['campaign name']);

            if (in_array($action, ['unsubscribe', 'spam'])) {
                acym_query('UPDATE #__acym_user_stat SET unsubscribe = unsubscribe + 1 WHERE user_id = '.intval($user->id).' AND mail_id = '.intval($mailId));
                acym_query('UPDATE #__acym_mail_stat SET unsubscribe_total = unsubscribe_total + 1 WHERE mail_id = '.intval($mailId));
                acym_query('UPDATE #__acym_user_has_list SET status = 0 WHERE user_id = '.intval($user->id));
            }

            if ($action === 'hard_bounce') {
                $userStatClass = new UserStatClass();
                $currentUserStats = $userStatClass->getOneByMailAndUserId($mailId, $user->id);
                if ($currentUserStats->bounce < 1) {
                    acym_query('UPDATE #__acym_mail_stat SET bounce_unique = bounce_unique + 1 WHERE mail_id = '.intval($mailId));
                }
                acym_query('UPDATE #__acym_user_stat SET bounce = bounce + 1 WHERE user_id = '.intval($user->id).' AND mail_id = '.intval($mailId));
            }
        }

        $user->active = 0;
        $userClass->save($user);

        $historyClass = new HistoryClass();
        $historyClass->insert($user->id, $action, ['Brevo'], $mailId);

        exit;
    }
}
