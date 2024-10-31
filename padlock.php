<?php
/*
  Plugin Name: Padlock
  Plugin URI: http://onesheep.org
  Description: A security plugin to control access to article content
  Version: 2.3
  Author: Jannie Theunissen <jannie@onesheep.org>
  Author URI: http://onesheep.org
  License: GPL2
 */

/*
 * Copyright 2013  OneSheep  (email : hallo@onesheep.org)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * https://codex.wordpress.org/Writing_a_Plugin
 */

class Padlock
{

    const GROUPS_TABLE_NAME = "padlock_groups";
    const MEMBERSHIP_TABLE_NAME = "padlock_membership";
    const ACCESS_TABLE_NAME = "padlock_access";
    const GROUPS_SLUG = "user_permission_groups";
    const ACCESS_SLUG = "group_access_settings";
    const OPTIONS_KEY = "padlock_options";

    private static $default_options = array(
        'secure_site' => false,
        'restricted_template' => '404.php'
    );

    /**
     * A few filters are necessary to restrict access and they all need to 
     * do the same resource-hungry test to decide if a user has permission
     * to see content. This variable cache the test result.
     * 
     * @var boolean 
     */
    public $hide_content = null;

    public function __construct()
    {
        /* add the two admin menu items */
        add_action('admin_menu', array(&$this, 'add_menu'));

        /* filters to block restricted categories, content and comments */
        add_filter('list_terms_exclusions', array(&$this, 'filter_cats'), 10, 2);  // priority 10, num of args 2
        add_filter('template_include', array(&$this, 'filter_template'), 10, 1);

        /* filter to limit search results and such. 
         * http://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts
         * (http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where)
         */
        add_action('pre_get_posts', array(&$this, 'search_filter'));

        /* setup and cleanup */
        $basename = plugin_basename(__FILE__);
        add_action('activate_' . $basename, array($this, 'activate'));
        add_action('deactivate_' . $basename, array($this, 'deactivate'));
    }

// <editor-fold defaultstate="collapsed" desc="plugin activation">

    public function activate()
    {
        /* set up the tables */
        global $wpdb;

        $table_name = $wpdb->prefix . self::GROUPS_TABLE_NAME;
        $sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    name tinytext NOT NULL,
                    UNIQUE KEY id (id)
                    );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        /*
         * dbDelta handles schema migrations. 
         * In this case, if the tables doesn't exists it will create it
         */
        dbDelta($sql);

        $table_name = $wpdb->prefix . self::MEMBERSHIP_TABLE_NAME;
        $sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    user_id mediumint(9) NOT NULL,
                    group_id mediumint(9) NOT NULL,
                    UNIQUE KEY id (id)
                    );";

        dbDelta($sql);

        $table_name = $wpdb->prefix . self::ACCESS_TABLE_NAME;
        $sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    term_id mediumint(9) NOT NULL,
                    group_id mediumint(9) NOT NULL,
                    UNIQUE KEY id (id)
                    );";

        dbDelta($sql);

        /*
         * register some options
         */
        $options = get_option(self::OPTIONS_KEY);
        if ($options === false)
        {
            $options = Padlock::$default_options;
        }

        update_option(self::OPTIONS_KEY, $options);
    }

    public function deactivate()
    {
        // remove site_lock
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="access story">
    public function do_access()
    {
        global $wpdb;

        /* get the view data */

        $table_name = $wpdb->prefix . self::GROUPS_TABLE_NAME;
        $groups = $wpdb->get_results("
                SELECT id, name 
                FROM $table_name
                ORDER BY id
                ");

        $categories = $this->get_ordered_taxonomy();

        $table_name = $wpdb->prefix . self::ACCESS_TABLE_NAME;
        $access = $wpdb->get_results("
                SELECT term_id, group_id 
                FROM $table_name
                ");

        $options = get_option(self::OPTIONS_KEY);
        $site_is_private = ($options['secure_site']) ? "checked" : "";
        $restricted_template = $options['restricted_template'];

        $this->get_the_styles();

        /* call the view */

        include plugin_dir_path(__FILE__) . "views/access.php";
    }

    private function get_ordered_taxonomy()
    {
        global $wpdb;

        /* get all the taxonomies */
        $taxonomies = $wpdb->get_col("
                SELECT DISTINCT taxonomy 
                FROM $wpdb->term_taxonomy
                ");

        include plugin_dir_path(__FILE__) . "taxonomy_walker.php";
        $taxonomy_walker = new Taxonomy_Walker();

        /*
         * http://http://codex.wordpress.org/Template_Tags/wp_list_categories
         */
        $args = array(
            'type' => 'post',
            'hide_empty' => 0,
            'hierarchical' => 1, // include sub-categories that are empty, as long as those sub-categories have sub-categories 
            'taxonomy' => 'category',
            'orderby' => 'id', // id, name, slug, count, term_group
            'walker' => $taxonomy_walker,
            'echo' => 0,
            'style' => 'none',
        );

        $collection = "";
        foreach ($taxonomies as $taxonomy)
        {
            $args['taxonomy'] = $taxonomy;
            $collection .= wp_list_categories($args);
        }
        $items = array();
        foreach (explode(Taxonomy_Walker::DELIMITER . Taxonomy_Walker::DELIMITER,
                trim($collection, Taxonomy_Walker::DELIMITER)) as $set)
        {
            list($class, $id, $name) = explode(Taxonomy_Walker::DELIMITER, $set);
            $items[] = array('class' => $class, 'id' => $id, 'name' => $name);
        }

        return $items;
    }

    public function set_access()
    {
        /* was a form posted with the set_access button? */
        if (!isset($_POST['set_access']))
            return;

        global $wpdb;

        /* remove all previous memberships */
        $table_name = $wpdb->prefix . self::ACCESS_TABLE_NAME;
        $wpdb->query("
                        DELETE FROM $table_name
                        ");

        /* add the new access permissions */
        foreach ($_POST['access'] as $set)
        {
            list($term, $group) = explode(".", $set);
            $wpdb->query($wpdb->prepare("
                    INSERT INTO $table_name
                    ( term_id, group_id )
                    VALUES ( %d, %d )",
                            $term, $group
            ));
        }
    }

    public function do_site_privacy()
    {
        /* was a form posted with the site_private button? */
        if (!isset($_POST['site_private']))
            return;

        $options['secure_site'] = ($_POST['make_private'] == 1);
        $options['rectricted_template'] = $_POST['restricted_template'];
        update_option(self::OPTIONS_KEY, $options);
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="groups story">

    public function do_groups()
    {
        global $wpdb;

        /* get the $users, $groups and $membership */
        $level_key = $wpdb->prefix . "user_level";

        $users = $wpdb->get_results("
                SELECT u.ID, u.display_name, um.meta_value 
                FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um
                ON (u.ID = um.user_id)
                where um.meta_key = '$level_key'
                ORDER BY display_name
                ");

        $table_name = $wpdb->prefix . self::GROUPS_TABLE_NAME;
        $groups = $wpdb->get_results("
                SELECT id, name 
                FROM $table_name
                ORDER BY id
                ");

        $table_name = $wpdb->prefix . self::MEMBERSHIP_TABLE_NAME;
        $membership = $wpdb->get_results("
                SELECT user_id, group_id 
                FROM $table_name
                ");

        $this->get_the_styles();
        $this->get_the_javascript();


        /* call the view */
        include plugin_dir_path(__FILE__) . "views/groups.php";
    }

    public function add_group()
    {
        /* was a form posted with the add_new button? */
        if (!isset($_POST['add_new']))
            return;

        if (empty($_POST['new_group']))
            return;

        global $wpdb;

        // TODO: validate $_POST['new_group'] (for duplicates)
        $table_name = $wpdb->prefix . Padlock::GROUPS_TABLE_NAME;
        $wpdb->query($wpdb->prepare("
                    INSERT INTO $table_name
                    ( name )
                    VALUES ( %s )",
                        $_POST['new_group']
        ));
    }

    public function set_membership()
    {
        /* was a form posted with the assignment button? */
        if (!isset($_POST['assignment']))
            return;

        global $wpdb;

        /* remove all previous memberships */
        $table_name = $wpdb->prefix . Padlock::MEMBERSHIP_TABLE_NAME;
        $wpdb->query("
                        DELETE FROM $table_name
                        ");

        /* add the new memberships */
        $memberships = isset($_POST['membership']) ? $_POST['membership'] : array();
        foreach ($memberships as $set)
        {
            list($user, $group) = explode(".", $set);
            $wpdb->query($wpdb->prepare("
                    INSERT INTO $table_name
                    ( user_id, group_id )
                    VALUES ( %d, %d )",
                            $user, $group
            ));
        }
    }

    public function zap_group()
    {

        /* was a form posted with the zap_last button? */
        if (!isset($_POST['zap_last']))
            return;

        global $wpdb;

        $table_name = $wpdb->prefix . Padlock::GROUPS_TABLE_NAME;

        /* find the last added group */
        $last_group = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");

        /* now delete it */
        if ($last_group)
        {
            $wpdb->query("
                        DELETE FROM $table_name
                        WHERE id = $last_group->id
                        ");
        }
    }

    public static function rename_group()
    {
        /* is there a post with a new group name? */
        if (!isset($_POST['groupName']))
            return;

        /* ajax call, so get an instance of the database */
        require_once '../../../wp-config.php';
        require_once '../../../wp-includes/wp-db.php';
        $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

        /* TODO: do some validation */
        $wpdb->set_prefix($table_prefix);
        $table_name = $table_prefix . Padlock::GROUPS_TABLE_NAME;
        $new_name = trim($_POST['groupName']);
        $group_id = $_POST['groupId'];

        $sql = "
              UPDATE $table_name
              SET name = '$new_name'
              WHERE id = $group_id";

        /* now rename it */
        try
        {
            $wpdb->query($sql);  // $wpdb->prepare() not available in ajax context
        }
        catch (Exception $exc)
        {
            die(json_encode(array('feedback' => 'fail')));
        }

        die(json_encode(array('feedback' => 'OK', 'newname' => $new_name)));
    }

// </editor-fold>
// <editor-fold desc="site lock story">
    public function lock_site($wp)
    {
        
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="utility methods">
    private function get_the_styles()
    {
        wp_register_style("padlock.css",
                plugins_url('/assets/padlock.css', __FILE__));
        wp_enqueue_style('padlock.css');
    }

    private function get_the_javascript()
    {
        $handle = "GROUP_PAGE_JS";
        $src = plugins_url('/assets/padlock.js', __FILE__);
        wp_register_script($handle, $src, false, null, true);
        wp_enqueue_script($handle);
    }

// </editor-fold>
// <editor-fold desc="filtering search results">

    private function user_may_see_these()
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        $padlock_membership = $wpdb->prefix . self::MEMBERSHIP_TABLE_NAME;
        $padlock_access = $wpdb->prefix . self::ACCESS_TABLE_NAME;

        return $wpdb->get_col("
            SELECT r.object_id
            FROM $wpdb->term_relationships r
            WHERE r.term_taxonomy_id IN (
                SELECT a.term_id 
                FROM $padlock_access a  
                WHERE a.group_id IN (
                    SELECT g.group_id 
                    FROM $padlock_membership g 
                    WHERE user_id = $current_user->ID 
                    )
                )
            ");
    }

    public function search_filter($query)
    {

        if (is_super_admin() || ($query->is_main_query() == false))
            return;

        if ($query->is_search)
        {
            $list = $this->user_may_see_these();
            $query->set('post__in', $list);
        }
    }

// </editor-fold>
// <editor-fold desc="article filter">

    private function is_restricted()
    {
        /* check if the test result is cached */
        if (!is_null($this->hide_content))
        {
            return $this->hide_content;
        }

        if (is_super_admin() || !is_singular() || is_front_page())
        {
            $this->hide_content = false;
            return false;
        }

        global $post;  // $post->ID
        $this->hide_content = !in_array($post->ID, $this->user_may_see_these());
        return $this->hide_content;
    }

    public function filter_template($template)
    {

        if ($this->is_restricted())
        {
            $options = get_option(self::OPTIONS_KEY);
            return locate_template($options['restricted_template']);
        }
        return $template;
    }

    public function filter_cats($where, $args)
    {
        /*
         * is_admin() adminback-end is being displayed
         * is_super_admin() the user has complete admin rights 
         */
        if (is_super_admin())
        {
            return $where;
        }
        global $wpdb;
        $current_user = wp_get_current_user();

        $padlock_membership = $wpdb->prefix . self::MEMBERSHIP_TABLE_NAME;
        $padlock_access = $wpdb->prefix . self::ACCESS_TABLE_NAME;
        $terms = $wpdb->get_col("
            SELECT DISTINCT term_id
            FROM $padlock_access
            WHERE group_id IN (
                SELECT group_id 
                FROM $padlock_membership
                WHERE user_id = $current_user->ID
                )
                ");

        $can_see = implode(',', $terms);
        return $where . " AND t.term_id IN ($can_see)";
    }

// </editor-fold>
// <editor-fold desc="hook handlers">

    public function add_menu()
    {
        /*
         * https://codex.wordpress.org/Adding_Administration_Menus
         */
        if (!is_super_admin())
            return;

        add_submenu_page('users.php', 'User Groups', 'Groups', 'read',
                self::GROUPS_SLUG, array(&$this, 'do_groups'));
        add_submenu_page('options-general.php', 'Access Settings', 'Access',
                'read', self::ACCESS_SLUG, array(&$this, 'do_access'));
    }

// </editor-fold>
}

/* static ajax functions */
Padlock::rename_group();

/* instantiate the plugin class */
$padlock = new Padlock();

/* Handle requests */
$padlock->add_group();
$padlock->zap_group();
$padlock->set_membership();
$padlock->set_access();
$padlock->do_site_privacy();

/**
 * uninstall hook - remove options
 */
register_uninstall_hook(__FILE__, 'padlock_uninstall');

function padlock_uninstall()
{
    /* remove the plugin tables */
    global $wpdb;
    $wpdb->query("DROP TABLE " . $wpdb->prefix . Padlock::ACCESS_TABLE_NAME);
    $wpdb->query("DROP TABLE " . $wpdb->prefix . Padlock::MEMBERSHIP_TABLE_NAME);
    $wpdb->query("DROP TABLE " . $wpdb->prefix . Padlock::GROUPS_TABLE_NAME);

    /* remove the plugin settings from the global options table */
    delete_option(Padlock::OPTIONS_KEY);
}

/**
ISSUES:
* 
* RSS feeds security
*/