<?php
declare(strict_types=1);
function require_login(): void
{
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}
function current_user_id(): int
{
    $userId = (int)($_SESSION['benutzer_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(400);
        exit('Session-Fehler: Benutzer-ID fehlt. Bitte neu einloggen.');
    }
    return $userId;
}
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function post_str(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}
function get_str(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}
function redirect_to(string $path, array $query = []): void
{
    $url = $path . (count($query) ? ('?' . http_build_query($query)) : '');
    header("Location: $url");
    exit;
}
function is_valid_date_ymd(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $date));
    return checkdate($m, $d, $y);
}