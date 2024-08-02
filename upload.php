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
  'max_file_size' => 1,
  //'max_width' => 2000,
  'safe_name' => false,
  'resize' => false,
  'width' => 0,
  'height' => 0,
  'resize_mode' => '',
  'thumbs' => [
    [
      'width' => 500,
      'height' => 0,
      'resize_mode' => 'auto',
    ],
    [
      'width' => 150,
      'height' => 150,
      'resize_mode' => 'crop',
    ]
  ],
];
$fileUpload = new FileUpload($uploadCfg);

$paramName2 = 'arquivo';
$uploadCfg2 = [
  'path' => UP_PATH,
  'allowed_files' => ['pdf','txt'],
  'max_file_size' => 10,
  'safe_name' => true,
];
$fileUpload2 = new FileUpload($uploadCfg2);

echo "<pre>";
print_r($fileUpload->imageUpload($_FILES[$paramName]));
//print_r($fileUpload->imageUploadMultiple($paramName));
//print_r($fileUpload2->fileUpload($_FILES[$paramName2]));
echo "</pre>";
exit();