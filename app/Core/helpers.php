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
        'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'folder'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'inbox'     => '<path d="M3 13l3-8h12l3 8v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
        'doc'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'wallet'    => '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>',
        'star'      => '<path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 16.9 6.8 19.7l1-5.9L3.5 9.7l5.9-.8z"/>',
        'pin'       => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 3-5.5 6.5-5.5s6.5 1.9 6.5 5.5M16 4.8a3.5 3.5 0 0 1 0 6.4M21.5 20c0-2.6-1.6-4.3-4-5"/>',
        'shield'    => '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'code'      => '<path d="M8 8l-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
        'pen'       => '<path d="M4 20l1-4L16.5 4.5a2 2 0 0 1 3 3L8 19z"/>',
        'camera'    => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'truck'     => '<path d="M2 6h12v10H2zM14 10h4l4 3v3h-8"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
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
