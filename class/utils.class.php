<?php

class utils
{
    public function apc_getLastModified($key)
    {
        $cache = apc_cache_info('user');

        if (empty($cache['cache_list']))
            return false;
        
        foreach($cache['cache_list'] as $entry)
        {
            if($entry['key'] != $key)
                continue;

            return $entry['ctime'];
        }
    }

}
?>
