<?php
// helpers.php — included by all 4 PHP files on Render
// Set in Render Dashboard → Environment Variables:
//   FIREBASE_URL = https://your-project-default-rtdb.firebaseio.com

define('FIREBASE_URL', rtrim($_ENV['FIREBASE_URL'] ?? getenv('FIREBASE_URL') ?: '', '/'));

date_default_timezone_set('Asia/Kolkata');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// ── Firebase REST API ─────────────────────────────────────────────────────

function fbGet(string $path, array $query = []): mixed {
    $url = FIREBASE_URL . '/' . ltrim($path, '/') . '.json';
    if ($query) $url .= '?' . http_build_query($query);
    $ctx  = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true]]);
    $resp = @file_get_contents($url, false, $ctx);
    return ($resp && $resp !== 'null') ? json_decode($resp, true) : null;
}

function fbSet(string $path, mixed $data): void {
    _fbReq('PUT', $path, $data);
}

function fbPush(string $path, mixed $data): ?string {
    $r = _fbReq('POST', $path, $data);
    return is_array($r) ? ($r['name'] ?? null) : null;
}

function fbUpdate(string $path, array $data): void {
    _fbReq('PATCH', $path, $data);
}

function fbDelete(string $path): void {
    _fbReq('DELETE', $path, null);
}

function _fbReq(string $method, string $path, mixed $data): mixed {
    $url  = FIREBASE_URL . '/' . ltrim($path, '/') . '.json';
    $opts = ['method' => $method, 'timeout' => 15, 'ignore_errors' => true];
    if ($data !== null) {
        $opts['header']  = "Content-Type: application/json\r\n";
        $opts['content'] = json_encode($data);
    }
    $ctx  = stream_context_create(['http' => $opts]);
    $resp = @file_get_contents($url, false, $ctx);
    return ($resp && $resp !== 'null') ? json_decode($resp, true) : null;
}

// ── Utilities (no DB needed) ──────────────────────────────────────────────

function getAllSettings(): array {
    return fbGet('settings') ?? [];
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getClientIP(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return filter_var(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0], FILTER_VALIDATE_IP) ?: '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getBrowserInfo(): string {
    return json_encode([
        'user_agent'  => $_SERVER['HTTP_USER_AGENT']      ?? '',
        'accept_lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'referer'     => $_SERVER['HTTP_REFERER']         ?? '',
        'timestamp'   => date('Y-m-d H:i:s'),
        'ip'          => getClientIP(),
    ]);
}

function parseBrowserName(string $ua): string {
    foreach (['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Chrome' => 'Chrome', 'Safari' => 'Safari', 'Firefox' => 'Firefox'] as $k => $v)
        if (stripos($ua, $k) !== false) return $v;
    return 'Other';
}

function parseOSName(string $ua): string {
    foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $k => $v)
        if (stripos($ua, $k) !== false) return $v;
    return 'Other';
}

function generateOrderId(): string {
    return 'PS' . date('ymd') . strtoupper(substr(uniqid(), -6));
}

function generateSlug(string $title): string {
    $s = strtolower(trim($title));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-');
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}
