<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload de Imagens</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
</head>
<body>
    <form action="upload2.php" class="dropzone" id="image-upload">
        <div class="dz-message">
            Arraste e solte as imagens aqui ou clique para fazer upload.
        </div>
    </form>

    <div id="upload-progress"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    <script>
        Dropzone.options.imageUpload = {
            paramName: "file", // O nome do parâmetro que será enviado ao servidor
            maxFilesize: 2, // Tamanho máximo do arquivo em MB
            acceptedFiles: "image/*", // Apenas aceita arquivos de imagem
            uploadMultiple: false, // Processar um arquivo por vez
            init: function() {
                this.on("uploadprogress", function(file, progress) {
                    document.querySelector("#upload-progress").innerHTML = "Progresso: " + Math.round(progress) + "%";
                });

                this.on("success", function(file, response) {
                    console.log(response);
                    // Aqui você pode manipular a resposta do servidor após o upload
                });

                this.on("error", function(file, errorMessage, xhr) {
                    console.log(errorMessage);
                    // Manipular erro de upload, se necessário
                });
            }
        };
    </script>
</body>
</html>
