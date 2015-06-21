<?php
/*
Plugin Name: Opus Primus Taglines
Plugin URI: http://opusprimus.com/features/stanzas/taglines/
Description: Add a meta box for a tagline to various places in the administration panels
Version: 1.0.4
Text Domain: opusprimus-taglines-stanza
Author: Edward Caissie
Author URI: http://edwardcaissie.com/
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * Opus Primus TagLines
 *
 * Add a meta box for a tagline to various places in the administration panels
 *
 * @package     OpusPrimus_TagLines
 * @since       0.1
 *
 * @author      Opus Primus <in.opus.primus@gmail.com>
 * @copyright   Copyright (c) 2012-2015, Opus Primus
 *
 * This file is part of Opus Primus Taglines, a part of Opus Primus.
 *
 * Opus Primus is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.
 *
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to:
 *
 *      Free Software Foundation, Inc.
 *      51 Franklin St, Fifth Floor
 *      Boston, MA  02110-1301  USA
 *
 * The license for this software can also likely be found here:
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version     1.0.1
 * @date        February 21, 2013
 * Re-order methods: action and filter calls by request order, then alphabetical
 * Modified action hook call to use current standard
 *
 * @version     1.0.3
 * @date        February 28, 2013
 * Changed name from "Meta_Boxes" to "TagLines" and moved to Stanzas
 *
 * @version     1.0.4
 * @date        June 20, 2015
 * Refactored into stand-alone plugin
 */
class OpusPrimusTagLines {

	/**
	 * Constructor
	 *
	 * @package        OpusPrimus_TagLines
	 * @since          0.1
	 *
	 * @uses           add_action
	 */
	function __construct() {

		/** Enqueue Styles */
		add_action(
			'wp_enqueue_scripts', array(
				$this,
				'scripts_and_styles'
			)
		);

		/** Add taglines meta boxes */
		add_action( 'add_meta_boxes', array( $this, 'tagline_create_boxes' ) );

		/** Save tagline data entered */
		add_action( 'save_post', array( $this, 'tagline_save_postdata' ) );

		/** Send tagline to screen after post title */
		add_action( 'opus_post_title_after', array( $this, 'tagline_output' ) );

		/** Set Opus Primus Tagline stanza off by default */
		add_filter( 'default_hidden_meta_boxes', array(
			$this,
			'default_screen_option'
		) );

	}


	/**
	 * Enqueue Scripts and Styles
	 * Use to enqueue the extension scripts and stylesheets, if they exists
	 *
	 * @package       OpusPrimus_TagLines
	 * @since         1.0.3
	 *
	 * @uses          OpusPrimusRouter::path_uri
	 * @uses          opus_primus_theme_version
	 * @uses          wp_enqueue_script
	 * @uses          wp_enqueue_style
	 * @uses          wp_get_theme
	 *
	 * @version       1.2.4
	 * @date          May 17, 2014
	 * Use `opus_primus_theme_version` in place of `wp_get_theme` call
	 *
	 * @version       1.3
	 * @date          September 1, 2014
	 * Replace CONSTANTS with OpusPrimusRouter method
	 */
	function scripts_and_styles() {

		/** @var object $optl_data - plugin header data */
		$optl_data = $this->plugin_data();

		/** Enqueue Styles */
		/** Enqueue Taglines Stanza Stylesheets */
		wp_enqueue_style( 'Opus-Primus-TagLines', plugin_dir_url( __FILE__ ) . 'opus-primus.taglines.css', array(), $optl_data['Version'], 'screen' );

	}


	/**
	 * Plugin Data
	 * Returns the plugin header data as an array
	 *
	 * @package    OpusPrimus_TagLines
	 * @since      1.0.4
	 *
	 * @uses       get_plugin_data
	 *
	 * @return array
	 */
	function plugin_data() {

		/** Call the wp-admin plugin code */
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		/** @var $plugin_data - holds the plugin header data */
		$plugin_data = get_plugin_data( __FILE__ );

		return $plugin_data;

	}


	/**
	 * Create Tagline Boxes
	 * Create Meta Boxes for use with the taglines feature
	 *
	 * @package           OpusPrimus_TagLines
	 * @since             0.1
	 *
	 * @uses              OpusPrimusTagLines::tagline_callback
	 * @uses              (GLOBAL) $post - post_type
	 * @uses              add_meta_box
	 *
	 * @internal          used with action hook add_meta_boxes
	 *
	 * @version           1.2.5
	 * @date              July 27, 2014
	 * Refactored to clarify the parameter usage
	 */
	function tagline_create_boxes() {

		global $post;

		/** May not work with attachments */
		if ( 'attachment' <> $post->post_type ) {
			/** @var string $context - valid values: advanced, normal, or side */
			$context = 'normal';
			/** @var string $priority - valid values: default, high, low, or core */
			$priority = 'high';

			/** $context / $priority = normal / high should put this close to the content textarea box */

			add_meta_box(
				'opus_tagline',
				apply_filters( 'opus_taglines_meta_box_title', sprintf( __( '%1$s Tagline', 'opusprimus-taglines-stanza' ), ucfirst( $post->post_type ) ) ),
				array( $this, 'tagline_callback' ),
				$post->post_type,
				$context,
				$priority,
				null
			);

		}

	}


	/**
	 * Tagline Save Postdata
	 * Save tagline text field data entered via callback
	 *
	 * @package            OpusPrimus_TagLines
	 * @since              0.1
	 *
	 * @param   $post_id
	 *
	 * @uses               (CONSTANT) DOING_AUTOSAVE
	 * @uses               check_admin_referrer
	 * @uses               current_user_can
	 * @uses               update_post_meta
	 */
	function tagline_save_postdata( $post_id ) {

		/** If this is an auto save routine we do not want to do anything */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/** Check if this is a new post and if user can edit pages */
		if ( isset( $_POST['post_type'] ) && ( 'page' == $_POST['post_type'] ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/** If tagline is set, save and update the post meta data */
		if ( isset( $_POST['tagline_text_field'] ) ) {
			$tagline_text = $_POST['tagline_text_field'];
			update_post_meta( $post_id, 'tagline_text_field', $tagline_text );
		}

	}


	/**
	 * Tagline Output
	 * Create output to be used
	 *
	 * @package          OpusPrimus_TagLines
	 * @since            0.1
	 *
	 * @uses             (GLOBAL) $post - ID, post_type
	 * @uses             apply_filters
	 * @uses             get_post_meta
	 *
	 * @version          1.2.4
	 * @date             May 19, 2014
	 * Separated the output class into two different classes
	 */
	function tagline_output() {

		/** Since we are not inside the loop grab the global post object */
		global $post;
		$tagline = apply_filters( 'opus_tagline_output_' . $post->ID, get_post_meta( $post->ID, 'tagline_text_field', true ) );

		/** Make sure there is a tagline before sending anything to the screen */
		if ( ! empty( $tagline ) ) {
			echo '<div class="opus-primus-tagline"><span class="' . $post->post_type . '-tagline">' . $tagline . '</span></div>';
		}

	}


	/**
	 * Tagline Callback
	 * Used to display text field box on edit page
	 *
	 * @package OpusPrimus_TagLines
	 * @since   0.1
	 *
	 * @param   $post -> ID, post_type
	 *
	 * @uses    __
	 * @uses    apply_filters
	 * @uses    get_post_meta
	 */
	function tagline_callback( $post ) {

		/** Create and display input for tagline text field */
		echo '<label for="tagline_text_field">';
		echo apply_filters( 'opus_taglines_text_field_description', sprintf( __( 'Add custom tagline to this %1$s: ', 'opusprimus-taglines-stanza' ), $post->post_type ) );
		echo '</label>';
		echo '<input type="text" id="tagline_text_field" name="tagline_text_field" value="' . get_post_meta( $post->ID, 'tagline_text_field', true ) . '" size="100%" />';

	}


	/**
	 * Default Screen Option
	 * Used to set Opus Primus Tagline off by default in editor screen options
	 *
	 * @package       OpusPrimus_TagLines
	 * @since         1.2.5
	 *
	 * @param $hidden
	 *
	 * @return array
	 *
	 * @version       1.4
	 * @date          March 31, 2015
	 * Removed `$screen` parameter as not necessary
	 */
	function default_screen_option( $hidden ) {

		/** Add `opus_tagline` to default hidden screen options array */
		$hidden[] = 'opus_tagline';

		return $hidden;

	}


	/**
	 * Load Opus Primus TagLines Widget
	 *
	 * Register widget to be used in the widget init hook
	 *
	 * @package OpusPrimus_TagLines
	 * @since   1.0.4
	 *
	 * @uses    register_widget
	 */
	function load_optl_widget() {
		register_widget( 'OpusPrimusTagLines' );
	}


}

/** @var $opus_taglines - new instance of class */
$opus_taglines = new OpusPrimusTagLines();