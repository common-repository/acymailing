<?php

namespace AcyMailing\Helpers;


use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailArchiveClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\OverrideClass;
use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UserClass;
use AcyMailerPhp\Exception;
use AcyMailerPhp\OAuth;
use AcyMailerPhp\AcyMailerPhp;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

class MailerHelper extends AcyMailerPhp
{
    public $XMailer = ' ';

    public $SMTPAutoTLS = false;

    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $ReplyTo = [];
    public $attachment = [];
    public $CustomHeader = [];
    public $Preheader;
    public $replyname;
    public $replyemail;
    public $body;
    public $altbody;
    public $subject;
    public $from;
    public $fromName;
    public $replyto;

    private $encodingHelper;
    private $editorHelper;
    private $userClass;
    private $mailClass;
    private $mailArchiveClass;
    public $config;

    public $report = true;
    public $alreadyCheckedAddresses = false;
    public $errorNumber = 0;
    public $errorNewTry = [1, 6];
    public $failedCounting = true;
    public $autoAddUser = false;
    public $userCreationTriggers = true;
    public $reportMessage = '';

    public $trackEmail = false;

    public $externalMailer;

    public $stylesheet = '';
    public $settings;

    public $parameters = [];

    public $userLanguage = '';
    public $receiverEmail;

    public $overrideEmailToSend = '';
    public $isTest = false;
    public $isSpamTest = false;
    public $isForward = false;

    public $clientId = '';
    public $clientSecret = '';
    public $refreshToken = '';
    public $oauthToken = '';
    public $expiredIn = '';
    public $mailId = null;
    public $creator_id;
    public $type;
    public $links_language;
    public $id = null;
    public $mail = null;
    private $nameIdMap = [];
    public $defaultMail = [];

    public $listsIds = [];
    private $currentMethodSetting = [];

    public $isAbTest = false;
    public $isTransactional = false;
    public $isOneTimeMail = false;
    private $currentSendingMethod = '';
    public $isSendingMethodByListActive = false;

    public function __construct()
    {
        parent::__construct();

        $this->encodingHelper = new EncodingHelper();
        $this->editorHelper = new EditorHelper();
        $this->userClass = new UserClass();
        $this->mailClass = new MailClass();
        $this->mailArchiveClass = new MailArchiveClass();
        $this->config = acym_config();
        $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
        $this->Sender = $this->cleanText($this->config->get('bounce_email'));
        if (empty($this->Sender)) {
            $this->Sender = '';
        }
        $maxLineLength = $this->config->get('mailer_wordwrap', 0);
        if (!empty($maxLineLength) && self::MAX_LINE_LENGTH > $maxLineLength) {
            $this->WordWrap = $maxLineLength;
        }

        $this->setSendingMethodSetting();

        if ($this->config->get('dkim', 0) && $this->Mailer != 'elasticemail') {
            $this->DKIM_domain = $this->config->get('dkim_domain');
            $this->DKIM_selector = $this->config->get('dkim_selector', 'acy');
            if (empty($this->DKIM_selector)) $this->DKIM_selector = 'acy';
            $this->DKIM_passphrase = $this->config->get('dkim_passphrase');
            $this->DKIM_identity = $this->config->get('dkim_identity');
            $this->DKIM_private = trim($this->config->get('dkim_private'));
            $this->DKIM_private_string = trim($this->config->get('dkim_private'));
        }

        $this->CharSet = strtolower($this->config->get('charset'));
        if (empty($this->CharSet)) {
            $this->CharSet = 'utf-8';
        }

        $this->clearAll();

        $this->Encoding = $this->config->get('encoding_format');
        if (empty($this->Encoding)) {
            $this->Encoding = '8bit';
        }

        @ini_set('pcre.backtrack_limit', 1000000);

        $this->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

        $this->addParamInfo();
    }

    private function getSendingMethodSettings($settingName, $default = ''): string
    {
        if (empty($this->currentMethodSetting)) {
            return $this->config->get($settingName, $default);
        }

        return $this->currentMethodSetting[$settingName] ?? $default;
    }

    private function getSendingMethod(): string
    {
        $this->currentMethodSetting = [];

        $sendingMethods = $this->config->get('sending_method_list', '{}');
        $sendingMethods = json_decode($sendingMethods, true);
        $sendingMethodsByList = $this->config->get('sending_method_list_by_list', '{}');
        $sendingMethodsByList = json_decode($sendingMethodsByList, true);

        if (empty($this->listsIds) || empty($sendingMethods) || empty($sendingMethodsByList) || empty($sendingMethodsByList[$this->listsIds[0]])) {
            return $this->config->get('mailer_method', 'phpmail');
        }

        $sendingMethodIds = [];

        foreach ($this->listsIds as $listId) {
            if (!empty($sendingMethodsByList[$listId])) {
                $sendingMethodIds[] = $sendingMethodsByList[$listId];
            }
        }

        if (count($sendingMethodIds) !== count($this->listsIds) || count(array_unique($sendingMethodIds)) !== 1) {
            return $this->config->get('mailer_method', 'phpmail');
        }

        $sendingMethodId = $sendingMethodIds[0];

        if (empty($sendingMethods[$sendingMethodId])) {
            return $this->config->get('mailer_method', 'phpmail');
        }

        $this->currentMethodSetting = $sendingMethods[$sendingMethodId];

        return $sendingMethods[$sendingMethodId]['mailer_method'];
    }

    public function setSendingMethodSetting(): void
    {
        $externalSendingMethod = [];
        acym_trigger('onAcymGetSendingMethods', [&$externalSendingMethod, true]);
        $externalSendingMethod = array_keys($externalSendingMethod['sendingMethods']);

        $mailerMethodConfig = $this->getSendingMethod();
        $this->currentSendingMethod = $mailerMethodConfig;

        if ($mailerMethodConfig === 'smtp') {
            $this->isSMTP();
            $this->Host = trim($this->getSendingMethodSettings('smtp_host'));
            $port = $this->getSendingMethodSettings('smtp_port');
            if (empty($port) && $this->getSendingMethodSettings('smtp_secured') === 'ssl') {
                $port = 465;
            }
            if (!empty($port)) {
                $this->Host .= ':'.$port;
            }
            $this->SMTPAuth = (bool)$this->getSendingMethodSettings('smtp_auth', true);
            $this->Username = trim($this->getSendingMethodSettings('smtp_username'));
            $connectionType = $this->getSendingMethodSettings('smtp_type');
            $hostName = explode(':', $this->Host)[0];

            if (OAuth::hostRequireOauth($hostName, $connectionType)) {
                $this->AuthType = 'XOAUTH2';

                $this->clientSecret = trim($this->getSendingMethodSettings('smtp_secret'));
                $this->clientId = trim($this->getSendingMethodSettings('smtp_clientId'));
                $this->refreshToken = trim($this->getSendingMethodSettings('smtp_refresh_token'));
                $this->oauthToken = trim($this->getSendingMethodSettings('smtp_token'));
                $this->expiredIn = trim($this->getSendingMethodSettings('smtp_token_expireIn'));

                $oauth = new OAuth(
                    [
                        'userName' => $this->Username,
                        'clientSecret' => $this->clientSecret,
                        'oauthToken' => $this->oauthToken,
                        'clientId' => $this->clientId,
                        'refreshToken' => $this->refreshToken,
                        'expiredIn' => $this->expiredIn,
                        'host' => $hostName,
                    ]
                );
                $this->setOAuth($oauth);
            } else {
                $authMethod = $this->getSendingMethodSettings('smtp_method');
                if (in_array($authMethod, ['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'])) {
                    $this->AuthType = $authMethod;
                }
                if ($this->SMTPAuth) {
                    $this->Password = trim($this->getSendingMethodSettings('smtp_password'));
                }
            }

            $this->SMTPSecure = trim((string)$this->getSendingMethodSettings('smtp_secured'));

            if (empty($this->Sender)) {
                $this->Sender = strpos($this->Username, '@') ? $this->Username : $this->getSendingMethodSettings('from_email');
            }
        } elseif ($mailerMethodConfig === 'sendmail') {
            $this->isSendmail();
            $this->Sendmail = trim($this->getSendingMethodSettings('sendmail_path'));
            if (empty($this->Sendmail)) {
                $this->Sendmail = '/usr/sbin/sendmail';
            }
        } elseif ($mailerMethodConfig === 'qmail') {
            $this->isQmail();
        } elseif ($mailerMethodConfig === 'amazon') {
            $this->isSMTP();
            $amazonCredentials = [];
            acym_trigger('onAcymGetCredentialsSendingMethod', [&$amazonCredentials, 'amazon', $this->currentMethodSetting], 'plgAcymAmazon');
            $this->Host = trim($amazonCredentials['amazon_host']).':587';
            $this->Username = trim($amazonCredentials['amazon_username']);
            $this->Password = trim($amazonCredentials['amazon_password']);
            $this->SMTPAuth = true;
            $this->SMTPSecure = 'tls';
        } elseif ($mailerMethodConfig === 'brevo-smtp') {
            $this->isSMTP();
            $brevoSmtpCredentials = [];
            acym_trigger('onAcymGetCredentialsSendingMethod', [&$brevoSmtpCredentials, 'brevo-smtp', $this->currentMethodSetting], 'plgAcymBrevo');
            $this->Host = trim($brevoSmtpCredentials['brevo-smtp_host']).':587';
            $this->Username = trim($brevoSmtpCredentials['brevo-smtp_username']);
            $this->Password = trim($brevoSmtpCredentials['brevo-smtp_password']);
            $this->SMTPAuth = true;
            $this->SMTPSecure = 'tls';
        } elseif (in_array($mailerMethodConfig, $externalSendingMethod)) {
            $this->isExternal($mailerMethodConfig);
        } else {
            $this->isMail();
        }
    }

    public function isExternal($method)
    {
        $this->Mailer = 'external';
        $this->externalMailer = $method;
    }

    protected function externalSend($MIMEHeader, $MIMEBody)
    {
        $fromName = empty($this->FromName) ? $this->config->get('from_name') : $this->FromName;
        $reply_to = array_shift($this->ReplyTo);

        $attachments = [];
        if (!empty($this->attachment) && $this->config->get('embed_files')) {
            foreach ($this->attachment as $i => $oneAttach) {
                $encodedContent = $this->encodeFile($oneAttach[0], $oneAttach[3]);
                $this->attachment[$i]['contentEncoded'] = $encodedContent;
            }
            $attachments = $this->attachment;
        }

        $response = [];
        $data = [
            &$response,
            $this,
            ['email' => $this->to[0][0], 'name' => $this->to[0][1]],
            ['email' => $this->From, 'name' => $fromName],
            ['email' => $reply_to[0], 'name' => $reply_to[1]],
            !empty($this->bcc) ? $this->bcc : [],
            $attachments,
            $this->currentMethodSetting,
        ];
        acym_trigger('onAcymSendEmail', $data);

        if (!empty($response['error'])) {
            $this->setError($response['message']);

            return false;
        }

        return true;
    }

    public function send()
    {
        if (!file_exists(ACYM_LIBRARIES.'mailer'.DS.'mailer.php')) {
            $this->reportMessage = acym_translationSprintf('ACYM_X_FILE_MISSING', 'mailer', ACYM_LIBRARIES.'mailer'.DS);
            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }

            return false;
        }

        if (empty($this->Subject) || empty($this->Body)) {
            if ($this->isTest && empty($this->Subject)) {
                $this->Subject = acym_translation('ACYM_EMAIL_SUBJECT');
            } else {
                $this->reportMessage = acym_translation('ACYM_SEND_EMPTY');
                $this->errorNumber = 8;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }
        }

        if (empty($this->ReplyTo) && empty($this->ReplyToQueue)) {
            if (!empty($this->replyemail)) {
                $replyToEmail = $this->replyemail;
            } elseif ($this->config->get('from_as_replyto', 1) == 1) {
                $replyToEmail = $this->getSendSettings('from_email');
            } else {
                $replyToEmail = $this->getSendSettings('replyto_email');
            }

            if (!empty($this->replyname)) {
                $replyToName = $this->replyname;
            } elseif ($this->config->get('from_as_replyto', 1) == 1) {
                $replyToName = $this->getSendSettings('from_name');
            } else {
                $replyToName = $this->getSendSettings('replyto_name');
            }

            $this->_addReplyTo($replyToEmail, $replyToName);
        }

        $shouldEmbed = $this->config->get('embed_images', 0);
        if (intval($shouldEmbed) === 1 && $this->Mailer !== 'elasticemail') {
            $this->embedImages();
        }

        if (!$this->alreadyCheckedAddresses) {
            $this->alreadyCheckedAddresses = true;

            $replyToTmp = '';
            if (!empty($this->ReplyTo)) {
                $replyToTmp = reset($this->ReplyTo);
                $replyToTmp = $replyToTmp[0];
            } elseif (!empty($this->ReplyToQueue)) {
                $replyToTmp = reset($this->ReplyToQueue);
                $replyToTmp = $replyToTmp[1];
            }

            if (empty($replyToTmp) || !acym_isValidEmail($replyToTmp)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_REPLYTO_EMAIL').' : '.(empty($this->ReplyTo) ? '' : $replyToTmp).' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }

            if (empty($this->From) || !acym_isValidEmail($this->From)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_FROM_EMAIL').' : '.$this->From.' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }

            if (!empty($this->Sender) && !acym_isValidEmail($this->Sender)) {
                $this->reportMessage = acym_translation('ACYM_VALID_EMAIL').' ( '.acym_translation('ACYM_BOUNCE_EMAIL').' : '.$this->Sender.' ) ';
                $this->errorNumber = 9;
                if ($this->report) {
                    acym_enqueueMessage($this->reportMessage, 'error');
                }

                return false;
            }
        }

        if ($this->config->get('save_body') === '1') {
            @file_put_contents(ACYM_ROOT.'acydebug_mail.html', $this->Body);
        }

        $this->Body = htmlentities($this->Body);
        $this->Body = htmlspecialchars_decode($this->Body);
        $this->Body = str_replace(['&amp;', '&sigmaf;'], ['&', 'ς'], $this->Body);

        if ($this->CharSet !== 'utf-8') {
            $this->Body = $this->encodingHelper->change($this->Body, 'UTF-8', $this->CharSet);
            $this->Subject = $this->encodingHelper->change($this->Subject, 'UTF-8', $this->CharSet);
        }

        $this->Subject = str_replace(
            ['’', '“', '”', '–'],
            ["'", '"', '"', '-'],
            $this->Subject
        );

        $this->Body = str_replace(" ", ' ', $this->Body);

        $externalSending = false;

        $this->isOneTimeMail = $this->isTransactional = $this->isForward || $this->isTest || $this->isSpamTest;
        if (!empty($this->mailId) && !empty($this->defaultMail[$this->mailId])) {
            if ($this->mailClass->isTransactionalMail($this->defaultMail[$this->mailId])) {
                $this->isTransactional = true;
            }

            if ($this->mailClass->isOneTimeMail($this->defaultMail[$this->mailId])) {
                $this->isOneTimeMail = true;
            }
        }

        acym_trigger('onAcymProcessQueueExternalSendingCampaign', [&$externalSending, $this->isTransactional]);

        $warnings = '';

        if (ACYM_PRODUCTION) {
            if ($externalSending) {
                $result = false;
                acym_trigger('onAcymRegisterReceiverContentAndList', [&$result, $this->Subject, $this->Body, $this->receiverEmail, $this->mailId, &$warnings]);
            } else {
                acym_trigger('onAcymBeforeEmailSend', [&$this]);
                ob_start();
                $result = parent::send();
                $warnings = ob_get_clean();
            }
        }


        if (!empty($warnings) && strpos($warnings, 'bloque')) {
            $result = false;
        }

        $this->mailHeader = '';

        $receivers = [];
        foreach ($this->to as $oneReceiver) {
            $receivers[] = $oneReceiver[0];
        }

        if (!$result) {
            $this->reportMessage = acym_translationSprintf('ACYM_SEND_ERROR', '<b>'.$this->Subject.'</b>', '<b>'.implode(' , ', $receivers).'</b>');
            if (!empty($this->ErrorInfo)) {
                $this->reportMessage .= " \n\n ".$this->ErrorInfo;
            }
            if (!empty($warnings)) {
                $this->reportMessage .= " \n\n ".$warnings;
            }
            $this->errorNumber = 1;
            if ($this->report) {
                $this->reportMessage = str_replace(
                    'Could not instantiate mail function',
                    '<a target="_blank" href="'.ACYM_DOCUMENTATION.'faq/could-not-instantiate-mail-function">'.acym_translation('ACYM_COUND_NOT_INSTANCIATE_MAIL_FUCNTION').'</a>',
                    $this->reportMessage
                );
                acym_enqueueMessage(nl2br($this->reportMessage), 'error');
            }
        } else {
            if ($this->isSendingMethodByListActive) {
                $this->reportMessage = acym_translationSprintf(
                    'ACYM_SEND_SUCCESS_WITH_SENDING_METHOD',
                    '<b>'.$this->Subject.'</b>',
                    '<b>'.implode(' , ', $receivers).'</b>',
                    '<b>'.$this->currentSendingMethod.'</b>'
                );
            } else {
                $this->reportMessage = acym_translationSprintf('ACYM_SEND_SUCCESS', '<b>'.$this->Subject.'</b>', '<b>'.implode(' , ', $receivers).'</b>');
            }
            if (!empty($warnings)) {
                $this->reportMessage .= " \n\n ".$warnings;
            }
            if ($this->report) {
                if (acym_isAdmin()) {
                    acym_enqueueMessage(preg_replace('#(<br( ?/)?>){2}#', '<br />', nl2br($this->reportMessage)), 'info');
                }
            }
        }

        return $result;
    }

    public function clearAll()
    {
        $this->Subject = '';
        $this->Body = '';
        $this->AltBody = '';
        $this->ClearAllRecipients();
        $this->ClearAttachments();
        $this->ClearCustomHeaders();
        $this->ClearReplyTos();
        $this->errorNumber = 0;
        $this->MessageID = '';
        $this->ErrorInfo = '';
        $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
    }

    public function load(int $mailId, $user = null)
    {
        if (isset($this->defaultMail[$mailId])) {
            return $this->defaultMail[$mailId];
        }

        if (!empty($this->overrideEmailToSend)) {
            $this->defaultMail[$mailId] = $this->overrideEmailToSend;
        } else {
            $this->defaultMail[$mailId] = $this->mailClass->getOneById($mailId, true);
        }

        global $acymLanguages;
        if (!$this->isAbTest && empty($this->overrideEmailToSend) && acym_isMultilingual()) {
            $defaultLanguage = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
            $mails = $this->mailClass->getMultilingualMails($mailId);

            $this->userLanguage = $user != null && !empty($user->language) ? $user->language : $defaultLanguage;

            if (!empty($mails)) {
                $languages = array_keys($mails);
                if (count($languages) == 1) {
                    $key = $languages[0];
                } elseif (empty($mails[$this->userLanguage])) {
                    $key = $defaultLanguage;
                } else {
                    $key = $this->userLanguage;
                }

                if (isset($mails[$key])) {
                    $this->defaultMail[$mailId] = $mails[$key];
                }
            } else {
                unset($this->defaultMail[$mailId]);

                return false;
            }

            $acymLanguages['userLanguage'] = $this->userLanguage;
            $this->setFrom($this->getSendSettings('from_email'), $this->getSendSettings('from_name'));
        }

        if (empty($this->defaultMail[$mailId]->id)) {
            unset($this->defaultMail[$mailId]);

            return false;
        }

        if (!empty($this->defaultMail[$mailId]->attachments)) {
            $this->defaultMail[$mailId]->attach = [];

            $attachments = json_decode($this->defaultMail[$mailId]->attachments);
            foreach ($attachments as $oneAttach) {
                $attach = new \stdClass();
                $attach->name = basename($oneAttach->filename);
                $attach->filename = str_replace(['/', '\\'], DS, ACYM_ROOT).$oneAttach->filename;
                $attach->url = ACYM_LIVE.$oneAttach->filename;
                $this->defaultMail[$mailId]->attach[] = $attach;
            }
        }


        $this->mailId = $mailId;
        $this->id = $this->mailId;
        $this->mail = clone $this->defaultMail[$mailId];
        acym_trigger('replaceContent', [&$this->defaultMail[$mailId], true]);

        $this->prepareEmailContent($mailId);
        if (!$this->isTest) {
            $this->storeArchiveVersion($mailId);
        }

        return $this->defaultMail[$mailId];
    }

    private function storeArchiveVersion($mailId)
    {
        $mailId = intval($mailId);
        if (empty($mailId) || $this->defaultMail[$mailId]->type !== MailClass::TYPE_STANDARD) {
            return;
        }

        $mailArchive = new \stdClass();

        $alreadyExisting = $this->mailArchiveClass->getOneByMailId($mailId);
        if (!empty($alreadyExisting)) {
            $mailArchive->id = $alreadyExisting->id;
        }

        $mailArchive->mail_id = $mailId;
        $mailArchive->date = time();
        $mailArchive->body = $this->defaultMail[$mailId]->body;
        $mailArchive->subject = $this->defaultMail[$mailId]->subject;
        $mailArchive->settings = $this->defaultMail[$mailId]->settings;
        $mailArchive->stylesheet = $this->defaultMail[$mailId]->stylesheet;
        $mailArchive->attachments = $this->defaultMail[$mailId]->attachments;
        $this->mailArchiveClass->save($mailArchive);
    }

    private function canTrack($mailId, $user): bool
    {
        if (empty($mailId) || empty($user) || !isset($user->tracking) || $user->tracking != 1) return false;

        $mail = $this->mailClass->getOneById($mailId);
        if (!empty($mail) && $mail->tracking != 1) return false;

        $lists = $this->mailClass->getAllListsByMailIdAndUserId($mailId, $user->id);

        foreach ($lists as $list) {
            if ($list->tracking != 1) return false;
        }

        return true;
    }

    private function loadUser($user)
    {
        if (is_string($user) && strpos($user, '@')) {
            $receiver = $this->userClass->getOneByEmail($user);

            if (empty($receiver) && $this->autoAddUser && acym_isValidEmail($user)) {
                $newUser = new \stdClass();
                $newUser->email = $user;
                $this->userClass->checkVisitor = false;
                $this->userClass->sendConf = false;
                acym_setVar('acy_source', 'When sending a test');
                $this->userClass->triggers = $this->userCreationTriggers;
                $userId = $this->userClass->save($newUser);
                $receiver = $this->userClass->getOneById($userId);
            }
        } elseif (is_object($user)) {
            $receiver = $user;
        } else {
            $receiver = $this->userClass->getOneById($user);
        }

        $this->userLanguage = empty($receiver->language) ? acym_getLanguageTag() : $receiver->language;

        $this->receiverEmail = $receiver->email;

        return $receiver;
    }

    public function sendOne($mailId, $user, bool $isTest = false, string $testNote = '', bool $clear = true)
    {
        if ($clear) {
            $this->clearAll();
        }

        $receiver = $this->loadUser($user);
        $this->isTest = $isTest;

        if (empty($receiver->email)) {
            $this->reportMessage = acym_translationSprintf('ACYM_SEND_ERROR_USER', '<b><i>'.acym_escape($user).'</i></b>');
            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }
            $this->errorNumber = 4;

            return false;
        }

        if (!is_numeric($mailId)) {
            if (empty($this->nameIdMap[$mailId])) {
                $mail = $this->mailClass->getOneByName($mailId);
                if (empty($mail->id)) {
                    $this->reportMessage = 'Can not load the e-mail: '.acym_escape($mailId);
                    $this->errorNumber = 2;

                    if ($this->report) {
                        acym_enqueueMessage($this->reportMessage, 'error');
                    }

                    return false;
                }

                $this->nameIdMap[$mailId] = $mail->id;
            }

            $mailId = $this->nameIdMap[$mailId];
        }

        $mailId = intval($mailId);

        if (!$this->load($mailId, $receiver)) {
            $this->reportMessage = 'Can not load the e-mail with ID n°'.$mailId;
            $this->errorNumber = 2;

            if ($this->report) {
                acym_enqueueMessage($this->reportMessage, 'error');
            }

            return false;
        }

        if (!empty($this->defaultMail[$mailId]->bounce_email)) {
            $this->Sender = $this->cleanText($this->defaultMail[$mailId]->bounce_email);
        } else {
            $this->Sender = $this->cleanText($this->config->get('bounce_email', ''));
        }

        $subject = base64_encode(rand(0, 9999999)).'AC'.$receiver->id.'Y'.$this->defaultMail[$mailId]->id.'BA'.base64_encode(time().rand(0, 99999));
        $this->MessageID = '<'.preg_replace('|[^a-z0-9+_]|i', '', $subject).'@'.$this->serverHostname().'>';
        $this->addCustomHeader('Feedback-ID', $this->defaultMail[$mailId]->id.':'.$receiver->id.':'.$this->defaultMail[$mailId]->type.':'.base64_encode(ACYM_ROOT));

        $addedName = '';
        if ($this->config->get('add_names', true)) {
            $addedName = $this->cleanText($receiver->name);
            if ($addedName == $this->cleanText($receiver->email)) {
                $addedName = '';
            }
        }
        $this->addAddress($this->cleanText($receiver->email), $addedName);

        $this->isHTML();

        $this->Subject = $this->defaultMail[$mailId]->subject;
        $this->Body = $this->defaultMail[$mailId]->body;
        if ($this->isTest && $testNote != '') {
            $this->Body = '<div style="text-align: center; padding: 25px; font-family: Poppins; font-size: 20px">'.$testNote.'</div>'.$this->Body;
        }
        $this->Preheader = $this->defaultMail[$mailId]->preheader;

        if (!empty($this->defaultMail[$mailId]->stylesheet)) {
            $this->stylesheet = $this->defaultMail[$mailId]->stylesheet;
        }
        $this->settings = empty($this->defaultMail[$mailId]->settings) ? [] : json_decode($this->defaultMail[$mailId]->settings, true);

        if (!empty($this->defaultMail[$mailId]->headers)) {
            $this->mailHeader = $this->defaultMail[$mailId]->headers;
        }

        $this->setFrom($this->getSendSettings('from_email', $mailId), $this->getSendSettings('from_name', $mailId));
        $this->_addReplyTo($this->defaultMail[$mailId]->reply_to_email, $this->defaultMail[$mailId]->reply_to_name);

        if (!empty($this->defaultMail[$mailId]->bcc)) {
            $bcc = trim(str_replace([',', ' '], ';', $this->defaultMail[$mailId]->bcc));
            $allBcc = explode(';', $bcc);
            foreach ($allBcc as $oneBcc) {
                if (empty($oneBcc)) continue;
                $this->AddBCC($oneBcc);
            }
        }

        if (!empty($this->defaultMail[$mailId]->attach)) {
            if ($this->config->get('embed_files')) {
                foreach ($this->defaultMail[$mailId]->attach as $attachment) {
                    $this->addAttachment($attachment->filename);
                }
            } else {
                $attachStringHTML = '<fieldset><legend>'.acym_translation('ACYM_ATTACHMENTS').'</legend><table>';
                foreach ($this->defaultMail[$mailId]->attach as $attachment) {
                    $attachStringHTML .= '<tr><td><a href="'.$attachment->url.'" target="_blank">'.$attachment->name.'</a></td></tr>';
                }
                $attachStringHTML .= '</table></fieldset>';

                if ($this->config->get('attachments_position', 'bottom') === 'top') {
                    $this->Body = $attachStringHTML.'<br />'.$this->Body;
                } else {
                    $this->Body .= '<br />'.$attachStringHTML;
                }
            }
        }

        if (!empty($this->introtext)) {
            $this->Body = $this->introtext.$this->Body;
        }

        $preheader = '';
        if (!empty($this->Preheader)) {
            $spacing = str_repeat('&nbsp;&zwnj;', 100);

            $preheader = '<!--[if !mso 9]><!--><div style="visibility:hidden;mso-hide:all;font-size:0;color:transparent;height:0;line-height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;">'.$this->Preheader.$spacing.'</div><!--<![endif]-->';
        }

        if (!empty($preheader)) {
            preg_match('#(<(.*)<body(.*)>)#Uis', $this->Body, $matches);
            if (empty($matches) || empty($matches[1])) {
                $this->Body = $preheader.$this->Body;
            } else {
                $this->Body = $matches[1].$preheader.str_replace($matches[1], '', $this->Body);
            }
        }

        if (ACYM_CMS === 'wordpress') {
            ob_start();
            $this->Body = do_shortcode($this->Body);
            ob_end_clean();
        }


        $this->replaceParams();

        $this->body = &$this->Body;
        $this->altbody = &$this->AltBody;
        $this->subject = &$this->Subject;
        $this->from = &$this->From;
        $this->fromName = &$this->FromName;
        $this->replyto = &$this->ReplyTo;
        $this->replyname = $this->defaultMail[$mailId]->reply_to_name;
        $this->replyemail = $this->defaultMail[$mailId]->reply_to_email;
        $this->mailId = $this->defaultMail[$mailId]->id;
        $this->id = $this->mailId;
        $this->creator_id = $this->defaultMail[$mailId]->creator_id;
        $this->type = $this->defaultMail[$mailId]->type;
        $this->stylesheet = &$this->stylesheet;
        $this->links_language = $this->defaultMail[$mailId]->links_language;

        if (!$this->isTest && $this->canTrack($mailId, $receiver)) {
            $this->statPicture($this->mailId, $receiver->id);
            $this->body = acym_absoluteURL($this->body);
            $this->statClick($this->mailId, $receiver->id);
            if (acym_isTrackingSalesActive()) {
                $this->trackingSales($this->mailId, $receiver->id);
            }
        }

        $this->replaceParams();

        if (strpos($receiver->email, '@mt.acyba.com') !== false) {
            $currentUser = $this->userClass->getOneByEmail(acym_currentUserEmail());
            if (empty($currentUser)) {
                $currentUser = $receiver;
            }
            $result = acym_trigger('replaceUserInformation', [&$this, &$currentUser, true]);
        } else {
            $result = acym_trigger('replaceUserInformation', [&$this, &$receiver, true]);
            foreach ($result as $oneResult) {
                if (!empty($oneResult) && !$oneResult['send']) {
                    $this->reportMessage = $oneResult['message'];

                    return -1;
                }
            }
        }

        if (!empty($acymLanguages['userLanguage'])) {
            unset($acymLanguages['userLanguage']);
        }

        foreach ($result as $oneResult) {
            if (!empty($oneResult['emogrifier'])) {
                $this->prepareEmailContent();
                break;
            }
        }

        if ($this->config->get('multiple_part', false)) {
            $this->altbody = $this->textVersion($this->Body);
        }

        $this->replaceParams();

        $status = $this->send();
        if ($this->trackEmail) {
            $helperQueue = new QueueHelper();
            $statsAdd = [];
            $statsAdd[$this->mailId][$status][] = $receiver->id;
            $helperQueue->statsAdd($statsAdd);
            $this->trackEmail = false;
        }

        return $status;
    }

    public function triggerFollowUpAgain(string $mailId, string $userId): void
    {
        if ($this->defaultMail[$mailId]->type !== MailClass::TYPE_FOLLOWUP) {
            return;
        }

        $followUpClass = new FollowupClass();
        $followUp = $followUpClass->getOneByMailId(intval($mailId));

        if (empty($followUp)) {
            return;
        }

        if (empty($followUp->loop)) {
            return;
        }

        $lastEmail = $followUpClass->getLastEmail(intval($followUp->id));
        if (empty($lastEmail) || $lastEmail->id != $mailId) {
            return;
        }

        $delay = 0;
        if (!empty($followUp->loop_delay)) {
            $delay = $followUp->loop_delay;
        }

        $mailToSkip = [];
        if (!empty($followUp->loop_mail_skip)) {
            $mailToSkip = json_decode($followUp->loop_mail_skip);
        }

        $followUpClass->triggerFollowUp(intval($followUp->id), intval($userId), $delay, $mailToSkip);
    }

    private function trackingSales($mailId, $userId)
    {
        preg_match_all('#href[ ]*=[ ]*"(?!mailto:|\#|ymsgr:|callto:|file:|ftp:|webcal:|skype:|tel:)([^"]+)"#Ui', $this->body, $results);
        if (empty($results)) return;

        foreach ($results[1] as $key => $url) {
            $simplifiedUrl = str_replace(['https://', 'http://', 'www.'], '', $url);
            $simplifiedWebsite = str_replace(['https://', 'http://', 'www.'], '', ACYM_LIVE);
            if (strpos($simplifiedUrl, rtrim($simplifiedWebsite, '/')) === false || strpos($url, 'task=unsub')) continue;

            $toAddUrl = (strpos($url, '?') === false ? '?' : '&').'linkReferal='.$mailId.'-'.$userId;

            $posHash = strpos($url, '#');
            if ($posHash !== false) {
                $newURL = substr($url, 0, $posHash).$toAddUrl.substr($url, $posHash);
            } else {
                $newURL = $url.$toAddUrl;
            }

            $this->body = preg_replace('#href="('.preg_quote($url, '#').')"#Uis', 'href="'.$newURL.'"', $this->body);
        }
    }

    public function statPicture($mailId, $userId)
    {
        $pictureLink = acym_frontendLink('frontstats&task=openStats&id='.$mailId.'&userid='.$userId, true, false);

        $widthsize = 50;
        $heightsize = 1;
        $width = empty($widthsize) ? '' : ' width="'.$widthsize.'" ';
        $height = empty($heightsize) ? '' : ' height="'.$heightsize.'" ';

        $statPicture = '<img class="spict" alt="Statistics image" src="'.$pictureLink.'"  border="0" '.$height.$width.'/>';

        if (strpos($this->body, '</body>')) {
            $this->body = str_replace('</body>', $statPicture.'</body>', $this->body);
        } else {
            $this->body .= $statPicture;
        }
    }

    public function statClick($mailId, $userid, $fromStat = false)
    {
        if (!$fromStat && !in_array($this->type, MailClass::TYPES_WITH_STATS)) {
            return;
        }

        $urlClass = new UrlClass();
        $urls = [];

        $trackingSystemExternalWebsite = $this->config->get('trackingsystemexternalwebsite', 1);
        $trackingSystem = $this->config->get('trackingsystem', 'acymailing');
        if (false === strpos($trackingSystem, 'acymailing') && false === strpos($trackingSystem, 'google')) {
            return;
        }

        if (strpos($trackingSystem, 'google') !== false) {
            $mail = $this->mailClass->getOneById($mailId);
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneCampaignByMailId($mailId);

            $utmCampaign = acym_getAlias($mail->subject);
        }

        preg_match_all('#<[^>]* href[ ]*=[ ]*"(?!mailto:|\#|ymsgr:|callto:|file:|ftp:|webcal:|skype:|tel:)([^"]+)"#Ui', $this->body, $results);
        if (empty($results)) {
            return;
        }

        $countLinks = array_count_values($results[1]);
        if (array_product($countLinks) != 1) {
            $previousLinkHandled = '';
            foreach ($results[1] as $key => $url) {
                if ($countLinks[$url] === 1) {
                    continue;
                }

                $previousIsOutlook = false;
                if (strpos($results[0][$key], '<v:roundrect') === 0) {
                    $previousLinkHandled = $results[0][$key];
                    if ($countLinks[$url] === 2) {
                        $countLinks[$url] = 1;
                        continue;
                    }
                } elseif (strpos($previousLinkHandled, '<v:roundrect') === 0) {
                    $previousIsOutlook = true;
                }
                $previousLinkHandled = $results[0][$key];

                if (!$previousIsOutlook) {
                    $countLinks[$url]--;
                }

                $toAddUrl = (strpos($url, '?') === false ? '?' : '&').'idU='.$countLinks[$url];

                if ($previousIsOutlook) {
                    $countLinks[$url]--;
                }

                $posHash = strpos($url, '#');
                if ($posHash !== false) {
                    $newURL = substr($url, 0, $posHash).$toAddUrl.substr($url, $posHash);
                } else {
                    $newURL = $url.$toAddUrl;
                }

                $this->body = preg_replace('#href="('.preg_quote($url, '#').')"#Uis', 'href="'.$newURL.'"', $this->body, 1);

                $results[0][$key] = 'href="'.$newURL.'"';
                $results[1][$key] = $newURL;
            }
        }

        foreach ($results[1] as $i => $url) {
            $urlsNotToTrack = [
                'task=unsub',
                'fonts.googleapis.com',
            ];

            $track = true;

            foreach ($urlsNotToTrack as $urlNotToTrack) {
                if (strpos($url, $urlNotToTrack)) {
                    $track = false;
                    break;
                }
            }

            if (isset($urls[$results[0][$i]]) || !$track) {
                continue;
            }

            $simplifiedUrl = str_replace(['https://', 'http://', 'www.'], '', $url);
            $simplifiedWebsite = str_replace(['https://', 'http://', 'www.'], '', ACYM_LIVE);
            $internalUrl = strpos($simplifiedUrl, rtrim($simplifiedWebsite, '/')) === 0;

            $subfolder = false;
            if ($internalUrl) {
                $urlWithoutBase = str_replace($simplifiedWebsite, '', $simplifiedUrl);
                if (strpos($urlWithoutBase, '/') || strpos($urlWithoutBase, '?')) {
                    $slashPosition = strpos($urlWithoutBase, '/');
                    $folderName = substr($urlWithoutBase, 0, !$slashPosition ? strpos($urlWithoutBase, '?') : $slashPosition);
                    if (strpos($folderName, '.') === false) {
                        $subfolder = @is_dir(ACYM_ROOT.$folderName);
                    }
                }
            }

            if ((!$internalUrl || $subfolder) && $trackingSystemExternalWebsite != 1) {
                continue;
            }

            if (strpos($url, 'utm_source') === false && strpos($trackingSystem, 'google') !== false) {
                $idToUse = empty($campaign) ? $mailId : $campaign->id;
                $args = [];
                $args[] = empty($campaign->sending_params['utm_source']) ? 'utm_source=newsletter_'.$idToUse : 'utm_source='.urlencode($campaign->sending_params['utm_source']);
                $args[] = empty($campaign->sending_params['utm_medium']) ? 'utm_medium=email' : 'utm_medium='.urlencode($campaign->sending_params['utm_medium']);
                $args[] = empty($campaign->sending_params['utm_campaign']) ? 'utm_campaign='.$utmCampaign : 'utm_campaign='.urlencode($campaign->sending_params['utm_campaign']);
                $anchor = '';
                if (strpos($url, '#') !== false) {
                    $anchor = substr($url, strpos($url, '#'));
                    $url = substr($url, 0, strpos($url, '#'));
                }

                if (strpos($url, '?')) {
                    $mytracker = $url.'&'.implode('&', $args);
                } else {
                    $mytracker = $url.'?'.implode('&', $args);
                }
                $mytracker .= $anchor;
                $urls[$results[0][$i]] = str_replace($results[1][$i], $mytracker, $results[0][$i]);

                $url = $mytracker;
            }

            if (strpos($trackingSystem, 'acymailing') !== false) {
                $isAutologin = false;
                $autologinParams = 'autoSubId=%7Bsubscriber:id%7D&amp;subKey=%7Bsubscriber:key%7Curlencode%7D';
                if (strpos($url, $autologinParams) !== false) {
                    $isAutologin = true;
                    $url = str_replace(
                        [
                            '?'.$autologinParams.'&amp;',
                            '?'.$autologinParams,
                            '&amp;'.$autologinParams,
                        ],
                        [
                            '?',
                            '',
                            '',
                        ],
                        $url
                    );
                }

                if (preg_match('#passw|modify|\{|%7B#i', $url)) {
                    continue;
                }

                if (!$fromStat) {
                    $mytracker = $urlClass->getUrl($url, $mailId, $userid);
                }

                if (empty($mytracker)) {
                    continue;
                }

                if ($isAutologin) {
                    $mytracker .= strpos($mytracker, '?') === false ? '?' : '&amp;';
                    $mytracker .= $autologinParams;
                }

                $urls[$results[0][$i]] = str_replace($results[1][$i], $mytracker, $results[0][$i]);
            }
        }

        $this->body = str_replace(array_keys($urls), $urls, $this->body);
    }

    public function textVersion($html, $fullConvert = true)
    {
        $html = acym_absoluteURL($html);

        if ($fullConvert) {
            $html = preg_replace('# +#', ' ', $html);
            $html = str_replace(["\n", "\r", "\t"], '', $html);
        }

        $removepictureslinks = "#< *a[^>]*> *< *img[^>]*> *< *\/ *a *>#isU";
        $removeScript = "#< *script(?:(?!< */ *script *>).)*< */ *script *>#isU";
        $removeStyle = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
        $removeStrikeTags = '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
        $replaceByTwoReturnChar = '#< *(h1|h2)[^>]*>#Ui';
        $replaceByStars = '#< *li[^>]*>#Ui';
        $replaceByReturnChar1 = '#< */ *(li|td|dt|tr|div|p)[^>]*> *< *(li|td|dt|tr|div|p)[^>]*>#Ui';
        $replaceByReturnChar = '#< */? *(br|p|h1|h2|legend|h3|li|ul|dd|dt|h4|h5|h6|tr|td|div)[^>]*>#Ui';
        $replaceLinks = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*>(.+)< *\/ *a *>/Uis';

        $text = preg_replace(
            [
                $removepictureslinks,
                $removeScript,
                $removeStyle,
                $removeStrikeTags,
                $replaceByTwoReturnChar,
                $replaceByStars,
                $replaceByReturnChar1,
                $replaceByReturnChar,
                $replaceLinks,
            ],
            ['', '', '', '', "\n\n", "\n* ", "\n", "\n", '${2} ( ${1} )'],
            $html
        );

        $text = preg_replace('#(&lt;|&\#60;)([^ \n\r\t])#i', '&lt; ${2}', $text);

        $text = str_replace([" ", "&nbsp;"], ' ', strip_tags($text));

        $text = trim(@html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

        if ($fullConvert) {
            $text = preg_replace('# +#', ' ', $text);
            $text = preg_replace('#\n *\n\s+#', "\n\n", $text);
        }

        return $text;
    }

    protected function embedImages()
    {
        preg_match_all('/(src|background)=[\'|"]([^"\']*)[\'|"]/Ui', $this->Body, $images);

        if (empty($images[2])) {
            return true;
        }

        $embedSuccess = true;

        $mimetypes = [
            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'png' => 'image/png',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
        ];

        $allImages = [];

        foreach ($images[2] as $i => $url) {
            if (isset($allImages[$url])) {
                continue;
            }

            if (strpos($url, 'ctrl=') !== false) {
                continue;
            }

            $allImages[$url] = 1;

            $path = acym_internalUrlToPath($url);
            $path = $this->removeAdditionalParams($path);

            $filename = str_replace(['%', ' '], '_', basename($url));
            $filename = $this->removeAdditionalParams($filename);

            $md5 = md5($filename);
            $cid = 'cid:'.$md5;
            $fileParts = explode(".", $filename);
            if (empty($fileParts[1])) {
                continue;
            }
            $ext = strtolower($fileParts[1]);
            if (!isset($mimetypes[$ext])) {
                continue;
            }

            if ($this->addEmbeddedImage($path, $md5, $filename, 'base64', $mimetypes[$ext])) {
                $this->Body = preg_replace('/'.preg_quote($images[0][$i], '/').'/Ui', $images[1][$i].'="'.$cid.'"', $this->Body);
            } else {
                $embedSuccess = false;
            }
        }

        return $embedSuccess;
    }

    private function removeAdditionalParams($url)
    {
        $additionalParamsPos = strpos($url, '?');
        if (!empty($additionalParamsPos)) {
            $url = substr($url, 0, $additionalParamsPos);
        }

        return $url;
    }

    public function cleanText($text)
    {
        return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', (string)$text));
    }

    protected function _addReplyTo($email, $name)
    {
        if (empty($email)) {
            return;
        }
        $replyToName = $this->config->get('add_names', true) ? $this->cleanText(trim($name)) : '';
        $replyToEmail = trim($email);
        if (substr_count($replyToEmail, '@') > 1) {
            $replyToEmailArray = explode(';', str_replace([';', ','], ';', $replyToEmail));
            $replyToNameArray = explode(';', str_replace([';', ','], ';', $replyToName));
            foreach ($replyToEmailArray as $i => $oneReplyTo) {
                $this->addReplyTo($this->cleanText($oneReplyTo), @$replyToNameArray[$i]);
            }
        } else {
            $this->addReplyTo($this->cleanText($replyToEmail), $replyToName);
        }
    }

    private function replaceParams()
    {
        if (empty($this->parameters)) return;

        $helperPlugin = new PluginHelper();

        $this->generateAllParams();

        $vars = [
            'Subject',
            'Body',
            'From',
            'FromName',
            'replyname',
            'replyemail',
        ];

        foreach ($vars as $oneVar) {
            if (!empty($this->$oneVar)) {
                $this->$oneVar = $helperPlugin->replaceDText($this->$oneVar, $this->parameters);
            }
        }

        if (!empty($this->ReplyTo)) {
            foreach ($this->ReplyTo as $i => $replyto) {
                foreach ($replyto as $a => $oneval) {
                    $this->ReplyTo[$i][$a] = $helperPlugin->replaceDText($this->ReplyTo[$i][$a], $this->parameters);
                }
            }
        }
    }

    private function generateAllParams()
    {
        $result = '<table style="border:1px solid;border-collapse:collapse;" border="1" cellpadding="10"><tr><td>Tag</td><td>Value</td></tr>';
        foreach ($this->parameters as $name => $value) {
            if (!is_string($value)) continue;

            $result .= '<tr><td>'.trim($name, '{}').'</td><td>'.$value.'</td></tr>';
        }
        $result .= '</table>';
        $this->addParam('allshortcodes', $result);
    }

    public function addParamInfo()
    {
        if (!empty($_SERVER)) {
            $serverinfo = [];
            foreach ($_SERVER as $oneKey => $oneInfo) {
                $serverinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo, true));
            }
            $this->addParam('serverinfo', implode('<br />', $serverinfo));
        }

        if (!empty($_REQUEST)) {
            $postinfo = [];
            foreach ($_REQUEST as $oneKey => $oneInfo) {
                $postinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo, true));
            }
            $this->addParam('postinfo', implode('<br />', $postinfo));
        }
    }

    public function addParam($name, $value)
    {
        $tagName = '{'.$name.'}';
        $this->parameters[$tagName] = $value;
    }

    public function overrideEmail($subject, $body, $to)
    {
        $overrideClass = new OverrideClass();
        $override = $overrideClass->getMailByBaseContent($subject, $body);

        if (empty($override)) {
            return false;
        }

        $this->trackEmail = true;
        $this->autoAddUser = true;

        for ($i = 1 ; $i < count($override->parameters) ; $i++) {
            $oneParam = $override->parameters[$i];

            $unmodified = $oneParam;
            $oneParam = preg_replace(
                '/(http|https):\/\/(.*)/',
                '<a href="$1://$2" target="_blank">$1://$2</a>',
                $oneParam,
                -1,
                $count
            );
            if ($count > 0) $this->addParam('link'.$i, $unmodified);
            $this->addParam('param'.$i, $oneParam);
        }

        $this->addParam('subject', $subject);

        $this->overrideEmailToSend = $override;
        $statusSend = $this->sendOne($override->id, $to);
        if (!$statusSend && !empty($this->reportMessage)) {
            $cronHelper = new CronHelper();
            $cronHelper->messages[] = $this->reportMessage;
            $cronHelper->saveReport();
        }

        return $statusSend;
    }

    private function getSendSettings($type, $mailId = 0)
    {
        if (!in_array($type, ['from_name', 'from_email', 'replyto_name', 'replyto_email'])) return false;

        $mailType = strpos($type, 'replyto') !== false ? str_replace('replyto', 'reply_to', $type) : $type;

        if (!empty($mailId) && !empty($this->defaultMail[$mailId]) && !empty($this->defaultMail[$mailId]->$mailType)) return $this->defaultMail[$mailId]->$mailType;

        $lang = empty($this->userLanguage) ? acym_getLanguageTag() : $this->userLanguage;

        $setting = $this->config->get($type);

        $translation = $this->config->get('sender_info_translation');

        if (!empty($translation)) {
            $translation = json_decode($translation, true);

            if (!empty($translation[$lang])) {
                $setting = $translation[$lang][$type];
            }
        }

        return $setting;
    }

    private function prepareEmailContent(?int $mailId = null): void
    {
        $this->handleRelativeURLs($mailId);
        $this->inlineCSS($mailId);
        $this->finalizeHtmlStructure($mailId);
    }

    private function handleRelativeURLs(?int $mailId = null): void
    {
        $mail = empty($mailId) ? $this : $this->defaultMail[$mailId];
        $mail->body = acym_absoluteURL($mail->body);
    }

    private function inlineCSS(?int $mailId = null): void
    {
        $mail = empty($mailId) ? $this : $this->defaultMail[$mailId];

        global $emogrifiedMediaCSS;
        $emogrifiedMediaCSS = '';

        $style = $this->getEmailStylesheet($mail);
        $cssInliner = AcyCssInliner::fromHtml($mail->body)->inlineCss(implode('', $style));
        $domDocument = $cssInliner->getDomDocument();

        $mail->body = preg_replace(
            [
                '# id="mce_\d+"#Ui',
                '#(style="[^"]+);"#Ui',
            ],
            [
                '',
                '$1"',
            ],
            HtmlPruner::fromDomDocument($domDocument)
                      ->removeRedundantClassesAfterCssInlined($cssInliner)
                      ->renderBodyContent()
        );

        $mail->body = str_replace('&zwj;', '', $mail->body);

        $mail->body = preg_replace_callback(
            '#style="([^"]+)"#Ui',
            function ($matches) {
                return 'style="'.str_replace(['; ', ': '], [';', ':'], $matches[1]).'"';
            },
            $mail->body
        );
    }

    private function finalizeHtmlStructure(?int $mailId = null): void
    {
        $mail = empty($mailId) ? $this : $this->defaultMail[$mailId];

        $finalContent = '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">';
        $finalContent .= '<head>';
        $finalContent .= '<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->';
        $finalContent .= '<meta http-equiv="Content-Type" content="text/html; charset='.strtolower($this->config->get('charset')).'" />'."\n";
        $finalContent .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n";
        $finalContent .= '<title>'.$mail->subject.'</title>'."\n";

        global $emogrifiedMediaCSS;
        $finalContent .= '<style>'.$emogrifiedMediaCSS.'</style>';
        $finalContent .= '<!--[if mso]><style type="text/css">#acym__wysid__template center > table { width: 580px; }</style><![endif]-->';
        $finalContent .= '<!--[if !mso]><!--><style>#acym__wysid__template center > table { width: 100%; }</style><!--<![endif]-->';

        if (!empty($mail->headers)) {
            $finalContent .= $mail->headers;
        }
        $finalContent .= '</head>';

        preg_match('@<[^>"t]*body[^>]*>@', $mail->body, $matches);
        if (empty($matches[0])) {
            $mail->body = '<body>'.$mail->body.'</body>';
        } else {
            preg_match('@<[^>"t]*/body[^>]*>@', $mail->body, $matches);
            if (empty($matches[0])) {
                $mail->body .= '</body>';
            }
        }

        $finalContent .= $mail->body;
        $finalContent .= '</html>';

        $mail->body = $finalContent;
    }

    private function getEmailStylesheet(object $mail): array
    {
        $style = [];

        if (strpos($mail->body, 'acym__wysid__template') !== false) {
            static $foundationCSS = null;
            if (empty($foundationCSS)) {
                $foundationCSS = acym_fileGetContent(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css');
                $foundationCSS = str_replace('#acym__wysid__template ', '', $foundationCSS);
            }
            $style['foundation'] = $foundationCSS;
        }

        static $emailFixes = null;
        if (empty($emailFixes)) {
            $emailFixes = acym_getEmailCssFixes();
        }
        $style['fixes'] = $emailFixes;

        if (!empty($mail->stylesheet)) {
            $style['custom_stylesheet'] = $mail->stylesheet;
        }

        $settingsStyles = $this->editorHelper->getSettingsStyle($mail->settings);
        if (!empty($settingsStyles)) {
            $style['settings_stylesheet'] = $settingsStyles;
        }

        return $style;
    }


    public function setFrom($email, $name = '', $auto = false)
    {
        if (!empty($email)) {
            $this->From = $this->cleanText($email);
        }
        if (!empty($name) && $this->config->get('add_names', true)) {
            $this->FromName = $this->cleanText($name);
        }
    }

    protected function edebug($str)
    {
        if (strpos($this->ErrorInfo, $str) === false) {
            $this->ErrorInfo .= ' '.$str;
        }
    }

    public function getMailMIME()
    {
        $result = parent::getMailMIME();

        $result = rtrim($result, static::$LE);

        if ($this->Mailer != 'mail') {
            $result .= static::$LE.static::$LE;
        }

        return $result;
    }

    public static function validateAddress($address, $patternselect = null)
    {
        return true;
    }
}