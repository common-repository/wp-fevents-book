<?php
/*
  Plugin Name: WP FEvents Book
  Plugin URI: http://wordpress.org/extend/plugins/wp-fevents-book/
  Description: Create FEvents Book. Use the shortcode <code>[feventsbook]</code> to display all enabled Events, or <code>[feventsbook eventid=x]</code> to display Event with ID=x.
  Version: 0.46
  Author: faina09
  Author URI: http://profiles.wordpress.org/faina09
  License: GPLv2 or later
 */
$VER = '0.46';
require_once('wp-fevents-book.php');
//require_once('wp-fevents-6r.php');

global $wpfeventsbook;
$wpfeventsbook = new FEventsBook( 'WP FEvents Book', 'feventsbook', '/wp-fevents-book/', $VER );
//$wpfeventsbook -> child = new FEvents6r('WP FEvents Book', 'feventsbook', '/wp-fevents-book/', $VER);
