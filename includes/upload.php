<?php

function upload_image(array $file): array|false {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_exts  = ['jpg', 'jpeg', 'png', 'gif'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types, true)) {
        return false;
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts, true)) {
        return false;
    }

    $filename  = uniqid('img_', true) . '.' . $ext;
    $orig_path = UPLOAD_ORIGINAL . $filename;
    $thumb_path = UPLOAD_THUMBS  . $filename;

    if (!move_uploaded_file($file['tmp_name'], $orig_path)) {
        return false;
    }

    if (!create_thumbnail($orig_path, $thumb_path, $mime)) {
        copy($orig_path, $thumb_path);
    }

    return [
        'original' => 'uploads/original/' . $filename,
        'thumb'    => 'uploads/thumbs/'   . $filename,
    ];
}

function create_thumbnail(string $src, string $dst, string $mime): bool {
    [$orig_w, $orig_h] = getimagesize($src);

    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($src),
        'image/png'  => imagecreatefrompng($src),
        'image/gif'  => imagecreatefromgif($src),
        default      => false,
    };

    if (!$source) {
        return false;
    }

    $ratio_w = THUMB_WIDTH  / $orig_w;
    $ratio_h = THUMB_HEIGHT / $orig_h;
    $ratio   = min($ratio_w, $ratio_h, 1.0);

    $new_w = (int) round($orig_w * $ratio);
    $new_h = (int) round($orig_h * $ratio);

    $thumb = imagecreatetruecolor($new_w, $new_h);

    if ($mime === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

    $result = match ($mime) {
        'image/jpeg' => imagejpeg($thumb, $dst, 85),
        'image/png'  => imagepng($thumb, $dst, 6),
        'image/gif'  => imagegif($thumb, $dst),
        default      => false,
    };

    imagedestroy($source);
    imagedestroy($thumb);

    return (bool) $result;
}
