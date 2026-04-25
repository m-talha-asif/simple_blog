<?php
// Define a constant to tell Moodle this is an AJAX script, altering how errors are displayed.
define('AJAX_SCRIPT', true);
// Include the core Moodle configuration file to set up the environment.
require_once('../../config.php');

// Disable debugging output so we don't break the JSON response with PHP warnings.
$CFG->debug = 0;
$CFG->debugdisplay = 0;

// Import global variables.
global $USER, $DB, $PAGE;

// Set the execution context to SYSTEM level for script bootstrapping.
$PAGE->set_context(context_system::instance());

// Verify the user is logged in.
require_login();
// Verify the session key (CSRF protection) sent with the request.
require_sesskey();

global $USER, $DB;

// Grab the 'action' parameter from the HTTP request (POST or GET). Default to 'submit'.
$action = optional_param('action', 'submit', PARAM_ALPHANUMEXT);
// Prepare an array to return as a JSON response. Default success to false.
$response = ['success' => false];

// Define the trash icon string again for the backend renderer.
$trash_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>';

// ==========================================
// ACTION 1: SUBMIT A NEW POST
// ==========================================
if ($action === 'submit') {
    // Get the heading, treating it as plain text.
    $heading = optional_param('heading', '', PARAM_TEXT);

    // Get the body text, treating it as RAW because Quill sends HTML.
    $text = optional_param('text', '', PARAM_RAW);

    // Validate that neither field is empty.
    if (empty($heading) || empty(trim(strip_tags($text)))) {
        $response['error'] = 'Missing blog heading or text.';
        echo json_encode($response);
        die(); // Stop execution.
    }

    try {
        // Create an object to insert into the database.
        $record = new stdClass();
        $record->userid = $USER->id; // Current user ID
        $record->username = $USER->username; // Current user name
        $record->blog_heading = $heading;
        $record->blog_text = $text;
        $record->timecreated = time(); // Current Unix timestamp

        // Insert the record into the 'block_simple_blog' table. Returns the new row's ID.
        $id = $DB->insert_record('block_simple_blog', $record);

        // Format the date and sanitize strings for the new HTML block we are about to return.
        $date = userdate($record->timecreated, get_string('strftimedatetime', 'core_langconfig'));
        $safe_heading = s($heading);
        $safe_username = s($USER->username);
        
        // Moodle's format_text() prevents XSS by cleaning dangerous HTML while allowing safe tags.
        $safe_text = format_text($text, FORMAT_HTML);

        // Construct the HTML for the newly created post so the frontend can inject it without refreshing.
        $new_post_html = '<div class="card mb-3" style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; position: relative;">';
        $new_post_html .= '<a href="#" class="text-danger delete-blog-btn" data-id="' . $id . '" style="position: absolute; top: 10px; right: 10px; text-decoration: none;" title="Delete Post">' . $trash_icon . '</a>';
        $new_post_html .= '<h6 class="card-title" style="margin-top:0; padding-right: 20px;">' . $safe_heading . '</h6>';
        $new_post_html .= '<small class="text-muted">By <strong>' . $safe_username . '</strong> on ' . $date . '</small>';
        $new_post_html .= '<div class="card-text mt-2 blog-text blog-text-clamp" style="margin-bottom:0;">' . $safe_text . '</div>';
        $new_post_html .= '<div style="margin-top: 5px;">';
        $new_post_html .= '<a href="#" class="blog-toggle-btn" style="font-size: 12px; text-decoration: none; display: none;">Show More</a>';
        $new_post_html .= '</div></div>';

        // Set response success and attach the HTML.
        $response['success'] = true;
        $response['html'] = $new_post_html;

    } catch (Exception $e) {
        // Capture any database exceptions and include the error message in the response.
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}
// ==========================================
// ACTION 2: LOAD MORE POSTS
// ==========================================
elseif ($action === 'load_more') {
    // Get the current offset (how many posts are already showing). Default to 0.
    $offset = optional_param('offset', 0, PARAM_INT);
    $limit = 3; // We load 3 items at a time.

    try {
        // Query the database. Limit is set to $limit + 1 (4) to check if there are more posts beyond the ones we return.
        $posts = $DB->get_records('block_simple_blog', null, 'timecreated DESC', '*', $offset, $limit + 1);
        
        $has_more = false;
        // If we found 4, it means there's another page of data.
        if (count($posts) > $limit) {
            $has_more = true;
        }

        // Slice array to only keep the 3 posts we actually want to display.
        $posts_to_show = array_slice($posts, 0, $limit);

        $html = '';
        // Loop through the posts and generate HTML (similar to block_simple_blog.php)
        foreach ($posts_to_show as $post) {
            $date = userdate($post->timecreated, get_string('strftimedatetime', 'core_langconfig'));
            $safe_heading = s($post->blog_heading);
            $safe_username = s($post->username);
            $safe_text = format_text($post->blog_text, FORMAT_HTML);

            $html .= '<div class="card mb-3" style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; position: relative;">';
            
            if ($post->userid == $USER->id || is_siteadmin()) {
                $html .= '<a href="#" class="text-danger delete-blog-btn" data-id="' . $post->id . '" style="position: absolute; top: 10px; right: 10px; text-decoration: none;" title="Delete Post">' . $trash_icon . '</a>';
            }
            
            $html .= '<h6 class="card-title" style="margin-top:0; padding-right: 20px;">' . $safe_heading . '</h6>';
            $html .= '<small class="text-muted">By <strong>' . $safe_username . '</strong> on ' . $date . '</small>';
            $html .= '<div class="card-text mt-2 blog-text blog-text-clamp" style="margin-bottom:0;">' . $safe_text . '</div>';
            
            $html .= '<div style="margin-top: 5px;">';
            $html .= '<a href="#" class="blog-toggle-btn" style="font-size: 12px; text-decoration: none; display: none;">Show More</a>';
            $html .= '</div></div>';
        }

        // Return the HTML payload and a boolean letting JS know if it should hide the "Load More" button.
        $response['success'] = true;
        $response['html'] = $html;
        $response['has_more'] = $has_more; 

    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}
// ==========================================
// ACTION 3: DELETE A POST
// ==========================================
elseif ($action === 'delete') {
    $postid = optional_param('postid', 0, PARAM_INT);
    $offset = optional_param('offset', 0, PARAM_INT); 

    if (empty($postid)) {
        $response['error'] = 'Invalid post ID.';
        echo json_encode($response);
        die();
    }

    try {
        $post = $DB->get_record('block_simple_blog', ['id' => $postid]);

        if (!$post) {
            $response['error'] = 'Post not found or already deleted.';
        } else if ($post->userid != $USER->id && !is_siteadmin()) {
            $response['error'] = 'You do not have permission to delete this post.';
        } else {
            // Delete the post
            $DB->delete_records('block_simple_blog', ['id' => $postid]);
            $response['success'] = true;

            $replacement_index = max(0, $offset - 1);
            
            // Fetch 2 posts instead of 1 so we can check if there are any MORE left in the database
            $replacements = $DB->get_records('block_simple_blog', null, 'timecreated DESC', '*', $replacement_index, 2);
            
            // If we found more than 1 post, it means there is still hidden data waiting to be loaded
            $response['has_more'] = (count($replacements) > 1);

            if (!empty($replacements)) {
                $rep_post = reset($replacements); // Grab just the first item to return
                $date = userdate($rep_post->timecreated, get_string('strftimedatetime', 'core_langconfig'));
                $safe_heading = s($rep_post->blog_heading);
                $safe_username = s($rep_post->username);
                $safe_text = format_text($rep_post->blog_text, FORMAT_HTML);

                $html = '<div class="card mb-3" style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; position: relative; opacity: 0; transition: opacity 0.3s ease;">';
                
                if ($rep_post->userid == $USER->id || is_siteadmin()) {
                    $html .= '<a href="#" class="text-danger delete-blog-btn" data-id="' . $rep_post->id . '" style="position: absolute; top: 10px; right: 10px; text-decoration: none;" title="Delete Post">' . $trash_icon . '</a>';
                }
                
                $html .= '<h6 class="card-title" style="margin-top:0; padding-right: 20px;">' . $safe_heading . '</h6>';
                $html .= '<small class="text-muted">By <strong>' . $safe_username . '</strong> on ' . $date . '</small>';
                $html .= '<div class="card-text mt-2 blog-text blog-text-clamp" style="margin-bottom:0;">' . $safe_text . '</div>';
                $html .= '<div style="margin-top: 5px;">';
                $html .= '<a href="#" class="blog-toggle-btn" style="font-size: 12px; text-decoration: none; display: none;">Show More</a>';
                $html .= '</div></div>';

                $response['replacement_html'] = $html;
            } else {
                $response['replacement_html'] = ''; 
            }
        }

    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}

// Convert the PHP array into a JSON string and send it to the browser.
echo json_encode($response);
die(); // End script execution.