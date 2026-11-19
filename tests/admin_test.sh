#!/usr/bin/env bash
# Phase 9 tests: admin moderation (overview, suspend and restore users, remove gigs, audit log).
# Run from the project folder:  bash tests/admin_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_adm_XXXX.sqlite); PORT=8091; B=http://127.0.0.1:$PORT
C=$(mktemp); C2=$(mktemp); A=$(mktemp); F=$(mktemp); F2=$(mktemp); W=$(mktemp -d)
. tests/db_setup.sh
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf $W' EXIT; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ local j=$1; shift; curl -s -o /dev/null -w "%{http_code}" -b $j -c $j "$@"; }
loc(){ local j=$1; shift; curl -s -o /dev/null -w "%{redirect_url}" -b $j -c $j "$@"; }
page(){ curl -s -b $1 -c $1 "$B$2"; }
has(){ if grep -qF -- "$1" <<< "$2"; then echo yes; else echo no; fi; }
sql(){ php -r '$p=new PDO("'$DSN'","'$DBU'","'$DBP'");$r=$p->query("'"$1"'")->fetch(PDO::FETCH_NUM);echo $r?implode("|",$r):"";'; }
post(){ local j=$1 path=$2; shift 2; local t; t=$(tok $j /account); status $j -X POST "$B$path" --data-urlencode "_csrf=$t" "$@"; }
upload(){ local j=$1 path=$2 file=$3 name=$4; local t; t=$(tok $j /account); status $j -X POST "$B$path" -F "_csrf=$t" -F "note=done" -F "work=@$file;filename=$name"; }
reg(){ local j=$1; shift; local t; t=$(tok $j /register); status $j -X POST $B/register --data-urlencode "_csrf=$t" -d "$1" >/dev/null; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
login(){ local j=$1 t; t=$(tok $j /login); loc $j -X POST $B/login --data-urlencode "_csrf=$t" -d "email=$2&password=$3"; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $A  "name=Admin Person&email=admin@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"

# admins cannot be created from the website
RJ=$(mktemp); reg $RJ "name=Sneaky Admin&email=sneaky@example.com&role=admin&password=longpassword1&confirm=longpassword1&terms=1"
check "register form refuses the admin role"          "$(sql "select count(*) from users where email='sneaky@example.com'")" "0"
check "make-admin refuses an unknown email"           "$(php bin/make-admin.php nobody@example.com >/dev/null 2>&1; echo $?)" "1"
php bin/make-admin.php admin@example.com >/dev/null
check "make-admin promotes an existing account"       "$(sql "select role from users where email='admin@example.com'")" "admin"

# some activity to moderate: gig 1 (open), gig 2 (hired, in progress), gig 3 (open, other client)
mk(){ local j=$1 title=$2; local t; t=$(tok $j /gigs/create); status $j -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=$title&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null; }
mk $C "Design a logo"; mk $C "Translate a leaflet"; mk $C2 "Write product text"
post $F /gigs/2/apply -d 'proposal=I will translate the leaflet carefully&price=2500' >/dev/null
post $C /applications/1/hire >/dev/null
printf 'work\n' > $W/n.txt; upload $F /gigs/2/submit $W/n.txt n.txt >/dev/null

# access control
check "logged-out visitor is sent to login"           "$(status /dev/null $B/admin)" "302"
check "client cannot open /admin (403)"               "$(status $C $B/admin)" "403"
check "freelancer cannot open /admin/users (403)"     "$(status $F $B/admin/users)" "403"
check "client cannot open /admin/gigs (403)"          "$(status $C $B/admin/gigs)" "403"
check "client cannot suspend a user (403)"            "$(post $C /admin/users/3/suspend)" "403"
check "freelancer cannot remove a gig (403)"          "$(post $F /admin/gigs/3/remove -d 'reason=Not allowed')" "403"
check "nothing changed by those attempts"             "$(sql 'select sum(suspended) from users')/$(sql "select status from gigs where id=3")" "0/open"
check "admin dashboard link goes to /admin"           "$(loc $A $B/dashboard | sed 's#.*/##')" "admin"
check "admin cannot post a gig (403)"                 "$(status $A $B/gigs/create)" "403"

# overview
O=$(page $A /admin)
check "overview opens for the admin"                  "$(has 'Admin overview' "$O")" "yes"
check "counts clients (3 including the admin's old role is now admin)" "$(has '<div class="num">2</div><div class="lbl">Clients' "$O")" "yes"
check "counts freelancers"                            "$(has '<div class="num">2</div><div class="lbl">Freelancers' "$O")" "yes"
check "counts open gigs"                              "$(has '<div class="num">2</div><div class="lbl">Open gigs' "$O")" "yes"
check "counts gigs in progress"                       "$(has '<div class="num">1</div><div class="lbl">Gigs in progress' "$O")" "yes"
check "no admin actions yet"                          "$(has 'No admin actions yet' "$O")" "yes"
check "admin sees admin tabs, no Post a gig button"   "$(has 'Post a gig' "$O")/$(has 'href="/admin/users"' "$O")" "no/yes"

# users list
U=$(page $A /admin/users)
check "users list shows people"                       "$(has 'Client One' "$U")/$(has 'f1@example.com' "$U")" "yes/yes"
check "search by name"                                "$(has 'Client Two' "$(page $A '/admin/users?q=Two')")/$(has 'Client One' "$(page $A '/admin/users?q=Two')")" "yes/no"
check "search by email"                               "$(has 'Free Two' "$(page $A '/admin/users?q=f2@')")" "yes"
check "filter by role"                                "$(has 'Free One' "$(page $A '/admin/users?role=freelancer')")/$(has 'Client One' "$(page $A '/admin/users?role=freelancer')")" "yes/no"
check "admin has no suspend button for admins"        "$(has '/admin/users/5/suspend' "$U")" "no"
check "array search value is ignored"                 "$(status $A "$B/admin/users?q[]=x")" "200"

# suspend and restore
check "missing CSRF token is refused (419)"           "$(status $A -X POST $B/admin/users/1/suspend)" "419"
check "unknown user gets 404"                         "$(post $A /admin/users/999/suspend)" "404"
check "admin cannot suspend an admin (403)"           "$(post $A /admin/users/5/suspend)" "403"
check "gigs of client One are visible before"         "$(has 'Design a logo' "$(page $F2 /gigs)")" "yes"
post $C /gigs/1/cancel >/dev/null 2>&1; mk $C "Another open gig"
check "suspend works"                                 "$(post $A /admin/users/1/suspend >/dev/null; sql 'select suspended from users where id=1')" "1"
check "suspending is recorded"                        "$(sql "select action, target_id from admin_actions where id=1")" "suspend_user|1"
check "suspended client is signed out at once"        "$(loc $C $B/dashboard | sed 's#.*/##')" "login"
check "login page tells them why"                     "$(has 'has been suspended' "$(page $C /login)")" "yes"
check "suspended user cannot log in"                  "$(login $C c1@example.com longpassword1 | sed 's#.*/##')" "login"
check "login explains the suspension"                 "$(has 'This account has been suspended' "$(page $C /login)")" "yes"
check "wrong password still says incorrect (no leak)" "$(login $C c1@example.com wrongpassword >/dev/null; has 'Incorrect email or password' "$(page $C /login)")" "yes"
check "suspended client's open gigs are hidden"       "$(has 'Another open gig' "$(page $F2 /gigs)")/$(has 'Write product text' "$(page $F2 /gigs)")" "no/yes"
check "overview lists the suspended account"          "$(has '1 suspended account' "$(page $A /admin)")" "yes"
check "users list shows the Suspended tag"            "$(has 'Suspended' "$(page $A /admin/users?q=c1@)")/$(has '/admin/users/1/restore' "$(page $A /admin/users?q=c1@)")" "yes/yes"
check "restore works"                                 "$(post $A /admin/users/1/restore >/dev/null; sql 'select suspended from users where id=1')" "0"
check "restored user can log in again"                "$(login $C c1@example.com longpassword1 | sed 's#.*/##')" "dashboard"
check "restored client's gigs are visible again"      "$(has 'Another open gig' "$(page $F2 /gigs)")" "yes"

# removing gigs
check "removal needs a reason"                        "$(post $A /admin/gigs/3/remove -d 'reason=no' >/dev/null; sql "select status from gigs where id=3")" "open"
check "reason error is shown"                         "$(has 'Give a reason' "$(page $A /admin/gigs)")" "yes"
check "gig in progress cannot be removed"             "$(post $A /admin/gigs/2/remove -d 'reason=Looks like spam' >/dev/null; sql "select status from gigs where id=2")" "in_progress"
check "refusal is explained"                          "$(has 'Only open gigs can be removed' "$(page $A /admin/gigs)")" "yes"
check "unknown gig gets 404"                          "$(post $A /admin/gigs/999/remove -d 'reason=Looks like spam')" "404"
check "remove works with a reason"                    "$(post $A /admin/gigs/3/remove -d 'reason=Contains a phone scam' >/dev/null; sql "select status, removal_reason from gigs where id=3")" "cancelled|Contains a phone scam"
check "removed gig leaves the public list"            "$(has 'Write product text' "$(page $F2 /gigs)")" "no"
check "freelancer gets 404 for the removed gig"       "$(status $F2 $B/gigs/3)" "404"
check "owner sees the reason"                         "$(has 'Removed by an administrator' "$(page $C2 /gigs/3)")/$(has 'Contains a phone scam' "$(page $C2 /gigs/3)")" "yes/yes"
check "admin can open the removed gig"                "$(status $A $B/gigs/3)" "200"
check "removal is in the log"                         "$(sql "select action, target_id from admin_actions where action='remove_gig'")" "remove_gig|3"
check "reason text is escaped"                        "$(post $A /admin/gigs/4/remove --data-urlencode 'reason=<b>bad</b> gig' >/dev/null; has '<b>bad</b>' "$(page $A /admin/gigs)")" "no"
check "overview shows recent actions"                 "$(has 'Admin Person' "$(page $A /admin)")/$(has 'removed gig #3' "$(page $A /admin)")" "yes/yes"

# privacy: admins moderate but do not read private work
check "admin cannot download delivered files"         "$(status $A $B/submissions/1/download)" "404"
check "admin cannot see the delivery section"         "$(has 'notes' "$(page $A /gigs/2)")/$(has 'Download file' "$(page $A /gigs/2)")" "no/no"

for s in $(php -r '$p=new PDO("'$DSN'","'$DBU'","'$DBP'");foreach($p->query("select stored_name from submissions") as $r) echo $r[0]," ";'); do rm -f storage/deliveries/$s; done
echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
