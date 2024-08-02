<?php

use Verot\Upload\Upload;

class FileUpload 
{
  // Atributos
  protected $handle = null; // Instância do upload
  protected $error = ""; // Mensagem de erro
  protected $pathUpload = null; // Caminho do upload dos arquivos
  protected $allowedFiles = []; // Tipos de arquivos permitidos
  protected $quality = 90; // Qualidade de compressão
  protected $maxFileSize = 10; // Tamanho máximo do arquivo em megas (MB)
  protected $maxWidth = 0; // Largura máxima da imagem
  protected $safeName = []; // Ao invez de gerar um nome único aleatório, apenas limpa o nome original do arquivo
  protected $resize = false; // Se a imagem será redimensionada
  protected $resizeMode = 'auto'; // Forma de corte da imagem (auto: mantém proporção / crop: corte exato)
  protected $width = 0; // Largura em pixels da nova imagem
  protected $height = 0; // Altura em pixels da nova imagem
  protected $thumbs = 0; // Lista de miniaturas a serem criadas

  // Construtor
  public function __construct($cfg = []) 
  {
    $this->pathUpload = $cfg['path'] ?? $this->pathUpload;
    $this->quality = $cfg['quality'] ?? $this->quality;
    $this->maxFileSize = $cfg['max_file_size'] ?? $this->maxFileSize;
    $this->allowedFiles = $cfg['allowed_files'] ?? $this->allowedFiles;
    $this->safeName = $cfg['safe_name'] ?? $this->safeName;
    $this->maxWidth = $cfg['max_width'] ?? $this->maxWidth;
    $this->resize = $cfg['resize'] ?? false;
    $this->resizeMode = $cfg['resize_mode'] ?? 'auto';
    $this->width = $cfg['width'] ?? 0;
    $this->height = $cfg['height'] ?? 0;
    $this->thumbs = $cfg['thumbs'] ?? [];
  }

  // Configurações globais do upload
  private function applyGlobalOpts($handle) {
    $handle->file_overwrite = true;
    // $handle->no_script = false;
    $handle->jpeg_quality = $this->quality;
    $handle->webp_quality = $this->quality;
    if (is_array($this->allowedFiles) && count($this->allowedFiles) > 0) {
      $handle->allowed = $this->allowedFiles;
    }
  }

  // Realiza o upload de uma imagem
  public function imageUpload($file) 
  { 
    // Variáveis
    $response = [];
    $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileSize = $file['size'];
    $fileSizeMb = self::byteToMb($fileSize);
    // Verifica o tamanho do arquivo
    if ($fileSizeMb <= $this->maxFileSize) {
      $this->handle = new Upload($file, 'pt_BR');
      // Verifica o envio
      if ($this->handle->uploaded) {
        // Opções globais
        $this->applyGlobalOpts($this->handle);
        // Configurações variáveis
        if ($this->maxWidth > 0) {
          $this->handle->image_max_width = (int) $this->maxWidth;
        }
        // Converte a imagem de WEBP para JPG ou PNG
        if (strtolower($this->handle->file_src_name_ext) === 'webp' && $this->webpsupport()) {
          $originalExt = $this->webpConvertExt($this->handle->file_src_pathname);
          if ($originalExt === 'jpg' || $originalExt === 'png') {
            $this->handle->image_convert = $originalExt;
          }
        }
        // Converte a imagem JPEG para JPG
        if (strtolower($this->handle->file_src_name_ext) === 'jpeg') {
          $this->handle->image_convert = 'jpg';
        }
        // Nome do arquivo
        if ($this->safeName) {
          //$this->handle->file_safe_name = true;
          $this->handle->file_new_name_body = $this->safeName($fileName);
        } else {
          $this->handle->file_new_name_body = $this->generateFileName();
        }
        // Redimensiona a imagem
        if ($this->resize && $this->width > 0 && ($this->resizeMode === 'auto' || $this->resizeMode === 'crop')) {
          $this->handle->image_resize = true;
          $this->handle->image_x = (int) $this->width;
          $this->handle->image_ratio_crop = true;
          // Altura proporcional
          if ($this->resizeMode === 'auto') { 
            $this->handle->image_ratio_y = true;
          }
          // Corte exato
          else if ($this->resizeMode === 'crop') {
            $this->handle->image_y = (int) $this->height;
          }
        }
        // Processa o arquivo
        $this->handle->process($this->pathUpload);
        if ($this->handle->processed) {
          $fileDest = $this->handle->file_dst_name;
          $fileDestName = $this->handle->file_dst_name_body;
          $fileDestExt = $this->handle->file_dst_name_ext;
          $filePath = $this->pathUpload . '/' . $fileDest;
          $response['status'] = 'success';
          $response['http_status'] = 200;
          $response['file'] = $fileDest;
          $response['path'] = $filePath;
          $response['width'] = $this->handle->image_dst_x;
          $response['height'] = $this->handle->image_dst_y;
          $this->handle->clean();
          // Thumbs
          if (!empty($this->thumbs)) {
            $response['thumbs'] = [];
            if ($fileDestExt === 'jpg' || $fileDestExt === 'png') {
              foreach ($this->thumbs as $thumb) {
                $thumbWidth = $thumb['width'] ?? '0';
                $thumbHeight = $thumb['height'] ?? '0';
                $thumbResizeMode = $thumb['resize_mode'] ?? 'auto';
                $thumbPath = $this->pathUpload . '/thumb-' . $thumbWidth . '-' . $thumbHeight;
                $response['thumbs'][] = $this->createThumb($filePath, $thumbPath, $thumbWidth, $thumbHeight, $thumbResizeMode);
              }
            }
          }
        } else {
          $this->setError($this->handle->error);
          $response['status'] = 'error';
          $response['http_status'] = 500;
          $response['message'] = $this->handle->error;
        }
      } else {
        $this->setError($this->handle->error);
        $response['status'] = 'error';
        $response['http_status'] = 400;
        $response['message'] = $this->handle->error;
      }
    } 
    // Erro (Tamanho do arquivo)
    else {
      $this->setError('Arquivo muito grande.');
      $response['status'] = 'error';
      $response['http_status'] = 400;
      $response['message'] = 'Arquivo muito grande (Max: ' . $this->maxFileSize . 'MB)';
    }
    return $response;
  }

  // Cria uma miniatura
  private function createThumb($file, $savePath, $width = 0, $height = 0, $resizeMode = 'auto') 
  {
    $response = [];
    // Verifica se o arquivo existe
    if (file_exists($file)) {
      // Variáveis
      $fileName = pathinfo($file, PATHINFO_FILENAME);
      $fileExt = pathinfo($file, PATHINFO_EXTENSION);
      $handleThumb = new Upload($file, 'pt_BR');
      // Verifica o envio
      if ($handleThumb->uploaded) {
        // Opções globais
        $this->applyGlobalOpts($handleThumb);
        // Nome do arquivo
        $handleThumb->file_new_name_body = $fileName;
        // Redimensiona a imagem
        $handleThumb->image_resize = true;
        $handleThumb->image_x = (int) $width;
        $handleThumb->image_ratio_crop = true;
        // Altura proporcional
        if ($resizeMode === 'auto') { 
          $handleThumb->image_ratio_y = true;
        }
        // Corte exato
        else if ($resizeMode === 'crop') {
          $handleThumb->image_y = (int) $height;
        }
        // Processa o arquivo
        $handleThumb->process($savePath);
        if ($handleThumb->processed) {
          $response['status'] = 'success';
          $response['http_status'] = 200;
          $response['file'] = $handleThumb->file_dst_name;
          $response['path'] = $savePath . '/' . $handleThumb->file_dst_name;
          $response['width'] = $handleThumb->image_dst_x;
          $response['height'] = $handleThumb->image_dst_y;
        } else {
          $this->setError($handleThumb->error);
          $response['status'] = 'error';
          $response['http_status'] = 500;
          $response['message'] = $handleThumb->error;
        }
      } else {
        $this->setError($handleThumb->error);
        $response['status'] = 'error';
        $response['http_status'] = 400;
        $response['message'] = $handleThumb->error;
      }
    } else {
      $this->setError('Arquivo não encontrado.');
      $response['status'] = 'error';
      $response['http_status'] = 400;
      $response['message'] = 'Arquivo não encontrado.';
    }
    return $response;
  }

  // Realiza o upload de múltiplas imagens
  public function imageUploadMultiple($filesParam) 
  {
    $files = $this->normalizeFilesArr($_FILES);
    $files = $files[$filesParam];
    $response = [];
    if (is_array($files) && count($files) > 0) {
      $response['files'] = [];
      $response['files_success'] = 0;
      $response['files_error'] = 0;
      foreach ($files as $fileItem) {
        $uploadItem = $this->imageUpload($fileItem);
        if ($uploadItem['status'] === 'success') {
          $response['files_success']++;
        } else if ($uploadItem['status'] === 'error') {
          $uploadItem['file'] = $fileItem;
          $response['files_error']++;
        }
        $response['files'][] = $uploadItem;
      }
      $response['status'] = 'success';
      $response['http_status'] = 200;
    } else {
      $this->setError('Nenhum arquivo enviado.');
      $response['status'] = 'error';
      $response['http_status'] = 400;
      $response['message'] = 'Nenhum arquivo enviado.';
    }
    return $response;
  }

  // Realiza o upload de uma arquivo
  public function fileUpload($file) 
  { 
    // Variáveis
    $response = [];
    $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileSize = $file['size'];
    $fileSizeMb = self::byteToMb($fileSize);
    // Verifica o tamanho do arquivo
    if ($fileSizeMb <= $this->maxFileSize) {
      $this->handle = new Upload($file, 'pt_BR');
      // Verifica o envio
      if ($this->handle->uploaded) {
        // Opções globais
        $this->applyGlobalOpts($handleThumb);
        // Nome do arquivo
        if ($this->safeName) {
          //$this->handle->file_safe_name = true;
          $this->handle->file_new_name_body = $this->safeName($fileName);
        } else {
          $this->handle->file_new_name_body = $this->generateFileName();
        }
        // Processa o arquivo
        $this->handle->process($this->pathUpload);
        if ($this->handle->processed) {
          $fileDest = $this->handle->file_dst_name;
          $fileDestName = $this->handle->file_dst_name_body;
          $fileDestExt = $this->handle->file_dst_name_ext;
          $filePath = $this->pathUpload . '/' . $fileDest;
          $response['status'] = 'success';
          $response['http_status'] = 200;
          $response['file'] = $fileDest;
          $response['path'] = $filePath;
          $this->handle->clean();
        } else {
          $this->setError($this->handle->error);
          $response['status'] = 'error';
          $response['http_status'] = 500;
          $response['message'] = $this->handle->error;
        }
      } else {
        $this->setError($this->handle->error);
        $response['status'] = 'error';
        $response['http_status'] = 400;
        $response['message'] = $this->handle->error;
      }
    } 
    // Erro (Tamanho do arquivo)
    else {
      $this->setError('Arquivo muito grande.');
      $response['status'] = 'error';
      $response['http_status'] = 400;
      $response['message'] = 'Arquivo muito grande (Max: ' . $this->maxFileSize . 'MB)';
    }
    return $response;
  }

  // Converte o tamanho do arquivo de bytes para mega
  public function byteToMb($sizeBytes) 
  {
    return number_format($sizeBytes / (1024 * 1024), 2, '.', '');
  }
  
  // Converte o tamanho do arquivo de mega para bytes
  public function mbToByte($sizeMegas) 
  {
    return $sizeMegas * 1024 * 1024;
  }

  // Gera um nome único e aleatório para o arquivo
  private function generateFileName($fileExt = false) {
    $fileName = bin2hex(random_bytes(16));
    if ($fileExt) {
      return $fileName . '.' . $fileExt;
    }
    return $fileName;
  }

  // Limpa o nome do arquivo
  private function safeName($fileName)
  {
    $safeName = trim($fileName);
    $safeName = strtolower($fileName);
    $find = array('á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ü', 'ç', 'Á', 'À', 'Ã', 'Â', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', 'Ü', 'Ç', '&');
    $replace = array('a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'u', 'c', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'u', 'c', 'e');
    $safeName = str_replace($find, $replace, $safeName);
    $safeName = preg_replace("/[^a-z0-9_\s-]/", "", $safeName);
    $safeName = preg_replace("/[\s-]+/", " ", $safeName);
    $safeName = preg_replace("/[\s_]/", "-", $safeName);
    return $safeName;
  }

  // Remove um arquivo
  public function removeFile($filePath, $thumbs = false) 
  {
    if (file_exists($filePath) && !is_dir($filePath)) {
      $pathInfo = pathinfo($filePath);
      $fileName = $pathInfo['basename'];
      // Remove as thumbs
      if ($thumbs && !empty($this->thumbs)) {
        foreach ($this->thumbs as $thumb) {
          $thumbWidth = $thumb['width'] ?? '0';
          $thumbHeight = $thumb['height'] ?? '0';
          $thumbPath = $this->pathUpload . '/thumb-' . $thumbWidth . '-' . $thumbHeight;
          $thumbFile = $thumbPath . '/' . $fileName;
          if (file_exists($thumbFile)) {
            unlink($thumbFile);
          }
        }
      }
      // Remove o arquivo
      return unlink($filePath);
    }
    return false;
  }

  // Remove um diretório
	public function removeDir($pathDir) 
  {
		if (file_exists($pathDir)) {
			$files = glob($pathDir . '/*');
			foreach ($files as $file) {
				is_dir($file) ? self::removeDir($file) : unlink($file);
			}
			rmdir($pathDir);			
		}
    return;
  }

  // Retorna o erro
  public function getError() 
  {
    return $this->error;
  }

  // Seta o erro
  private function setError($error) 
  {
    $this->error = $error;
  }

  // Padroniza o array de arquivos enviados no formulário
  public function normalizeFilesArr($files) {
    $out = [];
    foreach ($files as $key => $file) {
      if (isset($file['name']) && is_array($file['name'])) {
        $new = [];
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
          array_walk_recursive($file[$k], function (&$data, $key, $k) {
            $data = [$k => $data];
          }, $k);
          $new = array_replace_recursive($new, $file[$k]);
        }
        $out[$key] = $new;
      } else {
        $out[$key] = $file;
      }
    }
    return $out;
  }

  // Remove os campos vazios do array
  function removeEmptys($array) 
  {
    $newArray = $array;
    foreach ($newArray['name'] as $index => $value) {
      if ($value == "") {
        unset($newArray['name'][$index]);
        unset($newArray['type'][$index]);
        unset($newArray['tmp_name'][$index]);
        unset($newArray['error'][$index]);
        unset($newArray['size'][$index]);
      }
    }
    return $newArray;
  }

  // Verifica se o GD possui suporte para WEBP
  public function webpSupport() {
    $gdInfo = gd_info();
    return isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
  }

  // Retorna a extensão original de uma imagem WEBP
  public function webpConvertExt($file) {
    $webpInfo = $this->webpinfo($file);
    $originalExt = 'jpg';
    if (is_array($webpInfo) && isset($webpInfo['Animation']) && $webpInfo['Animation'] === true) {
      $originalExt = 'gif';
    } else if (is_array($webpInfo) && isset($webpInfo['Alpha']) && $webpInfo['Alpha'] === true) {
      $originalExt = 'png';
    }
    return $originalExt;
  }

  // Retorna informações sobre uma imagem WEBP
  public function webpinfo($file) {
    if (!is_file($file)) {
      return false;
    } else {
      $file = realpath($file);
    }
    $fp = fopen($file, 'rb');
    if (!$fp) {
      return false;
    }
    $data = fread($fp, 90);
    fclose($fp);
    unset($fp);
    $header_format = 'A4Riff/' . // get n string
    'I1Filesize/' . // get integer (file size but not actual size)
    'A4Webp/' . // get n string
    'A4Vp/' . // get n string
    'A74Chunk';
    $header = unpack($header_format, $data);
    unset($data, $header_format);
    if (!isset($header['Riff']) || strtoupper($header['Riff']) !== 'RIFF') {
      return false;
    }
    if (!isset($header['Webp']) || strtoupper($header['Webp']) !== 'WEBP') {
      return false;
    }
    if (!isset($header['Vp']) || strpos(strtoupper($header['Vp']), 'VP8') === false) {
      return false;
    }
    if (
    strpos(strtoupper($header['Chunk']), 'ANIM') !== false ||
    strpos(strtoupper($header['Chunk']), 'ANMF') !== false
    ) {
      $header['Animation'] = true;
    } else {
      $header['Animation'] = false;
    }
    if (strpos(strtoupper($header['Chunk']), 'ALPH') !== false) {
      $header['Alpha'] = true;
    } else {
      if (strpos(strtoupper($header['Vp']), 'VP8L') !== false) {
        // if it is VP8L, I assume that this image will be transparency
        // as described in https://developers.google.com/speed/webp/docs/riff_container#simple_file_format_lossless
        $header['Alpha'] = true;
      } else {
        $header['Alpha'] = false;
      }
    }
    unset($header['Chunk']);
    return $header;
  }

}