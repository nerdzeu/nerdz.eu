<?php
$pages = [
    '\.' => [ $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'profile.php', 'id' ],
    ':'  => [ $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'project.php', 'gid' ]
];

foreach($pages as $separator => $elements)
{
    $page = $elements[0];
    $id   = $elements[1];

    if (preg_match("#^/(.+?){$separator}$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array( $id => $matches[1] );
        include $page;
    }
    else if (preg_match("#^/(.+?){$separator}(\d+)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array( $id => $matches[1], 'pid' => $matches[2] );
        include $page;
    }
    else if (preg_match("#^/(.+?){$separator}(friends|members|followers|following|interactions)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array( $id => $matches[1], 'action' => $matches[2] );
        include $page;
    }
    else if (preg_match("#^/(.+?){$separator}(friends|members|followers|following|interactions)\?(.*)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array( $id => $matches[1], 'action' => $matches[2] );
        $parameters = explode('&',$matches[3]);
        foreach($parameters as $parameter) {
            $parameter = explode('=',$parameter);
            $_GET[$parameter[0]] = $parameter[1];
        }
        include $page;
    }
}


return false;

?>
