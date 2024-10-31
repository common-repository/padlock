<?php
function check_access($cat, $group, $a)
{
    $group_id = (int)$group->id;
    foreach ($a as $entry)
    {
        if (($entry->term_id == $cat['id']) && ($entry->group_id == $group_id))
        {
            return "checked";
        }
    }
    
    return "";
}
?>

<div id="padlock_access" class="wrap">
    
    <h2>Access Settings</h2>
    <form method="post" id="access_admin">
        <div>
            <label>Make site private</label>
            <input type="hidden" name="make_private" value="0" />
            <input type="checkbox" name="make_private" <?php echo $site_is_private ?> value="1" />
            <div class="side-note">
                Not implemented yet. This is nicely covered by the excellent <a href="http://10up.com/plugins/restricted-site-access-wordpress/">Restricted Site Access</a> plugin.
            </div>
        </div>
        <div>
            <label>Template to use for restricted articles:</label> <br>
            <input type="text" name="restricted_template" value="<?php echo $restricted_template ?>" />
            <input type="submit" name="site_private" value="Save" class="button" />    
        </div>
    </form>
    
    <h2>Access Control</h2>
    <form method="post" id="access_control">

        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>Content</th>
                    <?php foreach ($groups as $group) : ?>
                        <th><?php echo $group->name; ?></th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                    <?php foreach ($categories as $cat) : ?>
                    <tr>
                        <td class="<?php echo $cat['class']; ?>"><?php echo $cat['name']; ?></td>
                        <?php foreach ($groups as $group) : ?>
                            <td>
                                <input type="checkbox" name="access[]" <?php echo check_access($cat, $group, $access) ?> value="<?php echo $cat['id'].".".$group->id ?>">
                            </td>
                        <?php endforeach ?>
                    </tr>
                    <?php endforeach ?>
            </tbody>
        </table>

        <input type="submit" name="set_access" value="Save Settings" class="button button-primary button-large padlock_save" />    
    </form>
</div>