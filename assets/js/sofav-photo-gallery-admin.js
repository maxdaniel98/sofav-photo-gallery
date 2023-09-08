let myDropzone = new Dropzone("#sofavPhotoGalleryUploader", {
    url: ajaxurl,
    paramName: "file",
    addRemoveLinks: true,
    parallelUploads: 3,
    init: function() {
        this.on("sending", function(file, xhr, formData){
                formData.append("name", file.name);
                formData.append("post_id", currentPostId);
                formData.append("action", "sofav_photo_gallery_upload_new");
        });
        this.on("success", function(file) {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "sofav_photo_gallery_images[]";
            const response = JSON.parse(file.xhr.response);
            input.value = response.data.attachment;
            document.querySelector("#uploadedImages").appendChild(input);
        });
        this.on("removedfile", function(file) {
            const images = document.querySelectorAll("#uploadedImages input");
            let xhr = new XMLHttpRequest();
            xhr.open("POST", ajaxurl, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            var attachment_id = "";
            if (file.xhr && file.xhr.response) {
                var response = JSON.parse(file.xhr.response).data.response;
                attachment_id = response.data.attachment;
            }else if (file.attachment_id){
                attachment_id = file.attachment_id;
            }
        
            xhr.send("action=sofav_photo_gallery_delete&attachment_id=" + attachment_id);


            for (let i = 0; i < images.length; i++) {
                if (images[i].value === file.xhr.response) {
                    images[i].remove();
                }
            }
        });

        for (let image in existingImages){
            this.displayExistingFile({
                name: existingImages[image].post_title,
                attachment_id: existingImages[image].ID,
            }, existingImages[image].url);
        }
    }
});

wp.media.controller.Library.prototype.defaults.filterable = "uploaded";