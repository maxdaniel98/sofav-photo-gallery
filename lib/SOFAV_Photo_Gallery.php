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
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        } else {
            add_filter('the_content', [$this, 'frontendHtml'], 1);
        }
    }

    function frontendHtml($content)
    {
        if (!is_single('sofav-photo-gallery') && !in_the_loop() && !is_main_query())
            return $content;

        $images = get_attached_media('image', get_the_ID());
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
            if ($images && count($images) > 0){
                $attachment = array_shift($images);
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
                'name' => __('Galleries', 'sofav-photo-gallery'),
                'singular_name' => __('Gallery', 'sofav-photo-gallery'),
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
            'wporg_box_id',
            // Unique ID
            'Upload new images',
            // Box title
            [$this, 'media_uploader_html'],
            // Content callback, must be of type callable
            "sofav-photo-gallery",
            // Post type
            "advanced",
            "core"
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

        $attached_images = ($post && $post->ID) ? get_attached_media('image', $post->ID) : '';
        $current_post_id = $post ? $post->ID : '';

        return require_once(SOFAV_PHOTO_GALLERY_PLUGIN_DIR . 'templates/media-uploader.php');
    }

    public function activate()
    {

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