<?php

namespace App\Library\Utils;

class File
{
    /**
     *  Generates a unique name based on a file name
     *
     * @param  string $originalName
     * @param  string $extension
     * @return string
     */
    public static function generateUniqueName(string $originalName, string $extension): string
    {
      $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalName);
      return $safeFilename . '-' . uniqid() . '.' . $extension;
    }
}
