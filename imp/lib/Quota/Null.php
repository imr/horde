<?php
/**
 * The IMP_Quota_Null:: is a null implementation of the quota driver.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Quota_Null extends IMP_Quota
{
    /**
     */
    public function getQuota($mailbox = null)
    {
        return array(
            'limit' => 0,
            'usage' => 0
        );
    }

}
