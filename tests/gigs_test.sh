#!/usr/bin/env bash
# Step 2 tests: posting, browsing, searching, editing and cancelling gigs, with two users (client + freelancer).
# Run from the project folder:  bash tests/gigs_test.sh      (needs php + curl; uses a temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_gigs_XXXX.sqlite); PORT=8097; B=http://127.0.0.1:$PORT; C=$(mktemp); F=$(mktemp)
php -r '$p=new PDO("sqlite:'$DB'");$p->exec(file_get_contents("tests/sqlite_schema.sql"));'
export GB_DB_DSN="sqlite:$DB" GB_DB_USER="" GB_DB_PASS=""
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
# helpers take the cookie jar as first argument: $C = client, $F = freelancer
tok(){ curl -s -b $1 -c $1 "$B$2" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ local j=$1; shift; curl -s -o /dev/null -w "%{http_code}" -b $j -c $j "$@"; }
loc(){ local j=$1; shift; curl -s -o /dev/null -w "%{redirect_url}" -b $j -c $j "$@"; }
page(){ curl -s -b $1 -c $1 "$B$2"; }
count(){ grep -c -F "$1" <<< "$2"; }
post(){ local j=$1 path=$2 form=$3; shift 3; local t; t=$(tok $j "$form"); loc $j -X POST "$B$path" --data-urlencode "_csrf=$t" "$@"; }

# --- accounts
post $C /register /register -d "name=Client One&email=c@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1" >/dev/null
post $F /register /register -d "name=Free Lancer&email=f@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1" >/dev/null

check "freelancer cannot open the post-gig form (403)" "$(status $F $B/gigs/create)" "403"
check "logged-out visitor cannot browse gigs"          "$(loc /dev/null $B/gigs)" "$B/login"

# --- validation
post $C /gigs/create /gigs/create -d "title=Hi&description=short&category_id=1&budget=1000" >/dev/null
check "short title is rejected"                        "$(count 'title must be 5 to 150' "$(page $C /gigs/create)")" "1"
post $C /gigs/create /gigs/create -d "title=Design a logo&description=A long enough description of the work.&category_id=99&budget=1000" >/dev/null
check "unknown category is rejected"                   "$(count 'class="error">Choose a category' "$(page $C /gigs/create)")" "1"
post $C /gigs/create /gigs/create -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=50" >/dev/null
check "budget below KES 100 is rejected"               "$(count 'between KES 100' "$(page $C /gigs/create)")" "1"
post $C /gigs/create /gigs/create -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=1000&deadline=2020-01-01" >/dev/null
check "deadline in the past is rejected"               "$(count 'cannot be in the past' "$(page $C /gigs/create)")" "1"
check "typed values are kept after an error"           "$(count 'value="Design a logo"' "$(post $C /gigs/create /gigs/create -d 'title=Design a logo&description=short&category_id=1&budget=1000' >/dev/null; page $C /gigs/create)")" "1"

# --- create gigs
check "valid gig redirects to its page"                "$(post $C /gigs/create /gigs/create -d 'title=Design a logo for my bakery&description=I need a simple modern logo in PNG and SVG formats.&category_id=1&budget=3,500&location=Nairobi')" "$B/gigs/1"
post $C /gigs/create /gigs/create -d "title=Translate a brochure&description=English to Swahili, about four pages of text.&category_id=2&budget=1200&location=Remote&deadline=2099-12-31" >/dev/null
post $C /gigs/create /gigs/create -d "title=Enter 200 survey forms&description=Type survey answers into a spreadsheet, 200 forms in total.&category_id=3&budget=8000&location=Mombasa" >/dev/null
post $C /gigs/create /gigs/create --data-urlencode "title=<script>alert(1)</script> gig" -d "description=Testing that HTML in titles is escaped properly.&category_id=5&budget=500&location=Remote" >/dev/null
check "gig page shows budget formatted as KES"         "$(count 'KES 3,500' "$(page $C /gigs/1)")" "1"
XS=$(count '&lt;script&gt;alert(1)&lt;/script&gt; gig' "$(page $F /gigs/4)")
check "HTML in a gig title is escaped"                 "$([ "$XS" -ge 1 ] && echo yes || echo no)" "yes"

# --- browse, search, filter, sort
ALL=$(page $F /gigs)
check "freelancer sees all 4 open gigs"                "$(count '4 open gigs found' "$ALL")" "1"
check "search by keyword finds the logo gig"           "$(count 'Design a logo for my bakery' "$(page $F '/gigs?q=logo')")" "1"
check "search is case-insensitive over description"    "$(count '1 open gig found' "$(page $F '/gigs?q=SWAHILI')")" "1"
check "% in search does not match everything"          "$(count 'No gigs match' "$(page $F '/gigs?q=%25')")" "1"
check "category filter"                                "$(count '1 open gig found' "$(page $F '/gigs?category=3')")" "1"
check "min budget filter"                              "$(count '2 open gigs found' "$(page $F '/gigs?min=3000')")" "1"
check "max budget filter"                              "$(count '2 open gigs found' "$(page $F '/gigs?max=1200')")" "1"
check "location filter"                                "$(count '1 open gig found' "$(page $F '/gigs?location=mombasa')")" "1"
FIRST=$(page $F '/gigs?sort=budget_high' | grep -o '<h3>[^<]*' | head -1)
check "sort by budget high to low puts KES 8000 first" "$FIRST" "<h3>Enter 200 survey forms"
FIRST=$(page $F '/gigs?sort=budget_low' | grep -o '<h3>[^<]*' | head -1)
check "sort by budget low to high puts KES 500 first"  "$(grep -c 'alert' <<< "$FIRST")" "1"
check "bad sort name falls back safely (no error)"     "$(status $F "$B/gigs?sort=budget;DROP%20TABLE%20gigs")" "200"
check "SQL-looking search is harmless"                 "$(status $F "$B/gigs?q=%27%20OR%201%3D1--")" "200"
check "array parameters do not crash the page"         "$(status $F -g "$B/gigs?q[]=x&category[]=1")" "200"

# --- permissions: edit and cancel
check "freelancer cannot edit a gig (403)"             "$(status $F $B/gigs/1/edit)" "403"
T=$(tok $F /gigs)
check "freelancer cannot cancel a gig (403)"           "$(status $F -X POST $B/gigs/1/cancel -d '_csrf=x')" "403"
check "cancel without CSRF token is rejected (419)"    "$(status $C -X POST $B/gigs/1/cancel)" "419"
check "client edit form is prefilled"                  "$(count 'value="Design a logo for my bakery"' "$(page $C /gigs/1/edit)")" "1"
check "owner can edit and is sent to the gig"          "$(post $C /gigs/1/edit /gigs/1/edit -d 'title=Design a modern bakery logo&description=A simple modern logo in PNG and SVG formats please.&category_id=1&budget=4000&location=Nairobi')" "$B/gigs/1"
check "edit was saved"                                 "$(count 'KES 4,000' "$(page $F /gigs/1)")" "1"

# a second client must not touch the first client's gig
J3=$(mktemp)
post $J3 /register /register -d "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1" >/dev/null
check "another client cannot edit someone else's gig"  "$(status $J3 $B/gigs/1/edit)" "403"
check "another client cannot cancel it either"         "$(status $J3 -X POST $B/gigs/1/cancel --data-urlencode "_csrf=$(tok $J3 /dashboard)")" "403"

check "owner cancels a gig"                            "$(post $C /gigs/2/cancel /gigs/2 )" "$B/my-gigs"
check "cancelled gig disappears from browse"           "$(count '3 open gigs found' "$(page $F /gigs)")" "1"
check "cancelled gig is hidden from other users (404)" "$(status $F $B/gigs/2)" "404"
check "owner still sees the cancelled gig"             "$(count 'Cancelled' "$(page $C /gigs/2)")" "2"
check "cancelled gig cannot be edited (403)"           "$(status $C $B/gigs/2/edit)" "403"
check "unknown gig id gives 404"                       "$(status $C $B/gigs/999)" "404"
check "my-gigs lists all of the client's gigs"         "$(count 'Translate a brochure' "$(page $C /my-gigs)")" "1"
check "dashboard counts for the client"                "$(count '<div class="num">1</div><div class="lbl">Cancelled' "$(page $C /dashboard)")" "1"

# --- pagination
for i in $(seq 1 12); do post $C /gigs/create /gigs/create -d "title=Bulk gig number $i&description=Filler gig number $i for testing the pager.&category_id=3&budget=1000" >/dev/null; done
check "pagination shows page 1 of 2"                   "$(count 'Page 1 of 2' "$(page $F /gigs)")" "1"
check "page 2 is reachable"                            "$(count 'Page 2 of 2' "$(page $F '/gigs?page=2')")" "1"

kill $PID 2>/dev/null; rm -f "$DB" "$C" "$F" "$J3"
echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
