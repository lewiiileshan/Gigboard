#!/usr/bin/env bash
# Smoke test: starts PHP's built-in server on a throw-away SQLite database and checks register / login / logout.
# Run from the project folder:  bash tests/smoke.sh      (needs php and curl; no MySQL required)
cd "$(dirname "$0")/.." || exit 1
DB=$(mktemp -u /tmp/gb_smoke_XXXX.sqlite); PORT=8099; B=http://127.0.0.1:$PORT; J=$(mktemp)
php -r '$p=new PDO("sqlite:'$DB'");$p->exec(file_get_contents("tests/sqlite_schema.sql"));'
export GB_DB_DSN="sqlite:$DB" GB_DB_USER="" GB_DB_PASS=""
php -S 127.0.0.1:$PORT -t public tests/dev-router.php >/dev/null 2>&1 & PID=$!; sleep 1
pass=0; fail=0
check(){ if [ "$2" = "$3" ]; then echo "PASS  $1"; pass=$((pass+1)); else echo "FAIL  $1 (expected '$3', got '$2')"; fail=$((fail+1)); fi; }
tok(){ curl -s -b $J -c $J "$B$1" | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
status(){ curl -s -o /dev/null -w "%{http_code}" -b $J -c $J "$@"; }
loc(){ curl -s -o /dev/null -w "%{redirect_url}" -b $J -c $J "$@"; }
page(){ curl -s -b $J -c $J "$B$1"; }
reg(){ T=$(tok /register); loc -X POST $B/register --data-urlencode "_csrf=$T" -d "$1"; }

check "dashboard redirects to login when logged out" "$(loc $B/dashboard)" "$B/login"
check "POST without CSRF token is rejected"          "$(status -X POST $B/register -d name=x)" "419"
reg "name=Amina&email=amina@example.com&role=client&password=short&confirm=short&terms=1" >/dev/null
check "short password is rejected with a message"     "$(page /register | grep -c 'at least 8 characters')" "1"
reg "name=Amina&email=amina@example.com&role=client&password=longpassword1&confirm=longpassword1" >/dev/null
check "terms of service are required"                 "$(page /register | grep -c 'You must accept the terms')" "1"
check "valid registration goes to dashboard"          "$(reg 'name=Amina&email=amina@example.com&role=client&password=longpassword1&confirm=longpassword1&terms=1')" "$B/dashboard"
check "dashboard greets the new user"                 "$(page /dashboard | grep -c 'Welcome back')" "1"
check "password stored as bcrypt hash"               "$(php -r '$p=new PDO("sqlite:'$DB'");echo substr($p->query("select password_hash from users")->fetchColumn(),0,4);')" '$2y$'
T=$(page /account | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
check "logout redirects to login"                     "$(loc -X POST $B/logout --data-urlencode "_csrf=$T")" "$B/login"
check "logout message is shown"                       "$(page /login | grep -c 'logged out')" "1"
check "dashboard blocked after logout"                "$(loc $B/dashboard)" "$B/login"
check "duplicate email (any case) is rejected"        "$(reg 'name=Other&email=AMINA@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1' >/dev/null; page /register | grep -c 'already exists')" "1"
T=$(tok /login); loc -X POST $B/login --data-urlencode "_csrf=$T" -d "email=amina@example.com&password=wrongwrong" >/dev/null
check "wrong password shows a generic error"          "$(page /login | grep -c 'Incorrect email or password')" "1"
T=$(tok /login)
check "correct password logs in"                      "$(loc -X POST $B/login --data-urlencode "_csrf=$T" -d 'email=amina@example.com&password=longpassword1')" "$B/dashboard"
J2=$(mktemp); T2=$(curl -s -b $J2 -c $J2 $B/register | grep -o 'name="_csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -o /dev/null -b $J2 -c $J2 -X POST $B/register --data-urlencode "_csrf=$T2" --data-urlencode "name=<script>alert(1)</script>" -d "email=x@example.com&role=freelancer&password=longpassword1&confirm=longpassword1&terms=1"
XSS=$(curl -s -b $J2 -c $J2 $B/dashboard | grep -c -F '&lt;script&gt;alert(1)&lt;/script&gt;')
check "HTML in a name is escaped (XSS)"               "$([ "$XSS" -ge 1 ] && echo yes || echo no)" "yes"
check "stylesheet is served"                          "$(status $B/css/app.css)" "200"
check "unknown page gives 404"                        "$(status $B/nope)" "404"
kill $PID 2>/dev/null; rm -f "$DB" "$J" "$J2"
echo; echo "$pass passed, $fail failed"; [ "$fail" -eq 0 ]
