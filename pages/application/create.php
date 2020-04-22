<?php

use NERDZ\Core\OAuth2;

$scopes = [];
foreach (OAuth2::SCOPES as $scope) {
    foreach (OAuth2::PERMISSIONS as $permission) {
        $scopes[] = strtolower("${scope}:${permission}");
    }
}
$scopeStr = implode(' ', $scopes);
$scope_descr = array_map([$user, 'lang'], OAuth2::getScopes($scopeStr));
$vals['scopes_a'] = [];
$len = count($scopes);
for ($i=0;$i<$len;++$i) {
    $scope = $scopes[$i];
    $vals["scopes_a"][$scope] = $scope_descr[$i];
}

$user->getTPL()->assign($vals);
$user->getTPL()->draw('application/create');
