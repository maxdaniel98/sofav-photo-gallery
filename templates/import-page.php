<?php
function custom_import_settings_init()
{
    add_settings_section(
        'import_folder_section',
        'Import from Folder',
        'import_folder_section_callback',
        'sofav-photo-gallery-import'
    );

    add_settings_field(
        'folder_path',
        'Folder Path',
        'folder_path_callback',
        'sofav-photo-gallery-import',
        'import_folder_section'
    );

    add_settings_field(
        'separator',
        'OR',
        'separator_callback',
        'sofav-photo-gallery-import',
        'import_folder_section'
    );

    add_settings_field(
        'zip_file',
        'ZIP File',
        'zip_file_callback',
        'sofav-photo-gallery-import',
        'import_folder_section'
    );
}
function import_folder_section_callback()
{
    echo '<p>Enter a folder path (for example, wp-content/my-photo-album), or a full URL containing a zip file. You can also upload a .zip file here.</p>';
}

function folder_path_callback()
{
    echo '<input type="text" name="folder_path" placeholder="wp-content/uploads/" />';
    echo '<p class="description">Enter a folder path (for example, wp-content/my-photo-album), or an URL to a zip file</p>';
}

function separator_callback()
{
    echo '<hr />';
}

function zip_file_callback()
{
    echo '<input type="file" name="zip_file" />';
    $max_upload_size = size_format(wp_max_upload_size());
    echo '<p class="description">Upload a zip file with a maximum of '.$max_upload_size.'</p>';
}

custom_import_settings_init();

?>

<div class="wrap">
    <h2><?php echo __("Import photos as new gallery") ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php settings_fields('sofav-photo-gallery-import_group'); ?>
        <?php do_settings_sections('sofav-photo-gallery-import'); ?>
        <?php submit_button(__('Import photos')); ?>
    </form>
</div>