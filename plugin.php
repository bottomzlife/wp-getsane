<?php
/*
    Plugin Name: wp-getsane
    Plugin URI: https://github.com/bottomzlife/wp-getsane/
    Description: Let WordPress Sane
    Version: 0.1.1
    Author: bottomzlife
    Author URI: http://netsp.in/
    License: GPL2
*/

/*  Copyright 2016 bottomzlife (email : spam@netsp.in)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

call_user_func(function () {
    $THIS_PLUGIN_PATH = dirname(__FILE__);
    set_include_path(
        get_include_path()
        . PATH_SEPARATOR
        . $THIS_PLUGIN_PATH
        . '/lib/'
    );

    // remove headers
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );

    // disable wpautop
    remove_filter ( 'the_content','wpautop');
    remove_filter ( 'the_excerpt','wpautop');
    add_filter( 'the_content',
        function ($txt) {
            return wpautop($txt, false);
        }
    );

    // disable quick post
    add_action( 'wp_dashboard_setup',
        function () {
            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        }
    );

    // disable autosave
    add_action( 'wp_print_scripts',
        function () {
            wp_deregister_script('autosave');
        }
    );

    // disable auto update
    add_filter( 'pre_site_transient_update_core',
        create_function('$a', "return null;")
    );

    // delete hostpart of attachment urls
    add_filter( 'wp_get_attachment_url',
        function ( $url ) {
            $regex = '/^http(s)?:\/\/[^\/\s]+(.*)$/';
            if ( preg_match( $regex, $url, $m ) ) {
                $url = $m[2];
            }
            return $url;
        }
    );
    add_filter( 'attachment_link',
        function ( $url ) {
            $regex = '/^http(s)?:\/\/[^\/\s]+(.*)$/';
            if ( preg_match( $regex, $url, $m ) ) {
                $url = $m[2];
            }
            return $url;
        }
    );

    // replace the output of wp_head()
    add_action(
        'wp_head',
        function () {
            ob_start( function ( $buffer ) {
                $buffer = preg_replace(
                    '/^(<script.*?src=[\'"])https*:/im',
                    '$1',
                    $buffer
                );
                $buffer = preg_replace(
                    '/^(<link rel=[\'"]stylesheet[\'"] .*?href=[\'"])https*:/im',
                    '$1',
                    $buffer
                );
                $buffer = preg_replace(
                    '/^(<link rel=[\'"]alternate[\'"] .*?href=[\'"])https*:/im',
                    '$1',
                    $buffer
                );
                $buffer = preg_replace(
                    '/^(<link rel=[\'"].*api\.w\.org\/[\'"] .*?href=[\'"])https*:/im',
                    '$1',
                    $buffer
                );
                // for crayon syntax higlighter
                $buffer = preg_replace(
                    '/^(var CrayonSyntaxSettings.*"ajaxurl":")http:/im',
                    '$1',
                    $buffer
                );
                // Remove some plugins' credits. Sorry.
                $buffer = preg_replace(
                    '/^.*<!-- This site is .*-->.*\r?\n/im',
                    '',
                    $buffer
                );
                $buffer = preg_replace(
                    '/^<!-- \/ Yoast WordPress SEO plugin. -->\r?\n/im',
                    '',
                    $buffer
                );
                return $buffer;
            } );
        },
        0
    );
    add_action(
        'wp_head',
        function () {
            ob_end_flush();
        },
        100
    );

    // remove protocol and fqdn part from internal links
    add_action(
        'get_header',
        function () {
            ob_start( function ( $content ) {
                $home_url = trailingslashit( get_home_url('/') );
                $r = preg_replace(
                    '!<a(.*?href=[\'"])' . $home_url . '!i',
                    '<a$1/',
                    $content
                );
                $home_url = untrailingslashit( $home_url );
                $r = preg_replace(
                    '!<form(.*?action=[\'"])' . $home_url . '!i',
                    '<form$1/',
                    $r
                );
                return $r;
            } );
        },
        1
    );
    add_action(
        'wp_footer',
        function () {
            ob_end_flush();
        },
        99999
    );

});
