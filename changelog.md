# Payplug module changelog

## Next version
- Refactoring :
  - [PRE-1983](https://git.payplug.com/plugins/prestashop_v2_1.7/-/merge_requests/2279): Add action Payment

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