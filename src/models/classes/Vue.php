<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\models\classes;

class Vue
{
    public function init()
    {
        return [
            "success" => true,
            "data" => [
                "payplug_wooc_settings" => $this->payplug_section_wooc_settings(),
			    "settings" => $this->payplug_section_settings(),
			    "header" => $this->payplug_section_header(),
			    "login" => $this->payplug_section_login(),
                "payment_methods"  => $this->payplug_section_payment_methods(),
                "payment_paylater"  => $this->payplug_section_paylater(),
                "status" => $this->payplug_section_status(),
			    "help" => $this->payplug_section_help()
            ]
		];
    }

    public function payplug_section_wooc_settings()
    {
        return [
            "rest_route" => "/payplug_api/login",
            "action" => "payplug_login",
            "payplug_email" => "testplugin+premium@payplug.com",
            "payplug_password" => "testplugin@21",
            "enabled" => "yes",
            "title" => "Pay by credit card",
            "description" => "sedfghj",
            "email" => "testplugin+premium@payplug.com",
            "payplug_test_key" => "sk_test_5viLdhhYB58UuSH0C49p0g",
            "payplug_merchant_id" => "433983",
            "mode" => "yes",
            "payment_method" => "popup",
            "debug" => "no",
            "oneclick" => "no",
            "bancontact" => "no",
            "apple_pay" => "no",
            "american_express" => "yes",
            "oney" => "no",
            "oney_type" => "with_fees",
            "oney_thresholds" => "",
            "oney_thresholds_min" => 100,
            "oney_thresholds_max" => 3000,
            "oney_product_animation" => "no",
            "payplug_merchant_country" => "FR"
        ];
    }

    public function payplug_section_settings()
    {
        return [
			"email" => "blablabla@blabalabl.com",
            "WP" => [
                "ajax_url" => "http://localhost:9000/",
                "nonce" => "xxxxxxxxx",
                "login_action" => "payplug_login",
                "logout_action" => "payplug_logout",
                "check_permission_action" => "payplug_check_permission",
                "check_requirements_action" => "payplug_check_requirements",
                "save_action" => "payplug_save",
                "_wpnonce" => "0b131d94c4"
            ],
            //"logged" => true,
            "mode" => 0,
		];
    }

    public function payplug_section_header()
    {
        $module_version = '3.12.0';
        $disabled = false;
        $enable = true;
        return [
			"title"        => 'payplug title',
			"descriptions" => [
				"live"    => [
					"description"    => 'LIVE description',
					"plugin_version" => $module_version
				],
				"sandbox" => [
					"description"    => 'TEST description',
					"plugin_version" => $module_version
				],
			],
			"options"      => [
				"type"    => "select",
				"name"    => "payplug_enable",
				"disabled" => $disabled,
				"options" => [
					[
						"value"   => 1,
						"label"   => 'label 1',
						"checked" => $enable === true ? true : false
					],
					[
						"value" => 0,
						"label" => 'label 2',
						"checked" => $enable === false ? true : false
					]
				]
			]
		];
    }

    public function payplug_section_login()
    {
        return [
			"name"         => "generalLogin",
			"title"        => 'Login title',
			"descriptions" => [
				"live"    => [
					"description"          => 'Live description',
					"not_registered"       => 'Live not registered',
					"connect"              => 'Live connect',
					"email_label"          => 'Live email label',
					"email_placeholder"    => 'Live email placeholder',
					"password_label"       => 'Live password label',
					"password_placeholder" => 'Live password placeholder',
					"link_forgot_password" => [
						"text"   => 'Live link forgot  password text',
						"url"    => "https://www.payplug.com/portal/forgot_password",
						"target" => "_blank"
					],
				],
				"sandbox" => [
					"description"          => 'Test description',
					"not_registered"       => 'Test not registered',
					"connect"              => 'Test connect',
					"email_label"          => 'Test email label',
					"email_placeholder"    => 'Test email placeholder',
					"password_label"       => 'Test password label',
					"password_placeholder" => 'Test password placeholder',
					"link_forgot_password" => [
						"text"   => 'Test link forgot  password text',
						"url"    => "https://www.payplug.com/portal/forgot_password",
						"target" => "_blank"
					],
				]
			]
		];
    }

    public function payplug_section_logged()
    {
		return [
			"title"        => 'Logged title',
			"descriptions" => [
				"live"    => [
					"description"        => 'Live description',
					"logout"             => 'Live logout',
					"mode"               => 'Live mode',
					"mode_description"   => 'Live mode description',
					"link_learn_more"    => [
						"text"   => 'Live learn more text',
						"url"    => 'Live learn more url',
						"target" => "_blank"
					],
					"link_access_portal" => [
						"text"   => 'Live access portal text',
						"url"    => "https://www.payplug.com/portal",
						"target" => "_blank"
					],
				],
				"sandbox" => [
					"description"        => 'Test description',
					"logout"             => 'Test logout',
					"mode"               => 'Test mode',
					"mode_description"   => 'Test mode description',
					"link_learn_more"    => [
						"text"   => 'Test learn more text',
						"url"    => 'Test learn more url',
						"target" => "_blank"
					],
					"link_access_portal" => [
						"text"   => 'Test access portal text',
						"url"    => "https://www.payplug.com/portal",
						"target" => "_blank"
					],
				]
			],
			"options"      => [
				[
					"name"     => "payplug_sandbox",
					"label"    => "Live",
					"value"    => 0, //live
					//"checked" => PayplugWoocommerceHelper::check_mode()
					"checked" => true
				],
				[
					"name"    => "payplug_sandbox",
					"label"   => "Test",
					"value"   => 1, //test
					//"checked" => !PayplugWoocommerceHelper::check_mode()
					"checked" => false
				],
			],
			"inactive_modal"		   => [
				//"inactive" => $inactive,
				"inactive" => false,
				"title" => 'Inactive modal title',
				"description" => 'Inactive modal description',
				"password_label" => 'Inactive modal password label',
				"cancel" => 'Inactive modal cancel',
				"ok" => 'Inactive modal ok',
			],
			"inactive_account" => [
				"warning" => [
					"title" => 'Inactive account title',
					"description" => 'Inactive account warning description1' .
						'Inactive account warning description2' .
						'Inactive account warning description3',
				],
				"error" => [
					"title" => 'Inactive account error title',
					"description" => 'Inactive account error description',
				]
			],
		];
    }

    public function payplug_section_payment_methods($options = array()) {

		return [
			"name"         => "paymentMethodsBlock",
			"title"        => 'payment methods title',
			"descriptions" => [
				"live"    => [
					"description" => 'Live description',
				],
				"sandbox" => [
					"description" => 'Test description',
				]
			],
			"options"      => [
				/*(new PaymentMethods())->payment_method_standard(),
				PaymentMethods::payment_method_amex(!empty($options) && $options['american_express'] === 'yes'),
				PaymentMethods::payment_method_applepay(!empty($options) && $options['apple_pay'] === 'yes'),
				PaymentMethods::payment_method_bancontact(!empty($options) &&$options['bancontact'] === 'yes')*/
                array()
			]
		];
	}

    public function payplug_section_paylater($options = array() ) {

		$max = !empty($options['oney_thresholds_max']) ? $options['oney_thresholds_max'] : 3000;
		$min = !empty($options['oney_thresholds_min']) ? $options['oney_thresholds_min'] : 100;
		$product_page = !empty($options['oney_product_animation']) && $options['oney_product_animation'] === 'yes' ? true : false;

		return [
			"name"         => "paymentMethodsBlock",
			"title"        => 'paylater title',
			"descriptions" => [
				"live"    => [
					"description" => 'Live description',
				],
				"sandbox" => [
					"description" => 'Test description',
				]
			],
			"options" => [
				"name" => "oney",
				"title" => 'paylater option title',
				//"image" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/lg-oney.png' ),
				"image" => 'assets/images/lg-oney.png',
				"checked" => !empty($options) && $options['oney'] === 'yes',
				"descriptions" => [
					"live"    => [
						"description"      => 'paylater description live description',
						"link_know_more" => "https://support.payplug.com/hc/fr/articles/4408142346002",
					],
					"sandbox" => [
						"description"      => 'paylater description test description',
						"link_know_more" => "https://support.payplug.com/hc/fr/articles/4408142346002",
					],
					"advanced" => [
						"description" => 'paylater description advanced description'
					]
				],
				"options" => [
					[
						"name" => "payplug_oney_type",
						"className" => "_paylaterLabel",
						"label" => 'paylater option 1 label',
						"subText" => 'paylater option 1 subText',
						"value" => "with_fees",
						"checked" => !empty($options) && $options['oney_type'] === 'with_fees',
					],
					[
						"name" => "payplug_oney_type",
						"className" => "_paylaterLabel",
						"label" => 'paylater option 2 label',
						"subText" => 'paylater option 2 label',
						"value" => "without_fees",
						"checked" => !empty($options) && $options['oney_type'] === 'without_fees',
					]
				],
				"advanced_options" => [
					$this->thresholds_option($max, $min),
					$this->show_oney_popup_product($product_page)
				]
			]
		];
	}

    public function thresholds_option($max, $min) {

		return [
			"name" => "thresholds",
			//"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/thresholds.jpg' ),
			"image_url" => 'assets/images/thresholds.jpg',
			"title" => 'thresholds option title',
			"descriptions" => [
				"description" => 'thresholds option desription',
				"min_amount" => [
					"name" => "oney_min_amounts",
					"value" => $min,
					"placeholder" => $min,
				],
				"inter" => 'thresholds option and',
				"max_amount" => [
					"name" => "oney_max_amounts",
					"value" => $max,
					"placeholder" => $max,
				],
				"error" => [
					"text" => 'thresholds option error text'
				]
			],
			"switch" => false
		];
	}

    public function show_oney_popup_product($active = false) {
		return [
			"name" => "oney_product_animation",
			//"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/product.jpg' ),
			"image_url" => 'assets/images/product.jpg',
			"title" => 'show oney popup product title',
			"descriptions" => [[
				"description" => 'show oney popup product description',
				"link_know_more" => "https://support.payplug.com/hc/fr/articles/4408142346002"
				]],
			"switch" => true,
			"checked" => $active
		];
	}

    public function payplug_section_status( $options = [] ) {
		//$payplug_requirements = new PayplugGatewayRequirements(new PayplugGateway());
		$checked = !empty($options['debug']) && $options['debug'] === 'yes' ? true : false;

		return [
			//"error" => !$this->payplug_requirements(),
			"error" => false,
			"title" => 'status title',
			"descriptions" => [
				"live" => [
					"description" => 'Live description',
					"errorMessage" => 'Live error message',
					"check" => 'Live check',
					"check_success" => 'Live check success',
				],
				"sandbox" => [
					"description" => 'Test description',
					"errorMessage" => 'Test error message',
					"check" => 'Test check',
					"check_success" => 'Test check success',
				]
			],
			"requirements" => [
				/*$payplug_requirements->curl_requirement(),
				$payplug_requirements->php_requirement(),
				$payplug_requirements->openssl_requirement(),
				$payplug_requirements->currency_requirement(), //MISSING THIS MESSAGES
				$payplug_requirements->account_requirement(),*/
                array()
			],
			"debug" => [
				"live" => [
					"title" => 'Live debug title',
					"description" => 'Live debug description',
				],
				"sandbox" => [
					"title" => 'Test debug title',
					"description" => 'Test debug description',
				]
			],
			"enable_debug_check" => $checked
		];
	}

    private function payplug_requirements() {
		$payplug_requirements = new PayplugGatewayRequirements(new PayplugGateway());
		return $payplug_requirements->satisfy_requirements();
	}

    public function payplug_section_help( ) {

		return [
			"description1" => 'help description 1',
			"description2" => 'help description 2',
			/*"link_help" => Component::link(
				'link help text',
				'link help url',
				"_blank"
			),*/
			"link_help" => 'link help text'
		];
	}
}