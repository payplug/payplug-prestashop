# Payplug module changelog

## Version 4.23.0
- Feature :
  - [PRE-3165](https://github.com/payplug/payplug-prestashop/pull/21): Add Spanish translations
  - [PRE-3202](https://github.com/payplug/payplug-prestashop/pull/28): Add Scalapay BO configuration
  - [PRE-3203](https://github.com/payplug/payplug-prestashop/pull/29): Make a payment with Scalapay

## Version 4.22.0
- Feature :
  - [PRE-3033](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/): Add new payment method: Wero & Bizum
  - [PRE-3154](https://github.com/payplug/payplug-prestashop/pull/18): Update wording for APM bundle description 

- Bugfix :
  - [SMP-3239](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2733): Integration complementary address from apple wallet

## Version 4.21.0
- Refactor :
  - [PRE-3040](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2723): Add log in logout action
  - [PRE-3064](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2721): Update JWT experacy date and update payplug-php library to 4.0.0
  - [PRE-2206](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2728): Refactor Oney unit tests
  - [PRE-3076](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2729): Try jwt generation in case of 401 error code

## Version 4.20.1
- Bugfix :
  - [SMP-3042](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2722): Fix Oney simulation when is elligible

## Version 4.20.0
- Feature :
  - [SMP-2967](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2715): Activate Apple Pay on product page

- Bugfix :
  - [SMP-3222](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2711): Fix Oney 4x display on checkout page
  - [SMP-3155](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2716): Remove order creation on validation postprocessing to avoid concurrence
  - [SMP-3224](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2717): Fix schedules display on checkout
  - [PRE-3051](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2718): Fix update carrier on applepay product & cart

## Version 4.19.0
- Feature :
  - [PRE-2835](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2685): Display oney on checkout in if cart is elligible
  - [PRE-2987](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2697): Deploy Unify authentication
  - [PRE-3010](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2703): Fix enable mode on unify authentication
  - [PRE-2953](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2700): Add partial refund status
  - [PRE-3018](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2706): Enable CB network with Appelpay

## Version 4.18.0
- Feature :
  - [PRE-2758](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2678): Enable CB by default and expose brand selector in Apple Pay
  - [PRE-2804](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2679): Enable Apple Pay on desktop
  - [PRE-2805](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2680): Place order from ApplePay SDK

## Version 4.17.4
- Bugfix :
  - [SMP-3156](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2680): Check getContainer avaibility to ensure symf service usage

## Version 4.17.3
- Bugfix :
  - [SMP-3105](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2669): Use defaut carrier set in Prestashop as ApplePay cart default carrier
  - [SMP-3087](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2672): Fix Ajax default call
  - [SMP-3153](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2677): Fix hook installation on OrderAdmin for Prestashop 1.7

## Version 4.17.2
- Bugfix :
  - [SMP-3074](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2665): Fix retrocompatibility on price display for oney
  - [PRE-2785](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2659): Fix Oney mobile phone check for Mayotte
  - [PRE-2850](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2668):  Fix address in apple pay order create on cart
  
## Version 4.17.1
- Bugfix :
  - [SMP-3111](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2666): Add symf vendor for retrocompatibility

## Version 4.17.0
- Feature :
    - [PRE-2700](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2636): Create order from payment link
    - [PRE-2699](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2635/): Activate Payment Link in BO
    - [PRE-2798](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2651): Initialize config before calling MPDC

- Bugfix :
  - [SMP-3080](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2658): Fix ordre state update 
  
## Version 4.16.2
- Bugfix :
  - [PRE-2769](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2644): Fix infinite loop in queue treatment from notification

## Version 4.16.1
- Feature :
  - [PRE-2106](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2632): PRE-2106 : Remove Oney BE and ES

- Bugfix :
  - [PRE-1958](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2630): Fix thresholds error on paylater feature (update payplug-ui to 1.7.4)
  - [PRE-2002](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2627): Remove obsolete adapter for prestashop 1.6.X and relative usage
  - [PRE-2760](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2637): Fix missing Payplug logo in configuration
  - [SMP-3047](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2646): Add sleep 1 second on validation

- Refactoring
  - [PRE-2640](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2621): Add second parameter when using ps_round for Prestashop 9
  - [PRE-2643](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2620): Use deprecated function l from translation class instead of ModuleAdminController
  - [PRE-2639](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2619): Rename deprecated paymentReturn hook name for Prestashop 1.7 and upper
  - [PRE-2642](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2618): Rename deprecated customerAccount hook name for Prestashop 1.7 and upper
  - [PRE-2004](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2628): remove cards template for ps1.6
  - [PRE-2745](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2624): Replace use of deprecated displayPrice by formatPrice for Oney CTA
  - [PRE-2000](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2623): Remove obsolete oney method
  - [PRE-2003](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2631): Clean ps 16 front dependencies

## Version 4.16.0
- Refactoring :
  - [PRE-1999](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2615): Clean obsolete hook
  - [PRE-1998](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2613): Clean last PayPlugAjaxClass usage
  - [PRE-1997](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2609): Remove obsolete front file
  - [PRE-2711](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2617): Update order state wording
  
- Chore:
  - [PRE-2729](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2616): Update payplug-php library version to 3.6.0
  

## Version 4.15.0
- Feature :
  - [PRE-2673](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2594): Allow refund for PPRO payment resource
  - [PRE-2600](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2601): Save JWT informations
  - [PRE-2601](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2584): Send JWT in api request
  - [PRE-2708](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2610): Update order state if payment resource is refunded
  - [PRE-2674](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2614): Change support contact message for disabled features

- Bugfix :
  - [PRE-2687](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2605): Delete the usage of deprecated php function utf8_encode
  - [PRE-2688](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2608): Fix the refund error message + unit tests
  - [PRE-2715](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2611): Fix login for non onborded account

## Version 4.14.4
- Bugfix :
  - [SMP-3018](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2602) :Fix return notification if notification resource has failure

## Version 4.14.3
- Bugfix :
  - [SMP-3017](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2600) :Fix permissions got when merchant has no live api key + Fix password encodage

## Version 4.14.2
- Bugfix :
  - [SMP-3003](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2590) : Force resource creation if stored and selected payment method are not the same
  - [SMP-3009](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2592) : Update upgrade script to fix column creation

## Version 4.14.1
- Bugfix :
  - [SMP-3005](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2589) : Fix 4.14.0 upgrade

## Version 4.14.0
- Feature :
  - [PRE-2477](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2539): Set handler in database to define live/test mode
  - [PRE-2599](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2568): Register client id and secret given from API
  - [PRE-2651](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2570): Add cache and parallelize tasks to optimize the packaging
  - [PRE-2628](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2571): Remove Sofort feature

- Security :
    - [PRE-2586](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2567): delete usage of check-php job to avoid a vulnerability
    - [PRE-2637](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2569): Fix vulnerability path traversal and update webpack version

- Bugfix :
  - [PRE-2652](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2572) : Fix state auto update on Oney order
  - [PRE-2666](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2581) : Fix unit tests
  - [PRE-2665](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2582) : Fix prestashop validator errors

## Version 4.13.1
- Bugfix :
  - [SMP-2979](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2565) : Add displayHeader to upgrade script
  - [SMP-2999](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2578) : Fix double order creation

## Version 4.13.0
- Feature :
  - [PRE-2565](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2546): Update ApplePay translations in BO
  - [PRE-2623](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2550): Activate queueing system
  - [PRE-2557](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2547): Update CB logo on client side
  - [PRE-2627](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2557): Update CB logo and Mastercard logos

- Bugfix :
  - [SMP-2959](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2560) : Fix check if installment plan is partially or fully refund

## Version 4.12.1
- Bugfix :
  - [SMP-2948](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2552) : Fix cancellable field for Satispay, Amex and Ideal payment methods
  - [SMP-2974](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2558) : Fix var declaration in front js
  
## Version 4.12.0
- Feature :
    - [PRE-2557](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2547): Update CB logo on client side
    - [PRE-2624](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2553): Add 5 life time to queue

## Version 4.12.0
- Refactoring :
  - [PRE-2519](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2517): Set Queue Entity
  - [PRE-2579](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2526): Add queue system on notifications and flag it
  - [PRE-2581](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2531): Add queue system on payment capture and flag it
  - [PRE-2580](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2532): Add queue system on refund and flag it
  - [PRE-2578](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2527): Add queue system on validation and flag it

- Feature :
    - [PRE-2509](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2533): Improve database usage

- Bugfix :
    - [PRE-2507](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2534) : Fix unit tests on updateAction on queue system
    - [PRE-2611](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2535): Fix order state query at plugin install
    - [PRE-2614](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2536): Disable queue system

## Version 4.11.2
- Bugfix :
  - [SMP-2936](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2518): Fix double order creation

- Security :
  - [PRE-2586](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2529): delete usage of check-php job to avoid a vulnerability

## Version 4.11.1
- Bugfix :
  - [SMP-2786](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2518): Fix Appelpay carrier display description

- Refactoring :
  - [PRE-2511](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2491): Set EntityRepository
  - [PRE-2515](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2492): Set Logger entity
  - [PRE-2517](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2494): Set State Entity
  - [PRE-2512](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2493): Set CacheEntity
  - [PRE-2518](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2502): Set Payment Entity
  - [PRE-2516](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2502): Clean Order Payment repository
  - [PRE-2513](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2504): Set Card Entity

## Version 4.11.0
- Feature :
  - [PRE-2561](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2514): Remove giropay payment method
  - [SMP-2882](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2515): Fix the use of installTab inside payplug.php

- Bugfix :
  - [SMP-2870](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2511): Remove my-account from excluded controllers to restore saved card section
  
## Version 4.10.0
- Improvment :
  - [PRE-2551](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2496): Add order reference to payment resource

- Bugfix :
  - [SMP-2736](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2488): Fix remove order state when installing Payplug module
  - [PRE-2547](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2495): Remove conflictual required in ajax & improve Product method usage
  - [PRE-2535](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2497): Fix apple pay redirection on guest mode

## Version 4.9.33
- Bugfix :
  - [SMP-2787](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2489): Fix the customer login check for guest creation during Apple Pay checkout

## Version 4.9.32
- Bugfix :
  - [SMP-2787](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2487): Fix applepay button display for logged user and fix security vulnerabilities

## Version 4.9.31
- Feature :
  - [SMP-2545](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2486): Fix applepay bug with guest account

## Version 4.9.28
- Bugfix :
  - [SMP-2787](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2480): Hide apple pay button when order guest are disabled in configuration

## Version 4.9.27
- Feature :
  - [SMP-2673](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2479): Fix carrier taxes display on appelpay

## Version 4.9.26
- Feature :
  - [SMP-2686](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2476): Adapt appelpay pay button call to custom theme

## Version 4.9.25
- Feature :
  - [SMP-2675](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2475): Improve dependancies loader with controllers black list

## Version 4.9.24
- Bugfix :
  - [SMP-2634](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2477):  Fix double resource creation for one click and amex payment

## Version 4.9.23
- Bugfix :
  - [SMP-2710](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2468): Patch payment resource in order process update if order id is missing
  
## Version 4.9.21
- Bugfix :
  - [PRE-2539](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2472): fix php unit and coverage test

## Version 4.9.20
- Bugfix :
  - [PRE-2540](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2473): Fix unit tests

## Version 4.9.19
- Bugfix :
  - [PRE-2520](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2471): Disabled apple pay product from feature json

## Version 4.9.18
- Bugfix :
  - [PRE-2535](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2470): Fix order confirmation redirection when order exists

## Version 4.9.17
- Bugfix :
  - [SMP-2558](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2469): Allow resource creation when no invoice address selected

## Version 4.9.16
- Bugfix :
  - [SMP-2681](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2466): Fix missing applepay metadatas

## Version 4.9.15
- Bugfix :
  - [PRE-2498](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2467): Fix applepay with inactive country

## Version 4.9.14
- Bugfix :
  - [PRE-2525](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2464): Update carrier list when address is selected

## Version 4.9.13
- Bugfix :
  - [PRE-2495](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2463): Add phone number to guest address for ApplePay

## Version 4.9.12
- Bugfix :
  - [PRE-2522](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2461):  Fix lock usage in notification script to prevent overload peak

## Version 4.9.10
- Bugfix :
  - [PRE-2525](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2459): Fix price display on address change

## Version 4.9.8
- Bugfix :
  - [PRE-2497](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2457): Do not display ApplePay button on product page if carrier not allowed

## Version 4.9.6
- Bugfix :
  - [PRE-2520](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2455): Set feature flag on applepay product feature

## Version 4.9.5
- Bugfix :
  - [PRE-2491](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2454): Fix oney with or without fees configurations
  
## Version 4.9.4
- Bugfix :
  - [PRE-2500](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2453): Fix sandbox switch on only available in live mode features

## Version 4.9.3
- Bugfix :
  - [PRE-2498](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2452): Set language use from current context in applepay cart/product

## Version 4.9.2
- Bugfix :
  - [PRE-2492](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2450): Retrieve previous cart on applepay modal cancel

## Version 4.9.1
- Bugfix :
  - [PRE-2495](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2451): Fix applepay phone number

## Version 4.9.0
- Feature :
  - [PRE-2280](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2424): Upgrade apple pay configuration display and add apple pay product display
  - [PRE-2281](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2430): Display applepay button on product page
  - [PRE-2282](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2430): Display appelpay popup on product page
  - [PRE-2283](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2430): Pay with applepay on product page
  - [PRE-2441](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2288): Set corner radius to applepay button on shopping cart and product page

- Bugfix :
  - [PRE-2428](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2425): Fix lock gesture on validation script
  - [SMP-2070](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2426): Fix confirmation modal translation

- Improvment :
  - [PRE-2078](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2431): Remove PSPL in CI
  - [PRE-2289](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2440): Add blink on ApplePay button when clicking it

## Version 4.8.3
- Bugfix :
  - [SMP-2489](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2427): Rename hookHeader for Prestashop 1.7 and 8
  - [SMP-2531](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2432): Fix IPN charge peak
  - [SMP-2592](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2436): Fix regression on order creation when resource has failure
  - [SMP-2553](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2433): Fix carriers get to configure applepay checkout
  
## Version 4.8.2
- Bugfix :
  - [SMP-2232](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2408): Fix satispay abandoned order bug
  - [SMP-2464](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2415): Fix order object saving on order history update
  - [SMP-2418](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2416): Fix order gesture when no stock available

## Version 4.8.1
- Bugfix :
  - [PRE-2301](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2419): Fix applepay trigger on shopping-cart

## Version 4.8.0
- Feature :
  - [PRE-2159](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2370): Add applepay shopping-cart button on cart page
  - [PRE-2157](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2374): Add applepay shopping-cart configuration
  - [PRE-2160](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2379): Show applepay shopping-cart modal
  - [PRE-2161](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2389): Pay with ApplePay on cart

- Bugfix :
  - [SMP-2422](https://git.payplug.com/plugins/p-restashop_v2_1.7/-/merge_requests/2392): Fix integrated payment retry when failure occured
  - [SMP-2341](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2393): Fix ApplePay button translation
  

## Version 4.7.2
- Bugfix :
  - [SMP-2375](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2384): Align cart hash with payment tab generation to avoid multiple creation
  - [SMP-2375](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2387): Fix payment method default props to force the resource creation on checkout validation
  
## Version 4.7.1
- Bugfix :
  - [SMP-2328](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2367): Fix payment method in database
  - [PRE-2222](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2368): Fix order detail display

## Version 4.7.0
- Refactoring :
  - [PRE-1985](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2309): Add action Order
  - [PRE-2091](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2339): Add Refund Action
  - [PRE-1980](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2340): Add Oney Action
  - [PRE-2100](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2341): Add install method for configuration action

- Bugfix :
  - [SMP-2215](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2343): Fix checkout continue steps when IP form is shown
  - [SMP-2280](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2346): Fix call to the method to get valid iso country list

## Version 4.6.2
- Improvment :
  - [PRE-2142](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2332): Add missing IP trads es/de

## Version 4.6.1
- Bugfix :
  - [PRE-2136](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2324): Fix set default language

## Version 4.6.0
- Refactoring :
  - [PRE-1983](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2279): Add action Payment
  - [PRE-1983](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2279): Add action Payment::abort && Payment::capture

- Improvment :
  - [PRE-2014](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2302): Set default translation as english while still using translation key
  - [PRE-2018](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2310): Update php checker on php 8.2 image

- Bugfix :
  - [PRE-2037](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2305): Fix multiple order states creation
  - [SMP-2082](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2312): Fix 500 error in cards list page
  - [SMP-2088](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2316): Fix multishop bug
  
## Version 4.5.0

- Refactoring :
  - [PRE-1981](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2279): Add action Card
  - [PRE-1982](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2290): Add action Order State
  
- Improvment : 
  - [PRE-2054](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2289): Set the job build release to fail if the zip is not generated
  - [PRE-2061](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2295): Add BO UI improvements

- Bugfix :
   - [PRE-2057](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2296): Fix notification for deferred payment
   - [PRE-2069](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2299): Fix IP form when only choice in checkout
   - [PRE-2079](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2304): Fix prestashop validator security errors

## Version 4.4.0

- Improvment :
   - [PRE-720](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2232): Only show oney payment option according merchent allowed country
   - [PRE-1936](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2240): Use endpoint for MPDC
   - [PRE-1937](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2228): Mpdc on configuration save
   - [PRE-1938](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2227): Send telemetry on notification
   - [PRE-1951](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2245): Create rebase script
   - [PRE-2052](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2270): Add a feature flag on telemetries send
   
- Refactoring :
   - [PRE-1538](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2238): Set repositories in plugin init
   - [PRE-1539](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2255): Add model repository Payment
   - [PRE-1541](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2246): Add model repository OrderState
   - [PRE-1542](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2248): Add model repository PayplugOrderState
   - [PRE-1543](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2263): Add model repository Lock
   - [PRE-1546](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2241): Add model repository Order
   - [PRE-1547](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2262): Add model repository Logger
   - [PRE-2049](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2265): Add model repository Cache

- Bugfix :
   - [PRE-1956](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2237): Clean dev directory in build archive
   - [PRE-1986](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2233): Fix using deferred payment with integrated payment
   - [PRE-2016](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2242): Fix warnings on Oney elligibility

## Version 4.3.2

 - Improvement :
   - [PRE-2039](https://gitlab.com/dalenys/public/ecommerce/prestashop_v4_17_8/-/merge_requests/2256) : Uniformisation of order states between Validation and Notification.