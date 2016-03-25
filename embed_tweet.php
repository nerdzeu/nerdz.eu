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
?>
<?php
header ('Content-type: application/json');

// Configuration
define ('CACHE_DIR', './tmp/twitter_cache/');
define ('CACHE_DIR_PERMS', 0755);
define ('CACHE_FILE_PERMS', 0644);
define ('CACHE_AGE', 3153600000); // cache_age from the oembed response
define ('API_ENDPOINT', 'https://api.twitter.com/1/statuses/oembed.json?omit_script=true&id=');
// EOC

if (!is_dir (CACHE_DIR) && !mkdir (CACHE_DIR, CACHE_DIR_PERMS))
    die (generate_error ('N: I/O error', -1));

if (!isset ($_POST['id']) || !is_numeric ($_POST['id']))
    die (generate_error ('N: Invalid ID', -2));

$cache_file = CACHE_DIR . (substr (CACHE_DIR, -1) === '/' ? '' : '/') . $_POST['id'] . '.json';

if (file_exists ($cache_file))
    if (time() - filemtime ($cache_file) > CACHE_AGE)
        unlink ($cache_file);
    else
        die (file_get_contents ($cache_file));

$ce = curl_init (API_ENDPOINT . $_POST['id']);
curl_setopt ($ce, CURLOPT_HEADER, false);
curl_setopt ($ce, CURLOPT_RETURNTRANSFER, true);
// To let this work on platforms with broken SSL CAs (do not enable in production)
// curl_setopt ($ce, CURLOPT_SSL_VERIFYPEER, false);
$out = curl_exec ($ce);
curl_close ($ce);

if (!$out) // don't cache empty responses, may be temporary errors
    die (generate_error ('N: Bad response', -3));

file_put_contents ($cache_file, $out);
chmod ($cache_file, CACHE_FILE_PERMS);

echo $out;

function generate_error ($msg, $code)
{
    return json_encode ([
        'errors' => [
            [
                'message' => $msg,
                'code'    => $code
            ]
        ]
    ]);
}
