<?php
namespace NERDZ\Core;

class Security
{
    public static function refererControl()
    {
        return isset($_SERVER['HTTP_REFERER']) && in_array(parse_url($_SERVER['HTTP_REFERER'])['host'],[ Config\SITE_HOST,Config\MOBILE_HOST ] );
    }

    public static function getCsrfToken($n = '')
    {
        $_SESSION['tok_'.$n] = isset($_SESSION['tok_'.$n]) ? $_SESSION['tok_'.$n] : md5(uniqid(rand(7,21)));
        return $_SESSION['tok_'.$n];
    }

    public static function csrfControl($tok,$n = '')
    {
        if(empty($_SESSION['tok_'.$n]))
            return false;
        return $_SESSION['tok_'.$n] === $tok;
    }

    public static function limitControl($limit,$n)
    {
        if(is_numeric($limit) && $limit < $n && $limit > 0)
            return $limit;

        if(!is_string($limit))
            return $n;

        $r = sscanf($limit,'%d,%d',$a,$b);

        if($r != 2) {
            $r = sscanf($limit,'%d OFFSET %d',$b,$a);
        }

        if($r != 2 || ($r == 2 && $b > $n))
            return $n;

        return "{$b} OFFSET {$a}";
    }

    public static function fieldControl($field, array $fields, $default = '')
    {
        if(Utils::in_arrayi($field, $fields))
            return $field;

        return $default;
    }

    public static function passwordControl($password) {
        if(mb_strlen($password, 'UTF-8') < Config\MIN_LENGTH_PASS) {
            return 'PASSWORD_SHORT';
        }
        if(isset($password[40])) {
            return 'PASSWORD_LONG';
        }
        return '';
    }

    public static function setNextAndPrevURLs(array &$vals, $limit, array $options = null)
    {
        extract((array)$options);
        $order = !empty($order) ? $order : false;
        $query = !empty($query) ? $query : false;
        $field = !empty($field) ? $field : false;

        $maxElements  = !empty($maxElements) ? $maxElements : 20;
        $validFields = !empty($validFields) && is_array($validFields) ? $validFields : [];

        $limit = static::limitControl($limit, $maxElements);

        $queryParams = [];
        $queryParams['order'] = $order ? 'desc='.(trim(strtolower($order)) == 'desc' ? '1' : '0') : '';
        $queryParams['query'] = $query ? 'q='.(trim(htmlspecialchars($query, ENT_QUOTES, 'UTF-8', false))) : '';

        if(static::fieldControl($field, $validFields))
            $queryParams['field'] = 'orderby='.$field;

        $url = '?'.implode('&amp;',array_filter($queryParams));

        if(is_numeric($limit)) {
            $vals['prev_url_n'] = '';
            $vals['next_url_n'] = count($vals['list_a']) == $maxElements ? "{$url}&amp;lim={$maxElements},{$maxElements}" : '';
        } else {
            $limitnext = $limitprev = $maxElements;

            if(2 == sscanf($_GET['lim'],'%d,%d',$a,$b)) {
                $next = $a+$maxElements;
                $prev = $a-$maxElements;
                $limitnext = "{$next},{$maxElements}";
                $limitprev = $prev >0 ? "{$prev},{$maxElements}" : $maxElements;
            }

            $vals['next_url_n'] = count($vals['list_a']) == $maxElements ? $url."&amp;lim={$limitnext}" : '';
            $vals['prev_url_n'] = $url."&amp;lim={$limitprev}";
        }
    }

    public static function floodPushRegControl()
    {
        if(!(new User())->isLogged())
            return false;

        $id = $_SESSION['id'];
        //If there has been a request in the last 5 seconds, return false.
        //Always update timer to NOW to cut off flooders.
        if (!($o = Db::query(
            [
                'SELECT EXTRACT(EPOCH FROM NOW() - "pushregtime") >= 3 AS valid
                FROM "profiles" WHERE "counter" = :user',
                [
                    ':user' => $id
                ]
            ],Db::FETCH_OBJ)) ||
            Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "profiles" SET "pushregtime" = NOW() WHERE "counter" = :user',
                    [
                        ':user' => $id
                    ]
                ],Db::FETCH_ERRNO)
            )
            return false;

        return $o->valid;
    }
}
?>
