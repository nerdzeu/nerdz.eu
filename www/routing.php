<?php

if (preg_match('#^/(.+?)\.$#', $_SERVER['REQUEST_URI'], $matches)) {
  $_GET = array( 'id' => $matches[1] );
  include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'profile.php';
}
else if (preg_match('#^/(.+?):$#', $_SERVER['REQUEST_URI'], $matches)) {
  $_GET = array( 'gid' => $matches[1] );
  include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'project.php';
}
else if (preg_match('#/(.+?)\.(\d+)#', $_SERVER['REQUEST_URI'], $matches)) {
  $_GET = array( 'id' => $matches[1], 'pid' => $matches[2] );
  include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'profile.php';
}
else if (preg_match('#/(.+?):(\d+)#', $_SERVER['REQUEST_URI'], $matches)) {
  $_GET = array( 'gid' => $matches[1], 'pid' => $matches[2] );
  include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'project.php';
}
else {
  return false;
}

?>
