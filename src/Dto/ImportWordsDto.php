<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class ImportWordsDto
{
    #[Assert\NotBlank(message: "Le fichier est obligatoire.")]
    #[Assert\File(
        maxSize: '1024k',
        mimeTypes: ['text/csv', 'text/plain'],
        mimeTypesMessage: "Merci de téléverser un fichier CSV valide."
    )]
    public UploadedFile $file;
}
