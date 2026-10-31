<?php
use App\Core\Session;
use App\Core\Url;
use App\Core\View;

/** Escape text before printing it in HTML. This is what stops XSS. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string { return Url::to($path); }

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Session::csrfToken()) . '">';
}

function old(string $key, string $default = ''): string { return Session::old($key, $default); }

/** Show an error page (404 not found, 403 no access) and stop. */
function abort(int $code, string $message = 'Page not found'): never
{
    http_response_code($code);
    View::render('errors/error', ['title' => $message, 'message' => $message, 'code' => $code]);
    exit;
}

/** 4500 -> "KES 4,500" */
function kes($amount): string { return 'KES ' . number_format((float) $amount, 0); }

/** "2026-10-24 09:30:00" -> "3 hours ago" */
function time_ago(string $timestamp): string
{
    $seconds = time() - strtotime($timestamp);
    if ($seconds < 60)     return 'just now';
    if ($seconds < 3600)   return floor($seconds / 60) . ' min ago';
    if ($seconds < 86400)  return floor($seconds / 3600) . ' hr ago';
    if ($seconds < 604800) return floor($seconds / 86400) . ' days ago';
    return date('d M Y', strtotime($timestamp));
}

/** Coloured label for a gig status. */
function status_tag(string $status): string
{
    $map = [
        'open'        => ['Open', 'ok'],
        'in_progress' => ['In progress', 'warn'],
        'completed'   => ['Completed', ''],
        'cancelled'   => ['Cancelled', 'danger'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst($status), ''];
    return '<span class="tag ' . $class . '">' . e($label) . '</span>';
}

/** Small line icons (inline SVG, so no image files or icon fonts are needed). */
function icon(string $name): string
{
    $paths = [
        'home'      => '<path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'chevron'   => '<path d="M9 6l6 6-6 6"/>',
        'wrench'    => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.2-.5-.5-2.2z"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** The logo: navy disc with a briefcase on a white rounded tile. */
function logo_tile(string $size = ''): string
{
    return '<span class="logo-tile ' . e($size) . '"><span class="disc">' . icon('briefcase') . '</span></span>';
}

/** 2411724 -> "2.3 MB" */
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';
    return $bytes . ' B';
}
