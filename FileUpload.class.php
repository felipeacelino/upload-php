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
  }

  // Configurações globais do upload
  private function applyGlobalOpts() {
    $this->handle->file_overwrite = true;
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
        $this->applyGlobalOpts();
        // Configurações variáveis
        $this->handle->jpeg_quality = $this->quality;
        $this->handle->webp_quality = $this->quality;
        if ($this->maxWidth > 0) {
          $this->handle->image_max_width = (int) $this->maxWidth;
        }
        // Verifica se é imagem
        if (!$this->handle->file_is_image) {
          $this->setError('O arquivo não é uma imagem válida.');
          $response['status'] = 'error';
          $response['message'] = 'O arquivo não é uma imagem válida.';
          return $response;
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
          $response['status'] = 'success';
          $response['file'] = $this->handle->file_dst_name;
          $response['path'] = $this->pathUpload . '/' . $this->handle->file_dst_name;
          $this->handle->clean();
        } else {
          $this->setError($this->handle->error);
          $response['status'] = 'error';
          $response['message'] = $this->handle->error;
        }
      } else {
        $this->setError($this->handle->error);
        $response['status'] = 'error';
        $response['message'] = $this->handle->error;
      }
    } 
    // Erro (Tamanho do arquivo)
    else {
      $this->setError('Arquivo muito grande.');
      $response['status'] = 'error';
      $response['message'] = 'Arquivo muito grande (Max: ' . $this->maxFileSize . 'MB)';
    }
    return $response;
  }

  // Cria uma miniatura
  public function createThumb($file, $savePath, $width, $height, $crop) 
  {
    return true;
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

}