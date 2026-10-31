#!/usr/bin/env bash
# Step 3 tests: applying, hiring, and PRIVATE delivery of work files.
# Run from the project folder:  bash tests/delivery_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_del_XXXX.sqlite); PORT=8096; B=http://127.0.0.1:$PORT
C=$(mktemp); C2=$(mktemp); F=$(mktemp); F2=$(mktemp); W=$(mktemp -d)
php -r '$p=new PDO("sqlite:'$DB'");$p->exec(file_get_contents("tests/sqlite_schema.sql"));'
export GB_DB_DSN="sqlite:$DB" GB_DB_USER="" GB_DB_PASS=""
BEFORE=$(ls storage/deliveries | grep -vc '^\.gitkeep$')
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf $W' EXIT; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ local j=$1; shift; curl -s -o /dev/null -w "%{http_code}" -b $j -c $j "$@"; }
loc(){ local j=$1; shift; curl -s -o /dev/null -w "%{redirect_url}" -b $j -c $j "$@"; }
page(){ curl -s -b $1 -c $1 "$B$2"; }
has(){ if grep -qF -- "$1" <<< "$2"; then echo yes; else echo no; fi; }
# post a normal form; token comes from /account (always has the logout form)
post(){ local j=$1 path=$2; shift 2; local t; t=$(tok $j /account); status $j -X POST "$B$path" --data-urlencode "_csrf=$t" "$@"; }
# upload a file: $1 jar, $2 path, $3 local file, $4 name shown to the server
upload(){ local j=$1 path=$2 file=$3 name=$4; local t; t=$(tok $j /account); status $j -X POST "$B$path" -F "_csrf=$t" -F "note=here you go" -F "work=@$file;filename=$name"; }

reg(){ local j=$1; shift; local t; t=$(tok $j /register); status $j -X POST $B/register --data-urlencode "_csrf=$t" -d "$1" >/dev/null; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"

# client posts a gig (id 1)
t=$(tok $C /gigs/create); status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null

# ---- applying
check "client cannot apply (403)"                      "$(post $C /gigs/1/apply -d 'proposal=I would like to do this work for you&price=2500')" "403"
check "short proposal is rejected"                     "$(post $F /gigs/1/apply -d 'proposal=short&price=2500' >/dev/null; has 'at least 20 characters' "$(page $F /gigs/1)")" "yes"
check "bad price is rejected"                          "$(post $F /gigs/1/apply -d 'proposal=I will design three logo options for you&price=5' >/dev/null; has 'between KES 100' "$(page $F /gigs/1)")" "yes"
check "valid application is accepted"                  "$(post $F /gigs/1/apply -d 'proposal=I will design three logo options for you&price=2500')" "302"
check "application shows on the gig page"              "$(has 'Your application' "$(page $F /gigs/1)")" "yes"
post $F /gigs/1/apply -d 'proposal=I will design three logo options for you&price=2600' >/dev/null
check "applying twice is refused"                      "$(has 'already applied' "$(page $F /gigs/1)")" "yes"
check "second freelancer can apply"                    "$(post $F2 /gigs/1/apply -d 'proposal=I have five years of logo design experience&price=2800')" "302"
check "client sees both applicants"                    "$(has 'Applicants (2)' "$(page $C /gigs/1)")" "yes"
check "other client does not see applicants"           "$(has 'Applicants (' "$(page $C2 /gigs/1)")" "no"

# ---- hiring
check "other client cannot hire (403)"                 "$(post $C2 /applications/1/hire)" "403"
check "freelancer cannot hire (403)"                   "$(post $F /applications/1/hire)" "403"
check "client hires freelancer one"                    "$(post $C /applications/1/hire)" "302"
check "gig is now in progress"                         "$(has 'In progress' "$(page $C /gigs/1)")" "yes"
check "hiring again is refused (403)"                  "$(post $C /applications/2/hire)" "403"
check "payment record created with the agreed price"   "$(php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("select amount from payments where gig_id=1")->fetchColumn();')" "2500"
check "other applicant was declined"                   "$(php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("select status from applications where id=2")->fetchColumn();')" "rejected"
check "declined freelancer can no longer open the gig" "$(status $F2 $B/gigs/1)" "404"
check "nobody can apply to a gig in progress (403)"    "$(post $F2 /gigs/1/apply -d 'proposal=I have five years of logo design experience&price=2800')" "403"

# ---- uploading work
printf 'My finished logo notes\n' > $W/notes.txt
printf '<?php echo "hacked"; ?>' > $W/shell.php
printf '<?php echo "hacked"; ?>' > $W/fake.png
: > $W/empty.txt
head -c 11000000 /dev/zero > $W/big.zip
check "unhired freelancer cannot upload (403)"         "$(upload $F2 /gigs/1/submit $W/notes.txt notes.txt)" "403"
check "client cannot upload (403)"                     "$(upload $C /gigs/1/submit $W/notes.txt notes.txt)" "403"
upload $F /gigs/1/submit $W/shell.php shell.php >/dev/null
check ".php file is refused"                           "$(has 'not allowed' "$(page $F /gigs/1)")" "yes"
upload $F /gigs/1/submit $W/fake.png logo.png >/dev/null
check "script disguised as .png is refused"            "$(has 'does not look like a real PNG' "$(page $F /gigs/1)")" "yes"
upload $F /gigs/1/submit $W/empty.txt empty.txt >/dev/null
check "empty file is refused"                          "$(has 'file is empty' "$(page $F /gigs/1)")" "yes"
upload $F /gigs/1/submit $W/big.zip big.zip >/dev/null
check "file over 10 MB is refused"                     "$(has 'too large' "$(page $F /gigs/1)")" "yes"
check "nothing was saved from the bad uploads"         "$(php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("select count(*) from submissions")->fetchColumn();')" "0"
check "valid file is accepted"                         "$(upload $F /gigs/1/submit $W/notes.txt '../../etc/notes.txt')" "302"
check "client sees the delivery"                       "$(has 'notes.txt' "$(page $C /gigs/1)")" "yes"
check "folder part of the file name was stripped"      "$(php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("select original_name from submissions where id=1")->fetchColumn();')" "notes.txt"
STORED=$(php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("select stored_name from submissions where id=1")->fetchColumn();')
check "stored under a random 32-character name"        "$(printf '%s' "$STORED" | grep -cE '^[a-f0-9]{32}$')" "1"

# ---- who can download
check "client can download"                            "$(status $C $B/submissions/1/download)" "200"
check "client gets the exact file"                     "$(page $C /submissions/1/download | tr -d '\n')" "My finished logo notes"
check "download is an attachment, not shown inline"    "$(curl -s -D - -o /dev/null -b $C $B/submissions/1/download | tr -d '\r' | grep -ic 'content-disposition: attachment')" "1"
check "download sends nosniff"                         "$(curl -s -D - -o /dev/null -b $C $B/submissions/1/download | tr -d '\r' | grep -ic 'x-content-type-options: nosniff')" "1"
check "uploading freelancer can download"              "$(status $F $B/submissions/1/download)" "200"
check "other freelancer gets 404"                      "$(status $F2 $B/submissions/1/download)" "404"
check "other client gets 404"                          "$(status $C2 $B/submissions/1/download)" "404"
check "logged-out visitor is sent to login"            "$(loc /dev/null $B/submissions/1/download)" "$B/login"
check "no public URL reaches the stored file"          "$(status /dev/null $B/storage/deliveries/$STORED)" "404"
check "unknown file id gives 404"                      "$(status $C $B/submissions/999/download)" "404"

# ---- review
check "other client cannot approve (404)"              "$(post $C2 /submissions/1/approve)" "404"
check "freelancer cannot approve (403)"                "$(post $F /submissions/1/approve)" "403"
post $C /submissions/1/changes -d "feedback=no" >/dev/null
check "feedback must be specific"                      "$(has 'Say what should change' "$(page $C /gigs/1)")" "yes"
check "client requests changes"                        "$(post $C /submissions/1/changes -d 'feedback=Please make the logo bigger and use blue')" "302"
check "freelancer sees the feedback"                   "$(has 'make the logo bigger' "$(page $F /gigs/1)")" "yes"
check "reviewed work cannot be reviewed again (403)"   "$(post $C /submissions/1/approve)" "403"
printf 'My finished logo notes v2\n' > $W/notes2.txt
check "freelancer uploads a new version"               "$(upload $F /gigs/1/submit $W/notes2.txt notes_v2.txt)" "302"
check "client approves the new version"                "$(post $C /submissions/2/approve)" "302"
check "gig is completed"                               "$(has 'Completed' "$(page $C /gigs/1)")" "yes"
check "no more uploads after completion (403)"         "$(upload $F /gigs/1/submit $W/notes2.txt again.txt)" "403"
check "freelancer lists the gig under My applications" "$(has 'Design a logo' "$(page $F /my-applications)")" "yes"
check "uploaded files exist on disk (2)"               "$(( $(ls storage/deliveries | grep -vc '^\.gitkeep$') - BEFORE ))" "2"

# clean up files this test created
for s in $(php -r '$p=new PDO("sqlite:'$DB'");foreach($p->query("select stored_name from submissions") as $r) echo $r[0]," ";'); do rm -f storage/deliveries/$s; done
echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
