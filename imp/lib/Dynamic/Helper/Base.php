<?php
/**
 * View helper class for all dynamic pages.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_Dynamic_Helper_Base extends Horde_View_Helper_Base
{
    /**
     * Output an action button.
     *
     * @param array $params  A list of parameters:
     *   - app: (string) The application to load the icon from.
     *   - class: (string) The CSS classname to use for the link.
     *   - icon: (string) The icon CSS classname.
     *   - id: (string) The DOM ID of the link.
     *   - title: (string) The title string.
     *
     * @return string  An HTML link to $url.
     */
    public function actionButton(array $params = array())
    {
        return Horde::link(
            '',
            '',
            empty($params['class']) ? '' : $params['class'],
            '',
            '',
            '',
            Horde::getAccessKey($params['title']),
            empty($params['id']) ? array() : array('id' => $params['id']),
            true
        ) . (empty($params['icon'])
            ? ''
            : '<span class="iconImg dimpaction' . $params['icon'] . '"></span>').
        $params['title'] . '</a>';
    }

}
