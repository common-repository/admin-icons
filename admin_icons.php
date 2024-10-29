<?php
/*
Plugin Name: Admin Icons
Plugin URI: http://trac.wp-box.fr/opensource/
Description: This plugin allow to customize icons in WordPress administration, version 2.7 and upper.
Version: 1.1
Author: Amaury BALMER
Author URI: http://wp-box.fr

Copyright 2008 Amaury BALMER (balmer.amaury@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

class AdminIcons {
	var $version = '1.1';
	var $option_name = 'admin-icons';
	var $sets_dir = array();
	var $sets = array();
	
	function AdminIcons() {
		// Build dir
		$this->sets_dir['built-in'] = dirname(__FILE__) . '/icons-sets/'; // built-in set : /wp/wp-content/plugins/admin-icons/icons-sets/
		$this->sets_dir['custom'] = WP_CONTENT_DIR . '/icons-sets/'; // Custom sets : /wp/wp-content/icons-sets/
		
		// Load current CSS
		$set = get_option( $this->option_name );
		if ( $set !== false ) {
			if ( is_file($this->sets_dir[$this->getFolderByName( $set )] . $set . '/'. $set . '.css') || $set != 'default' ) {
				wp_enqueue_style( 'admin-icons-'.$set , $this->getUriFromSetName($set) . '/'. $set . '.css', array('colors'), $this->version, 'all' );
			}
		}
		
		// Add Menu
		add_action('admin_menu', array(&$this,'addMenu'));
		
		// Security and DB options
		add_action('admin_init', array(&$this,'checkPost'));
		
		// Init default set
		$this->sets['ok'][] = 'default';
	}
	
	function addMenu() {
		add_options_page(__('Admin Icons', 'admin-icons'), __('Admin Icons', 'admin-icons'), 'manage_options', 'admin-icons', array(&$this, 'pageManage'));		
	}

	function pageManage() {
		// Get current set
		$current_set = get_option( $this->option_name );
		
		// Get sets
		$this->getSets( $this->sets_dir['built-in'] ); 
		$this->getSets( $this->sets_dir['custom'] );
		?>
		<style type="text/css">
			.sets td, .sets th { text-align:center;vertical-align:middle; }
		</style>
		<div class="wrap">
			<h2><?php _e('Admin Icons', 'admin-icons'); ?></h2>
			
			<form action="" method="post">
				<h3><?php _e('Icons sets available', 'admin-icons'); ?></h3>
				<table class="widefat sets">
					<thead>
						<tr>
							<th><?php _e('Set selected', 'admin-icons'); ?></th>
							<th><?php _e('Name', 'admin-icons'); ?></th>
							<th><?php _e('Preview and description', 'admin-icons'); ?></th>
							<th><?php _e('Author', 'admin-icons'); ?></th>
							<th><?php _e('Version', 'admin-icons'); ?></th>
						<tr>
					</thead>
					<tbody>
						<?php
						foreach( (array) $this->sets['ok'] as $set ) {
							$dir = $this->sets_dir[$this->getFolderByName( $set )];
				            $set_data = $this->getSetData( $dir . $set . '/'. $set . '.css' );
				            ?>
				            <tr>
				            	<td>
				            		<input type="radio" id="set-<?php echo $set; ?>" <?php checked( $set, $current_set ); ?> <?php if ( empty($current_set) && $set == 'default' ) echo 'checked="checked"'; ?> name="set" value="<?php echo $set; ?>" />
				            	</td>
				            	<td>
									<?php printf(__('<h4><a href="%1$s">%2$s</a></h4>', 'admin-icons'), $set_data['URI'], $set_data['Name'] ); ?>
				            	</td>
								<td>
									<?php if ( $set == 'default' || ( !empty($set_data['Screenshot']) && is_file($dir . $set . '/' . $set_data['Screenshot']) ) ) : ?>
				            			<label for="set-<?php echo $set; ?>">
				            				<img src="<?php echo $this->getUriFromSetName($set) . '/' . $set_data['Screenshot'] ; ?>" alt="<?php echo attribute_escape($set_data['Name']); ?>" />
				            			</label>
				            		<?php endif; ?>
				            		<p><?php echo $set_data['Description']; ?></p>
								</td>
								<td>
									<?php echo $set_data['Author']; ?>
								</td>
								<td>
									<?php echo $set_data['Version']; ?>
								</td>
				            </tr>
				            <?php		
						}
						?>
					</tobdy>
				</table>
				
				<?php if ( isset($this->sets['broken']) && !empty($this->sets['broken']) ) : ?>
					<h3><?php _e('Broken icons sets', 'admin-icons'); ?></h3>
					<ul>
						<?php foreach( (array) $this->sets['broken'] as $set ) : ?>
							<li><?php echo attribute_escape($set); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
		
				<p class="submit">
				    <input type="submit" class="submit" name="update_icons_set" value="<?php _e('Update set', 'admin-icons'); ?>" />
				    <?php wp_nonce_field('update_icons_set_nonce'); ?>
				</p>
			</form>
		</div>
		<?php
	}
	
	function getFolderByName( $set_name ) {
		if ( is_file( $this->sets_dir['custom'] . $set_name . '/' . $set_name . '.css' ) ) { // Custom
			return 'custom';			
		} elseif( is_file( $this->sets_dir['built-in'] . $set_name . '/' . $set_name . '.css' ) ) { // Built-in
			return 'built-in';
		}
		return false;		
	}
	
	function getUriFromSetName( $set_name ) {	
		if ( $set_name == 'default' ) {
			return clean_url(get_option('siteurl') . '/wp-admin/images' );
		}
		
		return clean_url(get_option('siteurl') . '/' . str_replace( ABSPATH, '', $this->sets_dir[$this->getFolderByName( $set_name )]) . $set_name);
	}
	
	function checkPost() {
		if ( isset($_POST['update_icons_set']) && isset($_GET['page']) && $_GET['page'] == 'admin-icons' ) {
			check_admin_referer('update_icons_set_nonce');

			// Update theme
			update_option( $this->option_name, stripslashes($_POST['set']) );
		}
	}
	
	function getSets( $dir ) {
		if( is_dir( $dir ) ) {
			if( $dh = opendir( $dir ) ) {
				while( ( $set = readdir( $dh ) ) !== false ) {
					// Remove parents folder, and remove classic files
					if ( $set == '.' || $set == '..' || $set == 'CVS' || $set == '.svn' || !is_dir($dir.$set) ) {
						continue;				
					}
					
					// Check if CSS file for SET exist
					if ( is_file($dir.$set.'/'.$set.'.css') ) {
						$this->sets['ok'][] = $set;						
					} else {
						$this->sets['broken'][]	= $set;					
					}
				}
				return true;
			}
		}
		return false;
	}
	
	function getSetData( $set_file ) {
		if ( strpos( $set_file, 'default.css' ) !== false ) {
			global $wp_version;
			return array( 
				'Name' => __('Default', 'admin-icons'), 
				'URI' => __('http://wordpress.org', 'admin-icons'), 
				'Description' => __('Default icons set from WordPress 2.7', 'admin-icons'), 
				'Author' => __('<a href="http://wordpress.org" title="Visit author homepage">Automattic</a>', 'admin-icons'), 
				'Version' => $wp_version, 'Screenshot' => 'menu.png' );
		}
		
		$sets_allowed_tags = array(
			'a' => array(
				'href' => array(),'title' => array()
				),
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array()
		);

		$set_data = implode( '', file( $set_file ) );
		$set_data = str_replace ( '\r', '\n', $set_data );
		preg_match( '|Set Name:(.*)$|mi', $set_data, $set_name );
		preg_match( '|Set URI:(.*)$|mi', $set_data, $set_uri );
		preg_match( '|Description:(.*)$|mi', $set_data, $description );

		if ( preg_match( '|Author URI:(.*)$|mi', $set_data, $author_uri ) )
			$author_uri = clean_url( trim( $author_uri[1]) );
		else
			$author_uri = '';

		if ( preg_match( '|Version:(.*)|i', $set_data, $version ) )
			$version = wp_kses( trim( $version[1] ), $sets_allowed_tags );
		else
			$version = '';

		$name = wp_kses( trim( $set_name[1] ), $sets_allowed_tags );
		$set_uri = clean_url( trim( $set_uri[1] ) );
		$description = wptexturize( wp_kses( trim( $description[1] ), $sets_allowed_tags ) );

		if ( preg_match( '|Author:(.*)$|mi', $set_data, $author_name ) ) {
			if ( empty( $author_uri ) ) {
				$author = wp_kses( trim( $author_name[1] ), $sets_allowed_tags );
			} else {
				$author = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $author_uri, __( 'Visit author homepage' ), wp_kses( trim( $author_name[1] ), $sets_allowed_tags ) );
			}
		} else {
			$author = __('Anonymous');
		}
		
		// Screenshots
		if ( preg_match( '|Screenshot:(.*)$|mi', $set_data, $screenshot ) )
			$screenshot = wp_kses( trim( $screenshot[1] ), $sets_allowed_tags );
		else
			$screenshot = '';

		return array( 'Name' => $name, 'URI' => $set_uri, 'Description' => $description, 'Author' => $author, 'Version' => $version, 'Screenshot' => $screenshot );
	}
}

// initialisation plugin for admin only
add_action('plugins_loaded', 'initAdminIcons');

function initAdminIcons() {
	if ( is_admin() ) {
		global $wp_version, $admin_icons;
	
		if ( version_compare( '2.7', $wp_version, '>=' ) ) {
			$admin_icons = new AdminIcons();
			return true;
		}
	}
	return false;
}
?>