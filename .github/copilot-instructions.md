# Copilot Instructions

These instructions apply to code reviews on pull requests in this repository.

## Project Context

This is PayPlug's official PrestaShop module. PayPlug is a Payment Service Provider (PSP). The module integrates PayPlug's payment processing into PrestaShop via multiple payment methods: standard card, Oney financing, Bancontact, Scalapay, Apple Pay, American Express, iDEAL, MyBank, Satispay, Wero, Bizum, installment plans, one-click (saved card), email link, and SMS link.

**Stack**: PHP 7.4+, PrestaShop 1.7+, Symfony DI Container (YAML), `payplug/payplug-php ^4.0`, no Payum.

**Key architectural layers**:
- `payplug/payplug.php` — module entry point, bootstraps the Symfony DI container
- `src/actions/` — business logic actions (`PaymentAction`, `ValidationAction`, `OrderAction`, `CartAction`, `OneyAction`, `CardAction`, `QueueAction`, …)
- `src/models/classes/paymentMethod/` — one class per payment method (e.g. `StandardPaymentMethod`, `OneyPaymentMethod`)
- `src/application/adapter/` — thin wrappers around PrestaShop core classes (enables testability and version compatibility)
- `src/models/repositories/` — data access layer for custom DB tables (Payment, Card, Lock, Queue, …)
- `src/application/dependencies/` — `DependenciesClass` / `PluginInit` compose the DI container
- `controllers/front/` — front-office controllers (`ipn.php`, `validation.php`, `ajax.php`, `dispatcher.php`, …)
- `controllers/admin/` — back-office controllers
- `classes/` — legacy classes (`ConfigClass`, `PayplugLock`, `HookClass`, …)
- `config/services.yml` — all service registrations

## Intentional Patterns — Do Not Flag as Issues

- **`sleep()` in `PayplugLock`** — the `while`-loop in `classes/PayplugLock.php` sleeps between lock checks to prevent a race condition between the IPN webhook and the customer redirect. Never suggest removing it.
- **`$notification->treat()` in `ipn.php`** — `ConfigClass::setNotification()` wraps `php://input` reading and SDK signature verification. Calling `->treat()` on the returned object is the correct and only safe IPN processing path. Direct reads of `php://input` outside of this wrapper are a bug.
- **No direct SDK calls** — `PayPlugApiClient` (via `src/utilities/services/API.php`) is the only allowed entry point to the PayPlug PHP SDK. Any direct instantiation of SDK classes bypasses this and is a bug.
- **`convertAmount()` arithmetic trick** — `AmountHelper::convertAmount()` multiplies by 1000 then divides by 10 before rounding to avoid floating-point drift (e.g. `17.90 → 1789`). This is intentional and correct; do not simplify it.
- **Adapter pattern everywhere** — wrapping PrestaShop core classes in adapters (`src/application/adapter/`) is intentional for testability and PS version isolation. Do not flag as over-engineering.
- **`allow_save_card` unset in some payment methods** — methods that do not support card saving (iDEAL, MyBank, Bancontact, Satispay, Apple Pay, email link, SMS link, installment) explicitly `unset` or force `allow_save_card = false`. This is correct behaviour; its absence in a new method is a bug.

## Code Review Dimensions

### Security
- SQL injection, XSS, CSRF
- Authentication and authorization flaws
- Secrets or credentials committed in code
- Insecure deserialization, path traversal, SSRF
- Direct calls to PayPlug PHP SDK classes instead of going through `PayPlugApiClient`
- IPN payloads must be processed via `ConfigClass::setNotification()->treat()` — never act on raw `php://input` directly
- Card data (PAN, CVV, raw card numbers) must never appear in logs, error messages, or stored fields
- API secret keys must never appear in logs, exception messages, or HTTP responses
- Payment amounts must be validated server-side — never trust a client-submitted amount
- `cancel_url` / `return_url` values must be built from `$this->context->link->getModuleLink(…)`, never constructed from user input (open redirect risk)

### Performance
- N+1 queries (especially during cart or order entity traversal)
- Unnecessary memory allocations
- Algorithmic complexity (O(n²) in hot paths)
- Missing database indexes on custom tables
- Unbounded queries or loops
- Resource leaks

### Correctness
- Edge cases: empty input, null, overflow
- Race conditions and concurrency issues
- Error handling and propagation
- Off-by-one errors, type safety
- `declare(strict_types=1)` is encouraged but not universally enforced (PHP 7.4 baseline — flag its absence in new files, not existing ones)
- **Amount units**: the PayPlug API expects amounts as integers in euro-cents. `AmountHelper::convertAmount($amount)` converts from a float (euros) to an integer (cents); `convertAmount($amount, true)` converts from cents back to float. Silently mixing units is a payment amount bug. Any code that passes an unconverted float to the API, or converts twice, must be flagged.
- **EUR-only enforcement**: currency validation must happen before the API call, not after. Missing it for a new payment method is a bug.
- **Refund amounts**: a refund must not exceed `resource->amount - resource->amount_refunded`. Missing this guard is a bug.
- **Card saving guard**: a card must only be saved when the PayPlug API response confirms `allow_save_card`. New one-click flows missing this guard are a bug.
- **New payment method checklist**: for any new payment method class in `src/models/classes/paymentMethod/`, verify: dedicated class extending `PaymentMethod`, `allow_save_card` handling (unset or conditional), currency validation, `payment_context.cart` included if Oney/Scalapay-style financing, service registration in `config/services.yml`, templates, translations in all 8 locales.
- **`payment_context.cart`**: required in the API payload for Oney and Scalapay. Missing it causes API rejection.

### Maintainability
- Naming clarity, single responsibility, duplication
- Test coverage: PHPUnit in `tests/`, Behat if present
- Documentation for non-obvious logic only — do not flag missing comments on self-explanatory code
- Coding standard: PHP-CS-Fixer with PSR-12 + custom rules (see `.php-cs-fixer.php`)
- **Translations**: all 8 locales must be complete (`en`, `gb`, `fr`, `de`, `it`, `es`, `nl`, `pt`). A translation present in some locales but missing in others is a regression.
- **Service registration**: new services must be registered in `config/services.yml`. Forgetting registration causes a runtime `ServiceNotFoundException`.
- **`config/services.yml` vs inline wiring**: prefer explicit constructor injection declared in `services.yml` over manual instantiation inside action or model classes.

### Headless / API Compliance

When the module is used in a headless storefront, the front-end cannot follow server-side HTTP redirects. Shop-facing controllers must not return a `RedirectResponse`; they should return a JSON payload with a `redirect_url` key and let the client perform the redirect.

- Front-office controllers and AJAX endpoints in `controllers/front/` must return a JSON response containing `redirect_url` rather than a `RedirectResponse` for headless compatibility.
- Known existing offenders (do not flag these as new issues, but flag any new code that replicates the pattern):
    - `controllers/front/validation.php` — returns a redirect on all exit paths
    - `controllers/front/dispatcher.php` — dispatches to handlers that may return redirects
- Admin controllers (`controllers/admin/`) are exempt — headless compliance only applies to the shop payment flow.
- If a new front-office controller or AJAX handler returns `RedirectResponse` (or `Tools::redirect()`), flag it as a headless compliance issue.

## Output Format

Structure the review comment exactly as follows:

### 1. What's Good

A bullet list of positive observations — things done well, non-obvious correct decisions, solid patterns.

---

### 2. Summary table

A markdown table with two columns: **Dimension** and **Rating**. One row per review dimension. Use emoji inline with the rating text:

| Dimension | Rating |
|---|---|
| Security | ✅ Fine |
| Correctness | ⚠️ Medium (short reason) |
| Performance | ✅ Fine |
| Maintainability | ⚠️ Low (short reason) |

Severity scale:
- ✅ **Fine** — no issues
- ⚠️ **Low / Medium** — should be fixed but not blocking
- ❌ **High / Critical** — must be fixed before merge

---

### 3. Closing one-liner

A single sentence summarising what needs to be addressed before merge (or that the PR is ready if nothing critical).

---

### 4. Individual findings (one section per issue)

Each finding follows this exact structure:

**Heading:** `[Dimension] [emoji] [Severity]` — e.g. `Security ⚠️ Medium`

**Subtitle (bold):** short title followed by the file path and line number as a markdown link — e.g. `**Open redirect in return URL** (StandardPaymentMethod.php:364)`

**Code block:** the relevant snippet from the diff showing the problem.

**Explanation paragraph:** what the risk is and why it matters. Be concrete.

**Fix line:** start with `Fix:` in bold, then a brief description, followed by a code block showing the suggested fix.

Lead with Critical/High findings. Omit the findings section entirely if there are no issues.

## Iterative Reviews

When reviewing a new commit on a PR that already has open review threads:

- **Resolve threads** for issues that have been addressed in the new commit — do not leave them open if the fix is present.
- **Do not re-open or re-comment** on issues that were already resolved in a previous round.
- Only open new threads for issues that are genuinely new or that remain unresolved.
- If a previous finding was partially addressed, update the thread with what still needs attention rather than opening a duplicate.