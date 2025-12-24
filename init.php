<?php
/**
 * GW Core - General Bootstrap
 *
 * Here we automatically load all core modules.
 * Each module lives in its own folder within /gw-core/
 * and can contain its own PHP, JS and CSS files.
 * 
 * Updated for Github auto-updater.
 * 
 * @package GW Core
 * @version 1.2.0
 * @author Luigi Libet
 * @link https://github.com/LuigiLibet/gw-core
 * @license GPL-2.0+
 * @copyright 2025 Luigi Libet
 */

/* ---------------------------------------------------------
 * 1) Auto-load ALL PHP modules
 * --------------------------------------------------------- */

 $core_dir = __DIR__;

 $modules = glob( $core_dir . '/*', GLOB_ONLYDIR );
 
 foreach ( $modules as $module_path ) {
     // If the module has a main PHP file, we include it
     $php_files = glob( $module_path . '/*.php' );
     foreach ( $php_files as $php_file ) {
         require_once $php_file;
     }
 }
 
 
 /* ---------------------------------------------------------
  * 2) Register CSS and JS for all modules automatically
  * --------------------------------------------------------- */
 
 add_action( 'enqueue_block_editor_assets', function() use ( $modules ) {
     foreach ( $modules as $module_path ) {
 
         $module_name = basename( $module_path );
         $base_url = get_stylesheet_directory_uri() . '/components/gw-core/' . $module_name;
 
         // JS
         $js = $module_path . '/' . $module_name . '.js';
         if ( file_exists( $js ) ) {
             wp_enqueue_script(
                 'gw-core-' . $module_name . '-js',
                 $base_url . '/' . $module_name . '.js',
                 [ 'wp-blocks', 'wp-element', 'wp-editor' ],
                 GW_CORE_VERSION,
                 true
             );
         }
 
         // CSS
         $css = $module_path . '/' . $module_name . '.css';
         if ( file_exists( $css ) ) {
             wp_enqueue_style(
                 'gw-core-' . $module_name . '-css',
                 $base_url . '/' . $module_name . '.css',
                 [],
                 GW_CORE_VERSION
             );
         }
     }
 });
 
 
 add_action( 'wp_enqueue_scripts', function() use ( $modules ) {
     foreach ( $modules as $module_path ) {
 
         $module_name = basename( $module_path );
         $base_url = get_stylesheet_directory_uri() . '/components/gw-core/' . $module_name;
 
         $css = $module_path . '/' . $module_name . '.css';
         if ( file_exists( $css ) ) {
             wp_enqueue_style(
                 'gw-core-' . $module_name . '-css',
                 $base_url . '/' . $module_name . '.css',
                 [],
                 GW_CORE_VERSION
             );
         }
     }
 });