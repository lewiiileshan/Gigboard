#!/usr/bin/env bash
# Step 6 tests: editing profiles, and who may view a freelancer's profile.
# Run from the project folder:  bash tests/profile_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_prof_XXXX.sqlite); PORT=8091; B=http://127.0.0.1:$PORT
C=$(mktemp); C2=$(mktemp); F=$(mktemp); F2=$(mktemp)
. tests/db_setup.sh
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!
trap 'kill $PID 2>/dev/null' EXIT; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ local j=$1; shift; curl -s -o /dev/null -w "%{http_code}" -b $j -c $j "$@"; }
loc(){ local j=$1; shift; curl -s -o /dev/null -w "%{redirect_url}" -b $j -c $j "$@"; }
page(){ curl -s -b $1 -c $1 "$B$2"; }
has(){ if grep -qF -- "$1" <<< "$2"; then echo yes; else echo no; fi; }
sql(){ php -r '$p=new PDO("'$DSN'","'$DBU'","'$DBP'");$r=$p->query("'"$1"'")->fetch(PDO::FETCH_NUM);echo $r?implode("|",$r):"";'; }
# save profile: $1 jar, rest = curl --data-urlencode pairs
save(){ local j=$1; shift; local t; t=$(tok $j /account/edit); local args=(); for kv in "$@"; do args+=(--data-urlencode "$kv"); done; status $j -X POST $B/account/edit --data-urlencode "_csrf=$t" "${args[@]}"; }
reg(){ local j=$1; shift; local t; t=$(tok $j /register); status $j -X POST $B/register --data-urlencode "_csrf=$t" -d "$1" >/dev/null; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"

# ---- validation
save $F "name=X" >/dev/null
check "short name is refused"                    "$(has 'Enter your full name' "$(page $F /account/edit)")" "yes"
save $F "name=Free One" "phone=abc" >/dev/null
check "bad phone is refused"                     "$(has 'valid phone number' "$(page $F /account/edit)")" "yes"
save $F "name=Free One" "portfolio_url=javascript:alert(1)" >/dev/null
check "javascript: link is refused"              "$(has 'http:// or https://' "$(page $F /account/edit)")" "yes"
save $F "name=Free One" "skills=a,b,c,d,e,f,g,h,i" >/dev/null
EDIT=$(page $F /account/edit)
check "more than 8 skills is refused"            "$(has 'at most 8 skills' "$EDIT")" "yes"
check "typed values come back after an error"    "$(has 'a,b,c,d,e,f,g,h,i' "$EDIT")" "yes"
save $F "name=Free One" "hourly_rate=-5" >/dev/null
check "negative rate is refused"                 "$(has 'hourly rate must be' "$(page $F /account/edit)")" "yes"
save $F "name=Free One" "bio=$(head -c 1001 /dev/zero | tr '\0' 'x')" >/dev/null
check "bio over 1000 characters is refused"      "$(has 'bio is too long' "$(page $F /account/edit)")" "yes"
check "nothing was saved by the failed attempts" "$(sql 'select count(*) from freelancer_profiles')" "0"

# ---- saving
check "valid profile is saved"                   "$(save $F 'name=Free One' 'phone=0712 345 678' 'headline=Graphic designer' 'town=Nairobi' 'bio=I design logos <script>alert(1)</script>' 'skills=Logo design, branding, LOGO DESIGN,  Photoshop ' 'hourly_rate=500' 'portfolio_url=https://example.com/me')" "302"
check "skills tidied: trimmed, no duplicates"    "$(sql 'select skills from freelancer_profiles')" "Logo design, branding, Photoshop"
check "phone is saved on the account"            "$(has '0712 345 678' "$(page $F /account)")" "yes"
check "saving again updates, not duplicates"     "$(save $F 'name=Free One' 'headline=Designer' >/dev/null; sql 'select count(*) from freelancer_profiles')" "1"
save $F "name=Free One" "phone=0712 345 678" "headline=Graphic designer" "town=Nairobi" "bio=I design logos <script>alert(1)</script>" "skills=Logo design, branding, Photoshop" "hourly_rate=500" "portfolio_url=https://example.com/me" >/dev/null
check "client can edit name and phone"           "$(save $C 'name=Client Uno' 'phone=0700 000 000')" "302"
check "new name shows on the account page"       "$(has 'Client Uno' "$(page $C /account)")" "yes"
save $C "name=Client Uno" "headline=sneaky" "bio=sneaky" >/dev/null
check "clients cannot create a freelancer profile" "$(sql 'select count(*) from freelancer_profiles')" "1"

# ---- who can view a freelancer profile
t=$(tok $C /gigs/create)
status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
t=$(tok $F /gigs/1); status $F -X POST $B/gigs/1/apply --data-urlencode "_csrf=$t" -d "proposal=I will design three logo options for you&price=2500" >/dev/null
check "freelancer can view own profile"          "$(status $F $B/freelancers/3)" "200"
check "client with an applicant can view it"     "$(status $C $B/freelancers/3)" "200"
check "other client gets 404"                    "$(status $C2 $B/freelancers/3)" "404"
check "other freelancer gets 404"                "$(status $F2 $B/freelancers/3)" "404"
check "logged-out visitor is sent to login"      "$(loc /dev/null $B/freelancers/3)" "$B/login"
check "a client's id is not a freelancer profile" "$(status $C $B/freelancers/1)" "404"
check "unknown id gives 404"                     "$(status $C $B/freelancers/99)" "404"
PROF=$(page $C /freelancers/3)
check "profile shows the headline"               "$(has 'Graphic designer' "$PROF")" "yes"
check "script in the bio is escaped (XSS)"       "$(has '&lt;script&gt;alert(1)&lt;/script&gt;' "$PROF")" "yes"
check "no live <script> from the bio"            "$(has '<script>alert(1)' "$PROF")" "no"
check "portfolio link opens safely"              "$(has 'rel="noopener noreferrer nofollow"' "$PROF")" "yes"
GIG=$(page $C /gigs/1)
check "applicant card shows headline and skills" "$(has 'Graphic designer' "$GIG")/$(has 'Photoshop' "$GIG")" "yes/yes"
check "applicant card links to the profile"      "$(has '/freelancers/3' "$GIG")" "yes"

echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
