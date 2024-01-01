# SOFAV Photo Gallery WordPress plugin
> A WordPress plugin to create a photo gallery, upload photos and manage them in categories.

## Installation
1. Download the plugin .zip file
2. Go to Plugins > Add New > Upload Plugin
3. Upload the .zip file

## First time setup
1. Navigate to Pages
2. Go to the page where you want to show the gallery list
3. From the Blocks panel (on the left) add a new block called "Photo Gallery List"
4. If you only want to show a specific category, go to "Filters" on the right hand side, and add the "Taxonomies" field. There you can enter a comma separated list of categories to show. If you want to show all categories, leave this field empty.

## Usage
### Add a new gallery
1. Navigate to Photo Galleries > Add New
2. Enter a title
3. Upload all the photos by dragging them in the dashed box or click on the box to open the file browser
4. Wait for the images to be uploaded, and click publish on the right hand side
5. If you want to add categories, click on the "Categories" tab on the right hand side, and add a new category or choose one from the list
6. If you want to add a thumbnail image, click on the "Set Gallery Thumbnail" tab on the right hand side, and choose an image from the list

### Import a gallery
> It is possible to import galleries from a .zip file or folder on your server. 
>
1. Navigate to Photo Galleries > Import
2. Enter the full path of your folder or .zip file in the "Folder Path" field (e.g. /var/www/wp-content/uploads/2021/01/gallery.zip)
3. Or, if you want to download the .zip from another server, enter the full URL of the .zip file in the "Folder Path" field (e.g. https://example.com/gallery.zip)
4. You can also select a .zip file to upload from your computer. Note the upload limit of your server.
5. Click on "Import photos"
6. This process could take a while, depending on the amount of photos and the size of the .zip file
7. After the import is finished, you can edit the gallery and add categories and a thumbnail image

## Development
This plugin should be fairly straightforward to develop further. Most of the business logic is located in `lib/SOFAV_Photo_Gallery.php`. If you want your changes to be pulled, just create a pull request. 

## Licence
SOFAV Photo Gallery is licensed under the GNU General Public License v3.0. See the [LICENSE](LICENSE.txt) file for more details.

    SOFAV Photo Gallery WordPress plugin
    Copyright (C) 2023 Max van den Bosch

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.