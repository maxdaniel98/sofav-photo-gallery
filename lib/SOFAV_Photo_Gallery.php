<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class SOFAV_Photo_Gallery
{
    public function init()
    {
        $this->register_post_type();

        // is admin
        if (is_admin()) {
            add_action('wp_ajax_sofav_photo_gallery_upload_new', [$this, 'handleUpload']);
            add_action('wp_ajax_sofav_photo_gallery_delete', [$this, 'removeAttachment']);
            add_action('save_post_sofav-photo-gallery', [$this, 'save_post_sofav_photo_gallery'], 10, 3);
            add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 1);
            add_action('admin_menu', [$this, 'add_import_page']);
        } else {
            add_filter('the_content', [$this, 'frontendHtml'], 1);
        }
    }

    public function add_import_page()
    {
        // Add a submenu page to the "sofav-photo-gallery" post type
        add_submenu_page(
            'edit.php?post_type=sofav-photo-gallery',
            // Parent menu slug
            __('Import as gallery', 'sofav-photo-gallery'),
            // Page title
            __('Import as gallery', 'sofav-photo-gallery'),
            // Menu title
            'manage_options',
            // Capability required to access
            'sofav-photo-gallery-import',
            // Menu slug
            [$this, 'renderImportPage'] // Callback function to render the page
        );
    }

    public function renderImportPage()
    {
        if (isset($_POST['submit'])) {
            return $this->handleSaveImportPage();
        }
        return require_once(SOFAV_PHOTO_GALLERY_PLUGIN_DIR . 'templates/import-page.php');
    }

    public function handleSaveImportPage()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        if (!$_POST['folder_path'] && !array_key_exists('zip_file', $_FILES)) {
            echo "<h2>" . __("Import photos as new gallery") . "</h2>";
            echo "Invalid or empty folder path";
            return;
        }

        $folder_path = "";

        // Check if folder path is posted
        if ($_POST['folder_path']) {
            $current_dir = getcwd();
            chdir(ABSPATH);
            $folder_path = $_POST['folder_path'];
            $realpath = realpath($folder_path);
            chdir($current_dir);
        }

        // Check if zip file is uploaded
        if ($_FILES['zip_file'] && $_FILES['zip_file']['tmp_name']) {
            $zip_file_path = $_FILES['zip_file']['tmp_name'];
            $zip = new ZipArchive;
            $res = $zip->open($zip_file_path);
            if ($res === TRUE) {
                $zip->extractTo($zip_file_path . '_extracted');
                $zip->close();
                unlink($zip_file_path);
                $realpath = realpath($zip_file_path . '_extracted');
            } else {
                echo "<h2>" . __("Import photos as new gallery") . "</h2>";
                echo "Invalid zip file";
                return;
            }
        }

        // Check if folder path is a URL
        if (filter_var($folder_path, FILTER_VALIDATE_URL)) {
            $zip_file = download_url($folder_path);
            if (is_wp_error($zip_file)) {
                echo "<h2>" . __("Import photos as new gallery") . "</h2>";
                echo "Could not download zip file from URL";
                echo "<br />";
                echo $zip_file->get_error_message();
                return;
            }
            $zip_file_path = tempnam(sys_get_temp_dir(), 'sofav-photo-gallery');
            file_put_contents($zip_file_path, $zip_file);
            $zip = new ZipArchive;
            $res = $zip->open($zip_file_path);
            if ($res === TRUE) {
                $zip->extractTo($zip_file_path . '_extracted');
                $zip->close();
                unlink($zip_file_path);
                $realpath = realpath($zip_file_path . '_extracted');
            } else {
                echo "<h2>" . __("Import photos as new gallery") . "</h2>";
                echo "Invalid zip file";
                return;
            }
        }

        if (!$realpath) {
            echo "<h2>" . __("Import photos as new gallery") . "</h2>";
            echo "Invalid folder path";
            return;
        }

        // Get files from folder
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realpath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        $images = [];
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $images[] = $file->getRealPath();
            }
        }
        $images = array_filter($images, function ($image) {
            return wp_match_mime_types('image', wp_check_filetype($image)['type']);
        });
        $images = array_values($images);

        if (count($images) == 0) {
            echo "<h2>" . __("Import photos as new gallery") . "</h2>";
            echo "No images found in folder";
            return;
        }

        // Create gallery
        $gallery = wp_insert_post([
            "post_type" => "sofav-photo-gallery",
            "post_status" => "auto-draft",
        ]);
        if (is_wp_error($gallery)) {
            echo "<h2>" . __("Import photos as new gallery") . "</h2>";
            echo $gallery->get_error_message();
            return;
        }

        // Upload images
        foreach ($images as $image) {
            $imageArray = array(
                'name' => basename($image),
                'tmp_name' => $image
            );
            media_handle_sideload($imageArray, $gallery);
        }


        // Set featured image
        $images = get_attached_media('image', $gallery);
        if ($images && count($images) > 0) {
            $attachment = $images[array_keys($images)[rand(0, count($images) - 1)]];
            set_post_thumbnail($gallery, $attachment->ID);
        }

        // Redirect to gallery
        echo "<h2>" . __("Import photos as new gallery") . "</h2>";
        echo "Gallery created successfully. Redirecting...";
        echo "<script>window.location.href = '" . get_edit_post_link($gallery, '') . "';</script>";
        return;
    }

    function frontendHtml($content)
    {
        if (!is_single('sofav-photo-gallery') && !in_the_loop() && !is_main_query())
            return $content;

        if (post_password_required()) return $content;

        $images = $this->get_images_by_post('image', get_the_ID());
        $html = '<div class="sofav-photo-gallery-post">';
        foreach ($images as $image) {
            $html .= '<a href="' . $image->guid . '" class="sofav-photo-gallery__item" data-gallery="sofav-photo-gallery">';
            $html .= wp_get_attachment_image($image->ID, [255, 0]);
            $html .= '</a>';
        }
        $html .= '</div>';

        wp_enqueue_style('sofav-photo-gallery', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/css/sofav-photo-gallery.css', array(), SOFAV_PHOTO_GALLERY_VERSION, 'all');
        wp_enqueue_style('glightbox', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/css/lib/glightbox.min.css', array(), SOFAV_PHOTO_GALLERY_VERSION, 'all');
        wp_enqueue_style('glightbox-plyr', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/css/lib/plyr.css', array(), SOFAV_PHOTO_GALLERY_VERSION, 'all');
        wp_enqueue_script('glightbox', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/js/lib/glightbox.min.js', array(), SOFAV_PHOTO_GALLERY_VERSION, true);
        wp_enqueue_script('minimasonry', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/js/lib/minimasonry.min.js', array(), SOFAV_PHOTO_GALLERY_VERSION, true);
        wp_enqueue_script('sofav-photo-gallery', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/js/sofav-photo-gallery.js', array('minimasonry'), SOFAV_PHOTO_GALLERY_VERSION, true);

        return $content . $html;
    }
    public function removeAttachment()
    {
        $attachment_id = $_POST['attachment_id'];
        $attachment = get_post($attachment_id);
        if ($attachment) {
            wp_delete_attachment($attachment_id);
            wp_send_json_success([
                "attachment" => $attachment
            ]);
        } else {
            wp_send_json([
                "message" => "Attachment not found"
            ], 404);
        }
    }

    public function handleUpload()
    {
        // Check if user is allowed to upload files
        if (!current_user_can('upload_files')) {
            wp_send_json(
                array(
                    'success' => false,
                    'error' => __('Sorry, you are not allowed to upload files.'),
                    'filename' => esc_html($_FILES['file']['name']),
                ),
                403
            );

            wp_die();
        }

        // Check if file is an image
        $wp_filetype = wp_check_filetype_and_ext($_FILES['file']['tmp_name'], $_FILES['file']['name']);

        if (!wp_match_mime_types('image', $wp_filetype['type'])) {
            wp_send_json(
                array(
                    'success' => false,
                    'error' => __('The uploaded file is not a valid image. Please try again.'),
                    'filename' => esc_html($_FILES['file']['name']),
                ),
                403
            );

            wp_die();
        }

        // Upload file
        try {
            $attachment = media_handle_upload('file', 0, $_FILES['file'], ['action' => 'sofav_photo_gallery_upload_new']);
            if (array_key_exists('post_id', $_POST) && $_POST['post_id']) {
                wp_update_post([
                    "ID" => $attachment,
                    "post_parent" => $_POST['post_id']
                ]);

                $post = get_post($_POST['post_id']);

                // Check if the gallery already has a featured image
                if (!$post->post_thumbnail) {
                    // Set the gallery's featured image to the first uploaded image
                    set_post_thumbnail($_POST['post_id'], $attachment);
                }
            }

            if (is_wp_error($attachment)) {
                wp_send_json([
                    'success' => false,
                    'error' => $attachment->get_error_message()
                ], 500);
            }
        } catch (Exception $e) {
            wp_send_json([
                'success' => false,
                'error' => e->getMessage()
            ], 500);

            wp_die();
        }

        wp_send_json_success([
            "attachment" => $attachment
        ]);

        // Don't forget to stop execution afterward.
        wp_die();
    }

    public function save_post_sofav_photo_gallery($post_id, $post, $update)
    {
        $thumb = get_post_thumbnail_id($post_id);
        if (!$thumb) {
            // Set the gallery's featured image to the first uploaded image
            $images = get_attached_media('image', $post->ID);
            if ($images && count($images) > 0) {
                $attachment = $images[array_keys($images)[rand(0, count($images) - 1)]];
                set_post_thumbnail($post->ID, $attachment->ID);
            }
        }
    }

    public function register_post_type()
    {
        register_post_type("sofav-photo-gallery", [
            "description" => __("Photo galleries", "sofav-photo-gallery"),
            "public" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_rest" => true,
            "menu_icon" => "dashicons-format-gallery",
            'supports' => array('title', 'thumbnail'),
            'taxonomies' => [],
            'labels' => array(
                'name' => __('Galleries'),
                'singular_name' => __('Galleries'),
                'add_new_item' => __('Add Gallery'),
                'add_new' => __('Add Gallery'),
                'edit_item' => __('Edit Gallery'),
                'featured_image' => __('Gallery Image'),
                'set_featured_image' => __('Set Gallery Thumbnail'),
                'remove_featured_image' => __('Random thumbnail'),
                'menu_name' => __('Galleries'),
            ),
            9
        ]);
        register_taxonomy(
            'gallery-category',
            'sofav-photo-gallery',
            array(
                'labels' => array(
                    'name' => __('Photo gallery categories'),
                    'singular_name' => __('Photo gallery category'),
                ),
                'rewrite' => array('slug' => 'sofav-photo-gallery-category'),
                'hierarchical' => true,
                'show_in_rest' => true,
            ),
            9
        );
    }

    function add_meta_boxes()
    {
        add_meta_box(
            'sofav-photo-gallery-media-uploader',
            // Unique ID
            'Upload new images',
            // Box title
            [$this, 'media_uploader_html'],
            // Content callback, must be of type callable
            "sofav-photo-gallery",
            // Post type
            "normal",
            "high",
        );
    }

    function media_uploader_html()
    {
        global $post;
        // Add .css from ../assets/css/sofav-photo-gallery-admin.css
        wp_enqueue_style('sofav-photo-gallery-admin-dropzone', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/css/lib/dropzone.css', array(), SOFAV_PHOTO_GALLERY_VERSION, 'all');
        wp_enqueue_script('sofav-photo-gallery-admin-dropzone', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/js/lib/dropzone-min.js', array(), SOFAV_PHOTO_GALLERY_VERSION, true);
        wp_enqueue_style('sofav-photo-gallery-admin', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/css/sofav-photo-gallery-admin.css', array(), SOFAV_PHOTO_GALLERY_VERSION, 'true');
        wp_enqueue_script('sofav-photo-gallery-admin', SOFAV_PHOTO_GALLERY_PLUGIN_URL . 'assets/js/sofav-photo-gallery-admin.js', array(), SOFAV_PHOTO_GALLERY_VERSION, true);

        $attached_images = ($post && $post->ID) ? $this->get_images_by_post('image', $post->ID) : '';
        $scaled_attached_images = [];
        foreach ($attached_images as $image) {
            $scaled_attached_images[] = [
                "ID" => $image->ID,
                "guid" => $image->guid,
                "url" => wp_get_attachment_image_url($image->ID, [255, 0]),
                "title" => $image->post_title,
                "alt" => get_post_meta($image->ID, '_wp_attachment_image_alt', true)
            ];
        }
        $current_post_id = $post ? $post->ID : '';

        return require_once(SOFAV_PHOTO_GALLERY_PLUGIN_DIR . 'templates/media-uploader.php');
    }

    private function get_images_by_post($type, $post)
    {
        $post = get_post($post);

        if (!$post) {
            return array();
        }

        $args = array(
            'post_parent' => $post->ID,
            'post_type' => 'attachment',
            'post_mime_type' => $type,
            'posts_per_page' => -1,
            'orderby' => 'post_title', 
            'order' => 'ASC'
        );
        $args = apply_filters('get_attached_media_args', $args, $type, $post);

        $args['orderby'] = 'post_title'; // [1]
        $args['order'] = 'ASC'; // [1]
        $children = get_children( $args );
        
        return (array) apply_filters( 'get_attached_media', $children, $type, $post );
    }

    public function activate()
    {
        $this->register_post_type();
        flush_rewrite_rules();
    }
    public function deactivate()
    {
        update_option('sofav_photo_gallery_activated', false);
    }
    public function uninstall()
    {

    }
    public function activation_notice()
    {
        // Check if the plugin is already activated.
        if (get_option('sofav_photo_gallery_activated')) {
            return;
        }

        // Set the activation flag.
        update_option('sofav_photo_gallery_activated', true);

        // Display the activation notice.
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php _e('SOFAV Photo Gallery is now active!', 'sofav-photo-gallery'); ?>
            </p>
        </div>
        <?php
    }
}