<?php

// Reasons for an invalid upload (Niang\Core\Http\UploadedFile::errorMessage()), used as-is by the
// Validator.
return [
    'too_large' => 'The file exceeds the maximum size allowed by the server (:max).',
    'partial' => 'The file was only partially uploaded.',
    'no_file' => 'No file was uploaded.',
    'no_tmp_dir' => 'Missing temporary folder on the server.',
    'cant_write' => 'The server could not write the file to disk.',
    'extension' => 'A PHP extension stopped the file upload.',
    'moved' => 'The file has already been moved.',
    'not_uploaded' => 'The file was not received through an HTTP upload.',
    'unknown' => 'Unknown error while uploading the file.',
];
