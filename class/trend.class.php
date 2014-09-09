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
            'with all_tag_time(tag, time) as (
                (
                    select pc.tag, p.time
                    from posts p inner join posts_classification pc
                    on pc.u_hpid = p.hpid group by pc.tag, p.time order by p.time desc
                )
                union
                (
                    select pc.tag, p.time
                    from groups_posts p inner join posts_classification pc
                    on pc.g_hpid = p.hpid group by pc.tag, p.time
                )
            )
            select lower(t.tag) as tag, extract(epoch from t.time) as time
            from all_tag_time t inner join (
                select lower(tag) as tag, max(time) as maxtime
                from all_tag_time group by lower(tag)
            ) as tbl
            on lower(tbl.tag) = lower(t.tag) and tbl.maxtime = t.time
            order by t.time desc limit 10', Db::FETCH_OBJ, true)))
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
?>
