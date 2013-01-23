<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines an AJAX variable queue for IMP.  These are variables that may be
 * generated by various IMP code that should be added to the eventual output
 * sent to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Queue
{
    /**
     * Flag entries to add to response.
     *
     * @var array
     */
    protected $_flag = array();

    /**
     * Mailbox options.
     *
     * @var array
     */
    protected $_mailboxOpts = array();

    /**
     * Message queue.
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Mail log queue.
     *
     * @var array
     */
    protected $_maillog = array();

    /**
     * Poll mailboxes.
     *
     * @var array
     */
    protected $_poll = array();

    /**
     * Add quota information to response?
     *
     * @var string
     */
    protected $_quota = false;

    /**
     * Generates AJAX response task data from the queue.
     *
     * For flag data (key: 'flag'), an array of objects with these properties:
     *   - add: (array) The list of flags that were added.
     *   - remove: (array) The list of flags that were removed.
     *   - uids: (string) Indices of the messages that have changed (IMAP
     *           sequence string; mboxes are base64url encoded).
     *
     * For mailbox data (key: 'mailbox'), an array with these keys:
     *   - a: (array) Mailboxes that were added (base64url encoded).
     *   - all: (integer) TODO
     *   - base: (string) TODO
     *   - c: (array) Mailboxes that were changed (base64url encoded).
     *   - d: (array) Mailboxes that were deleted (base64url encoded).
     *   - expand: (integer) Expand subfolders on load.
     *   - noexpand: (integer) TODO
     *   - switch: (string) Load this mailbox (base64url encoded).
     *
     * For maillog data (key: 'maillog'), an object with these properties:
     *   - buid: (integer) BUID.
     *   - log: (array) List of log entries.
     *   - mbox: (string) Mailbox.
     *
     * For message preview data (key: 'message'), an object with these
     * properties:
     *   - buid: (integer) BUID.
     *   - data: (object) Message viewport data.
     *   - mbox: (string) Mailbox.
     *
     * For poll data (key: 'poll'), an array with keys as base64url encoded
     * mailbox names, values as the number of unseen messages.
     *
     * For quota data (key: 'quota'), an array with these keys:
     *   - m: (string) Quota message.
     *   - p: (integer) Quota percentage.
     *
     * @param IMP_Ajax_Application $ajax  The AJAX object.
     */
    public function add(IMP_Ajax_Application $ajax)
    {
        /* Add flag information. */
        if (!empty($this->_flag)) {
            $ajax->addTask('flag', $this->_flag);
            $this->_flag = array();
        }

        /* Add folder tree information. */
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->setIteratorFilter(IMP_Imap_Tree::FLIST_NOSPECIALMBOXES);
        $out = $imptree->getAjaxResponse();
        if (!empty($out)) {
            $ajax->addTask('mailbox', array_merge($out, $this->_mailboxOpts));
        }

        /* Add mail log information. */
        if (!empty($this->_maillog)) {
            $imp_maillog = $GLOBALS['injector']->getInstance('IMP_Maillog');
            $maillog = array();

            foreach ($this->_maillog as $val) {
                if ($tmp = $imp_maillog->getLogObs($val['msg_id'])) {
                    $log_ob = new stdClass;
                    $log_ob->buid = intval($val['buid']);
                    $log_ob->log = $tmp;
                    $log_ob->mbox = $val['mailbox']->form_to;
                    $maillog[] = $log_ob;
                }
            }

            if (!empty($maillog)) {
                $ajax->addTask('maillog', $maillog);
            }
        }

        /* Add message information. */
        if (!empty($this->_messages)) {
            $ajax->addTask('message', $this->_messages);
            $this->_messages = array();
        }

        /* Add poll information. */
        $poll = $poll_list = array();
        foreach ($this->_poll as $val) {
            $poll_list[strval($val)] = 1;
        }

        $imap_ob = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        if ($imap_ob->ob) {
            foreach ($imap_ob->statusMultiple(array_keys($poll_list), Horde_Imap_Client::STATUS_UNSEEN) as $key => $val) {
                $poll[IMP_Mailbox::formTo($key)] = intval($val['unseen']);
            }
        }

        if (!empty($poll)) {
            $ajax->addTask('poll', $poll);
            $this->_poll = array();
        }

        /* Add quota information. */
        if (($this->_quota !== false) &&
            ($quotadata = $GLOBALS['injector']->getInstance('IMP_Quota_Ui')->quota($this->_quota))) {
            $ajax->addTask('quota', array(
                'm' => $quotadata['message'],
                'p' => round($quotadata['percent']),
                'l' => $quotadata['percent'] >= 90
                    ? 'alert'
                    : ($quotadata['percent'] >= 75 ? 'warn' : '')
            ));
            $this->_quota = false;
        }
    }

    /**
     * Add flag entry to response queue.
     *
     * @param array $flags          List of flags that have changed.
     * @param boolean $add          Were the flags added?
     * @param IMP_Indices $indices  Indices object.
     */
    public function flag($flags, $add, IMP_Indices $indices)
    {
        global $injector;

        if (!$injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FLAGS)) {
            return;
        }

        $changed = $injector->getInstance('IMP_Flags')->changed($flags, $add);

        $result = new stdClass;
        if (!empty($changed['add'])) {
            $result->add = array_map('strval', $changed['add']);
        }
        if (!empty($changed['remove'])) {
            $result->remove = array_map('strval', $changed['remove']);
        }

        if ($indices instanceof IMP_Indices_Mailbox) {
            $indices = $indices->joinIndices();
        }

        if (count($indices)) {
            $result->buids = $indices->toArray();
            $this->_flag[] = $result;
        }
    }

    /**
     * Add message data to output.
     *
     * @param IMP_Indices $indices  Index of the message.
     * @param boolean $preview      Preview data?
     * @param boolean $peek         Don't set seen flag?
     */
    public function message(IMP_Indices $indices, $preview = false,
                            $peek = false)
    {
        try {
            $show_msg = new IMP_Ajax_Application_ShowMessage($indices, $peek);
            $msg = (object)$show_msg->showMessage(array(
                'preview' => $preview
            ));
            $msg->save_as = strval($msg->save_as);

            if ($indices instanceof IMP_Indices_Mailbox) {
                $indices = $indices->joinIndices();
            }

            foreach ($indices as $val) {
                foreach ($val->uids as $val2) {
                    $ob = new stdClass;
                    $ob->buid = $val2;
                    $ob->data = $msg;
                    $ob->mbox = $val->mbox->form_to;
                    $this->_messages[] = $ob;
                }
            }
        } catch (Exception $e) {}
    }

    /**
     * Add mail log data to output.
     *
     * @param IMP_Indices $indices  Indices object.
     * @param string $msg_id        The message ID of the original message.
     */
    public function maillog(IMP_Indices $indices, $msg_id)
    {
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            if ($indices instanceof IMP_Indices_Mailbox) {
                $indices = $indices->joinIndices();
            }

            foreach ($indices as $val) {
                foreach ($val->uids as $val2) {
                    $this->_maillog[] = array(
                        'buid' => $val2,
                        'mailbox' => $val->mbox,
                        'msg_id' => $msg_id
                    );
                }
            }
        }
    }

    /**
     * Add additional options to the mailbox output.
     *
     * @param array $name   Option name.
     * @param mixed $value  Option value.
     */
    public function setMailboxOpt($name, $value)
    {
        $this->_mailboxOpts[$name] = $value;
    }

    /**
     * Add poll entry to response queue.
     *
     * @param mixed $mboxes  A mailbox name or list of mailbox names.
     */
    public function poll($mboxes)
    {
        if (!is_array($mboxes)) {
            $mboxes = array($mboxes);
        }

        foreach (IMP_Mailbox::get($mboxes) as $val) {
            if ($val->polled) {
                $this->_poll[] = $val;
            }
        }
    }

    /**
     * Add quota entry to response queue.
     *
     * @param string $mailbox  Mailbox to query for quota.
     */
    public function quota($mailbox)
    {
        $this->_quota = $mailbox;
    }

}
