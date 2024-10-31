<?php
function check_membership($user, $group, $m)
{
    $user_id = (int)$user->ID;
    $group_id = (int)$group->id;
    foreach ($m as $entry)
    {
        if (($entry->user_id == $user_id) && ($entry->group_id == $group_id))
        {
            return "checked";
        }
    }
    
    return "";
}

?>

<div id="padlock_groups"  class="wrap">
    <h2>User Groups</h2>
    <form method="post" id="add_group">
        <div>
            Create a new group:
            <input type="text" name="new_group" value="" />
            <input type="submit" name="add_new" value="Add Group" class="button button-primary" />    
        <!--</div>
        <div>-->
            <?php if (count($groups) > 0): ?>
                <input type="submit" name="zap_last" value="Delete Last Group" class="button" />
            <?php endif ?>
        </div>
    </form>
    <h2>User Assignment</h2>
    <form method="post" id="user_assign">
 
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>User</th>
                    <?php foreach ($groups as $group) : ?>
                    <th>
                        <span><?php echo htmlspecialchars($group->name, ENT_COMPAT, "UTF-8"); ?></span>
                        <div class="row-actions" data-gid="<?php echo $group->id; ?>" data-gname="<?php echo $group->name; ?>">
                            <span class="inline hide-if-no-js"><a href="#" class="editinline" title="Rename this group">Rename</a> | </span><span class="trash">Delete</span>
                        </div>
                    </th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?php echo $user->display_name; ?></td>
                        <?php foreach ($groups as $group) : ?>
                            <td>
                                <?php if ($user->meta_value > 7): ?>
                                <q></q>
                                <?php else: ?>
                                <input type="checkbox" name="membership[]" <?php echo check_membership($user, $group, $membership) ?> value="<?php echo "$user->ID.$group->id" ?>">
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
            <?php endforeach ?>
            </tbody>
        </table>

        <input type="submit" name="assignment" value="Save Settings" class="button button-primary button-large padlock_save" />    
    </form>
</div>
