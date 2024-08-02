<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Múltiplas Imagens</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
    <style>
        .dz-error .dz-error-message {
            display: none; /* Esconde a mensagem de erro padrão do Dropzone */
        }
    </style>
</head>
<body>
    <h1>Upload de Múltiplas Imagens</h1>
    <form action="upload2.php" class="dropzone" id="imageDropzone"></form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    <script>
        Dropzone.options.imageDropzone = {
            paramName: "fotos", // The name that will be used to transfer the file
            maxFilesize: 10, // MB
            acceptedFiles: "image/*",
            dictDefaultMessage: "Arraste as imagens aqui para fazer o upload",
            addRemoveLinks: true, // Adiciona o botão de remoção
            parallelUploads: 1,
            init: function() {
                this.on("success", function(file, response) {
                    try {
                        // Parse a resposta como JSON
                        var jsonResponse = JSON.parse(response);
                        if (jsonResponse.status === 'error') {
                            // Trate a resposta como erro
                            this.emit("error", file, jsonResponse.message);
                            this.emit("complete", file);
                        } else {
                            console.log("Upload bem-sucedido:", jsonResponse);
                        }
                    } catch (e) {
                        // Caso a resposta não seja JSON, trate como erro
                        this.emit("error", file, "Erro ao parsear resposta do servidor");
                        this.emit("complete", file);
                    }
                });
                this.on("error", function(file, response) {
                    var message = typeof response === 'string' ? response : response.message;
                    alert("Erro no upload: " + message);
                    console.log("Erro no upload:", response);
                    
                    // Exibe o botão de remoção
                    var removeButton = Dropzone.createElement("<button class='dz-remove'>Remover arquivo</button>");
                    var _this = this; // Referência ao Dropzone
                    removeButton.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        _this.removeFile(file);
                    });
                    file.previewElement.appendChild(removeButton);
                });
            }
        };
    </script>
</body>
</html>
