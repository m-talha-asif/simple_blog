<?php
class block_simple_blog extends block_base {
    
    public function init() {
        $this->title = get_string('simple_blog', 'block_simple_blog');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER, $CFG, $DB;

        $this->content = new stdClass;

        if (!isloggedin() || isguestuser()) {
            $this->content->text = 'Please log in to submit a blog post.';
            return $this->content;
        }

        $trash_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>';

        $html = '
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <style>
        .blog-text-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        #blog_editor {
            background-color: #ffffff;
            font-family: inherit;
            font-size: 14px;
        }
        </style>
        ';

        $html .= '
        <div id="simple-blog-container">
            <input type="text" id="blog_heading" placeholder="Blog Heading" class="form-control mb-2" required>
            <div id="blog_editor" style="height: 120px; border-radius: 0 0 4px 4px;"></div>
            <button id="submit_blog_btn" class="btn btn-primary btn-sm mt-2">Submit Post</button>
            <div id="blog_status_msg" class="mt-2 font-weight-bold" style="font-size: 14px;"></div>
        </div>
        <hr>';

        $html .= '<h5>Recent Posts</h5>';
        $html .= '<div id="blog_posts_list">';

        $posts = $DB->get_records('block_simple_blog', null, 'timecreated DESC', '*', 0, 4);
        
        $has_more_initially = false;
        if (count($posts) > 3) {
            $has_more_initially = true;
        }

        $posts_to_show = array_slice($posts, 0, 3);
        $initial_count = count($posts_to_show);

        if ($posts_to_show) {
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
        } else {
            $html .= '<p id="no_posts_msg">No blog posts found. Be the first to post!</p>';
        }

        $html .= '</div>';

        $display_btn = $has_more_initially ? 'block' : 'none';
        $html .= '<div style="display: flex; gap: 5px; margin-top: 10px;">';
        $html .= '<button id="load_more_btn" class="btn btn-secondary btn-sm" style="flex: 1; display: ' . $display_btn . ';">Load More</button>';
        $html .= '<button id="show_less_btn" class="btn btn-outline-secondary btn-sm" style="flex: 1; display: none;">Show Less</button>';
        $html .= '</div>';
        $html .= '<div id="load_more_status" class="text-center mt-1 text-muted" style="font-size: 12px;"></div>';

        $html .= '<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>';

        $this->content->text = $html;
        $this->content->footer = '';

        $this->page->requires->js_call_amd('block_simple_blog/main', 'init', [
            $CFG->wwwroot,
            sesskey(),
            $initial_count
        ]);

        return $this->content;
    }
}