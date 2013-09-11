/**
 * This script is strictly confidential and is intendend for use with MySQL NERDZ databases.
 *
 * This software correctly migrates a MySQL NERDZ database data into an already initialized PostgreSQL database.
 * It also corrects some weird bugs that may be present into your MySQL NERDZ database.
 *
 * It requires groovy, postgresql-jdbc (v4) and mysql-connector-java.
 *
 * Usage: <thisprogram> myuser mypass mydb myport pguser pgpass pgdb pgport
 *
 * I am not responsible to any damage this software could provoke to your software, data or equipment. Use at your risk.
 *
 * Any data contained into the given PostgreSQL database will be wiped.
 *
 */

import groovy.sql.GroovyResultSet
import groovy.sql.Sql

import java.text.DecimalFormat

if(args.length != 8) {
        System.err << "Wrong number of arguments :${args.length}\n"
        System.err << "<thisprogram> myuser mypass mydb myport pguser pgpass pgdb pgport\n"
        return
}

def now = new Date()
println "Started at $now\n" +
        "You could be able to undo with CTRL+C (DO IT AT YOUR RISK!), because we're using transactions so the database will remain consistant.\n" +
        "However, your db will remain exactly equal to the one you had before running this.\n" +
        "After a brutal interruption you might need to restart PostgreSQL.\n\n"

def startTime = now.time
def lastOp = startTime

//connection to MySQL
def mySql = Sql.newInstance("jdbc:mysql://localhost:${args[3]}/${args[2]}",args[0],args[1],"com.mysql.jdbc.Driver")

//connection to PostgreSQL
def pgSql = Sql.newInstance("jdbc:postgresql://localhost:${args[7]}/${args[6]}",args[4],args[5],"org.postgresql.Driver")

pgSql.execute("SET TIMEZONE TO 'UTC'")

//clean up PostgreSQL

pgSql.connection.autoCommit = false

def tables = ["ban", "blacklist", "bookmarks", "closed_profiles", "comments", "comments_no_notify", "comments_notify", "follow", "gravatar_profiles", "groups", "groups_bookmarks", "groups_comments", "groups_comments_no_notify", "groups_comments_notify", "groups_followers", "groups_lurkers", "groups_members", "groups_notify", "groups_posts", "groups_posts_no_notify", "lurkers", "pms", "posts", "posts_no_notify", "profiles", "users", "whitelist"]. each {

    print "Cleaning table \"${it}\"..."

    pgSql.execute("DELETE FROM " + it)

    println "Done."
}

pgSql.commit()

//end of cleanup

//utility methods for pretty printing

def lastTime = new Date().time

def reportValues = { String tabName ->

    def valNum = mySql.firstRow("SELECT COUNT(*) AS howMany FROM `" + tabName+ "`").howMany
    println "-> ${valNum} values to migrate in ${tabName}\n"
    return valNum
}

final int OFFSET = 50

def df = new DecimalFormat("######.0")

class Avg {
    private double mSum
    private int mN

    Avg() {
        this.reset()
    }

    def setAvg(more) {
        ++this.mN
        this.mSum += more
    }

    def getAvg() {
        (this.mN > 0) ? this.mSum / this.mN : 0
    }

    def reset() {
        this.mN = this.mSum = 0
    }
}

def avg = new Avg()

def reportProgress = { curValue, totValue, redraw = false ->

    def ptime = lastTime
    lastTime = new Date().time
    def double rps = OFFSET/(lastTime - ptime + 1) * 1000L
    avg.avg = rps
    print "${redraw ? '\r' : ''}${curValue}/${totValue} records read (${df.format(rps)}) rows/s)                "

}

def thisMightTakeAWhile = {

    println "This might take a while. " + (it ? "Ok, maybe more than a while. This table can be very huge sometimes." : '')

}

def forEachRowInTable = { String tabName, long rowNum, Closure callBack ->

    reportProgress(0, rowNum)

    for (int start = 0; start < rowNum; start += (rowNum - start > OFFSET) ? OFFSET : (rowNum - start)) {
        mySql.eachRow("SELECT * FROM `" + tabName + "` LIMIT $start,$OFFSET", callBack)
        reportProgress(start, rowNum, true)
    }

    reportProgress(rowNum, rowNum, true)
    println()

}

def removeNulls = { String table, String field, String pk ->

    print "Removing NULL characters from ${table}(${field})... "

    def count = 0

    mySql.eachRow("SELECT * FROM `" + table +"`") {
        if (it.message.contains('\0')) {
            ++count

            mySql.executeUpdate("UPDATE `" + table + "` SET `" + field + "`='This message contained illegal characters and has been removed.' WHERE `" + pk +  "`=${it.getLong(pk)}")

        }
    }

    println "Field '$field' changed  in $count rows."

}

//definition of migration closure map

def int num = 0

def closures = [ "migrate_users" : {

    def rowNum = reportValues("users")

    //before doing anything we must fix the fact that for some weird reason mysql allows null dates in not null fields.

    mySql.execute("UPDATE users SET birth_date = CURRENT_DATE() WHERE birth_date IS NULL")

    forEachRowInTable("users", rowNum) { GroovyResultSet row ->

        //fields: "last", "notify_story", "private", "lang", "username", "password", "name", "surname", "email", "gender", "birth_date", "board_lang", "timezone", "viewonline"
        pgSql.execute("INSERT INTO users VALUES (${row.counter.longValue()}, TO_TIMESTAMP($row.last), ${row.notify_story}::json, ${row.private == 1}, ${row.lang}, ${row.username}, ${row.password}, ${row.name}, ${row.surname}, ${row.email}, ${row.gender == 1}, ${row.birth_date}, ${row.board_lang}, ${row.timezone}, ${row.viewonline == 1})")

    }

    //set sequence to a right value
    pgSql.execute("ALTER SEQUENCE users_counter_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(counter) AS maxId FROM users").maxId))

}

, "migrate_profiles" : {

    def rowNum = reportValues("profiles")

    forEachRowInTable("profiles", rowNum) { GroovyResultSet row ->

        //fields: "counter", "remote_addr", "http_user_agent", "website", "quotes", "biography", "interests", "photo", "skype", "jabber", "yahoo", "userscript", "template", "dateformat", "facebook", "twitter", "steam"
        pgSql.execute("INSERT INTO profiles VALUES (${row.counter.longValue()}, ${row.remote_addr ?: null }::inet, ${row.http_user_agent}, ${row.website}, ${row.quotes}, ${row.biography},${row.interests},${row.photo},${row.skype},${row.jabber},${row.yahoo},${row.userscript},${row.template},${row.dateformat},${row.facebook},${row.twitter},${row.steam})")

    }

    pgSql.execute("ALTER SEQUENCE profiles_counter_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(counter) AS maxId FROM profiles").maxId))

}

, "migrate_closed_profiles" : {

    def rowNum = reportValues("closed_profiles")

    forEachRowInTable("closed_profiles", rowNum) { GroovyResultSet row ->

        //fields: "counter"
        pgSql.execute("INSERT INTO closed_profiles VALUES (${row.counter.longValue()})")

    }

}

, "migrate_gravatar_profiles" : {

    def rowNum = reportValues("gravatar_profiles")

    forEachRowInTable("gravatar_profiles", rowNum) { GroovyResultSet row ->

        //fields: "counter"
        pgSql.execute("INSERT INTO gravatar_profiles VALUES (${row.counter.longValue()})")

    }

}

, "migrate_groups" : {

    def rowNum = reportValues("groups")

    forEachRowInTable("groups", rowNum) { GroovyResultSet row ->

        //fields: "counter", "description", "owner", "name", "private", "photo", "website", "goal", "visible", "open"
        pgSql.execute("INSERT INTO groups VALUES (${row.counter.longValue()}, ${row.description}, ${row.owner.longValue()}, ${row.name}, ${row.private == 1}, ${row.photo}, ${row.website}, ${row.goal == 1}, ${row.visible == 1}, ${row.open == 1})")

    }

    //set sequence to a right value
    pgSql.execute("ALTER SEQUENCE groups_counter_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(counter) AS maxId FROM groups").maxId))

}

, "migrate_groups_notify" : {

    def rowNum = reportValues("groups_notify")

    forEachRowInTable("groups_notify", rowNum) { GroovyResultSet row ->

        //fields: "group", "to", "time"
        pgSql.execute("INSERT INTO groups_notify VALUES (${row.group.longValue()}, ${row.to.longValue()}, TO_TIMESTAMP(${row.time}))")

    }
}

, "migrate_groups_members" : {

    def rowNum = reportValues("groups_members")

    forEachRowInTable("groups_members", rowNum) { GroovyResultSet row ->

        //fields: "group", "user"
        pgSql.execute("INSERT INTO groups_members VALUES (${row.group.longValue()}, ${row.user.longValue()})")

    }
}

, "migrate_groups_followers" : {

    def rowNum = reportValues("groups_followers")

    forEachRowInTable("groups_followers", rowNum) { GroovyResultSet row ->

        //fields: "group", "user"
        pgSql.execute("INSERT INTO groups_followers VALUES (${row.group.longValue()}, ${row.user.longValue()})")

    }
}

, "migrate_ban" : {

    def rowNum = reportValues("ban")

    forEachRowInTable("ban", rowNum) { GroovyResultSet row ->

        //fields: "user", "motivation"
        pgSql.execute("INSERT INTO ban VALUES (${row.user.longValue()}, ${row.motivation})")

    }

}

, "migrate_blacklist" : {

    def rowNum = reportValues("blacklist")

    forEachRowInTable("blacklist", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "motivation"
        pgSql.execute("INSERT INTO blacklist VALUES (${row.from.longValue()}, ${row.to.longValue()}, ${row.motivation})")

    }

}

, "migrate_whitelist" : {

    def rowNum = reportValues("whitelist")

    forEachRowInTable("whitelist", rowNum) { GroovyResultSet row ->

        //fields: "from", "to"
        pgSql.execute("INSERT INTO blacklist VALUES (${row.from.longValue()}, ${row.to.longValue()})")

    }

}

, "migrate_follow" : {

    def rowNum = reportValues("follow")

    forEachRowInTable("follow", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "notified", "time"
        pgSql.execute("INSERT INTO follow VALUES (${row.from.longValue()}, ${row.to.longValue()}, ${row.notified == 1}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_groups_posts" : {

    thisMightTakeAWhile false

    def rowNum = reportValues("groups_posts")

    removeNulls("groups_posts", "message", "hpid")

    forEachRowInTable("groups_posts", rowNum) { GroovyResultSet row ->

        //fields: "hpid", "from", "to", "pid", "message", "time", "news"
        pgSql.execute("INSERT INTO groups_posts VALUES (${row.hpid.longValue()}, ${row.from.longValue()}, ${row.to.longValue()}, ${row.pid.longValue()}, ${row.message}, TO_TIMESTAMP(${row.time}), ${row.news == 1})")

    }

    pgSql.execute("ALTER SEQUENCE groups_posts_hpid_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(hpid) AS maxHpid FROM groups_posts").maxHpid))

}

, "migrate_groups_posts_no_notify" : {

    def rowNum = reportValues("groups_posts_no_notify")

    forEachRowInTable("groups_posts_no_notify", rowNum) { GroovyResultSet row ->

        //fields: "user", "hpid", "time"
        pgSql.execute("INSERT INTO groups_posts_no_notify VALUES (${row.user.longValue()}, ${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_groups_lurkers" : {

    def rowNum = reportValues("groups_lurkers")

    forEachRowInTable("groups_lurkers", rowNum) { GroovyResultSet row ->

        //fields: "user", "post", "time"
        pgSql.execute("INSERT INTO groups_lurkers VALUES (${row.user.longValue()}, ${row.post.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_groups_bookmarks" : {

    def rowNum = reportValues("groups_bookmarks")

    forEachRowInTable("groups_bookmarks", rowNum) { GroovyResultSet row ->

        //fields: "from", "hpid", "time"
        pgSql.execute("INSERT INTO groups_bookmarks VALUES (${row.from.longValue()}, ${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_groups_comments" : {

    thisMightTakeAWhile false

    def rowNum = reportValues("groups_comments")

    removeNulls("groups_comments", "message", "hcid")

    forEachRowInTable("groups_comments", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "message", "time", "hcid"
        pgSql.execute("INSERT INTO groups_comments VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, ${row.message}, TO_TIMESTAMP(${row.time}), ${row.hcid.longValue()})")

    }

    pgSql.execute("ALTER SEQUENCE groups_comments_hcid_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(hcid) AS maxHcid FROM groups_comments").maxHcid))

}

, "migrate_groups_comments_no_notify" : {

    def rowNum = reportValues("groups_comments_no_notify")

    forEachRowInTable("groups_comments_no_notify", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "time"
        pgSql.execute("INSERT INTO groups_comments_no_notify VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_groups_comments_notify" : {

    def rowNum = reportValues("groups_comments_notify")

    forEachRowInTable("groups_comments_notify", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "time"
        pgSql.execute("INSERT INTO groups_comments_notify VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_pms" : {

    thisMightTakeAWhile false

    def rowNum = reportValues("pms")

    removeNulls("pms", "message", "pmid")

    forEachRowInTable("pms", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "time", "message", "read", "pmid"
        pgSql.execute("INSERT INTO pms VALUES (${row.from.longValue()}, ${row.to.longValue()}, TO_TIMESTAMP(${row.time}), ${row.message}, ${row.read == 1}, ${row.pmid.longValue()})")

    }

    pgSql.execute("ALTER SEQUENCE pms_pmid_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(pmid) AS maxPmid FROM pms").maxPmid))

}

, "migrate_posts" : {

    thisMightTakeAWhile true

    def rowNum = reportValues("posts")

    removeNulls("posts", "message", "hpid")

    forEachRowInTable("posts", rowNum) { GroovyResultSet row ->

        //fields: "hpid", "from", "to", "pid", "message", "notify", "time"
        pgSql.execute("INSERT INTO posts VALUES (${row.hpid.longValue()}, ${row.from.longValue()}, ${row.to.longValue()}, ${row.pid.longValue()}, ${row.message}, ${row.notify == 1}, TO_TIMESTAMP(${row.time}))")

    }

    pgSql.execute("ALTER SEQUENCE posts_hpid_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(hpid) AS maxHpid FROM posts").maxHpid))

}

, "migrate_posts_no_notify" : {

    def rowNum = reportValues("posts_no_notify")

    forEachRowInTable("posts_no_notify", rowNum) { GroovyResultSet row ->

        //fields: "user", "hpid", "time"
        pgSql.execute("INSERT INTO posts_no_notify VALUES (${row.user.longValue()}, ${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_lurkers" : {

    def rowNum = reportValues("lurkers")

    forEachRowInTable("lurkers", rowNum) { GroovyResultSet row ->

        //fields: "user", "post", "time"
        pgSql.execute("INSERT INTO lurkers VALUES (${row.user.longValue()}, ${row.post.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_bookmarks" : {

    def rowNum = reportValues("bookmarks")

    forEachRowInTable("bookmarks", rowNum) { GroovyResultSet row ->

        //fields: "from", "hpid", "time"
        pgSql.execute("INSERT INTO bookmarks VALUES (${row.from.longValue()}, ${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_comments" : {

    thisMightTakeAWhile true

    def rowNum = reportValues("comments")

    removeNulls("comments", "message", "hcid")

    forEachRowInTable("comments", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "message", "time", "hcid"
        pgSql.execute("INSERT INTO comments VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, ${row.message}, TO_TIMESTAMP(${row.time}), ${row.hcid.longValue()})")

    }

    pgSql.execute("ALTER SEQUENCE comments_hcid_seq RESTART WITH " + ++(pgSql.firstRow("SELECT MAX(hcid) AS maxHcid FROM comments").maxHcid))

}

, "migrate_comments_no_notify" : {

    def rowNum = reportValues("comments_no_notify")

    forEachRowInTable("comments_no_notify", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "time"
        pgSql.execute("INSERT INTO comments_no_notify VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}

, "migrate_comments_notify" : {

    def rowNum = reportValues("comments_notify")

    forEachRowInTable("comments_notify", rowNum) { GroovyResultSet row ->

        //fields: "from", "to", "hpid", "time"
        pgSql.execute("INSERT INTO comments_notify VALUES (${row.from.longValue()}, ${row.to.longValue()},${row.hpid.longValue()}, TO_TIMESTAMP(${row.time}))")

    }

}]

//Then, run
closures.each {
    println "\n(${++num} of ${closures.size()} on ${tables.size()} tables) Executing closure ${it.getKey()}:"
    it.getValue()()
    now = new Date()
    println "Average: ${df.format(avg.avg)} rows/s in ${(now.time - lastOp) / 1000 as long} seconds"
    avg.reset()
    lastOp = new Date().time
}

pgSql.commit()

now = new Date()

println "\nCommit done! Database correctly migrated.\nFinished at $now after ${(now.time - startTime) / 1000 as long} seconds"

