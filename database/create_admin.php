<?php

declare(strict_types=1);

/**
 * Création / réinitialisation sécurisée d'un compte administrateur.
 *
 * Usage :
 *   php database/create_admin.php --login=admin --password=MonMotDePasse
 *   php database/create_admin.php --login=admin            (mot de passe demandé)
 *   php database/create_admin.php --login=admin --hash='$2y$...'  (hash existant)
 *
 * - Refuse les mots de passe trop faibles.
 * - Journalise l'action dans admin_logs.
 * - Idempotent : met à jour le mot de passe si le login existe déjà.
 */

require dirname(__DIR__) . '/app/autoload.php';

use App\Helpers\App;
use App\Helpers\Database;
use App\Helpers\Security;

$config = require dirname(__DIR__) . '/config/app.php';
App::boot($config);
Database::configure($config['db']);

function arg(string $name): ?string
{
    global $argv;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen($name) + 3);
        }
    }
    return null;
}

function fail(string $msg): never
{
    fwrite(STDERR, "[ERREUR] {$msg}\n");
    exit(1);
}

$login = arg('login');
if ($login === null || $login === '') {
    $line = readline('Identifiant admin [admin] : ');
    $login = trim((string) $line) ?: 'admin';
}
$login = strtolower(trim($login));

if (!preg_match('/^[a-z0-9._-]{3,60}$/', $login)) {
    fail('Identifiant invalide (3-60 caractères : a-z 0-9 . _ -).');
}

$hash = arg('hash');
if ($hash === null) {
    $password = arg('password');
    if ($password === null) {
        $password = (string) readline('Mot de passe (min. 8 caractères) : ');
    }
    if (strlen($password) < 8) {
        fail('Mot de passe trop court : 8 caractères minimum.');
    }
    $confirm = arg('password') === null ? (string) readline('Confirmation : ') : $password;
    if ($password !== $confirm) {
        fail('Les mots de passe ne correspondent pas.');
    }
    $hash = Security::hashPassword($password);
} elseif (!password_get_info($hash)['algo']) {
    fail('Hash fourni non reconnu par password_verify().');
}

try {
    Database::pdo()->query('SELECT 1 FROM admins LIMIT 1');
} catch (Throwable $e) {
    fail('Base inaccessible : ' . $e->getMessage() . "\nImportez d'abord database/schema.sql");
}

$existing = Database::one('SELECT id FROM admins WHERE login = ?', [$login]);

if ($existing !== null) {
    Database::query('UPDATE admins SET password_hash = ? WHERE id = ?', [$hash, (int) $existing['id']]);
    $adminId = (int) $existing['id'];
    $verb = 'réinitialisé';
} else {
    Database::query('INSERT INTO admins (login, password_hash) VALUES (?, ?)', [$login, $hash]);
    $adminId = (int) Database::pdo()->lastInsertId();
    $verb = 'créé';
}

try {
    Database::query(
        'INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)',
        [$adminId, 'create_admin', "Compte {$verb} via CLI", 'cli']
    );
} catch (Throwable) {
}

echo "Compte admin \"{$login}\" {$verb} (id {$adminId}).\n";
echo "Connexion : " . rtrim((string) $config['url'], '/') . "/admin/login\n";
