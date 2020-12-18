<?php
/**
 * Plugin Name: Git Status
 * Version: 1.0.0
 * Plugin URI: https://wpgitupdater.dev/docs/latest/plugins
 * Author: WP Git Updater
 * Author URI: https://wpgitupdater.dev
 * Description: A simple WordPress plugin to display your current git branch and status in the admin area.
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: git-status
 * Domain Path: /languages
 *
 * @package git-status
 *
 * Git Status is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Git Status is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Git Status. If not, see https://wordpress.org/plugins/git-status/.
 */

// This plugin only operates in the admin area, there is no need to continue otherwise.
if ( ! is_admin() ) {
	return;
}

register_activation_hook( __FILE__, 'get_status_install_hook' );
/**
 * Sets default option values if none are present.
 */
function get_status_install_hook() {
	if ( ! get_option( 'git_status_options' ) ) {
		update_option( 'git_status_options', array( 'git_directory' => rtrim( WP_CONTENT_DIR, '/' ) ) );
	}
}

/**
 * Returns the location of the git repository, defaulting to `wp-content` if not set.
 *
 * @return string The location of the git repository
 */
function git_status_get_respository_location() {
	$options = get_option( 'git_status_options' );
	if ( is_array( $options ) && isset( $options['git_directory'] ) ) {
		return $options['git_directory'];
	}
	return rtrim( WP_CONTENT_DIR, '/' );
}

/**
 * Returns the current branch name for a given location.
 *
 * @return string The current branch name
 */
function git_status_get_branch_name() {
	return trim( shell_exec( 'cd ' . git_status_get_respository_location() . ' && git rev-parse --abbrev-ref HEAD' ) );
}

/**
 * Returns a boolean for git status, true being up to date, false otherwise
 *
 * @return bool true when no untracked changes, false otherwise.
 */
function git_status_is_up_to_date() {
	$status = trim( shell_exec( 'cd ' . git_status_get_respository_location() . ' && git status --porcelain=v1' ) );
	if ( '' === $status ) {
		return true;
	}

	return false;
}

add_action( 'admin_head', 'git_status_admin_css' );
/**
 * Add plugins admin css.
 */
function git_status_admin_css() {
	echo '<style>
	.git-status-menu img.ab-icon {
		height: 22px !important;
		width: 22px !important;
		padding-top: 5px !important;
	}
	.git-status-menu.git-status-untracked a {
		background-color: #f05133 !important;
	}
	.git-status-menu.git-status-untracked:hover a {
		background-color: #d4492f !important;
		color: #ffffff !important;
	}
</style>';
}

add_action( 'admin_bar_menu', 'git_status_add_branch_link', 100 );
/**
 * Adds a link with the current branch to the admin bar when repository located
 *
 * @param WP_Admin_Bar $admin_bar WordPress admin bar instance.
 */
function git_status_add_branch_link( WP_Admin_Bar $admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$branch = git_status_get_branch_name();
	if ( '' === $branch ) {
		return;
	}

	if ( git_status_is_up_to_date() ) {
		$img = plugins_url( 'assets/git.svg', __FILE__ );
		$title = sprintf(
			/* translators: Asserting the current git branch */
			__( 'You are currently on the %s branch', 'git-status' ),
			$branch
		);
		$class_names = 'git-status-menu git-status-up-to-date';
	} else {
		$img = plugins_url( 'assets/git-white.svg', __FILE__ );
		$title = sprintf(
			/* translators: Asserting the current git branch */
			__( 'You are currently on the %s branch, but there are uncommitted changes!', 'git-status' ),
			$branch
		);
		$class_names = 'git-status-menu git-status-untracked';
	}
	$admin_bar->add_menu(
		array(
			'id'    => 'git-status',
			'parent' => null,
			'group'  => null,
			'title' => '<img src="' . $img . '" alt="' . __( 'Git Icon', 'git-status' ) . '" class="ab-icon" />' . $branch,
			'href'  => admin_url( 'admin.php?page=git-status' ),
			'meta' => array(
				'title' => $title,
				'class' => $class_names,
			),
		)
	);
}

add_action( 'admin_menu', 'git_status_add_pages' );
/**
 * Adds the Git Status Tools menu page.
 */
function git_status_add_pages() {
	add_management_page( __( 'Git Status', 'git-status' ), __( 'Git Status', 'git-status' ), 'manage_options', 'git-status', 'git_status_page' );
}

/**
 * Outputs the Git Status page content.
 */
function git_status_page() {
	if ( git_status_get_branch_name() === '' ) {
		add_settings_error( 'git_status_options', 'git_status_setting_git_directory', __( 'The saved location is not a git repository! The git status menu item will be hidden from view.', 'git-status' ), 'error' );
	}
	?>
	<div class="wrap">
		<style type="text/css">
			.page-title img {
				width: 30px;
				height: 30px;
				margin-bottom: -6px;
			}
		</style>
		<h1 class="page-title">
			<img src="<?php echo esc_attr( plugins_url( 'assets/git.svg', __FILE__ ) ); ?>" alt="<?php esc_attr_e( 'Git Icon', 'git-status' ); ?>" />
			<?php esc_attr_e( 'Git Status', 'git-status' ); ?>
		</h1>
		<form action="options.php" method="post">
			<?php
			settings_errors( 'git_status_options' );
			settings_fields( 'git_status_options' );
			do_settings_sections( 'git_status' );
			?>
			<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save Settings', 'git-status' ); ?>" />
		</form>
	</div>
	<?php
}

add_action( 'admin_init', 'git_status_register_settings' );
/**
 * Register our plugins settings, sections and fields.
 */
function git_status_register_settings() {
	register_setting(
		'git_status_options',
		'git_status_options',
		array(
			'type' => 'array',
			'sanitize_callback' => 'git_status_sanitize_options',
		)
	);
	add_settings_section( 'git_settings', __( 'Git Settings', 'git-status' ), 'git_status_git_section_text', 'git_status' );
	add_settings_field( 'git_status_setting_git_directory', __( 'Git Repository Location', 'git-status' ), 'git_status_setting_git_directory', 'git_status', 'git_settings', array( 'label_for' => 'git_status_setting_git_directory' ) );
}

/**
 * Sanitize user supplied settings for our plugin, adding notices where appropriate.
 *
 * @param array $options plugin settings form options.
 * @return array Sanitized settings
 */
function git_status_sanitize_options( $options ) {
	$options['git_directory'] = esc_attr( rtrim( $options['git_directory'], '/' ) );
	add_settings_error( 'git_status_options', 'git_status_setting_git_directory', __( 'Settings Saved', 'git-status' ), 'success' );
	return $options;
}

/**
 * Introduction text for the git settings section.
 */
function git_status_git_section_text() { }

/**
 * Output our git directory setting input.
 */
function git_status_setting_git_directory() {
	$options = get_option( 'git_status_options' );
	echo '<input id="git_status_setting_git_directory" class="regular-text code" name="git_status_options[git_directory]" type="text" value="' . esc_attr( $options['git_directory'] ) . '" />';
	echo '<p class="description">' . esc_attr( 'Enter the full path to your sites git repository.', 'git-status' ) . '</p>';
}
