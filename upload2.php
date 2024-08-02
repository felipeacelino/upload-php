<?php
include("paths.php");
require_once(VENDOR_PATH . "/autoload.php");

require_once("FileUpload.class.php");

ini_set('display_errors', 1);
ini_set('display_startup_erros', 1);
error_reporting(E_ALL);

$paramName = 'fotos';
$uploadCfg = [
  'path' => UP_PATH,
  'allowed_files' => ['jpg','jpeg','png','webp','gif'],
  'max_file_size' => 10,
  //'max_width' => 2000,
  'safe_name' => false,
  'resize' => false,
  'width' => 0,
  'height' => 0,
  'resize_mode' => '',
  'thumbs' => [
    [
      'width' => 150,
      'height' => 150,
      'resize_mode' => 'crop',
    ]
  ],
];
$fileUpload = new FileUpload($uploadCfg);

$upload = $fileUpload->imageUpload($_FILES[$paramName]);
sleep(1);
if (isset($upload['http_status'])) {
  //http_response_code($upload['http_status']);
  http_response_code(200);
}
echo json_encode($upload);