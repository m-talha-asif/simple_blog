# AJAX Blog Post (block_simple_blog)

**AJAX Blog Post** is a dynamic Moodle block plugin that allows users to quickly share updates and thoughts without leaving the page. By leveraging JavaScript (AMD modules) and asynchronous PHP endpoints, this plugin provides a smooth, modern user experience for micro-blogging inside Moodle.

## ✨ Features

* **Seamless AJAX Interactions**: Submit new posts, delete existing ones, and load older posts instantly without refreshing your browser.
* **Rich Text Editing**: Integrates the Quill rich-text editor, allowing users to format their posts with bold, italic, underline, strikethrough, lists, and headers.
* **Smart Pagination & Text Clamping**: Long posts are automatically truncated with a "Show More" toggle to save space. Users can also browse history via "Load More" and "Show Less" buttons.
* **Secure Permissions**: Users can only delete their own posts, while Site Administrators retain the ability to moderate and delete any post. Cross-Site Request Forgery (CSRF) protection is strictly enforced on all actions.
* **Flexible Placement**: The block can be added to standard course pages or directly to a user's My Moodle (Dashboard) page. 

## 📋 Requirements

* **Moodle Version:** Requires Moodle version `2020110900` or higher.

## 🚀 Installation

1. Download the plugin and extract the files.
2. Rename the extracted folder to `simple_blog` (if it isn't already).
3. Place the `simple_blog` folder into the `blocks/` directory of your Moodle installation.
    * The path should be: `[moodle_root]/blocks/simple_blog`
4. Log in to your Moodle site as an Administrator.
5. Go to **Site administration > Notifications** to complete the plugin installation and database upgrades. The plugin will create a custom `block_simple_blog` table to store posts.

## ⚙️ Usage

Once installed, users with appropriate permissions can start using the block:

1. Turn editing on within a course or on your Dashboard.
2. Add the **AJAX Blog Post** block to the page.
3. To create a post, type a heading into the text field, write your content in the Quill editor, and click **Submit Post**.
4. The new post will immediately appear in the "Recent Posts" feed below the editor.

## 📄 License
This plugin is developed for Moodle and inherits the GNU General Public License (GPL) standards utilized by the Moodle core platform.
