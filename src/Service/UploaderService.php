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

    public function uploadImage(UploadedFile $avatar, $type):string{
        //Elegimos la carpeta de destino y le modificamos el nombre con un id unico
        $destiny = $this->uploadPath.'/'.$type;
        $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
        $imageFileType = strtolower(pathinfo($avatar->getClientOriginalExtension(), PATHINFO_FILENAME));
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
                return "0";
        }
        $newFilename = uniqid().'.'.$avatar->guessExtension();
        //Movemos la imagen al directorio especificado
        $avatar->move($destiny, $newFilename);
        return $newFilename;
    }
}