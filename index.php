<?php
/**
 * Plugin Name: Williams Meerkat Videowall
 * Description: Accepts a Youtube playlist ID and outputs an interactive "wall" of videos.
 * Author: Williams College WebOps
 * License: GPL3
 *
 * Copyright 2013 Williams College  (email : webteam@williams.edu)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Include constants file
require_once(dirname(__FILE__) . '/lib/constants.php');
require_once(dirname(__FILE__) . '/lib/wmv-helpers.php');

add_action('widgets_init', function() {
    register_widget('Meerkat_Videowall');
});

class Meerkat_Videowall extends WP_Widget {
    private static $instance;
    var $widgetname;
    var $id_base = 'meerkat_videowall';
    var $classname = 'meerkat-videowall';
    var $version = "2.6.0";

    var $defaults = array(
        'widget_title'  => '',
        'playlist_id'   => '',
        'username'      => '',
        'password'      => '',
        'developer_key' => '', // Williams College
        'tag_list'      => '',
        'hide_tags'     => 0,
        'hide_intro'    => 1,
        'content'       => '',
        'link'          => '',
        'linktarget'    => '',
        'width'         => 0,
        'height'        => 0
    );

    function __construct() {
        $this->add_hooks();
        $this->widgetname = get_class();

        // Name of the option_value to store plugin options in
        //$this->option_name = '_' . $this->classname . '--options';

        if ( ! function_exists('google_api_php_client_autoload')) {
            require_once(WMS_EXT_LIB . '/google-api-php-client/src/Google/autoload.php');
        }

        $description = 'Accepts a Youtube playlist ID and outputs an interactive "wall" of videos';
        $label       = '. Williams Videowall';
        $widget_ops  = array('classname' => $this->classname . ' cf', 'description' => __($description));

        parent::__construct(
            $this->id_base,
            _($label),
            $widget_ops
        );

    }

    /**
     * Add in various hooks
     *
     * Place all add_action, add_filter, add_shortcode hook-ins here
     */
    function add_hooks() {
        // Register front-end js and styles for this plugin
        add_action('wp_enqueue_scripts', array(&$this, 'register_scripts'), 1);
        add_action('wp_enqueue_scripts', array(&$this, 'register_styles'), 1);

        // Register admin js and styles for this plugin
        add_action('admin_head', array(&$this, 'register_admin_scripts'), 1);
        add_action('admin_head', array(&$this, 'register_admin_styles'), 1);

        // Get video tags to choose filters in widget admin
        add_action('wp_ajax_get_playlist_tags', array(&$this, 'get_playlist_tags_callback'));
        add_action('wp_ajax_nopriv_get_playlist_tags', array(&$this, 'get_playlist_tags_callback'));

        // Add Shortcode for widget
        add_shortcode('wms_videowall', array(&$this, 'meerkat_videowall_shortcode'));
    }

    function register_scripts() {
        $name = $this->classname . '-widget';
        wp_enqueue_script($name . '-mCustomScrollbar', MEERKATVIDEOWALL_URLPATH . '/assets/js/jquery.mCustomScrollbar.concat.min.js', array('jquery'), $this->version, true);
        wp_enqueue_script($name, MEERKATVIDEOWALL_URLPATH . '/assets/js/widget.js', array(
            'jquery',
            $name . '-mCustomScrollbar'
        ), $this->version, true);
    }

    /**
     * Register styles used by this plugin for enqueuing elsewhere
     *
     * @uses wp_register_style()
     */
    function register_styles() {
        $name = $this->classname . '-widget';
        wp_enqueue_style($name, MEERKATVIDEOWALL_URLPATH . '/assets/css/style.css', array(), $this->version, 'screen');
        // Hack to support Black Tie icons for non-Meerkat16 themes
        if ( ! class_exists('Meerkat16')) {
            wp_enqueue_style($name . '-blacktie', WMS_LIB_URL . '/assets/fonts/blacktie/black-tie.css', array(), $this->version, 'screen');
        }
    }

    function register_admin_scripts() {
        $name = $this->classname . '-widget';
        global $pagenow;
        if ($pagenow == "widgets.php") {
            wp_enqueue_script($name . '-admin', MEERKATVIDEOWALL_URLPATH . '/assets/js/admin.js', array('jquery'), $this->version, true);
            wp_localize_script($name . '-admin', 'videowallAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        }
    }

    function register_admin_styles() {
        $name = $this->classname . '-widget';
        wp_enqueue_style($name . '-admin', MEERKATVIDEOWALL_URLPATH . '/assets/css/admin.css', array(), $this->version, 'screen');
    }

    /**
     * Widget Display
     *
     * @param array $args Settings
     * @param array $instance Widget Instance
     */
    function widget($args, $instance) {
        echo $args['before_widget'];

        //Set up some default widget settings.
        $defaults = $this->defaults;
        $instance = wp_parse_args((array) $instance, $defaults);

        echo $args['before_title'] . $instance['widget_title'] . $args['after_title'];

        require(MEERKATVIDEOWALL_DIRNAME . '/views/view-widget.php');

        echo $args['after_widget'];
    }

    /**
     * Widgets page form submission logic
     *
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array Updated data
     */
    function update($new_instance, $old_instance) {
        $data = array();

        foreach ($new_instance as $key => $val) {
            $data[ $key ] = self::_sanitize($val);
        }
        delete_transient('videowall_cached_' . $new_instance['playlist_id']);

        return $data;
    }

    /**
     * Widgets page form controls
     *
     * @param array $instance
     */
    function form($instance) {
        //Set up some default widget settings.
        $defaults = $this->defaults;

        $instance = wp_parse_args((array) $instance, $defaults);

        require('views/view-form.php');
    }

    function get_playlist_tags_callback() {
        $instance                  = $_POST;
        $instance['developer_key'] = $this->defaults['developer_key'];
        $mvw                       = new MeerkatVideowallHelper($instance);
        $mvw->get_video_feed(); ?>
        <ul>
            <?php foreach ($mvw->video_feed as $video) { ?>
                <li><?php echo $video->getVideoTags(); ?></li>
            <?php } ?>
        </ul>
        <?php
        die();
    }

    /**
     * Widget shortcode
     *
     * @param array $atts
     *
     * @return String Widget HTML
     */
    function meerkat_videowall_shortcode($atts) {
        static $widget_i = 0;
        global $wp_widget_factory;

        $defaults = shortcode_atts($this->defaults, $atts);

        $instance = wp_parse_args((array) $instance, $defaults);

        if ( ! is_a($wp_widget_factory->widgets[ $this->widgetname ], $this->widgetname)) {
            $wp_class = 'WP_Widget_' . ucwords(strtolower($instance['class']));

            if ( ! is_a($wp_widget_factory->widgets[ $wp_class ], 'WP_Widget')) {
                return '<p>' . sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"), '<strong>' . $wp_class . '</strong>') . '</p>';
            }
        }

        ob_start();

        the_widget($this->widgetname, $instance, array(
            'widget_id'     => $this->classname . '-' . $widget_i,
            'before_widget' => '<div id="' . $this->classname . '-' . $widget_i++ . '" class="' . $this->classname . ' cf">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2 class="title">',
            'after_title'   => '</h2>'
        ));

        return ob_get_clean();
    }

    /**
     * Sanitize data
     *
     * @param mixed $str The data to be sanitized
     *
     * @return mixed The sanitized version of the data
     * @uses wp_kses()
     *
     */
    public static function _sanitize($str) {
        if ( ! function_exists('wp_kses')) {
            require_once(ABSPATH . 'wp-includes/kses.php');
        }
        global $allowedposttags;
        global $allowedprotocols;

        if (is_string($str)) {
            $str = wp_kses($str, $allowedposttags, $allowedprotocols);
        } elseif (is_array($str)) {
            $arr = array();
            foreach ((array) $str as $key => $val) {
                $arr[ $key ] = self::_sanitize($val);
            }
            $str = $arr;
        }

        return $str;
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return  Meerkat_Videowall The singleton instance.
     */
    public static function instance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * singleton instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the singleton
     * instance.
     *
     * @return void
     */
    private function __wakeup() {
    }
}
