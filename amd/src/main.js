/* eslint-disable */
define([], function() {
    return {
        init: function(wwwroot, sesskey, initialOffset) {
            if (!document.getElementById("blog_editor")) {
                return;
            }

            var quill = new Quill("#blog_editor", {
                theme: "snow",
                placeholder: "Write your blog post here...",
                modules: {
                    toolbar: [
                        ["bold", "italic", "underline", "strike"],
                        [{"list": "ordered"}, {"list": "bullet"}],
                        [{"header": [1, 2, 3, false]}],
                        ["clean"]
                    ]
                }
            });

            var currentOffset = initialOffset;
            var loadMoreBtn = document.getElementById("load_more_btn");
            var showLessBtn = document.getElementById("show_less_btn");
            var postList = document.getElementById("blog_posts_list");

            /**
             *
             */
            function updateTextToggles() {
                var texts = postList.querySelectorAll(".blog-text");
                texts.forEach(function(text) {
                    var container = text.nextElementSibling;
                    if (container) {
                        var btn = container.querySelector(".blog-toggle-btn");
                        if (btn && text.classList.contains("blog-text-clamp") && text.scrollHeight > text.clientHeight) {
                            btn.style.display = "inline-block";
                        }
                    }
                });
            }

            postList.addEventListener("click", function(e) {
                // SHOW MORE/LESS TOGGLE
                if (e.target && e.target.classList.contains("blog-toggle-btn")) {
                    e.preventDefault();
                    var btn = e.target;
                    var textElement = btn.parentElement.previousElementSibling;

                    if (textElement.classList.contains("blog-text-clamp")) {
                        textElement.classList.remove("blog-text-clamp");
                        btn.innerText = "Show Less";
                    } else {
                        textElement.classList.add("blog-text-clamp");
                        btn.innerText = "Show More";
                    }
                }

                // DELETE POST LOGIC
                var deleteBtn = e.target.closest(".delete-blog-btn");
                if (deleteBtn) {
                    e.preventDefault();
                    if (!confirm("Are you sure you want to permanently delete this post?")) {
 return;
}

                    var postid = deleteBtn.getAttribute("data-id");
                    var card = deleteBtn.closest(".card");

                    deleteBtn.style.pointerEvents = "none";
                    deleteBtn.style.opacity = "0.3";

                    var formData = new FormData();
                    formData.append("action", "delete");
                    formData.append("postid", postid);
                    formData.append("sesskey", sesskey);
                    formData.append("offset", currentOffset);

                    fetch(wwwroot + "/blocks/simple_blog/ajax.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            card.style.opacity = "0";

                            setTimeout(() => {
                                card.remove();

                                if (data.replacement_html) {
                                    postList.insertAdjacentHTML("beforeend", data.replacement_html);
                                    var newCard = postList.lastElementChild;
                                    void newCard.offsetWidth;
                                    newCard.style.opacity = "1";
                                    updateTextToggles();
                                } else {
                                    currentOffset--;
                                    if (postList.children.length === 0) {
                                        postList.innerHTML = "<p id='no_posts_msg'>No blog posts found.</p>";
                                    }
                                }

                                // If the backend confirms there is no more data in the DB, hide the Load More button
                                if (data.has_more === false && loadMoreBtn) {
                                    loadMoreBtn.style.display = "none";
                                }

                                // If deleting dropped us down to 3 posts, hide the Show Less button
                                if (postList.children.length <= 3 && showLessBtn) {
                                    showLessBtn.style.display = "none";
                                }

                            }, 300);
                        } else {
                            alert("Error: " + data.error);
                            deleteBtn.style.pointerEvents = "auto";
                            deleteBtn.style.opacity = "1";
                        }
                    })
                    .catch(error => {
                        alert("Network error.");
                        deleteBtn.style.pointerEvents = "auto";
                        deleteBtn.style.opacity = "1";
                    });
                }
            });

            updateTextToggles();
            setTimeout(updateTextToggles, 200);

            // SUBMIT POST LOGIC
            document.getElementById("submit_blog_btn").addEventListener("click", function(e) {
                e.preventDefault();

                var heading = document.getElementById("blog_heading").value;
                var textHTML = quill.root.innerHTML;
                var plainText = quill.getText().trim();
                var statusMsg = document.getElementById("blog_status_msg");

                if (!heading || plainText.length === 0) {
                    statusMsg.innerHTML = "<span style='color:red;'>Please fill in both fields.</span>";
                    return;
                }

                statusMsg.innerHTML = "Submitting...";

                var formData = new FormData();
                formData.append("action", "submit");
                formData.append("heading", heading);
                formData.append("text", textHTML);
                formData.append("sesskey", sesskey);

                fetch(wwwroot + "/blocks/simple_blog/ajax.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusMsg.innerHTML = "<span style='color:green;'>Post published!</span>";
                        document.getElementById("blog_heading").value = "";
                        quill.setContents([]);

                        var noMsg = document.getElementById("no_posts_msg");
                        if (noMsg) {
 noMsg.remove();
}

                        postList.insertAdjacentHTML("afterbegin", data.html);

                        // If there are more than 3 posts AND the list is not currently expanded
                        if (postList.children.length > 3 && showLessBtn.style.display === "none") {
                            // Remove the oldest post from the bottom of the list
                            postList.removeChild(postList.lastElementChild);

                            // Turn on the "Load More" button
                            if (loadMoreBtn) {
                                loadMoreBtn.style.display = "block";
                            }
                        } else {
                            currentOffset++;
                        }

                        updateTextToggles();

                        setTimeout(() => {
 statusMsg.innerHTML = "";
}, 3000);
                    } else {
                        statusMsg.innerHTML = "<span style='color:red;'>Error: " + data.error + "</span>";
                    }
                })
                .catch(error => {
                    statusMsg.innerHTML = "<span style='color:red;'>Network error.</span>";
                });
            });

            // LOAD MORE LOGIC
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    var statusMsg = document.getElementById("load_more_status");

                    statusMsg.innerHTML = "Loading...";
                    loadMoreBtn.disabled = true;

                    var formData = new FormData();
                    formData.append("action", "load_more");
                    formData.append("offset", currentOffset);
                    formData.append("sesskey", sesskey);

                    fetch(wwwroot + "/blocks/simple_blog/ajax.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        statusMsg.innerHTML = "";
                        loadMoreBtn.disabled = false;

                        if (data.success) {
                            postList.insertAdjacentHTML("beforeend", data.html);
                            currentOffset += 3;
                            showLessBtn.style.display = "block";

                            updateTextToggles();

                            if (!data.has_more) {
                                loadMoreBtn.style.display = "none";
                            }
                        } else {
                            statusMsg.innerHTML = "<span style='color:red;'>Error: " + data.error + "</span>";
                        }
                    })
                    .catch(error => {
                        statusMsg.innerHTML = "Error loading posts.";
                        loadMoreBtn.disabled = false;
                    });
                });
            }

            // SHOW LESS LOGIC
            if (showLessBtn) {
                showLessBtn.addEventListener("click", function(e) {
                    e.preventDefault();

                    while (postList.children.length > 3) {
                        postList.removeChild(postList.lastElementChild);
                    }

                    currentOffset = 3;
                    showLessBtn.style.display = "none";
                    loadMoreBtn.style.display = "block";
                });
            }
        }
    };
});