<?php
function handleImageUpload($file) {
    // No file selected — not an error, images are optional
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, "There was a problem uploading the image."];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return [null, "Only JPG, PNG, GIF, or WEBP images are allowed."];
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return [null, "Image must be smaller than 5MB."];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('img_', true) . '.' . $extension;
    $destination = 'uploads/' . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [null, "Failed to save the uploaded image."];
    }

    return [$newFilename, null];
}