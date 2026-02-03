<?php

/*
Here's a **secure, production-aware PHP file upload form** using native PHP capabilities 
(including the `finfo` class for MIME validation) with critical security hardening. This solution avoids frameworks and leverages PHP's core functionality correctly.

---

### 📁 `upload.php` (Single-File Solution)
```php

*/


declare(strict_types=1);

// ===== SECURITY CONFIGURATION =====
$CONFIG = [
    'upload_dir'      => __DIR__ . '/uploads/', // STORE OUTSIDE WEB ROOT IF POSSIBLE
    'allowed_exts'    => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'max_size'        => 16 * 1024 * 1024, // 16MB
    'allowed_mimes'   => [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf']
    ]
];

// Create upload directory securely
if (!is_dir($CONFIG['upload_dir'])) {
    mkdir($CONFIG['upload_dir'], 0755, true);
    // Block PHP execution in upload directory (Apache)
    file_put_contents($CONFIG['upload_dir'] . '.htaccess', 'php_flag engine off');
}

$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $error = '';

    // 1. Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds php.ini upload limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL    => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension'
        ];
        $error = $errors[$file['error']] ?? 'Unknown upload error';
    } 
    // 2. Validate size
    elseif ($file['size'] > $CONFIG['max_size']) {
        $error = 'File too large (max 16MB)';
    } 
    // 3. Validate extension
    else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $CONFIG['allowed_exts'], true)) {
            $error = 'Invalid file type. Allowed: ' . implode(', ', $CONFIG['allowed_exts']);
        } 
        // 4. VALIDATE MIME TYPE USING NATIVE finfo CLASS (CRITICAL)
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE); // Native PHP class (Fileinfo extension)
            $mime = $finfo->file($file['tmp_name']);
            
            if (!in_array($mime, $CONFIG['allowed_mimes'][$ext] ?? [], true)) {
                $error = "Security alert: MIME type mismatch (detected: $mime)";
            } 
            // 5. Finalize upload
            else {
                // Generate cryptographically secure filename
                $newName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destination = $CONFIG['upload_dir'] . $newName;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Optional: Set strict permissions
                    chmod($destination, 0644);
                    $message = "✅ Success! Saved as: " . htmlspecialchars($newName);
                    $isSuccess = true;
                } else {
                    $error = 'Failed to save file (permissions issue?)';
                }
            }
        }
    }

    if ($error) {
        $message = "❌ $error";
        error_log("Upload failed: $error | Client: {$_SERVER['REMOTE_ADDR']}");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure PHP Upload</title>
    <style>
        body { font-family: system-ui; max-width: 600px; margin: 2rem auto; }
        .msg { padding: 12px; margin: 1rem 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; }
        small { color: #6c757d; display: block; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <h1>📤 Secure File Upload</h1>
    
    <?php if ($message): ?>
        <div class="msg <?= $isSuccess ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $CONFIG['max_size'] ?>">
        <div>
            <label>Select file:
                <input type="file" name="file" required 
                       accept=".jpg,.jpeg,.png,.gif,.pdf">
            </label>
        </div>
        <button type="submit" style="margin-top: 10px; padding: 8px 16px">Upload</button>
    </form>
    
    <small>✅ Allowed: JPG, PNG, GIF, PDF | Max 16MB<br>
    🔒 All uploads validated via MIME type + extension whitelist</small>
    
    <!-- SECURITY NOTE: This is visible for demo only. Remove in production! -->
    <small style="color:#dc3545; display:block; margin-top:20px">
        ⚠️ PRODUCTION MUST-DO: 
        1. Move 'uploads' outside public web root 
        2. Disable PHP execution in upload directory 
        3. Scan files with ClamAV 
        4. Implement CSRF tokens
    </small>
</body>
</html>


<?php
/*
```

---

### 🔒 Critical Security Implementation Details
| Risk | Native PHP Solution |
|------|---------------------|
| **MIME Spoofing** | `finfo` class (Fileinfo extension) validates *actual* file content |
| **Path Traversal** | `move_uploaded_file()` + randomized filename (`random_bytes`) |
| **Malicious Execution** | `.htaccess` blocks PHP execution in upload dir + strict permissions |
| **Size Bypass** | Dual validation: `MAX_FILE_SIZE` hidden field + server-side size check |
| **Extension Abuse** | Whitelist validation *before* MIME check |
| **Overwrites** | Cryptographically unique filenames (`bin2hex(random_bytes(16))`) |

---

### ⚙️ Required Server Configuration (`php.ini`)
```ini
; ESSENTIAL SETTINGS
file_uploads = On
upload_max_filesize = 16M
post_max_size = 18M       ; Slightly larger than upload limit
max_file_uploads = 3      ; Limit concurrent uploads
upload_tmp_dir = "/secure/tmp" ; Non-web-accessible directory

; SECURITY HARDENING
expose_php = Off
disable_functions = exec,passthru,shell_exec,system
```

---

### 🌐 Deployment Checklist
1. **Move uploads directory outside public web root**  
   (e.g., `/var/www/uploads` → `/var/uploads/appname`)
2. **Block execution in upload directory**  
   Apache: `.htaccess` with `php_flag engine off` + `RemoveHandler .php`  
   Nginx: `location ^~ /uploads/ { location ~ \.php$ { deny all; } }`
3. **Add CSRF protection** (critical for production):  
   ```php
   // Generate token
   if (empty($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }
   // In form: <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?>">
   // Validate: if ($_POST['csrf'] !== $_SESSION['csrf_token']) die('Invalid CSRF');
   ```
4. **Scan uploads** with ClamAV or similar
5. **Set strict directory permissions**: `chmod 755 uploads/` (directories), `chmod 644` (files)
6. **Log all upload attempts** (success/failure) for auditing

---

### 💡 Why This Approach?
- ✅ **Uses native PHP classes**: `finfo` for bulletproof MIME validation (core extension enabled by default since PHP 5.3)
- ✅ **No external dependencies** – pure PHP core functionality
- ✅ **Defense-in-depth**: Multiple validation layers (extension → MIME → size → path)
- ✅ **Modern PHP practices**: Strict types, secure random generation, error logging
- ✅ **Transparent security**: Clear comments on *why* each measure exists

> 📌 **Important**: PHP's `$_FILES` is a superglobal array – there is **no native PHP class** that 
replaces it (PSR-7 implementations like `UploadedFile`
 require frameworks). This solution correctly uses PHP's native upload handling *with* native classes (`finfo`) where applicable.

For enterprise needs: Add virus scanning, cloud storage integration (AWS SDK), 
or use frameworks with built-in upload handlers (Symfony HttpFoundation, Laravel). 
But for a **simple, secure, native PHP solution**, this covers all critical bases. 🛡️✨


https://www.bing.com/?FORM=Z9FD1
https://chat.qwen.ai/c/guest

*/