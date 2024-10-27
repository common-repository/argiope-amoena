<?php
/*
Plugin Name: Argiope amoena
Version: 0.3.6
Plugin URI: https://wordpress.org/plugins/argiope-amoena/ 
Description: Automatically upload media files to Amazon S3. Also change the link in the post to the URL of S3. This plugin is based on the Nephila clavata. Ajax campatibility and a few other modifications are added.
Author: Hidetoshi Fukushima
Author URI: https://www.mediwill.jp/
Text Domain: argiope-amoena
Domain Path: /languages
License: GPL2
 
Argiope amoena is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Argiope amoena is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Argiope amoena. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html.
*/

if ( !class_exists('Argiope_S3_Helper') )
	require(dirname(__FILE__).'/includes/class-argiope-s3-helper.php');
if ( !class_exists('Argiope_Admin') )
	require(dirname(__FILE__).'/includes/class-argiope-admin.php');
if ( !class_exists('Argiope_Amoena') )
	require(dirname(__FILE__).'/includes/class-argiope-amoena.php');

load_plugin_textdomain(Argiope_Amoena::TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

$argiope_amoena = Argiope_Amoena::get_instance();
$argiope_amoena->init(Argiope_Admin::get_option());
$argiope_amoena->add_hook();

if (is_admin()) {
	$argiope_admin = Argiope_Admin::get_instance();
	$argiope_admin->init();
	$argiope_admin->add_hook();
}
