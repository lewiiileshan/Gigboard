# Sourced by every test script. Sets DSN, DBU, DBP and prepares an empty database.
# Default: a throw-away SQLite file. Set GB_TEST_MYSQL=1 to run against a real MySQL/MariaDB
# database named gigboard_test (user, password and host can be changed with GB_MY_USER,
# GB_MY_PASS and GB_MY_HOST).
if [ "${GB_TEST_MYSQL:-0}" = "1" ]; then
  DSN="mysql:host=${GB_MY_HOST:-127.0.0.1};dbname=gigboard_test;charset=utf8mb4"
  DBU="${GB_MY_USER:-gb}"; DBP="${GB_MY_PASS:-gbpass}"
  MYARGS="-h ${GB_MY_HOST:-127.0.0.1} -u $DBU -p$DBP"
  mysql $MYARGS -e "DROP DATABASE IF EXISTS gigboard_test; CREATE DATABASE gigboard_test CHARACTER SET utf8mb4;" || { echo "Cannot reach MySQL"; exit 1; }
  sed '/^CREATE DATABASE/d;/^USE /d' database/schema.sql | mysql $MYARGS gigboard_test || { echo "Schema import failed"; exit 1; }
else
  DSN="sqlite:$DB"; DBU=""; DBP=""
  php -r '$p=new PDO("sqlite:'$DB'");$p->exec(file_get_contents("tests/sqlite_schema.sql"));'
fi
export GB_DB_DSN="$DSN" GB_DB_USER="$DBU" GB_DB_PASS="$DBP"
