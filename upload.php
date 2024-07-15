<?php
include("paths.php");
require_once(VENDOR_PATH . "/autoload.php");

require_once("FileUpload.class.php");

$paramName = 'image_field';
$uploadCfg = [
  'path' => UP_PATH,
  'max_file_size' => 1,
  'max_width' => 2000,
  'safe_name' => false,
  'resize' => true,
  'width' => 250,
  'height' => 250,
  'resize_mode' => 'crop',
];
$fileUpload = new FileUpload($uploadCfg);

echo "<pre>";
print_r($fileUpload->imageUpload($_FILES[$paramName]));
echo "</pre>";
exit();

/* 
use Verot\Upload\Upload;

$handle = new Upload($_FILES['image_field']);
if ($handle->uploaded) {
  //$handle->file_new_name_body   = 'image_resized';
  $handle->webp_quality = 90;
  $handle->jpeg_quality = 90;
  $handle->image_convert = 'jpg';
  //$handle->image_resize         = true;
  //$handle->image_x              = 250;
  //$handle->image_ratio_y        = true;
  $handle->process(UP_PATH);
  if ($handle->processed) {
    echo 'success!';
    $handle->clean();
  } else {
    echo 'error : ' . $handle->error;
  }
} */