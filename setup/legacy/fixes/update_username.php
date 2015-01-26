<?php

include "../class/config/index.php";

$pdo = new PDO("pgsql:host={$configuration['POSTGRESQL_HOST']};dbname={$configuration['POSTGRESQL_DATA_NAME']};port={$configuration['POSTGRESQL_PORT']}", $configuration['POSTGRESQL_USER'], $configuration['POSTGRESQL_PASS']);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('select "username", "counter" from users');

$users = $stmt->fetchAll(PDO::FETCH_OBJ);

if(!isset($users[0]->username) || !isset($users[0]->counter)) {
    var_dump($users);
    die;
}

$stmt = $pdo->query('select "name", "counter" from "groups"');

$projects = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "Processing users...\n";
foreach($users as $user) {
    echo "Processing user[{$user->counter}]: {$user->username}\n";
    $newName = htmlspecialchars(html_entity_decode($user->username, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    try
    {
        $stmt = $pdo->prepare('UPDATE "users" SET "username" = :username WHERE "counter" = :counter');
        $stmt->execute([':username' => $newName, ':counter' => $user->counter]);
        $maxpid = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        die($e);
    }

    echo "Updated to: {$newName}\n";
}

echo "\nUsers Fixed\n";


echo "Processing projects...\n";
foreach($projects as $user) {
    echo "Processing project[{$user->counter}]: {$user->name}\n";
    $newName = htmlspecialchars(html_entity_decode($user->name, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    try
    {
        $stmt = $pdo->prepare('UPDATE "groups" SET "name" = :username WHERE "counter" = :counter');
        $stmt->execute([':username' => $newName, ':counter' => $user->counter]);
        $maxpid = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        die($e);
    }

    echo "Updated to: {$newName}\n";
}

echo "\nProjects Fixed\n";

