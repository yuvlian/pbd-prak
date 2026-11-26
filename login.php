<?php

require 'config.php';

if (current_user() !== null) {
    redirect('index.php');
}

$err = '';

if (is_post()) {
    $username = post_str('username');
    $password = post_str('password');

    if ($username === '' || $password === '') {
        $err = 'username dan password wajib diisi.';
    } else {
        $u = row(
            'select u.iduser, u.username, u.role_idrole, r.nama_role
             from user u
             left join role r on r.idrole = u.role_idrole
             where u.username = ? and u.password = sha2(?, 256)',
            'ss',
            $username,
            $password
        );

        if ($u) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'iduser'    => (int) $u['iduser'],
                'username'  => $u['username'],
                'idrole'    => (int) $u['role_idrole'],
                'nama_role' => (string) ($u['nama_role'] ?? ''),
            ];

            redirect('index.php');
        }

        $err = 'username atau password salah.';
    }
}

page_start('login', $err, false);
?>
<form method="post">
    <p>username
        <input name="username" value="<?= e(post_str('username')) ?>" required>
    </p>
    <p>password
        <input type="password" name="password" required>
    </p>
    <p><button>masuk</button></p>
</form>
<?php page_end(); ?>
