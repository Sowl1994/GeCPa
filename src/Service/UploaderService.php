<?php

namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderService
{
    /*Esta variable esta enlazada en services.yaml a la ruta por defecto de las subidas de fichero -> bind: $uploadPath*/
    private $uploadPath;
    public function __construct(string $uploadPath)
    {
        $this->uploadPath = $uploadPath;
    }

    public function uploadClientImage(UploadedFile $avatar):string{
        //Elegimos la carpeta de destino y le modificamos el nombre con un id unico
        $destiny = $this->uploadPath.'/client_avatar';
        $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = uniqid().'.'.$avatar->guessExtension();
        //Movemos la imagen al directorio especificado
        $avatar->move($destiny, $newFilename);
        return $newFilename;
    }
}