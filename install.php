<?php
// Run once after importing database.sql, then delete this file.
require 'config.php';

$check = $pdo->query("SELECT COUNT(*) c FROM users")->fetch();
if ($check['c'] > 0) {
    die("Users already exist — install.php has already been run. Delete this file. 
        (যদি নতুন করে চালাতে চান, প্রথমে phpMyAdmin থেকে `users` টেবিল খালি করুন।)");
}

$accounts = [
    ['username' => 'admin',          'password' => 'Admin@123',   'role' => 'admin',        'ngo_id' => null, 'phone' => null,          'nid' => null,            'family_id' => null],
    ['username' => 'asha_operator',  'password' => 'operator123', 'role' => 'ngo_operator', 'ngo_id' => 1,    'phone' => null,          'nid' => null,            'family_id' => null],
    ['username' => 'relief_operator','password' => 'operator123', 'role' => 'ngo_operator', 'ngo_id' => 2,    'phone' => null,          'nid' => null,            'family_id' => null],
    ['username' => 'sfv_operator',   'password' => 'operator123', 'role' => 'ngo_operator', 'ngo_id' => 3,    'phone' => null,          'nid' => null,            'family_id' => null],
    ['username' => 'citizen_rahim',  'password' => 'Citizen@123', 'role' => 'citizen',     'ngo_id' => null, 'phone' => '01711234501', 'nid' => '1985011234501', 'family_id' => 1],
];

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, ngo_id, phone, nid_hash, family_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
foreach ($accounts as $a) {
    $nidHash = $a['nid'] ? hashNID($a['nid']) : null;
    $stmt->execute([
        $a['username'],
        password_hash($a['password'], PASSWORD_BCRYPT),
        $a['role'],
        $a['ngo_id'],
        $a['phone'],
        $nidHash,
        $a['family_id']
    ]);
}

echo "<div style='font-family:sans-serif;max-width:500px;margin:60px auto;padding:24px;border:1px solid #ddd;border-radius:10px;'>
<h2>✅ Accounts created</h2>
<table style='width:100%;border-collapse:collapse;font-size:14px;'>
<tr style='text-align:left;'><th>Username</th><th>Password</th><th>Role</th></tr>";
foreach ($accounts as $a) {
    echo "<tr><td>{$a['username']}</td><td>{$a['password']}</td><td>{$a['role']}</td></tr>";
}
echo "</table>
<p style='color:#b00;margin-top:16px;'><b>এখনই এই ফাইলটি (install.php) ডিলিট করে দিন।</b></p>
<p><a href='index.php'>লগইন পেজে যান →</a></p>
</div>";
