<?php

namespace PayPlug\tests\mock;

class AddressMock
{
    public static function get()
    {
        $address = new \stdClass();
        $address->id_customer = 1;
        $address->id_country = 1;
        $address->country = 'France';
        $address->alias = 'Adresse';
        $address->company = 'Payplug';
        $address->lastname = 'Lorem';
        $address->firstname = 'Ipsum';
        $address->address1 = '1 rue de l\'avenue';
        $address->address2 = '';
        $address->postcode = '75000';
        $address->city = 'Paris';
        $address->other = '';
        $address->phone = '+33123456789';
        $address->phone_mobile = '+33623456789';
        $address->date_add = '2021-01-01 00:00:00';
        $address->date_upd = '2021-01-01 00:00:00';
        $address->id = 1;

        return $address;
    }

    public static function getDeliveryAddress()
    {
        $deliveryAddress = self::get();
        $deliveryAddress->alias = 'Adresse de livraison';
        $deliveryAddress->id = 2;

        return $deliveryAddress;
    }

    public static function getInvoiceAddress()
    {
        $invoiceAddress = self::get();
        $invoiceAddress->alias = 'Adresse de facturation';
        $invoiceAddress->id = 3;

        return $invoiceAddress;
    }
}
