<?php
$form->renderActive(null, null, Horde::url('img/upload.php'), 'post', 'multipart/form-data');
if ($browser->getPlatform() == 'win' && $GLOBALS['registry']->showService('help')) {
    echo '<div class="box" style="margin:8px; padding:8px"><h3>' . Horde_Help::link('ansel', 'xppublish') . ' ' . _("Learn how to publish photos directly from Windows.") . '</h3></div>';
}
$lbcssurl = Horde::url($GLOBALS['registry']->get('themesuri', 'ansel') . '/lightbox.css', true);
