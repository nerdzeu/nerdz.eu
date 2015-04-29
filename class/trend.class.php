<?php
namespace NERDZ\Core;

class Trend
{
    private $tag;
    private $user;

    public function __construct($tag = null)
    {
        $this->tag = $tag;
        $this->user = new User();
    }

    public function getNewest()
    {
        $ret = [];
        if(!($trends = Db::query(
            'select distinct t.tag, extract(epoch from t.time) as time
            from posts_classification t inner join (
                select lower(tag) as tag, max(time) as maxtime
                from posts_classification group by lower(tag)
            ) as tbl
            on lower(tbl.tag) = lower(t.tag) and tbl.maxtime = t.time
            order by time desc limit 10', Db::FETCH_OBJ, true)))
            return $ret;

        $c = 0;
        foreach($trends as $t) {
            $ret[$c]['trend_n']      = $t->tag;
            $ret[$c]['trend4link_n'] = urlencode($t->tag);
            $ret[$c]['lastat_n']     = $this->user->getDateTime($t->time);
            ++$c;
        }
        return $ret;
    }

    public function getPopular()
    {
        $ret = [];
        if(!($trends = Db::query(
                        'select count(*) as cc, lower(tag) as tag
                        from posts_classification
                        group by lower(tag)
                        order by cc desc limit 10', Db::FETCH_OBJ, true)))
            return $ret;

        $c = 0;
        foreach($trends as $t) {
            $ret[$c]['trend_n']      = $t->tag;
            $ret[$c]['trend4link_n'] = urlencode($t->tag);
            $ret[$c]['posts_n']      = $t->cc;
            ++$c;
        }
        return $ret;
    }

}
