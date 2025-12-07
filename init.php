<?php
/**
 * GW Core - Bootstrap general
 *
 * Aquí cargamos automáticamente todos los módulos del core.
 * Cada módulo vive en su propia carpeta dentro de /gw-core/
 * y puede contener sus propios archivos PHP, JS y CSS.
 * 
 * Updated for Github auto-updater.
 * 
 * @package GW Core
 * @version 1.1.0
 * @author Luigi Libet
 * @link https://github.com/LuigiLibet/gw-core
 * @license GPL-2.0+
 * @copyright 2025 Luigi Libet
 */

/* ---------------------------------------------------------
 * 1) Auto-cargar TODOS los módulos PHP
 * --------------------------------------------------------- */

 $core_dir = __DIR__;

 $modules = glob( $core_dir . '/*', GLOB_ONLYDIR );
 
 foreach ( $modules as $module_path ) {
     // Si el módulo tiene un archivo PHP principal, lo incluimos
     $php_files = glob( $module_path . '/*.php' );
     foreach ( $php_files as $php_file ) {
         require_once $php_file;
     }
 }
 
 
 /* ---------------------------------------------------------
  * 2) Registrar CSS y JS de todos los módulos automáticamente
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