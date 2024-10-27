<?php

if( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
	exit();

if ( !class_exists('Argiope_Admin') )
	require(dirname(__FILE__).'/includes/class-argiope-admin.php');
if ( !class_exists('Argiope_Amoena') )
	require(dirname(__FILE__).'/includes/class-argiope-amoena.php');

global $wpdb;

delete_option(Argiope_Admin::OPTION_KEY);

$sql = $wpdb->prepare(
	"delete from {$wpdb->postmeta} where meta_key in (%s, %s)",
	Argiope_Amoena::META_KEY,
	Argiope_Amoena::META_KEY.'-replace'
	);
$wpdb->query($sql);
