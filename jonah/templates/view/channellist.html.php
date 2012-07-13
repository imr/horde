<?php
/**
 * index view for rendering channel list. Expects:
 *  ->search_img
 *  ->channels
 *
 *
 */
?>
<div class="header">
 <?php echo _("Manage Feeds") ?>
 <a id="quicksearchL" href="#" title="<?php echo _("Search")?>" onclick="$('quicksearchL').hide(); $('quicksearch').show(); $('quicksearchT').focus(); return false;"><?php echo $this->search_img?></a>
 <div id="quicksearch" style="display:none;">
  <input type="text" name="quicksearchT" id="quicksearchT" for="feeds-body" empty="feeds-empty" />
  <small>
   <a title="<?php echo _("Close Search")?>" href="#" onclick="$('quicksearch').hide(); $('quicksearchT').value = ''; QuickFinder.filter($('quicksearchT')); $('quicksearchL').show(); return false;">X</a>
  </small>
 </div>
</div>

<?php if (count($this->channels)):?>
    <table id="feeds" width="100%" class="sortable" cellspacing="0">
    <thead>
     <tr>
      <th width="1%">&nbsp;</th>
      <th class="sortdown"><?php echo _("Name")?></th>
      <th><?php echo _("Type")?></th>
      <th><?php echo _("Last Update")?></th>
     </tr>
    </thead>

    <tbody id="feeds-body">
     <?php foreach (array_values($this->channels) as $feed):?>
     <tr>
      <td nowrap><?php echo Horde::link(Horde::url('stories/edit.php')->add('channel_id', $feed->getName()), _("Add Story")) . $this->add_img . '</a>' ?>
          <?php echo Horde::link(Horde::url('channels/' . $feed->getName() . '/edit'), _("Edit")) . $this->edit_img . '</a>' ?>
<?php if (empty($conf['share']['no_sharing'])): ?>
          <?php echo Horde::link(Horde_Util::addParameter($perms_url_base, 'share', $feed->getName()), _("Change Permissions"), '', '_blank', Horde::popupJs($perms_url_base, array('params' => array('share' => $feed->getName()), 'urlencode' => true)) . 'return false;') . $this->perms_img . '</a>' ?>
<?php endif; ?>
          <?php echo Horde::link(Horde::url($feed->getName()), _("Delete")) . $this->delete_img . '</a>' ?></td>
      <td><?php echo htmlspecialchars($feed->get('name')) ?></td>
      <td><?php echo is_null($feed->get('owner')) ? _("System") : _("User") ?></td>
      <td><?php $url = Horde::url($feed->getName()); echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars($url) . '</a>' ?></td>
      <td><?php $url = Horde::url('/' . $feed->getName() . '/rss'); echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars($url) . '</a>' ?></td>
     </tr>
     <?php endforeach; ?>
    </tbody>
    </table>
    <div id="feeds-empty">
     <?php echo _("No feeds match")?>
    </div>
<?php else:?>
    <div class="text">
     <em><?php echo _("No channels are available.")?></em>
    </div>
<?php endif;?>
