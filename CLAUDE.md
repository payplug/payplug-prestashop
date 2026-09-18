# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

PayPlug's official PrestaShop payment module (PHP 7.4+, PrestaShop 1.7+/8.x/9.x, Symfony DI container via YAML). It integrates PayPlug payment processing through multiple payment methods: standard card, Oney financing, Bancontact, Scalapay, Apple Pay, American Express, iDEAL, MyBank, Satispay, Wero, Bizum, installment plans, one-click (saved card), email link, and SMS link. All API calls go through `payplug/payplug-php`; there is no Payum.

## Commands

### PHP

```bash
composer install          # installs deps, installs CaptainHook git hooks, builds webpack assets
composer stan              # phpstan analyse (src/ and classes/, level 1)
composer cs-lint           # php-cs-fixer dry-run (check only)
composer cs:fix            # php-cs-fixer, applies fixes (also runs automatically on pre-commit)
composer test              # phpunit -c tests/phpunit.xml (runs every test under tests/)
composer hooks:install     # reinstall CaptainHook hooks if needed
```

Run a single PHPUnit test file or method directly (composer's `test` script has no way to pass args):

```bash
php vendor/bin/phpunit -c tests/phpunit.xml tests/actions/PaymentAction/SomeTest.php
php vendor/bin/phpunit -c tests/phpunit.xml --filter testSomeMethod
```

`phpstan.dist.neon` excludes `src/utilities/services/Mcp.php` (uses PHP 8 attributes, incompatible with the PHP 7.4 baseline used for static analysis).

### JS / assets

```bash
npm run build    # webpack build (dev/js, dev/css -> versioned bundles)
npm run watch     # webpack --watch
npm run lint      # eslint dev/js/
```

The admin configuration UI itself (Vue.js) lives in a separate repo (`payplug-ui-plugins`) and is not built from this repo — see [README.md](README.md) for the local dev proxy setup and the commands used to rebuild and copy its output into `dev/dist/payplug/views` and `dev/dist/pspaylater/views`.

## Git workflow

- Branch names must match `(feature|fix|hotfix|refactor)/(PRE|SMP)-<number>...` or `(release|patch)/<semver>` (enforced by CaptainHook `pre-commit`).
- Commit messages must match `^(PRE|SMP)-\d+:\s.+$` (enforced by CaptainHook `commit-msg`), e.g. `PRE-3622: Add Unified hosted fields configuration`.
- `composer cs:fix` runs automatically on every commit via CaptainHook; `.php-cs-fixer.dist.php`/`phpstan.dist.neon` are copied to the untracked `.php-cs-fixer.php`/`phpstan.neon` on checkout/merge.

## Architecture

### Bootstrap / dependency injection

`payplug.php` is the module entry point. On construction it instantiates `PayPlug\classes\DependenciesClass`, which is the root of a hand-rolled DI graph (separate from the `config/services.yml` Symfony container used for hook/action services registered there):

1. `DependenciesClass` builds validators and helpers, then instantiates `src/application/dependencies/PluginInit`.
2. `PluginInit` (extends `BaseClass`) constructs every adapter, action, model class, and repository, then wires them all onto a single `PluginEntity` via fluent setters (`setCardAction()`, `setAddress()`, `setOrderRepository()`, ...). This `PluginEntity` is the object graph everything else reads from (`$dependencies->getPlugin()`).
3. `DependenciesClass` also builds a set of legacy top-level classes (`AdminClass`, `CartClass`, `ConfigClass`, `HookClass`, `MediaClass`, `OrderClass`, `PaymentClass`, `PayplugLock`, `AmountCurrencyClass`) that wrap/delegate into the same plugin graph — these are what `payplug.php` and `classes/` code call directly (e.g. `$this->payplug_dependencies->hookClass->...`).

**`PluginInit`/`PluginEntity` is legacy — do not add new collaborators to it.** The steps above describe what already exists and why `$dependencies->getPlugin()->getXxx()` still works for old collaborators; they are not instructions for new code. Every new collaborator (repository, adapter, model class, action) is registered directly in `config/services.yml` instead — no `PluginInit` constructor line, no `PluginEntity` property/getter/setter. Resolve it either via the module (`$this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name)->getService('service.id')`) or, for any class that already exposes `$dependencies`, via the `ServiceGetter` trait (`src/utilities/traits/ServiceGetter.php`): `$this->getService('service.id')`. A service used but not registered fails at runtime with `ServiceNotFoundException`.

For a class whose constructor already expects an injected `$dependencies` (e.g. anything extending `EntityRepository`/`QueryRepository` — their base constructor is `__construct($dependencies = null)`, stored as-is with **no fallback** to building its own), register it with an explicit argument reference to the existing `payplug.dependencies_class` service, e.g.:

```yaml
  payplug.models.repositories.operation:
    class: PayPlug\src\models\repositories\OperationRepository
    arguments: ['@payplug.dependencies_class']
    public: true
```

Omitting `arguments` here is a silent runtime bug, not a simplification — the container then calls the constructor with zero arguments, `$dependencies` is `null`, and the first `$this->dependencies->getPlugin()->...` call inside the class fatals. Classes that instead build their own `new DependenciesClass()` internally (the established convention for `src/actions/*Action`, `src/utilities/services/*`, and `src/models/classes/*` — see `HookAction`, `API`, `Merchant`) take no constructor argument and need no `arguments` key at all.

Usage from calling code stays the same either way:

```php
$operation_repository = $this->dependencies
    ->getPlugin()
    ->getModule()
    ->getInstanceByName($this->dependencies->name)
    ->getService('payplug.models.repositories.operation');
// or, inside a class using ServiceGetter:
$operation_repository = $this->getService('payplug.models.repositories.operation');
```

### Layers under `src/`

- `src/actions/` — business logic entry points (`PaymentAction`, `ValidationAction`, `OrderAction`, `CartAction`, `OneyAction`, `CardAction`, `QueueAction`, `HookAction`, ...). Controllers and hooks call into these rather than touching models/adapters directly.
- `src/application/adapter/` — thin wrappers around **CMS-native classes** (PrestaShop's `Product`, `Order`, `Cart`, `Context`, `Tools`, ...), each with a matching interface in `src/interfaces/`. The criterion for a file/method belonging here is narrow: it must directly call a CMS core class/static. E.g. `ProductAdapter::getIdProductAttributeByIdAttributes()` calls `\Product::getIdProductAttributeByIdAttributes(...)` directly — if this module were ported to a CMS where the equivalent class is `Item`, only that one line inside the adapter changes (`\Product::...` → `\Item::...`); every business-logic call site (`$this->productAdapter->getIdProductAttributeByIdAttributes(...)`) stays identical. This indirection exists for testability and CMS-version/CMS-portability isolation; do not bypass it to call CMS core statics directly from business logic. Conversely, a class that only orchestrates *other* module classes/repositories (no direct CMS static/class call of its own) is not an adapter, even if it implements a third-party vendor interface (e.g. a `payplug/unified-plugin-core` contract) — that's domain logic and belongs in `src/models/classes/` instead (see `UpcLogger`/`UpcLock`/`UpcConfigurationRepository`/etc., PRE-3624).
- `src/models/classes/` — domain model classes (`Order`, `Configuration`, `ApiRest`, `Merchant`, ...) and, under `paymentMethod/`, one class per payment method extending `PaymentMethod`.
- `src/models/repositories/` and `src/repositories/` — two parallel repository layers (the `models/repositories` ones are the newer, DI-wired versions constructed in `PluginInit::setRepositories()`; `src/repositories/*` plus `classes/*` are older repositories still constructed directly in `PluginInit::setOldRepositories()`/`DependenciesClass`). Check which one a given entity already uses before adding new persistence code for it.
- `src/utilities/services/` — cross-cutting services: `API.php` (the only allowed entry point to the PayPlug PHP SDK — never instantiate SDK classes directly elsewhere), `Core.php`, `Mail.php`, `PhoneNumber.php`, `Routes.php`, `MerchantTelemetry.php`, `Mcp.php` (MCP integration, PHP 8+ only, excluded from phpstan/cs-fixer on PHP < 8).
- `classes/` — legacy top-level classes predating the `src/` restructure (`ConfigClass`, `HookClass`, `PayplugLock`, `CartClass`, `OrderClass`, `PaymentClass`, `MediaClass`, `AdminClass`, `AmountCurrencyClass`, `DependenciesClass`).
- `controllers/front/` — front-office endpoints: `ipn.php` (webhook), `validation.php` (return from payment), `ajax.php`, `dispatcher.php`, `cards.php`, `applepaypaymentrequest.php`, `uhf.php` (unified hosted fields), `oauthcallback.php`.
- `controllers/admin/` — `AdminPayplugController` (main config) and `AdminPayPlugInstallmentController`.

### Intentional patterns (do not "fix" these)

- `PayplugLock` uses a `sleep()`-based polling loop by design, to prevent a race between the IPN webhook and the customer redirect both trying to finalize the same order.
- IPN payloads must go through `PayPlug\classes\ConfigClass::setNotification()->treat()` — this wraps `php://input` reading and SDK signature verification. Never read/act on raw `php://input` outside of it.
- `AmountHelper::convertAmount()` multiplies by 1000 then divides by 10 before rounding (e.g. `17.90 → 1789`) specifically to avoid floating-point drift; don't simplify the arithmetic. The PayPlug API expects integer amounts in cents; `convertAmount($amount, true)` converts cents back to a float. Passing an unconverted float, or converting twice, is a bug.
- Payment methods that don't support saved cards (iDEAL, MyBank, Bancontact, Satispay, Apple Pay, email link, SMS link, installment) explicitly unset/force `allow_save_card = false` — expected, not an oversight.
- Front-office controllers/AJAX endpoints under `controllers/front/` must respond with JSON containing `redirect_url` rather than an HTTP redirect, for headless-storefront compatibility. `validation.php` and `dispatcher.php` are known pre-existing exceptions — don't replicate that pattern in new code.
- New payment methods need: a class extending `PaymentMethod` in `src/models/classes/paymentMethod/`, correct `allow_save_card` handling, currency validation before the API call (EUR-only enforcement), `payment_context.cart` in the payload for Oney/Scalapay-style financing, registration in `config/services.yml`, templates, and translations in all 8 locales (`en`, `gb`, `fr`, `de`, `it`, `es`, `nl`, `pt`).

(Full detail, including PR review dimensions, is in [.github/copilot-instructions.md](.github/copilot-instructions.md).)

### Unified Hosted Fields (UHF)

New, in-progress payment flow for non-EUR carts (PRE-3622/PRE-3623), gated behind the `feature_hosted_fields` flag in `features.json`. It coexists with the existing Retail API flow rather than replacing it.

- **Config**: a single `Configuration` key, `hosted_fields` (JSON, defaults to `{}`), maps lowercase currency ISO code → UHF identifier for that currency (e.g. `{"usd":"...","gbp":"..."}`). `ConfigurationAction::saveAction()` derives one `payplug_identifier_<iso>` field per active non-EUR currency and merges each into that JSON blob (merge, not replace — don't collapse this back into a single flat key).
- **Admin UI gating**: `CurrencyAdapter::hasOtherCurrency()` (shop has at least one non-EUR active currency) controls whether the per-currency identifier inputs are even shown on `StandardPaymentMethod::getOption()`. Conversely, `PaymentMethod::getAvailablePaymentMethod()` returns only `['standard']` when `feature_hosted_fields` is on and the shop has **no** EUR currency at all — other payment methods are EUR-only and get disabled outright in that case. `StandardPaymentMethod::getOption()` was refactored to extract `getEmbeddedOptions()` / `getWarningOptions()` / `getAliasingOptions()`; the `integrated`/`popup`/`redirect` embedded-mode selector itself (`getEmbeddedOptions()`) is hidden whenever the shop has no EUR currency, since it's meaningless for non-EUR-only shops.
- **Checkout routing**: `PrestashopAdapter17::displayPaymentOption()` branches on the cart's currency, not the shop's — the existing `integrated` embedded mode is now explicitly restricted to `'EUR' === $this->context->currency->iso_code`; a non-EUR cart instead goes through `setHostedFieldsPaymentOption()` (mirrors `setIntegratedPaymentOption()`) when `feature_hosted_fields` is on and an identifier is configured for that cart's currency. It renders `views/templates/hook/checkout/payment/hosted_fields.tpl`.
- **Front controller**: `controllers/front/uhf.php` (`PayplugUhfModuleFrontController`) receives `hfToken`/`selectedBrand`/`save_card`/`id_cart` from the client-side SDK, validates the card brand (`cb`/`visa`/`mastercard` only) and that the cart hasn't already been turned into an order, then just logs and acknowledges receipt. **It does not create a payment resource yet** — that's explicitly deferred to a follow-up ticket ("Branchement paiement / 3DS sur UPC"). Don't assume a 200 from this controller means a payment happened.
- **JS SDK**: `dev/js/front.js` gained a `hosted_fields` submodule initializing the `dalenys.hostedFields` widget (card/expiry/CVV fields), with per-field validation, brand allow-listing, an anti-double-submit guard with a safety timeout, and tokenization before POSTing to `uhf.php`.
- **Routes**: `Routes::getHostedFieldsUrl()` reads `HOSTED_FIELDS_URL` from `payplugroutes/.env` if present, else returns an empty string — unlike `getOneyLoaderUrl()`, there is deliberately no hardcoded production fallback (the real production CDN URL isn't confirmed; a guessed-but-wrong URL would fail silently, so every real deployment must set `HOSTED_FIELDS_URL` explicitly).
- The pre-existing Retail API flow (`dispatcher.php`, `PaymentAction`, resource creation) is untouched by this work so far.

### Design docs

Feature specs and implementation plans produced via the `superpowers` skill workflow live under [docs/superpowers/specs/](docs/superpowers/specs/) and [docs/superpowers/plans/](docs/superpowers/plans/).
