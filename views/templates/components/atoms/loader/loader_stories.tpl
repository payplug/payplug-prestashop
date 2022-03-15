
{*
* 2022 PayPlug
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0).
* It is available through the world-wide-web at this URL:
* https://opensource.org/licenses/osl-3.0.php
* If you are unable to obtain it through the world-wide-web, please send an email
* to contact@payplug.com so we can send you a copy immediately.
*
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
*
*  @author PayPlug SAS
*  @copyright 2022 PayPlug SAS
*  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*}

<h2>Loader component</h2>

<section style="position: relative;height: 80px;">
    <h3>Loader de taille 30</h3>
    {include file='./loader.tpl' loaderSize='30'}
</section>

<section style="position: relative;height: 120px;">
    <h3>Loader par défaut</h3>
    {include file='./loader.tpl'}
</section>

<section style="position: relative;height: 140px;">
    <h3>Loader de taille 70</h3>
    {include file='./loader.tpl' loaderSize='70'}
</section>

<section>
    props :
    <ul>
        <li>size</li>
    </ul>
</section>
