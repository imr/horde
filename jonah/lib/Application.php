<?php
/**
 * Jonah application API.
 *
 * @package Jonah
 */

if (!defined('JONAH_BASE')) {
    define('JONAH_BASE', __DIR__. '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(JONAH_BASE. '/config/horde.local.php')) {
        include JONAH_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', JONAH_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Jonah_Application extends Horde_Registry_Application
{
    public $version = 'H5 (1.0-git)';

    /**
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Jonah_Driver', 'Jonah_Factory_Driver', 'create');
        $GLOBALS['injector']->bindFactory('Jonah_Shares', 'Jonah_Factory_Shares', 'create');
    }

    /**
     */
    protected function _init()
    {
        if ($channel_id = Horde_Util::getFormData('channel_id')) {
            $url = Horde::url('delivery/rss.php', true, -1)
                ->add('channel_id', $channel_id);
            if ($tag_id = Horde_Util::getFormData('tag_id')) {
                $url->add('tag_id', $tag_id);
            }

            $GLOBALS['page_output']->addLinkTag(array(
                'href' => $url,
                'title' => 'RSS 0.91'
            ));
        }
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'admin' => array(
                'title' => _("Administrator")
            ),
            'news' => array(
                'title' => _("News")
            )
        );

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('list.php'), _("_List Stories"), 'jonah_list'); 

    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        global $page_output, $prefs;

        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        $sidebar->addNewButton(
            _("_New Story"),
            Horde::url('stories/edit.php'));

        $sidebar->containers['my'] = array(
            'header' => array(
                'id' => 'jonah-toggle-my',
                'label' => _("My Feeds"),
                'collapsed' => false,
            ),
        );

        $list = Horde::url('stories/');
        $edit = Horde::url('channels/edit.php');
        $user = $GLOBALS['registry']->getAuth();

        if (!$GLOBALS['prefs']->isLocked('default_feed')) {
            $sidebar->containers['my']['header']['add'] = array(
                'url' => Horde::url('channels/edit.php'),
                'label' => _('Create a new Feed'),
            );
        }
        $sidebar->containers['shared'] = array(
            'header' => array(
                'id' => 'nag-toggle-shared',
                'label' => _("Shared Feeds"),
                'collapsed' => true,
            ),
        );
        foreach (Jonah::listFeeds(false, Horde_Perms::SHOW) as $name => $feed) {
            $url = $list->add(array('channel_id' => $name));
            $row = array(
                'selected' => in_array($name, $display_feeds),
                'url' => $url,
                'label' => $name,
                'edit' => $edit->add('channel_id', $feed->getName()),
                'type' => 'checkbox'
            );
            if ($feed->get('owner') == $user) {
                $sidebar->addRow($row, 'my');
            } else {
                $sidebar->addRow($row, 'shared');
            }
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        if (!Jonah::checkPermissions('jonah:news', Horde_Perms::EDIT) ||
            !in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            return;
        }

        $url = Horde::url('stories/');

        try {
            $channels = Jonah::listFeeds();
        } catch (Jonah_Exception $e) {
            var_dump($e);
            return;
        }

        $story_img = Horde_Themes::img('editstory.png');

        foreach ($channels as $channel) {
            $tree->addNode(array(
                'id' => $parent . $channel->getName(),
                'parent' => $parent,
                'label' => $channel->get('name'),
                'expanded' => false,
                'params' => array(
                    'icon' => $story_img,
                    'url' => $url->add('channel_id', $channel->getName())
                )
            ));
        }
    }

}
