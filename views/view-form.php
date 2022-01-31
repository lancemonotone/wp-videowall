<?php 
/**
 * @package Williams_Meerkat_Videowall
 */
?>
<p xmlns="http://www.w3.org/1999/html"><strong>Please note:</strong> This widget is not formatted for use in the sidebar.</p>
<p class="wms_widget_text acfi">
    For best image quality and fastest load-time, the thumbnail for each video in the designated Youtube playlist should be 640w x 480h.
</p>
<p>&nbsp;</p>
<p class="wms_widget_text">
	<label for="<?php echo $this->get_field_name( 'widget_title' ); ?>"><?php _e( 'Title' )?>
	   <input style="display: block !important;" size="50" class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" value="<?php echo $instance['widget_title']; ?>" />
	</label>
</p><!--

<p class="wms_widget_text">
	<label for="<?php /*echo $this->get_field_name( 'tag_list' ); */?>"><?php /*_e( 'Tag Filters (comma-separated with exact spelling)' )*/?>
	   <input class="widefat" id="<?php /*echo $this->get_field_id( 'tag_list' ); */?>" name="<?php /*echo $this->get_field_name( 'tag_list' ); */?>" type="text" value="<?php /*echo $instance['tag_list']; */?>" />
	</label>
</p>

<p class="wms_widget_text">
	<label for="<?php /*echo $this->get_field_name( 'username' ); */?>"><?php /*_e( 'YouTube Username' )*/?>
	   <input class="widefat" id="<?php /*echo $this->get_field_id( 'username' ); */?>" name="<?php /*echo $this->get_field_name( 'username' ); */?>" type="text" value="<?php /*echo $instance['username']; */?>" />
	</label>
</p>

<p class="wms_widget_text">
	<label for="<?php /*echo $this->get_field_name( 'password' ); */?>"><?php /*_e( 'YouTube Password' )*/?>
	   <input class="widefat" id="<?php /*echo $this->get_field_id( 'password' ); */?>" name="<?php /*echo $this->get_field_name( 'password' ); */?>" type="password" value="<?php /*echo $instance['password']; */?>" />
	</label>
</p>-->

<p class="wms_widget_text">
	<label for="<?php echo $this->get_field_name( 'playlist_id' ); ?>"><?php _e( 'YouTube Playlist ID' )?> <small><?php _e('e.g.: PLsRNoUx8w3rNhkOfRBau4qOeHXVMm92NB')?></small>
		<input style="display: block !important;" size="50" class="widefat videowall-playlist-id" id="<?php echo $this->get_field_id( 'playlist_id' ); ?>" name="<?php echo $this->get_field_name( 'playlist_id' ); ?>" type="text" value="<?php echo $instance['playlist_id']; ?>" />
	</label>
</p><!--

<p class="wms_widget_text">
	<label for="<?php /*echo $this->get_field_name( 'video_size' ); */?>"><?php /*_e( 'Video Size' )*/?>
		<select class="widefat" name="<?php /*echo $this->get_field_name( 'video_size' )*/?>" id="<?php /*echo $this->get_field_id( 'video_size' )*/?>>">
			<?php
/*			$options = array('auto' => __('Auto'), '240p' => __('240p'),'360p' => __('360p'),'480' => __('480p'));
			foreach ($options as $option => $value) {*/?>
				<option value="<?php /*echo $option*/?>" id="<?php /*echo $option*/?>" <?php /*selected( $instance['video_size'], $option ); */?>><?php /*echo $value*/?></option>;
			<?php /*} */?>
		</select>
	</label>
</p>

<p class="wms_widget_checkbox">
  	<input id="<?php /*echo $this->get_field_id('hide_tags'); */?>" name="<?php /*echo $this->get_field_name('hide_tags'); */?>" type="checkbox" value="1" <?php /*checked( '1', $instance['hide_tags'] ); */?>/>
	<label for="<?php /*echo $this->get_field_name('hide_tags'); */?>"><?php /*_e('Hide Filters'); */?>?</label>
</p>-->