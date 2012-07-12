<?php
/**
 * View for displaying Jonah feeds.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_ChannelList extends Jonah_View_Base
{
    /**
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);
        try {
            $feeds = Jonah::listFeeds();
        } catch (Exception $e) {
            $notification->push(sprintf(_("An error occurred fetching feeds: %s"), $e->getMessage()), 'horde.error');
            $feeds = false;
        }
        /* Build feed specific fields. */
        foreach ($feeds as $feed) {
            $sorted_feeds[$feed->getName()] = $feed->get('name');
        }
        asort($sorted_feeds);

        $perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=jonah');
        $subscribe_url_base = $registry->get('webroot', 'horde');

        $search_img = Horde::img('search.png', _("Search"));
        $add_img = Horde::img('new.png', _("Add Story"));
        $edit_img = Horde::img('edit.png', _("Edit"));
        $perms_img = Horde::img('perms.png', _("Change Permissions"));
        $delete_img = Horde::img('delete.png', _("Delete"));

        $view = new Horde_View(array('templatePath' => JONAH_TEMPLATES . '/view'));
        $view->addHelper('Tag');
        $view->channels = $channels;
        $view->search_img = Horde::img('search.png');
        global $page_output;
        $page_output->addScriptFile('tables.js', 'horde');
        $page_output->addScriptFile('quickfinder.js', 'horde');

        $page_output->header(array(
            'title' => _("Feeds")
        ));
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('channellist');
        $page_output->footer();
    }

}
