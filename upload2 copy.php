<?php
include("paths.php");
require_once(VENDOR_PATH . "/autoload.php");

use Verot\Upload\Upload;

$response = array();
$upload_dir = 'uploads/';

if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['file'])) {
  $handle = new Upload($_FILES['file']);
  
  if ($handle->uploaded) {
    // Nome do arquivo
    $handle->file_new_name_body = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
    $handle->file_new_name_ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    
    // Redimensionar imagem
    $handle->image_resize = true;
    $handle->image_x = 800;
    $handle->image_ratio_y = true;
    
    // Pasta de destino
    $handle->process($upload_dir);
    
    if ($handle->processed) {
      $response = array(
        'name' => $handle->file_dst_name,
        'size' => $handle->file_src_size,
        'url' => $upload_dir . $handle->file_dst_name
      );
      $handle->clean();
    } else {
      $response = array(
        'error' => $handle->error
      );
    }
  } else {
    $response = array(
      'error' => $handle->error
    );
  }
} else {
  $response = array(
    'error' => 'No file was uploaded.'
  );
}

echo json_encode($response);
?>