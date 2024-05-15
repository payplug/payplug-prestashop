# Payplug module changelog

## Next version
- Feature :
  - [PRE-2280](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2424): Upgrade apple pay configuration display and add apple pay product display
  - [PRE-2281](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2430): Display applepay button on product page
  - [PRE-2282](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2430): Display appelpay popup on product page
  - [PRE-2283](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2430): Pay with applepay on product page
- Bugfix :
  - [PRE-2428](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2425): Fix lock gesture on validation script
  - [SMP-2070](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2426): Fix confirmation modal translation
- Improvment :
  - [PRE-2078](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2431): Remove PSPL in CI

## Version 4.8.3
- Bugfix :
  - [SMP-2489](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2427): Rename hookHeader for Prestashop 1.7 and 8
  - [SMP-2531](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2432): Fix IPN charge peak
  - [SMP-2592](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2436): Fix regression on order creation when resource has failure
  - [SMP-2553](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2433): Fix carriers get to configure applepay checkout
  
## Version 4.8.2
- Bugfix :
  - [SMP-2232](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2408): Fix satispay abandoned order bug
  - [SMP-2464](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2415): Fix order object saving on order history update
  - [SMP-2418](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2416): Fix order gesture when no stock available

## Version 4.8.1
- Bugfix :
  - [PRE-2301](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2419): Fix applepay trigger on shopping-cart

## Version 4.8.0
- Feature :
  - [PRE-2159](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2370): Add applepay shopping-cart button on cart page
  - [PRE-2157](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2374): Add applepay shopping-cart configuration
  - [PRE-2160](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2379): Show applepay shopping-cart modal
  - [PRE-2161](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2389): Pay with ApplePay on cart

- Bugfix :
  - [SMP-2422](https://git.payplug.com/plugins/p-restashop_v2_1.7/-/merge_requests/2392): Fix integrated payment retry when failure occured
  - [SMP-2341](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2393): Fix ApplePay button translation
  

## Version 4.7.2
- Bugfix :
  - [SMP-2375](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2384): Align cart hash with payment tab generation to avoid multiple creation
  - [SMP-2375](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2387): Fix payment method default props to force the resource creation on checkout validation
  
## Version 4.7.1
- Bugfix :
  - [SMP-2328](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2367): Fix payment method in database
  - [PRE-2222](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2368): Fix order detail display

## Version 4.7.0
- Refactoring :
  - [PRE-1985](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2309): Add action Order
  - [PRE-2091](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2339): Add Refund Action
  - [PRE-1980](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2340): Add Oney Action
  - [PRE-2100](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2337): Add install method for configuration action

- Bugfix :
  - [SMP-2215](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2343): Fix checkout continue steps when IP form is shown
  - [SMP-2280](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2346): Fix call to the method to get valid iso country list

## Version 4.6.2
- Improvment :
  - [PRE-2142](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2332): Add missing IP trads es/de

## Version 4.6.1
- Bugfix :
  - [PRE-2136](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2324): Fix set default language

## Version 4.6.0
- Refactoring :
  - [PRE-1983](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2279): Add action Payment
  - [PRE-1983](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2279): Add action Payment::abort && Payment::capture

- Improvment :
  - [PRE-2014](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2302): Set default translation as english while still using translation key
  - [PRE-2018](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2310): Update php checker on php 8.2 image

- Bugfix :
  - [PRE-2037](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2305): Fix multiple order states creation
  - [SMP-2082](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2312): Fix 500 error in cards list page
  - [SMP-2088](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2316): Fix multishop bug
  
## Version 4.5.0

- Refactoring :
  - [PRE-1981](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2279): Add action Card
  - [PRE-1982](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2290): Add action Order State
  
- Improvment : 
  - [PRE-2054](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2289): Set the job build release to fail if the zip is not generated
  - [PRE-2061](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2295): Add BO UI improvements

- Bugfix :
   - [PRE-2057](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2296): Fix notification for deferred payment
   - [PRE-2069](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2299): Fix IP form when only choice in checkout
   - [PRE-2079](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2304): Fix prestashop validator security errors

## Version 4.4.0

- Improvment :
   - [PRE-720](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2232): Only show oney payment option according merchent allowed country
   - [PRE-1936](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2240): Use endpoint for MPDC
   - [PRE-1937](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2228): Mpdc on configuration save
   - [PRE-1938](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2227): Send telemetry on notification
   - [PRE-1951](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2245): Create rebase script
   - [PRE-2052](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2270): Add a feature flag on telemetries send
   
- Refactoring :
   - [PRE-1538](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2238): Set repositories in plugin init
   - [PRE-1539](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2255): Add model repository Payment
   - [PRE-1541](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2246): Add model repository OrderState
   - [PRE-1542](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2248): Add model repository PayplugOrderState
   - [PRE-1543](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2263): Add model repository Lock
   - [PRE-1546](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2241): Add model repository Order
   - [PRE-1547](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2262): Add model repository Logger
   - [PRE-2049](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2265): Add model repository Cache

- Bugfix :
   - [PRE-1956](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2237): Clean dev directory in build archive
   - [PRE-1986](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2233): Fix using deferred payment with integrated payment
   - [PRE-2016](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2242): Fix warnings on Oney elligibility

## Version 4.3.2

 - Improvement :
   - [PRE-2039](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2256) : Uniformisation of order states between Validation and Notification.