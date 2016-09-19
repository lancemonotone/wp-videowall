<?php
/**
 * Constants used by this plugin
 * 
 * @package Williams_Meerkat_Videowall
 * 
 * @author Williams College WebOps
 * @version 1.0.0
 * @since 1.0.0
 */

$file = __FILE__;
// Define these things in case wms-master-widget isn't active.
if(!defined('WMS_WIDGET_PREFIX')) define( 'WMS_WIDGET_PREFIX', '. ');
if(!defined('WMS_WIDGET_WIDTH')) define( 'WMS_WIDGET_WIDTH', 350);
if(!defined('WMS_WIDGET_HEIGHT')) define( 'WMS_WIDGET_HEIGHT', 300);

// The current version of this plugin
if( !defined( 'MEERKATVIDEOWALL' ) ) define( 'MEERKATVIDEOWALL', '1.0.0' );

// The directory the plugin resides in
if( !defined( 'MEERKATVIDEOWALL_DIRNAME' ) ) define( 'MEERKATVIDEOWALL_DIRNAME', dirname( dirname( $file ) ) );

// The URL path of this plugin
if( !defined( 'MEERKATVIDEOWALL_URLPATH' ) ) define( 'MEERKATVIDEOWALL_URLPATH',  plugin_dir_url( '' ) .  plugin_basename( MEERKATVIDEOWALL_DIRNAME ) );

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );