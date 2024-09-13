<?php

use App\Exceptions\OperationException;
use Illuminate\Http\UploadedFile;

function UploadFile($file, $path, $encrypt = true)
{
    if ($file instanceof UploadedFile && $file->isValid() && $file->getSize() < 16777216) {
        try {
            $extension = $file->extension();

            $md5_file =  md5($file->getClientOriginalName() . date("Y-m-d H:i:s"));
            $file_name = $encrypt ? $md5_file . ".{$extension}" : $file->getClientOriginalName();

            $file->storeAs($path, $file_name, 's3');

            return $file_name;
        } catch (OperationException $e) {
            report($e);
            return '';
        }
    }
}

function UploadFileBase64($base64String, $path, $encrypt = true)
{
    $uploadPath = public_path($path);

    // Verifica se o diretório existe
    if (!file_exists($uploadPath)) {
        // Cria o diretório se não existir
        mkdir($uploadPath, 0777, true);
    }

    // Extrair dados da string base64
    list($type, $data) = explode(';', $base64String);
    list(, $data)      = explode(',', $data);
    $data = base64_decode($data);

    // Determinar a extensão do arquivo
    preg_match('/image\/(.*?);/', $base64String, $extensionArray);
    $extension = $extensionArray[1];

    // Gerar nome do arquivo
    $md5_file = md5(uniqid() . date("Y-m-d H:i:s"));
    $file_name = $encrypt ? $md5_file . ".{$extension}" : $md5_file;

    // Definir o caminho completo do arquivo
    $file_path = public_path($path) . '/' . $file_name;

    // Salvar o arquivo
    $file_path = $uploadPath . '/' . $file_name;
    file_put_contents($file_path, $data);

    // Retornar o nome do arquivo
    return $file_name;
}
