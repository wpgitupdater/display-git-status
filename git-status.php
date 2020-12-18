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

/**
 * Returns the current branch name for a given location.
 *
 * @return string The current branch name
 */
function git_status_get_branch_name() {
	return trim( shell_exec( 'cd ' . __DIR__ . ' && git rev-parse --abbrev-ref HEAD' ) );
}

/**
 * Returns a boolean for git status, true being up to date, false otherwise
 *
 * @return bool true when no untracked changes, false otherwise.
 */
function git_status_is_up_to_date() {
	$status = trim( shell_exec( 'cd ' . __DIR__ . ' && git status --porcelain=v1' ) );
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
 * Adds a link with the current branch to the admin bar
 *
 * @param WP_Admin_Bar $admin_bar WordPress admin bar instance.
 */
function git_status_add_branch_link( WP_Admin_Bar $admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$branch = git_status_get_branch_name();
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
