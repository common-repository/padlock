<?php

/**
 * taxonomy_walker class file
 *
 * @author Jannie Theunissen <jannie@onesheep.org>
 * @link http://onesheep.org/
 * @copyright Copyright &copy; 2013 OneSheep
 * @since 
 * 
 */
class Taxonomy_Walker extends Walker
{
    const DELIMITER = "|";
    
    public $db_fields = array('parent' => 'parent', 'id' => 'term_id');

    
    /**
     * @see Walker::start_el()
     * @since 2.1.0
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param object $category Category data object.
     * @param int $depth Depth of category. Used for padding.
     * @param array $args Uses 'selected' and 'show_count' keys, if they exist.
     */
    function start_el(&$output, $category, $depth, $args, $id = 0)
    {
        // $pad = str_repeat('&nbsp;', $depth * 3);
        
        /* allow other plugins to do their thing */
        $cat_name = apply_filters('list_cats', $category->name, $category); 
        $output .= self::DELIMITER . self::DELIMITER
                . "level-$depth" . self::DELIMITER
                . "$category->term_id" . self::DELIMITER
                . $cat_name;
    }

}


/* End of {name}.php file */
