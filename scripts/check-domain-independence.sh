#!/usr/bin/env bash
#
# scripts/check-domain-independence.sh
#
# CI / pre-commit gate for the QuickerFaster UI Library. It enforces the
# non-negotiable independence rule documented in:
#
#   docs/architecture/25-library-independence-safeguards.md §4
#
# The library (src/) MUST NOT reference:
#   1. HR / business-domain terms (employee, payroll, payslip, timesheet,
#      attendance, leave, holiday, clock_event, job_title).
#   2. Executable consuming-app namespace references (App\Modules\...) other
#      than the intentional config / discovery / decoupling seams.
#   3. Legacy HR branding (quick_hr, quick-hr, QuickHR, Quick HR).
#
# Exits 0 (PASS) only if all three checks pass; otherwise prints the offending
# file:line matches and exits non-zero. Dependency-free: uses only grep/sed.

set -euo pipefail

# Resolve the library root regardless of the caller's current working directory.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "$ROOT_DIR"

SRC="src"
FAIL=0

if [[ ! -d "$SRC" ]]; then
  echo "ERROR: $SRC/ not found under $ROOT_DIR" >&2
  exit 2
fi

echo "================================================================================"
echo " QuickerFaster UI Library — Domain Independence Gate"
echo " Root: $ROOT_DIR"
echo "================================================================================"
echo ""

# ---------------------------------------------------------------------------
# Check 1 — HR / business-domain terms in src/
# ---------------------------------------------------------------------------
echo "[1/3] HR / business-domain terms in src/ ..."

# Canonical domain nouns (snake_case and human forms) that must not appear in
# the domain-agnostic library source.
DOMAIN_TERMS='employee|payroll|payslip|timesheet|attendance|leave|holiday|clock_event|job_title|hr_admin|hr_manager|hr_staff|hr_supervisor'

# Pragmatic false-positive filters (case-insensitive):
#   - x-transition                    : Alpine.js attributes (x-transition:leave)
#   - leave_request                   : two-domain docblock example (§3.1/§6.4:
#                                       paired with 'expense_claim', not HR-only)
#   - leave <verb object>             : English verb "leave" ("Leave empty",
#                                       "leave the step pending", ...)
#   - <hr>/xhr/through/three/         : "hr" substrings — kept defensively to
#     throw/thread                      mirror the doc's canonical grep; none of
#                                       the current terms contain "hr"
#   - department/location             : generic Organization core-module concepts
FALSE_POSITIVES='x-transition|leave_request|leave[[:space:]]+(a|an|as|blank|conditions|empty|it|placeholder|session|that|the|them|this|unchanged)|<hr>|xhr|through|three|throw|thread|department|location'

CHECK1_MATCHES="$(
  grep -rniE "$DOMAIN_TERMS" "$SRC" --include='*.php' \
    | grep -viE "$FALSE_POSITIVES" \
    || true
)"

if [[ -n "$CHECK1_MATCHES" ]]; then
  echo "  ✗ FAIL — domain-specific term(s) found:"
  printf '%s\n' "$CHECK1_MATCHES" | sed 's/^/      /'
  FAIL=1
else
  echo "  ✓ PASS"
fi

# ---------------------------------------------------------------------------
# Check 2 — Executable App\Modules references in src/
# ---------------------------------------------------------------------------
echo ""
echo "[2/3] App\\Modules executable references in src/ ..."

# Grep both literal spellings:
#   App\\Modules   (one literal backslash)  -> FQCN examples in docblocks
#   App\\\\Modules (two literal backslashes) -> PHP string literals / config
#
# Allowed occurrences (filtered out below):
#   1. Comment-only lines                 — decoupling docblock/comment docs (§2.2)
#   2. src/Config/ui-library.php          — business_namespace default (§2.2)
#   3. src/Providers/ModuleServiceProvider.php  — generic discovery code (§2.2)
#   4. src/Services/AccessControl/ModelDiscovery.php — generic discovery code
#   5. src/Services/Discovery/DiscoveryRegistrar.php — generic discovery code
#   6. src/Core/*/Data/dashboards/*.php   — library-owned core-module (Admin,
#      System, Common, Organization) dashboard model configs, not business modules
CHECK2_MATCHES="$(
  grep -rnE 'App\\Modules|App\\\\Modules' "$SRC" --include='*.php' \
    | grep -vE '^[^:]+:[0-9]+:[[:space:]]*(\*|/\*|//|#)' \
    | grep -vE "^$SRC/Config/ui-library[.]php:" \
    | grep -vE "^$SRC/Providers/ModuleServiceProvider[.]php:" \
    | grep -vE "^$SRC/Services/AccessControl/ModelDiscovery[.]php:" \
    | grep -vE "^$SRC/Services/Discovery/DiscoveryRegistrar[.]php:" \
    | grep -vE "^$SRC/Core/[^:]+/Data/dashboards/" \
    || true
)"

if [[ -n "$CHECK2_MATCHES" ]]; then
  echo "  ✗ FAIL — executable App\\Modules reference(s) found:"
  printf '%s\n' "$CHECK2_MATCHES" | sed 's/^/      /'
  FAIL=1
else
  echo "  ✓ PASS"
fi

# ---------------------------------------------------------------------------
# Check 3 — Legacy HR branding in src/
# ---------------------------------------------------------------------------
echo ""
echo "[3/3] Legacy HR branding in src/ ..."

# Zero matches allowed: quick_hr, quick-hr, QuickHR, Quick HR (case-insensitive).
BRANDING='quick_hr|quick-hr|QuickHR|Quick HR'

CHECK3_MATCHES="$(grep -rniE "$BRANDING" "$SRC" --include='*.php' || true)"

if [[ -n "$CHECK3_MATCHES" ]]; then
  echo "  ✗ FAIL — legacy HR branding found:"
  printf '%s\n' "$CHECK3_MATCHES" | sed 's/^/      /'
  FAIL=1
else
  echo "  ✓ PASS"
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "================================================================================"
if [[ "$FAIL" -ne 0 ]]; then
  echo " RESULT: FAIL — fix the file:line matches above, then re-run this gate."
else
  echo " RESULT: PASS — src/ contains no business-domain, module, or branding leakage."
fi
echo "================================================================================"

exit "$FAIL"
