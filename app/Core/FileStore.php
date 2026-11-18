<?php
namespace App\Core;

/**
 * Safe storage for uploaded work.
 *  - Only whitelisted file types, checked by extension AND by the real content (finfo).
 *  - 10 MB limit.
 *  - Saved OUTSIDE public/ under a random name, so there is no URL that reaches the file.
 *    The only way to get it back is SubmissionController::download(), which checks who is asking.
 */
class FileStore
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** extension => mime types the content may be detected as */
    private const TYPES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/cdfv2', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/cdfv2', 'application/x-ole-storage', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/cdfv2', 'application/x-ole-storage', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
    ];

    public static function allowedList(): string { return strtoupper(implode(', ', array_keys(self::TYPES))); }

    public static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/deliveries';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        return $dir;
    }

    /** Full path for a stored name, or null if the name is not one we generated. */
    public static function path(string $storedName): ?string
    {
        return preg_match('/^[a-f0-9]{32}$/', $storedName) ? self::dir() . '/' . $storedName : null;
    }

    /**
     * Check and save one uploaded file ($_FILES['x']).
     * Returns [info|null, error|null]; info = original_name, stored_name, mime, size_bytes.
     */
    public static function save(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, 'Choose a file to upload.'];
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK: break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE: return [null, 'That file is too large. The limit is 10 MB.'];
            default:                   return [null, 'The upload did not finish. Please try again.'];
        }
        if (!is_uploaded_file($file['tmp_name']))      return [null, 'The upload did not finish. Please try again.'];
        if ($file['size'] <= 0)                        return [null, 'That file is empty.'];
        if ($file['size'] > self::MAX_BYTES)           return [null, 'That file is too large. The limit is 10 MB.'];

        // Name shown to people: strip folders and odd characters, keep it short.
        $original = basename(str_replace('\\', '/', (string) $file['name']));
        $original = preg_replace('/[^\p{L}\p{N} ._()\-]/u', '_', $original) ?? 'file';
        $original = mb_substr($original, 0, 120);
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (!isset(self::TYPES[$ext])) {
            return [null, 'That file type is not allowed. Use one of: ' . self::allowedList() . '.'];
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = (string) finfo_file($f, $file['tmp_name']);
            finfo_close($f);
            if (!in_array($mime, self::TYPES[$ext], true)) {
                return [null, 'That file does not look like a real ' . strtoupper($ext) . ' file.'];
            }
        }

        $stored = bin2hex(random_bytes(16));
        if (!move_uploaded_file($file['tmp_name'], self::dir() . '/' . $stored)) {
            return [null, 'The file could not be saved. Please try again.'];
        }
        chmod(self::dir() . '/' . $stored, 0640);

        return [['original_name' => $original, 'stored_name' => $stored, 'mime' => $mime, 'size_bytes' => (int) $file['size']], null];
    }

    /** Remove saved files again (used when a later step fails, so no orphan files stay on disk). */
    public static function delete(string $storedName): void
    {
        $path = self::path($storedName);
        if ($path !== null && is_file($path)) @unlink($path);
    }

    /** Send a stored file to the browser as a forced download. The caller must have checked permission first. */
    public static function send(array $row): never
    {
        $path = self::path($row['stored_name']);
        if ($path === null || !is_file($path)) abort(404, 'File not found');

        $name = str_replace(['"', "\r", "\n"], '_', $row['original_name']);
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($row['original_name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
