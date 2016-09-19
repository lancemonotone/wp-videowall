<?php
/**
 * @package Williams_Meerkat_Videowall
 * @uses TinyMCE code from Black Studio TinyMCE Widget (url: http://www.blackstudio.it/en/wordpress-plugins/black-studio-tinymce-widget/)
 * 
 * @todo Fix sidebar formatting.  Isotope filtering dynamically sets width of items wider than width of sidebar.  Is there a way to prevent this?
 */

add_action('widgets_init', create_function('', 'return register_widget("Meerkat_Videowall_Widget");'));

class Meerkat_Videowall_Widget extends WP_Widget {
	
	var $defaults = array(
        'widget_title'      => '', 
        'playlist_id'       => '',
        'username'          => '',
        'password'          => '',
        'developer_key'     => 'AIzaSyDYjUnAka5zAWbA7JZI0ccFmKrEp3QX9q8', // Williams College
        'tag_list'          => '',
        'hide_tags'         => 0,
        'hide_intro'  => 0,
        'content'           => '',
		'link'              => '',
		'linktarget'        => '',
		'width'             => 0,
		'height'            => 0,
		'image'             => 0, // reverse compatible - now attachment_id
		'imageurl'          => '', // reverse compatible.
		'align'             => 'none',
		'alt'               => '',
    );
    
    var $widgetname = 'Meerkat_Videowall_Widget';
    var $namespace  = 'meerkat_videowall';
	var $classname  = 'meerkat-videowall';
	var $version    = "2.0.0";
	    
	function __construct(){
		require_once WMS_EXT_LIB . '/google-api-php-client/src/Google/autoload.php';
		$description      = 'Accepts a Youtube playlist ID and outputs an interactive "wall" of videos';
        $label            =  WMS_WIDGET_PREFIX . 'Williams Videowall';
        $widget_ops  = array('classname' => $this->classname. ' cf', 'description' => __($description) );
        $control_ops = array('width' => WMS_WIDGET_WIDTH + 100, 'height' => WMS_WIDGET_HEIGHT);
		
		parent::__construct( 
			$this->namespace, 
			_($label),
			$widget_ops,
			$control_ops
		);
		$this->add_hooks();
	}
	
	/**
     * Add in various hooks
     * 
     * Place all add_action, add_filter, add_shortcode hook-ins here
     */
	function add_hooks(){
		// Register front-end js and styles for this plugin
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_register_scripts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_register_styles' ), 1 );

        // Register admin js and styles for this plugin
        add_action( 'admin_head', array( &$this, 'wp_register_scripts' ), 1 );
        add_action( 'admin_head', array( &$this, 'wp_register_styles' ), 1 );
        
        // Get video tags to choose filters in widget admin
        add_action('wp_ajax_get_playlist_tags', array( &$this, 'get_playlist_tags_callback' ));

        // Add Shortcode for widget
		add_shortcode('wms_videowall', array(&$this, 'meerkat_videowall_shortcode'));
		
		// Image Widget Uploader
		if ( $this->use_old_uploader() ) {
			new Videowall_Image_Widget_Deprecated( $this );
		} else {
			add_action( 'sidebar_admin_setup', array( $this, 'image_widget_setup' ) );
		}
	}
	
	/** VIDEOWALL
     * Register scripts used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_script()
     */
    function wp_register_scripts() {
        // load fancybox for youtube
        global $js;
        $js['fancybox-media']['load'] = true;
                
        $name = $this->classname.'-widget';
        if(!is_admin()){
	        wp_enqueue_script( $name.'-isotope', MEERKATVIDEOWALL_URLPATH . '/js/jquery.isotope.min.js', array( 'jquery' ), $this->version, true );
	        wp_enqueue_script( $name.'-perfectmasonry', MEERKATVIDEOWALL_URLPATH . '/js/jquery.isotope.perfectmasonry.js', array( 'jquery', $name.'-isotope' ), $this->version, true );
	        wp_enqueue_script( $name.'-mCustomScrollbar', MEERKATVIDEOWALL_URLPATH . '/js/jquery.mCustomScrollbar.concat.min.js', array( 'jquery' ), $this->version, true );
	        wp_enqueue_script( $name, MEERKATVIDEOWALL_URLPATH . '/js/widget.js', array( 'jquery', $name.'-perfectmasonry', $name.'-mCustomScrollbar' ), $this->version, true );
        }else{
            global $pagenow;
            if ($pagenow == "widgets.php") {
                wp_enqueue_script( $name.'-admin', MEERKATVIDEOWALL_URLPATH . '/js/admin.js', array( 'jquery' ), $this->version, true );
                wp_localize_script( $name.'-admin', 'videowallAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));  
            }
        }
    }
    
    /**
     * Register styles used by this plugin for enqueuing elsewhere
     * 
     * @uses wp_register_style()
     */
    function wp_register_styles() {
        // Admin Stylesheet
        $name = $this->classname.'-widget';
        if(!is_admin()){
	        wp_enqueue_style( $name, MEERKATVIDEOWALL_URLPATH . '/css/widget.css', array(), $this->version, 'screen' );
	        wp_enqueue_style( $name.'-mCustomScrollbar', MEERKATVIDEOWALL_URLPATH . '/css/jquery.mCustomScrollbar.css', array($name), $this->version, 'screen' );
	    }else{
        	wp_enqueue_style( $name.'-admin', MEERKATVIDEOWALL_URLPATH . '/css/admin.css', array(), $this->version, 'screen' );
        }
    }
	
    
    /**
     * Widget Display
     *
     * @param Array $args Settings
     * @param Array $instance Widget Instance
     */
	function widget( $args, $instance ) {
		extract( $args );
	    
	    echo $before_widget;
	    
	    //Set up some default widget settings.
    	$defaults = $this->defaults;
    	$instance = wp_parse_args( (array) $instance, $defaults ); 
	    extract($instance);
	    
	    echo $before_title . $instance['widget_title'] . $after_title;
		
		require(MEERKATVIDEOWALL_DIRNAME.'/views/view-widget.php');
	
	    echo $after_widget;
	}
	
	/**
	 * Widgets page form submission logic
	 *
	 * @param Array $new_instance
	 * @param Array $old_instance
	 * @return unknown
	 */
	function update( $new_instance, $old_instance ) {
	    global $Meerkat_Videowall;
	    
		// Reverse compatibility with $image, now called $attachement_id
		if ( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) && $new_instance['attachment_id'] > 0 ) {
			$new_instance['attachment_id'] = abs( $new_instance['attachment_id'] );
		} elseif ( $new_instance['image'] > 0 ) {
			$new_instance['attachment_id'] = $new_instance['image'] = abs( $new_instance['image'] );
			if ( class_exists('Videowall_Image_Widget_Deprecated') ) {
				$new_instance['imageurl'] = Videowall_Image_Widget_Deprecated::get_image_url( $instance['image'], $instance['width'], $instance['height'] );  // image resizing not working right now
			}
		}

		$data['aspect_ratio'] = $this->image_widget_get_image_aspect_ratio( $instance );
	    
	    foreach( $new_instance as $key => $val ) {
	        $data[$key] = $Meerkat_Videowall->_sanitize( $val );
	    }
	    delete_transient('videowall_cached_' . $new_instance['playlist_id']);
		return $data;
	}

	/**
	 * Widgets page form controls
	 *
	 * @param Array $instance
	 */
	function form( $instance ) {
	
    	//Set up some default widget settings.
    	$defaults = $this->defaults;
    	if( !defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) {
			$defaults['attachment_id'] = 0;
			$defaults['size'] = 0;
		}
    	$instance = wp_parse_args( (array) $instance, $defaults ); 
    	
    	require('views/view-form.php');
	}
	
	function get_playlist_tags_callback(){
	    $instance = $_POST;
	    $instance['developer_key'] = $this->defaults['developer_key'];
	    $mvw = new MeerkatVideowallHelper($instance);
	    $mvw->get_video_feed();?>
	     <ul>
	    <?php foreach($mvw->video_feed as $video){?>
	        <li><?php echo $video->getVideoTags();?></li>
	    <?php } ?>
	    </ul>
	    <?php
	    die();
	}
    
	/**
	 * Widget shortcode
	 *
	 * @param Array $atts
	 * @return String Widget HTML
	 */
	function meerkat_videowall_shortcode($atts) {
	    static $widget_i = 0;
	    global $wp_widget_factory;
	    
	    $defaults = shortcode_atts($this->defaults, $atts);
	    
	    $instance = wp_parse_args( (array) $instance, $defaults ); 
	    
	    $widget_name = wp_specialchars($widget_name);
	    
	    if (!is_a($wp_widget_factory->widgets[$this->widgetname], $this->widgetname)){
	        $wp_class = 'WP_Widget_'.ucwords(strtolower($class));
	        
	        if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')){
	            return '<p>'.sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"),'<strong>'.$class.'</strong>').'</p>';
	        } else {
	            $class = $wp_class;
	        }
	    }
	    
	    ob_start();
	    
	    the_widget($this->widgetname, $instance, array(
	    	'widget_id'     => $this->classname.'-'.$widget_i,
	        'before_widget' => '<div id="'.$this->namespace.'-'.$widget_i++.'" class="'.$this->classname.' cf">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="title">',
			'after_title'   => '</h2>'
	    ));
	    
	    return ob_get_clean();
	    
	}
	
	
	
	/**  IMAGE WIDGET UPLOADER
	 * Test to see if this version of WordPress supports the new image manager.
	 * @return bool true if the current version of WordPress does NOT support the current image management tech.
	 */
	private function use_old_uploader() {
		if ( defined( 'IMAGE_WIDGET_COMPATIBILITY_TEST' ) ) return true;
		return !function_exists('wp_enqueue_media');
	}
	
	/**
	 * For new image manager. Enqueue all the javascript.
	 */
	function image_widget_setup() {
		wp_enqueue_media();
		wp_enqueue_script( 'tribe-image-widget', MEERKATVIDEOWALL_URLPATH . '/js/image-widget.js', array( 'jquery', 'media-upload', 'media-views' ), $this->version );

		wp_localize_script( 'tribe-image-widget', 'TribeImageWidget', array(
			'frame_title' => __( 'Select an Image', 'image_widget' ),
			'button_title' => __( 'Insert Into Widget', 'image_widget' ),
		) );
	}

	/**
	 * Render the image html output.
	 *
	 * @param array $instance
	 * @param bool $include_link will only render the link if this is set to true. Otherwise link is ignored.
	 * @return string image html
	 */
	public function image_widget_get_image_html( $instance, $include_link = true ) {

		// Backwards compatible image display.
		if ( $instance['attachment_id'] == 0 && $instance['image'] > 0 ) {
			$instance['attachment_id'] = $instance['image'];
		}

		$output = '';

		if ( $include_link && !empty( $instance['link'] ) ) {
			$attr = array(
				'href' => $instance['link'],
				'target' => $instance['linktarget'],
				//'class' => 	$this->widget_options['classname'].'-image-link',
				'title' => ( !empty( $instance['alt'] ) ) ? $instance['alt'] : $instance['title'],
			);
			$attr = apply_filters('image_widget_link_attributes', $attr, $instance );
			$attr = array_map( 'esc_attr', $attr );
			$output = '<a';
			foreach ( $attr as $name => $value ) {
				$output .= sprintf( ' %s="%s"', $name, $value );
			}
			$output .= '>';
		}

		$size = $this->image_widget_get_image_size( $instance );
		if ( is_array( $size ) ) {
			$instance['width'] = $size[0];
			$instance['height'] = $size[1];
		} elseif ( !empty( $instance['attachment_id'] ) ) {
			//$instance['width'] = $instance['height'] = 0;
			$image_details = wp_get_attachment_image_src( $instance['attachment_id'], $size );
			if ($image_details) {
				$instance['imageurl'] = $image_details[0];
				$instance['width'] = $image_details[1];
				$instance['height'] = $image_details[2];
			}
		}
		$instance['width'] = abs( $instance['width'] );
		$instance['height'] = abs( $instance['height'] );

		$attr = array();
		$attr['alt'] = $instance['title'];
		if (is_array($size)) {
			$attr['class'] = 'attachment-'.join('x',$size);
		} else {
			$attr['class'] = 'attachment-'.$size;
		}
		$attr['style'] = '';
		if ($instance['width']) {
			//$attr['style'] .= "max-width: {$instance['width']}px;";
			$attr['style'] .= "max-width: 95%;";
		}
		if ($instance['height']) {
			//$attr['style'] .= "max-height: {$instance['height']}px;";
			$attr['style'] .= "max-height: auto;";
		}
		if (!empty($instance['align']) && $instance['align'] != 'none') {
			$attr['class'] .= " align{$instance['align']}";
		}
		$attr = apply_filters( 'image_widget_image_attributes', $attr, $instance );

		// If there is an imageurl, use it to render the image. Eventually we should kill this and simply rely on attachment_ids.
		if ( !empty( $instance['imageurl'] ) ) {
			// If all we have is an image src url we can still render an image.
			$attr['src'] = $instance['imageurl'];
			$attr = array_map( 'esc_attr', $attr );
			$hwstring = image_hwstring( $instance['width'], $instance['height'] );
			$output .= rtrim("<img $hwstring");
			foreach ( $attr as $name => $value ) {
				$output .= sprintf( ' %s="%s"', $name, $value );
			}
			$output .= ' />';
		} elseif( abs( $instance['attachment_id'] ) > 0 ) {
		    $img_src = wp_get_attachment_image_src($instance['image'],'full');
			//$output .= wp_get_attachment_image($instance['attachment_id'], $size, false, $attr);
			$output .= "<img src=\"$img_src[0]\" alt=\"{$instance['alt']}\" class=\"{$instance['align']}\" />";
		}

		if ( $include_link && !empty( $instance['link'] ) ) {
			$output .= '</a>';
		}

		return $output;
	}

	/**
	 * Assesses the image size in case it has not been set or in case there is a mismatch.
	 *
	 * @param $instance
	 * @return array|string
	 */
	private function image_widget_get_image_size( $instance ) {
		if ( !empty( $instance['size'] ) ) {
			$size = $instance['size'];
		} elseif ( isset( $instance['width'] ) && is_numeric($instance['width']) && isset( $instance['height'] ) && is_numeric($instance['height']) ) {
			$size = array(abs($instance['width']),abs($instance['height']));
		} else {
			$size = 'full';
		}
		return $size;
	}

	/**
	 * Establish the aspect ratio of the image.
	 *
	 * @param $instance
	 * @return float|number
	 */
	private function image_widget_get_image_aspect_ratio( $instance ) {
		if ( !empty( $instance['aspect_ratio'] ) ) {
			return abs( $instance['aspect_ratio'] );
		} else {
			$attachment_id = ( !empty($instance['attachment_id']) ) ? $instance['attachment_id'] : $instance['image'];
			if ( !empty($attachment_id) ) {
				$image_details = wp_get_attachment_image_src( $attachment_id, 'full' );
				if ($image_details) {
					return ( $image_details[1]/$image_details[2] );
				}
			}
		}
	}

	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme,
	 * 2) parent template, 3) plugin resources. will look in the image-widget/
	 * directory in a theme and the views/ directory in the plugin
	 *
	 * @param string $template template file to search for
	 * @return template path
	 * @author Modern Tribe, Inc. (Matt Wiebe)
	 **/

	function image_widget_getTemplateHierarchy($template) {
		// whether or not .php was added
		$template_slug = rtrim($template, '.php');
		$template = $template_slug . '.php';

		if ( $theme_file = locate_template(array('image-widget/'.$template)) ) {
			$file = $theme_file;
		} else {
			$file = $template;
		}
		return apply_filters( 'sp_template_image-widget_'.$template, $file);
	}
	
}

/**
 * Deprecated image upload integration code to support legacy versions of WordPress
 * @author Modern Tribe, Inc.
 */
class Videowall_Image_Widget_Deprecated {

	private $id_base;

	function __construct( $widget ) {
		add_action( 'admin_init', array( $this, 'admin_setup' ) );
		$this->id_base = $widget->id_base;
	}

	function admin_setup() {
		global $pagenow;
		if ( 'widgets.php' == $pagenow ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'tribe-image-widget', MEERKATVIDEOWALL_URLPATH . '/js/image-widget.deprecated.js', array('thickbox'), $widget->version, TRUE );
		}
		elseif ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {
			wp_enqueue_script( 'tribe-image-widget-fix-uploader', MEERKATVIDEOWALL_URLPATH . '/js/image-widget.deprecated.upload-fixer.js', array('jquery'), $widget->version, TRUE );
			add_filter( 'image_send_to_editor', array( $this,'image_send_to_editor'), 1, 8 );
			add_filter( 'gettext', array( $this, 'replace_text_in_thickbox' ), 1, 3 );
			add_filter( 'media_upload_tabs', array( $this, 'media_upload_tabs' ) );
			add_filter( 'image_widget_image_url', array( $this, 'https_cleanup' ) );
		}
		$this->fix_async_upload_image();
	}

	function fix_async_upload_image() {
		if(isset($_REQUEST['attachment_id'])) {
			$id = (int) $_REQUEST['attachment_id'];
			$GLOBALS['post'] = get_post( $id );
		}
	}

	/**
	 * Test context to see if the uploader is being used for the image widget or for other regular uploads
	 *
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	function is_sp_widget_context() {
		if ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$this->id_base) !== false ) {
			return true;
		} elseif ( isset($_REQUEST['_wp_http_referer']) && strpos($_REQUEST['_wp_http_referer'],$this->id_base) !== false ) {
			return true;
		} elseif ( isset($_REQUEST['widget_id']) && strpos($_REQUEST['widget_id'],$this->id_base) !== false ) {
			return true;
		}
		return false;
	}

	/**
	 * Somewhat hacky way of replacing "Insert into Post" with "Insert into Widget"
	 *
	 * @param string $translated_text text that has already been translated (normally passed straight through)
	 * @param string $source_text text as it is in the code
	 * @param string $domain domain of the text
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	function replace_text_in_thickbox($translated_text, $source_text, $domain) {
		if ( $this->is_sp_widget_context() ) {
			if ('Insert into Post' == $source_text) {
				return __('Insert Into Widget', 'image_widget' );
			}
		}
		return $translated_text;
	}

	/**
	 * Filter image_end_to_editor results
	 *
	 * @param string $html
	 * @param int $id
	 * @param string $alt
	 * @param string $title
	 * @param string $align
	 * @param string $url
	 * @param array $size
	 * @return string javascript array of attachment url and id or just the url
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	function image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {
		// Normally, media uploader return an HTML string (in this case, typically a complete image tag surrounded by a caption).
		// Don't change that; instead, send custom javascript variables back to opener.
		// Check that this is for the widget. Shouldn't hurt anything if it runs, but let's do it needlessly.
		if ( $this->is_sp_widget_context() ) {
			if ($alt=='') $alt = $title;
			?>
		<script type="text/javascript">
			// send image variables back to opener
			var win = window.dialogArguments || opener || parent || top;
			win.IW_html = '<?php echo addslashes($html); ?>';
			win.IW_id = '<?php echo $id; ?>';
			win.IW_alt = '<?php echo addslashes($alt); ?>';
			win.IW_caption = '<?php echo addslashes($caption); ?>';
			win.IW_title = '<?php echo addslashes($title); ?>';
			win.IW_align = '<?php echo esc_attr($align); ?>';
			win.IW_url = '<?php echo esc_url($url); ?>';
			win.IW_size = '<?php echo esc_attr($size); ?>';
		</script>
		<?php
		}
		return $html;
	}

	/**
	 * Remove from url tab until that functionality is added to widgets.
	 *
	 * @param array $tabs
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	function media_upload_tabs($tabs) {
		if ( $this->is_sp_widget_context() ) {
			unset($tabs['type_url']);
		}
		return $tabs;
	}

	/**
	 * Adjust the image url on output to account for SSL.
	 *
	 * @param string $imageurl
	 * @return string $imageurl
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	function https_cleanup( $imageurl = '' ) {
		// TODO: 3.5: Document that this is deprecated???
		if( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ) {
			$imageurl = str_replace('http://', 'https://', $imageurl);
		} else {
			$imageurl = str_replace('https://', 'http://', $imageurl);
		}
		return $imageurl;
	}



	/**
	 * Retrieve resized image URL
	 *
	 * @param int $id Post ID or Attachment ID
	 * @param int $width desired width of image (optional)
	 * @param int $height desired height of image (optional)
	 * @return string URL
	 * @author Modern Tribe, Inc. (Peter Chester)
	 */
	public static function get_image_url( $id, $width=false, $height=false ) {
		/**/
		// Get attachment and resize but return attachment path (needs to return url)
		$attachment = wp_get_attachment_metadata( $id );
		$attachment_url = wp_get_attachment_url( $id );
		if (isset($attachment_url)) {
			if ($width && $height) {
				$uploads = wp_upload_dir();
				$imgpath = $uploads['basedir'].'/'.$attachment['file'];
				if (WP_DEBUG) {
					error_log(__CLASS__.'->'.__FUNCTION__.'() $imgpath = '.$imgpath);
				}
				$image = self::resize_image( $imgpath, $width, $height );
				if ( $image && !is_wp_error( $image ) ) {
					$image = path_join( dirname($attachment_url), basename($image) );
				} else {
					$image = $attachment_url;
				}
			} else {
				$image = $attachment_url;
			}
			if (isset($image)) {
				return $image;
			}
		}
	}

	public static function resize_image( $file, $max_w, $max_h ) {
		if ( function_exists('wp_get_image_editor') ) {
			$dest_file = $file;
			if ( function_exists('wp_get_image_editor') ) {
				$editor = wp_get_image_editor( $file );
				if ( is_wp_error( $editor ) )
					return $editor;

				$resized = $editor->resize( $max_w, $max_h );
				if ( is_wp_error( $resized ) )
					return $resized;

				$dest_file = $editor->generate_filename();
				$saved = $editor->save( $dest_file );

				if ( is_wp_error( $saved ) )
					return $saved;

			}
			return $dest_file;
		} else {
			return image_resize( $file, $max_w, $max_h );
		}
	}

}
?>
