<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package local_metadata
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 POET
 */

/**
 * User metadata context handler class..
 *
 * @package local_metadata
 * @copyright  2016 POET
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadatacontext_user;

defined('MOODLE_INTERNAL') || die;

class context_handler extends \local_metadata\context\context_handler {

    /**
     * Constructor.
     * @param int $instanceid The instance of the context in question.
     * @param int $contextlevel The context level for this metadata.
     */
    public function __construct($instanceid = null, $contextlevel = null) {
        return parent::__construct($instanceid, CONTEXT_USER);
    }

    /**
     * Return the instance of the context. Must be handled by the implementing class.
     * @return object The Moodle data record for the instance.
     */
    public function get_instance() {
        global $DB;
        if (empty($this->instance)) {
            if (!empty($this->instanceid)) {
                $this->instance = $DB->get_record('user', ['id' => $this->instanceid], '*', MUST_EXIST);
            } else {
                $this->instance = false;
            }
        }
        return $this->instance;
    }

    /**
     * Return the instance of the context. Must be handled by the implementing class.
     * @return object The Moodle context.
     */
    public function get_context() {
        if (empty($this->context)) {
            if (!empty($this->instanceid)) {
                $this->context = \context_user::instance($this->instanceid);
            } else {
                $this->context = false;
            }
        }
        return $this->context;
    }

    /**
     * Return the instance of the context. Defaults to the home page.
     * @return object The Moodle redirect URL.
     */
    public function get_redirect() {
        return new \moodle_url('/user/preferences.php', ['userid' => $this->instanceid]);
    }

    /**
     * Check any necessary access restrictions and error appropriately. Must be implemented.
     * e.g. "require_login()". "require_capability()".
     * @return boolean False if access should not be granted.
     */
    public function require_access() {
        require_login();
        require_capability('moodle/user:editprofile', $this->context);
        return true;
    }

    /**
     * Implement if specific context settings can be added to a context settings page (e.g. user preferences).
     */
    public function add_settings_to_context_menu($navmenu) {
        // Add the settings page to the user setttings menu.
        $navmenu->add('users', new \admin_externalpage('metadatacontext_users', get_string('metadatatitle', 'metadatacontext_user'),
                new \moodle_url('/local/metadata/index.php', ['contextlevel' => CONTEXT_USER]), ['moodle/site:config']));
        return true;
    }

    /**
     * Hook function that is called when user profile page is being built.
     */
    public function myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
        if (get_config('metadatacontext_user', 'metadataenabled') == 1) {
            $content = local_metadata_display_fields($user->id, CONTEXT_USER, true);
            $node = new \core_user\output\myprofile\node('contact', 'metadata',
                get_string('metadata', 'local_metadata'), null, null, $content);
            $tree->add_node($node);
        }
    }

    /**
     * This function extends the navigation with the metadata for user settings node.
     *
     * @param navigation_node $navigation  The navigation node to extend
     * @param stdClass        $user        The user object
     * @param context         $usercontext The context of the user
     * @param stdClass        $course      The course to object for the tool
     * @param context         $coursecontext     The context of the course
     */
    public function extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
        global $USER, $SITE;

        if ((get_config('metadatacontext_user', 'metadataenabled') == 1) &&
            (($USER->id == $user->id) || has_capability('moodle/user:editprofile', $usercontext))) {

            $strmetadata = get_string('metadatatitle', 'metadatacontext_user');
            $url = new \moodle_url('/local/metadata/index.php',
                ['id' => $user->id, 'action' => 'userdata', 'contextlevel' => CONTEXT_USER]);
            $metadatanode = \navigation_node::create($strmetadata, $url, \navigation_node::NODETYPE_LEAF,
                'metadata', 'metadata', new \pix_icon('i/settings', $strmetadata)
            );
            $navigation->add_node($metadatanode);
        }
    }
}