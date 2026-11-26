<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli('127.0.0.1', 'root', 'root', 'pbd_prak', 3307);
$db->set_charset('utf8mb4');

function run_query(string $sql, string $types = '', ...$params): mysqli_stmt
{
    global $db;

    $st = $db->prepare($sql);
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();

    return $st;
}

function rows(string $sql): array
{
    global $db;

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function row(string $sql, string $types = '', ...$params): ?array
{
    return run_query($sql, $types, ...$params)->get_result()->fetch_assoc();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function post_int(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function post_str(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function page_start(string $title, string $err = '', bool $menu = true): void
{
    echo '<!doctype html>';
    echo '<html><head><meta charset="utf-8">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="stylesheet" href="style.css">';
    echo '</head><body>';

    if ($menu) {
        echo '<p><a href="index.php">menu</a></p>';
    }

    echo '<h1>' . e($title) . '</h1>';

    if ($err !== '') {
        echo '<p class="err">' . e($err) . '</p>';
    }
}

function page_end(): void
{
    echo '<br><br><br><br></body></html>';
}

const PPN_PERSEN = 11;

function begin(): void
{
    global $db;
    $db->begin_transaction();
}

function commit(): void
{
    global $db;
    $db->commit();
}

function rollback(): void
{
    global $db;
    $db->rollback();
}

function insert_id(): int
{
    global $db;
    return (int) $db->insert_id;
}

const ROLE_ADMIN = 102;

const AKSES_HALAMAN = [
    'index'            => [101, 102, 103, 104, 105],
    'barang'           => [101, 102, 104],
    'satuan'           => [101, 102],
    'vendor'           => [101, 102],
    'pengadaan'        => [101, 102, 104],
    'penerimaan'       => [101, 102, 104],
    'penjualan'        => [101, 102, 103],
    'kartu_stok'       => [101, 102, 103, 104],
    'margin_penjualan' => [101, 102],
    'user'             => [102],
    'role'             => [102],
];

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function wajib_login(): void
{
    if (current_user() === null) {
        redirect('login.php');
    }
}

function boleh_akses(string $halaman): bool
{
    $u = current_user();

    if ($u === null) {
        return false;
    }

    if ((int) $u['idrole'] === ROLE_ADMIN) {
        return true;
    }

    return in_array((int) $u['idrole'], AKSES_HALAMAN[$halaman] ?? [], true);
}

function wajib_akses(string $halaman): void
{
    wajib_login();

    if (boleh_akses($halaman)) {
        return;
    }

    http_response_code(403);
    page_start('akses ditolak');
    echo '<p>role kamu tidak punya akses ke halaman ini.</p>';
    page_end();
    exit;
}
