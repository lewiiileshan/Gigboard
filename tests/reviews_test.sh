#!/usr/bin/env bash
# Phase 8 tests: two-way reviews after a gig is completed, and ratings on profiles.
# Run from the project folder:  bash tests/reviews_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_rev_XXXX.sqlite); PORT=8090; B=http://127.0.0.1:$PORT
C=$(mktemp); C2=$(mktemp); C3=$(mktemp); F=$(mktemp); F2=$(mktemp); W=$(mktemp -d)
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
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $C3 "name=Client Three&email=c3@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"

# gig 1 posted, F hired but NOT yet delivered
t=$(tok $C /gigs/create); status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
post $F /gigs/1/apply -d 'proposal=I will design three logo options for you&price=2500' >/dev/null
post $F2 /gigs/1/apply -d 'proposal=I can also design a logo for you today&price=2800' >/dev/null
post $C /applications/1/hire >/dev/null

# not allowed before completion
check "no review form while gig is in progress"       "$(has 'Save review' "$(page $C /gigs/1)")" "no"
check "client cannot review an unfinished gig (403)"  "$(post $C /gigs/1/review -d 'rating=5')" "403"
check "freelancer cannot review an unfinished gig"    "$(post $F /gigs/1/review -d 'rating=5')" "403"

# deliver and approve
printf 'finished logo notes\n' > $W/notes.txt
upload $F /gigs/1/submit $W/notes.txt notes.txt >/dev/null
post $C /submissions/1/approve >/dev/null
check "gig is completed"                              "$(sql 'select status from gigs where id=1')" "completed"
check "client sees the review form"                   "$(has 'Rate Free One' "$(page $C /gigs/1)")" "yes"
check "freelancer sees the review form"               "$(has 'Rate Client One' "$(page $F /gigs/1)")" "yes"

# who may review
check "other client gets 404"                         "$(post $C3 /gigs/1/review -d 'rating=5')" "404"
check "rejected applicant gets 404"                   "$(post $F2 /gigs/1/review -d 'rating=5')" "404"
check "other users do not see the form"               "$(has 'Save review' "$(page $C3 /gigs/1)")" "no"
check "review needs a login"                          "$(status /dev/null -X POST $B/gigs/1/review -d 'rating=5')" "302"

# validation
check "missing CSRF token is refused (419)"           "$(status $C -X POST $B/gigs/1/review -d 'rating=5')" "419"
post $C /gigs/1/review -d 'rating=0' >/dev/null
check "rating 0 is refused"                           "$(sql 'select count(*) from reviews')" "0"
post $C /gigs/1/review -d 'rating=6' >/dev/null
check "rating 6 is refused"                           "$(sql 'select count(*) from reviews')" "0"
post $C /gigs/1/review -d 'rating=abc' >/dev/null
check "rating that is not a number is refused"        "$(sql 'select count(*) from reviews')" "0"
post $C /gigs/1/review -d 'rating[]=5' >/dev/null
check "array rating is refused"                       "$(sql 'select count(*) from reviews')" "0"
long=$(printf 'x%.0s' $(seq 1 501))
post $C /gigs/1/review -d "rating=4&comment=$long" >/dev/null
check "comment over 500 characters is refused"        "$(sql 'select count(*) from reviews')" "0"
check "error message is shown"                        "$(has 'too long' "$(page $C /gigs/1)")" "yes"

# saving
post $C /gigs/1/review -d 'rating=5&comment=Great work, very fast.' >/dev/null
check "client review saved for the freelancer"        "$(sql 'select reviewer_id, reviewee_id, rating from reviews')" "1|3|5"
check "client cannot review twice"                    "$(post $C /gigs/1/review -d 'rating=1' >/dev/null; sql 'select count(*) from reviews')" "1"
check "first rating was not overwritten"              "$(sql 'select rating from reviews')" "5"
check "form is gone after reviewing"                  "$(has 'Save review' "$(page $C /gigs/1)")" "no"
check "client sees thanks and own review"             "$(has 'Your review' "$(page $C /gigs/1)")/$(has 'Great work, very fast.' "$(page $C /gigs/1)")" "yes/yes"
check "freelancer sees the client's review"           "$(has 'Client One' "$(page $F /gigs/1)")/$(has 'Great work, very fast.' "$(page $F /gigs/1)")" "yes/yes"
post $F /gigs/1/review -d 'rating=4' >/dev/null
check "freelancer review saved, comment optional"     "$(sql 'select reviewer_id, reviewee_id, rating from reviews where reviewer_id=3')" "3|1|4"
check "two reviews on the gig"                        "$(sql 'select count(*) from reviews where gig_id=1')" "2"

# escaping
printf 'x' > /dev/null
t2=$(tok $C /gigs/create); status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t2" -d "title=Second gig here&description=A long enough description of the work.&category_id=1&budget=1000" >/dev/null
post $F /gigs/2/apply -d 'proposal=I will do the second gig quickly&price=900' >/dev/null
post $C /applications/3/hire >/dev/null
upload $F /gigs/2/submit $W/notes.txt notes.txt >/dev/null
post $C /submissions/2/approve >/dev/null
post $C /gigs/2/review --data-urlencode 'comment=<script>alert(1)</script>' -d 'rating=3' >/dev/null
check "comment is escaped on the profile page"        "$(has '<script>alert(1)</script>' "$(page $C /freelancers/3)")/$(has '&lt;script&gt;' "$(page $C /freelancers/3)")" "no/yes"

# ratings shown
check "profile shows average 4.0 from 2 reviews"      "$(has '4.0' "$(page $C /freelancers/3)")/$(has '(2 reviews)' "$(page $C /freelancers/3)")" "yes/yes"
check "freelancer sees own rating"                    "$(has '4.0' "$(page $F /freelancers/3)")" "yes"
check "gig page shows the client's rating"            "$(has '4.0' "$(page $F /gigs/1)")/$(has '(1 review)' "$(page $F /gigs/1)")" "yes/yes"
check "profile without reviews says none yet"         "$(has 'No reviews yet' "$(page $F2 /freelancers/4)")" "yes"
check "reviews stay private to allowed viewers"       "$(status $C3 $B/freelancers/3)" "404"

for s in $(php -r '$p=new PDO("'$DSN'","'$DBU'","'$DBP'");foreach($p->query("select stored_name from submissions") as $r) echo $r[0]," ";'); do rm -f storage/deliveries/$s; done
echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
