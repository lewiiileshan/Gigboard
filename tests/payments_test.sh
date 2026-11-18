#!/usr/bin/env bash
# Phase 7 tests: payment status (client marks paid, freelancer confirms or disputes) and the Payments page.
# Run from the project folder:  bash tests/payments_test.sh      (needs php + curl; temporary SQLite database)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_pay_XXXX.sqlite); PORT=8089; B=http://127.0.0.1:$PORT
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
sql(){ php -r '$p=new PDO("sqlite:'$DB'");echo $p->query("'"$1"'")->fetchColumn();'; }
post(){ local j=$1 path=$2; shift 2; local t; t=$(tok $j /account); status $j -X POST "$B$path" --data-urlencode "_csrf=$t" "$@"; }
upload(){ local j=$1 path=$2 file=$3 name=$4; local t; t=$(tok $j /account); status $j -X POST "$B$path" -F "_csrf=$t" -F "note=done" -F "work=@$file;filename=$name"; }
reg(){ local j=$1; shift; local t; t=$(tok $j /register); status $j -X POST $B/register --data-urlencode "_csrf=$t" -d "$1" >/dev/null; }
reg $C  "name=Client One&email=c1@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $C2 "name=Client Two&email=c2@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1"
reg $F  "name=Free One&email=f1@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
reg $F2 "name=Free Two&email=f2@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"

# set up: gig 1 posted, F applies at 2500, client hires F
t=$(tok $C /gigs/create); status $C -X POST $B/gigs/create --data-urlencode "_csrf=$t" -d "title=Design a logo&description=A long enough description of the work.&category_id=1&budget=3000" >/dev/null
post $F /gigs/1/apply -d 'proposal=I will design three logo options for you&price=2500' >/dev/null
check "no payment exists before hiring"              "$(sql 'select count(*) from payments')" "0"
check "payments page is empty before hiring"         "$(has 'No payments yet' "$(page $C /payments)")" "yes"
post $C /applications/1/hire >/dev/null
check "hiring creates one pending payment of 2500"   "$(sql "select status||'/'||amount from payments")" "pending/2500.0"
check "both people see it on the Payments page"      "$(has 'Design a logo' "$(page $C /payments)")/$(has 'Design a logo' "$(page $F /payments)")" "yes/yes"
check "other users do not see it"                    "$(has 'Design a logo' "$(page $C2 /payments)")/$(has 'Design a logo' "$(page $F2 /payments)")" "no/no"
check "in progress: amount counted as in progress"   "$(has '<div class="num">2,500</div><div class="lbl">In progress' "$(page $C /payments)")" "yes"
check "gig page says payment comes after approval"   "$(has 'recorded after the client approves' "$(page $C /gigs/1)")" "yes"
check "client cannot mark paid before approval (403)" "$(post $C /payments/1/paid)" "403"

# deliver and approve
printf 'finished logo notes\n' > $W/notes.txt
upload $F /gigs/1/submit $W/notes.txt notes.txt >/dev/null
post $C /submissions/1/approve >/dev/null
check "gig is completed"                             "$(sql 'select status from gigs where id=1')" "completed"
check "completed and unpaid: counted as due"         "$(has '<div class="num">2,500</div><div class="lbl">To pay' "$(page $C /payments)")" "yes"
check "freelancer sees it as awaiting payment"       "$(has '<div class="num">2,500</div><div class="lbl">Awaiting payment' "$(page $F /payments)")" "yes"
check "client sees the Mark as paid form"            "$(has 'Mark as paid' "$(page $C /gigs/1)")" "yes"
check "freelancer does not see the Mark as paid form" "$(has 'Mark as paid' "$(page $F /gigs/1)")" "no"

# who may do what
check "freelancer cannot mark paid (403)"            "$(post $F /payments/1/paid)" "403"
check "other client gets 404"                        "$(post $C2 /payments/1/paid)" "404"
check "other freelancer gets 404 on confirm"         "$(post $F2 /payments/1/confirm)" "404"
check "client cannot confirm (403)"                  "$(post $C /payments/1/confirm)" "403"
check "nothing to confirm while unpaid"              "$(post $F /payments/1/confirm >/dev/null; sql 'select status from payments')" "pending"
check "logged-out visitor is sent to login"          "$(loc /dev/null $B/payments)" "$B/login"
check "unknown payment gives 404"                    "$(post $C /payments/99/paid)" "404"

# marking paid
post $C /payments/1/paid -d 'reference=abc' >/dev/null
check "a wrong M-Pesa code is refused"               "$(has '10 letters and numbers' "$(page $C /gigs/1)")" "yes"
check "...and nothing changed"                       "$(sql 'select status from payments')" "pending"
check "client records payment with a lowercase code" "$(post $C /payments/1/paid -d 'reference=qjk7x8y9za')" "302"
check "code is saved in capitals"                    "$(sql 'select reference from payments')" "QJK7X8Y9ZA"
check "status is paid"                               "$(sql 'select status from payments')" "paid"
check "recording twice is refused"                   "$(post $C /payments/1/paid -d 'reference=ZZZZZZZZZZ' >/dev/null; sql 'select reference from payments')" "QJK7X8Y9ZA"
check "client sees waiting for confirmation"         "$(has 'to confirm they received it' "$(page $C /gigs/1)")" "yes"
check "freelancer sees confirm and report buttons"   "$(has 'Yes, I received it' "$(page $F /gigs/1)")/$(has 'No, I have not received it' "$(page $F /gigs/1)")" "yes/yes"
check "paid money is counted as paid, unconfirmed"   "$(has '<div class="num">2,500</div><div class="lbl">Paid, unconfirmed' "$(page $F /payments)")" "yes"

# dispute, then record again, then confirm
check "freelancer reports it as not received"        "$(post $F /payments/1/dispute)" "302"
check "status is disputed"                           "$(sql 'select status from payments')" "disputed"
check "client sees the dispute notice"               "$(has 'has not arrived' "$(page $C /gigs/1)")" "yes"
check "client records it again with a new code"      "$(post $C /payments/1/paid -d 'reference=AB12CD34EF')" "302"
check "new code replaced the old one"                "$(sql 'select reference from payments')" "AB12CD34EF"
check "freelancer confirms receipt"                  "$(post $F /payments/1/confirm)" "302"
check "status is confirmed"                          "$(sql 'select status from payments')" "confirmed"
check "confirming again changes nothing"             "$(post $F /payments/1/confirm >/dev/null; sql 'select status from payments')" "confirmed"
check "cannot dispute a confirmed payment"           "$(post $F /payments/1/dispute >/dev/null; sql 'select status from payments')" "confirmed"
check "gig page says payment complete"               "$(has 'Payment complete' "$(page $F /gigs/1)")" "yes"
check "confirmed total appears on the Payments page" "$(has '<div class="num">2,500</div><div class="lbl">Confirmed' "$(page $C /payments)")" "yes"
check "client cannot change a confirmed payment"     "$(post $C /payments/1/paid -d 'reference=ZZZZZZZZZZ' >/dev/null; sql 'select reference from payments')" "AB12CD34EF"

for s in $(php -r '$p=new PDO("sqlite:'$DB'");foreach($p->query("select stored_name from submissions") as $r) echo $r[0]," ";'); do rm -f storage/deliveries/$s; done
echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
