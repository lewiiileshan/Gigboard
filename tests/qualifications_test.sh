#!/usr/bin/env bash
# Step 5 tests: gig requirements, and private qualification files attached to applications.
# Run from the project folder:  bash tests/qualifications_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_qual_XXXX.sqlite); PORT=8094; B=http://127.0.0.1:$PORT
C=$(mktemp); C2=$(mktemp); F=$(mktemp); F2=$(mktemp); W=$(mktemp -d)
php -r '$p=new PDO("sqlite:'$DB'");$p->exec(file_get_contents("tests/sqlite_schema.sql"));'
export GB_DB_DSN="sqlite:$DB" GB_DB_USER="" GB_DB_PASS=""
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null; rm -rf $W' EXIT; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ local j=$1; shift; curl -s -o /dev/null -w "%{http_code}" -b $j -c $j "$@"; }
loc(){ local j=$1; shift; curl -s -o /dev/null -w "%{redirect_url}" -b $j -c $j "$@"; }
page(){ curl -s -b $1 -c $1 "$B$2"; }
has(){ if grep -qF -- "$1" <<< "$2"; then echo yes; else echo no; fi; }
nfiles(){ ls storage/deliveries | grep -vc '^\.gitkeep$'; }
sql(){ php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("'"$1"'")->fetchColumn();'; }
reg(){ local j=$1; shift; local t; t=$(tok $j /register); status $j -X POST $B/register --data-urlencode "_csrf=$t" -d "$1" >/dev/null; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"

# ---- requirements on the gig
t=$(tok $C /gigs/create)
LONG=$(head -c 1501 /dev/zero | tr '\0' 'x')
status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" --data-urlencode "requirements=$LONG" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
check "requirements over 1500 characters are refused"   "$(has 'requirements are too long' "$(page $C /gigs/create)")" "yes"
t=$(tok $C /gigs/create)
status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" --data-urlencode "requirements=Diploma in graphic design and a portfolio" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
check "gig page shows the requirements"                  "$(has 'Diploma in graphic design and a portfolio' "$(page $F /gigs/1)")" "yes"
check "apply form repeats what the client asked for"     "$(has 'The client asked for' "$(page $F /gigs/1)")" "yes"
t=$(tok $C /gigs/create)
status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=Gig without requirements&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
check "requirements are optional"                        "$(status $C $B/gigs/2)" "200"
check "edit form carries the saved requirements"         "$(has 'Diploma in graphic design' "$(page $C /gigs/1/edit)")" "yes"

# ---- applying with qualification files
printf 'Diploma in Graphic Design, Nairobi Polytechnic, 2024\n' > $W/diploma.txt
printf 'CV of Free One\n' > $W/cv.txt
printf '<?php echo 1; ?>' > $W/evil.php
apply(){ local j=$1; shift; local t; t=$(tok $j /gigs/1); status $j -X POST $B/gigs/1/apply -F "_csrf=$t" -F "proposal=I will design three logo options for you" -F "price=2500" "$@"; }

B0=$(nfiles)
apply $F -F "qual_title_1=Diploma" -F "qual_file_1=@$W/cv.txt" -F "qual_title_2=Bad one" -F "qual_file_2=@$W/evil.php;filename=evil.php" >/dev/null
check "a disallowed file stops the whole application"    "$(has 'not allowed' "$(page $F /gigs/1)")" "yes"
check "...no application was created"                    "$(sql 'select count(*) from applications')" "0"
check "...and the good file saved before it was removed" "$(( $(nfiles) - B0 ))" "0"
apply $F -F "qual_title_1=" -F "qual_file_1=@$W/cv.txt" >/dev/null
check "a file needs a title"                             "$(has 'title of 3 to 80' "$(page $F /gigs/1)")" "yes"
apply $F -F "qual_title_1=Orphan title" >/dev/null
check "a title needs a file"                             "$(has 'Choose a file for' "$(page $F /gigs/1)")" "yes"
check "still no application and no stray files"          "$(sql 'select count(*) from applications')/$(( $(nfiles) - B0 ))" "0/0"

check "valid application with two files is accepted"     "$(apply $F -F 'qual_title_1=Diploma in Graphic Design' -F "qual_file_1=@$W/diploma.txt;filename=diploma.txt" -F 'qual_title_2=My CV' -F "qual_file_2=@$W/cv.txt;filename=cv.txt")" "302"
check "two file records saved"                           "$(sql 'select count(*) from application_files')" "2"
check "applying with no files is still allowed"          "$(apply $F2)" "302"

# ---- who can see and download them
check "client sees the qualification titles"             "$(has 'Diploma in Graphic Design' "$(page $C /gigs/1)")" "yes"
check "client sees applicant without files too"          "$(has 'No qualification files attached' "$(page $C /gigs/1)")" "yes"
check "applicant sees own files on the gig page"         "$(has 'My CV' "$(page $F /gigs/1)")" "yes"
check "other applicant cannot see them"                  "$(has 'Diploma in Graphic Design' "$(page $F2 /gigs/1)")" "no"
check "client can download a file"                       "$(status $C $B/qualifications/1/download)" "200"
check "client gets the exact file"                       "$(page $C /qualifications/1/download | tr -d '\n')" "Diploma in Graphic Design, Nairobi Polytechnic, 2024"
check "download is an attachment"                        "$(curl -s -D - -o /dev/null -b $C $B/qualifications/1/download | tr -d '\r' | grep -ic 'content-disposition: attachment')" "1"
check "uploader can download own file"                   "$(status $F $B/qualifications/1/download)" "200"
check "other freelancer gets 404"                        "$(status $F2 $B/qualifications/1/download)" "404"
check "client of another gig gets 404"                   "$(status $C2 $B/qualifications/1/download)" "404"
check "logged-out visitor is sent to login"              "$(loc /dev/null $B/qualifications/1/download)" "$B/login"
check "unknown file gives 404"                           "$(status $C $B/qualifications/99/download)" "404"
S1=$(sql 'select stored_name from application_files where id=1')
check "stored under a random name"                       "$(printf '%s' "$S1" | grep -cE '^[a-f0-9]{32}$')" "1"
check "no public URL reaches the stored file"            "$(status /dev/null $B/storage/deliveries/$S1)" "404"

# clean up files this test created
for s in $(php -r '$p=new PDO("sqlite:'$DB'");foreach($p->query("select stored_name from application_files") as $r) echo $r[0]," ";'); do rm -f storage/deliveries/$s; done
echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
