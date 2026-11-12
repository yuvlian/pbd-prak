<?php

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

function page_start(string $title, string $err = ''): void
{
    echo '<!doctype html>';
    echo '<html><head><meta charset="utf-8">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="stylesheet" href="style.css">';
    echo '</head><body>';
    echo '<p><a href="index.php">menu</a></p>';
    echo '<h1>' . e($title) . '</h1>';

    if ($err !== '') {
        echo '<p class="err">' . e($err) . '</p>';
    }
}

function page_end(): void
{
    echo '</body></html>';
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
