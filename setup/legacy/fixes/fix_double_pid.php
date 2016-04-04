<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../class/config/index.php';

$pdo = new PDO("pgsql:host={$configuration['POSTGRESQL_HOST']};dbname={$configuration['POSTGRESQL_DATA_NAME']};port={$configuration['POSTGRESQL_PORT']}", $configuration['POSTGRESQL_USER'], $configuration['POSTGRESQL_PASS']);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('with repeated_posts as (select a.hpid, a.to, a.pid from posts a, posts b where a.pid = b.pid and a.to = b.to and a.hpid != b.hpid  group by a.to, a.pid, a.hpid) select hpid, "to" FROM (select hpid, pid, "to" from repeated_posts where hpid IN (select min(hpid) from repeated_posts group by "to")) T;');

$profilesToFix = $stmt->fetchAll(PDO::FETCH_OBJ);

if (!isset($profilesToFix[0]->hpid) || !isset($profilesToFix[0]->to)) {
    var_dump($profilesToFix);
    die;
}
echo "Processing users...\n";
foreach ($profilesToFix as $profile) {
    echo "Processing profile: {$profile->to}\n";
    $stmt = $pdo->query('SELECT COUNT(*) AS max_pid FROM posts WHERE "to" = '.$profile->to);
    $maxpid = $stmt->fetch(PDO::FETCH_OBJ);
    $maxpid = $maxpid->max_pid;
    echo "Max pid for {$profile->to}: {$maxpid}\n";

    $stmt = $pdo->query('SELECT "pid",hpid FROM posts WHERE "hpid" < '.$profile->hpid." AND \"to\" = {$profile->to} ORDER BY hpid DESC LIMIT 1"); // keep at this value
    $start = $stmt->fetch(PDO::FETCH_OBJ);
    $startpid = $start->pid;

    echo "[{$start->hpid}] Begin editing from pid: {$startpid} +1 \n";

    $stmt = $pdo->query('SELECT hpid FROM posts WHERE hpid > '.$start->hpid." AND \"to\" = {$profile->to} ORDER BY hpid ASC LIMIT 1");
    $start = $stmt->fetch(PDO::FETCH_OBJ);
    echo "First HPID to edit: $start->hpid\n";

    ++$startpid;
    for ($i = $startpid; $i <= $maxpid; ++$i) {
        $query = "UPDATE posts SET pid = '{$i}' WHERE hpid = '{$start->hpid}'";
        $stmt = $pdo->query($query);
        echo $query, "\n";
        $q2 = 'SELECT hpid FROM posts WHERE hpid > '.$start->hpid." AND \"to\" = {$profile->to} ORDER BY hpid ASC LIMIT 1";
        $stmt = $pdo->query($q2);
        echo $q2,"\n";
        $start = $stmt->fetch(PDO::FETCH_OBJ);
    }
    echo "\n\n";
}

echo "\nUsers Fixed\n";

echo "Processing projects...\n";

$stmt = $pdo->query('with repeated_groups_posts as (select a.hpid, a.to, a.pid from groups_posts a, groups_posts b where a.pid = b.pid and a.to = b.to and a.hpid != b.hpid  group by a.to, a.pid, a.hpid) select hpid, "to" FROM (select hpid, pid, "to" from repeated_groups_posts where hpid IN (select min(hpid) from repeated_groups_posts group by "to")) T;');

$profilesToFix = $stmt->fetchAll(PDO::FETCH_OBJ);

if (!isset($profilesToFix[0]->hpid) || !isset($profilesToFix[0]->to)) {
    var_dump($profilesToFix);
    die;
}

foreach ($profilesToFix as $profile) {
    echo "Processing profile: {$profile->to}\n";
    $stmt = $pdo->query('SELECT COUNT(*) AS max_pid FROM groups_posts WHERE "to" = '.$profile->to);
    $maxpid = $stmt->fetch(PDO::FETCH_OBJ);
    $maxpid = $maxpid->max_pid;
    echo "Max pid for {$profile->to}: {$maxpid}\n";

    $stmt = $pdo->query('SELECT "pid",hpid FROM groups_posts WHERE "hpid" < '.$profile->hpid." AND \"to\" = {$profile->to} ORDER BY hpid DESC LIMIT 1"); // keep at this value
    $start = $stmt->fetch(PDO::FETCH_OBJ);
    $startpid = $start->pid;

    echo "[{$start->hpid}] Begin editing from pid: {$startpid} +1 \n";

    $stmt = $pdo->query('SELECT hpid FROM groups_posts WHERE hpid > '.$start->hpid." AND \"to\" = {$profile->to} ORDER BY hpid ASC LIMIT 1");
    $start = $stmt->fetch(PDO::FETCH_OBJ);
    echo "First HPID to edit: $start->hpid\n";

    ++$startpid;
    for ($i = $startpid; $i <= $maxpid; ++$i) {
        $query = "UPDATE groups_posts SET pid = '{$i}' WHERE hpid = '{$start->hpid}'";
        $stmt = $pdo->query($query);
        echo $query, "\n";
        $q2 = 'SELECT hpid FROM groups_posts WHERE hpid > '.$start->hpid." AND \"to\" = {$profile->to} ORDER BY hpid ASC LIMIT 1";
        $stmt = $pdo->query($q2);
        echo $q2,"\n";
        $start = $stmt->fetch(PDO::FETCH_OBJ);
    }
    echo "\n\n";
}

echo "\nFixed\n";
