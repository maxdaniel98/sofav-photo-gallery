<div>
    <div id="sofavPhotoGalleryUploader" class="dropzone"></div>
</div>

<div id="uploadedImages">
    
</div>

<script>
    var asyncUploadUrl = "<?php echo admin_url( 'async-upload.php', 'relative' ) ?>";
    var existingImages = <?php echo json_encode($scaled_attached_images); ?>;
    var currentPostId = <?php echo $current_post_id ?>;
</script>