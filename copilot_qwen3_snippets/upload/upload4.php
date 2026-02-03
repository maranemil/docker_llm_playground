<?php


/*Here’s a complete, secure, and simple PHP file upload form using native PHP classes (no frameworks).
It includes HTML form, server-side validation, and safe file handling.

1️⃣ HTML Upload Form
Save this as upload_form.html (or embed in your PHP file before processing logic):
Html<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple PHP File Upload</title>
</head>
<body>
    <h2>Upload a File</h2>
    <!-- enctype is required for file uploads -->
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="file">Choose file:</label>
        <input type="file" name="file" id="file" required>
        <br><br>
        <input type="submit" value="Upload">
    </form>
</body>
</html>


2️⃣ PHP Upload Handler (upload.php)
This script uses native PHP classes like SplFileInfo for file info and finfo for MIME type checking.
Php<?php

*/

// start server
// php -S localhost:8888
// go to http://localhost:8888/upload4.html

// Set a directory for uploads (must be writable)
$uploadDir = __DIR__ . '/uploads/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    // Check if file was uploaded without errors
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No file uploaded or upload error.');
    }

    // Use SplFileInfo to get file details
    $fileInfo = new SplFileInfo($_FILES['file']['name']);
    $extension = strtolower($fileInfo->getExtension());

    // Allowed file extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type.');
    }

    // Validate MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($_FILES['file']['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/plain'
    ];

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException('Invalid MIME type.');
    }

    // Generate a safe unique filename
    $safeName = sprintf(
        '%s.%s',
        bin2hex(random_bytes(8)),
        $extension
    );

    // Move uploaded file to destination
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $safeName)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    echo "File uploaded successfully as: " . htmlspecialchars($safeName);

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage();
}


/*
3️⃣ How It Works


HTML Form

Uses enctype="multipart/form-data" for file uploads.
Sends file to upload.php.



PHP Script

Uses SplFileInfo to extract file extension safely.
Uses finfo to verify MIME type (prevents fake extensions).
Generates a random safe filename to avoid overwriting.
Creates uploads/ directory if it doesn’t exist.
Moves the file securely with move_uploaded_file().



Security Measures

Restricts allowed extensions and MIME types.
Sanitizes file names.
Prevents overwriting existing files.
Uses exceptions for error handling.




✅ Tip: Always store uploaded files outside the web root if possible, and serve them via a controlled script to prevent direct access.

If you want, I can also give you a version that supports multiple file uploads using the same native PHP classes.
Do you want me to prepare that?

*/
