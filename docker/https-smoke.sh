#!/usr/bin/env bash
# Checks what only happens behind Caddy's TLS (see docker-compose.ci.yml): redirect to HTTPS, HSTS, a CSP
# that allows the HTTPS app origin, and a session cookie marked Secure (X-Forwarded-Proto reaching PHP).
# Usage: docker/https-smoke.sh [https-base] [http-base]   (defaults: the CI stack on 8443/8080)
set -euo pipefail

HTTPS=${1:-https://localhost:8443}
HTTP=${2:-http://localhost:8080}
# Caddy's internal CA signs the localhost certificate; trust is not what's under test here.
CURL=(curl -sS -k --max-time 15)
failures=0

check() { # check <description> <haystack> <needle>
  if grep -qiF -- "$3" <<<"$2"; then echo "ok   $1"; else echo "FAIL $1 (expected: $3)"; failures=$((failures + 1)); fi
}

echo "Waiting for $HTTPS ..."
for _ in $(seq 1 60); do
  [ "$("${CURL[@]}" -o /dev/null -w '%{http_code}' "$HTTPS/" || true)" = 200 ] && break
  sleep 2
done

redirect=$("${CURL[@]}" -o /dev/null -D - "$HTTP/books")
check "plain HTTP redirects" "$redirect" "HTTP/1.1 308"
check "redirect goes to https" "$redirect" "location: https://"

page=$("${CURL[@]}" -o /dev/null -D - "$HTTPS/")
check "SPA served over HTTPS" "$page" "HTTP/2 200"
check "HSTS header" "$page" "strict-transport-security: max-age="
check "CSP lets the SPA call the HTTPS API" "$(grep -io 'connect-src [^;]*' <<<"$page")" " https://localhost "

email="smoke-$(date +%s)-$RANDOM@e2e.e-lib.test"
"${CURL[@]}" -o /dev/null -H 'Content-Type: application/json' \
  -d "{\"username\":\"smoke$RANDOM\",\"email\":\"$email\",\"password\":\"Smoke-test-pw-1\"}" "$HTTPS/api/v1/signup"
login=$("${CURL[@]}" -D - -H 'Content-Type: application/json' \
  -d "{\"email\":\"$email\",\"password\":\"Smoke-test-pw-1\"}" "$HTTPS/api/v1/login")
cookie=$(grep -i '^set-cookie: PHPSESSID=' <<<"$login" || true)
check "login sets the session cookie" "$cookie" "PHPSESSID="
check "session cookie is Secure" "$cookie" "; secure"
check "session cookie is HttpOnly" "$cookie" "; httponly"

token=$(grep -o '"token":"[^"]*"' <<<"$login" | cut -d'"' -f4 || true)
me=$("${CURL[@]}" -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $token" "$HTTPS/api/v1/user/profile")
check "Bearer token reaches PHP through Caddy and Apache" "$me" "200"

[ "$failures" -eq 0 ] || { echo "$failures check(s) failed"; exit 1; }
echo "All HTTPS checks passed"
