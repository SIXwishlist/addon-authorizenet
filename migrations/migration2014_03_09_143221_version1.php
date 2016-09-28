<?php
namespace Addon\Authorizenet\Migrations;

use \App\Libraries\BaseMigration;

class Migration2014_03_09_143221_version1 extends BaseMigration
{
    public function up($addon_id)
    {
        // Create the settings category
        $category = new \SettingCategory();
        $category->slug = 'authorizenet';
        $category->title = 'authorizenet_settings';
        $category->is_visible = '1';
        $category->sort = '99';
        $category->addon_id = $addon_id;
        $category->save();

        // Add the settings

        // CIM API Login ID
        $cim_login_id_settings = new \Setting();
        $cim_login_id_settings->slug = 'authorizenet_cim_login_id';
        $cim_login_id_settings->title = 'CIM Login ID';
        $cim_login_id_settings->field_type = 'text';
        $cim_login_id_settings->rules = '';
        $cim_login_id_settings->setting_category_id = $category->id;
        $cim_login_id_settings->editable = '1';
        $cim_login_id_settings->required = '1';
        $cim_login_id_settings->addon_id = $addon_id;
        $cim_login_id_settings->sort = '1';
        $cim_login_id_settings->save();

        // CIM API Transaction Key
        $cim_transaction_key_settings = new \Setting();
        $cim_transaction_key_settings->slug = 'authorizenet_cim_transaction_key';
        $cim_transaction_key_settings->title = 'CIM Transaction Key';
        $cim_transaction_key_settings->field_type = 'text';
        $cim_transaction_key_settings->rules = '';
        $cim_transaction_key_settings->setting_category_id = $category->id;
        $cim_transaction_key_settings->editable = '1';
        $cim_transaction_key_settings->required = '1';
        $cim_transaction_key_settings->addon_id = $addon_id;
        $cim_transaction_key_settings->sort = '2';
        $cim_transaction_key_settings->save();

        // AIM API Login ID
        $aim_login_id_settings = new \Setting();
        $aim_login_id_settings->slug = 'authorizenet_aim_login_id';
        $aim_login_id_settings->title = 'AIM Login ID';
        $aim_login_id_settings->field_type = 'text';
        $aim_login_id_settings->rules = '';
        $aim_login_id_settings->setting_category_id = $category->id;
        $aim_login_id_settings->editable = '1';
        $aim_login_id_settings->required = '1';
        $aim_login_id_settings->addon_id = $addon_id;
        $aim_login_id_settings->sort = '3';
        $aim_login_id_settings->save();

        // AIM API Transaction Key
        $aim_transaction_key_settings = new \Setting();
        $aim_transaction_key_settings->slug = 'authorizenet_aim_transaction_key';
        $aim_transaction_key_settings->title = 'AIM Transaction Key';
        $aim_transaction_key_settings->field_type = 'text';
        $aim_transaction_key_settings->rules = '';
        $aim_transaction_key_settings->setting_category_id = $category->id;
        $aim_transaction_key_settings->editable = '1';
        $aim_transaction_key_settings->required = '1';
        $aim_transaction_key_settings->addon_id = $addon_id;
        $aim_transaction_key_settings->sort = '4';
        $aim_transaction_key_settings->save();

        // Sandbox
        $sandbox_settings = new \Setting();
        $sandbox_settings->slug = 'authorizenet_enable_sandbox';
        $sandbox_settings->title = 'Enable Sandbox Mode';
        $sandbox_settings->field_type = 'checkbox';
        $sandbox_settings->rules = 'min:0|max:1';
        $sandbox_settings->setting_category_id = $category->id;
        $sandbox_settings->editable = '1';
        $sandbox_settings->value = '0';
        $sandbox_settings->addon_id = $addon_id;
        $sandbox_settings->sort = '5';
        $sandbox_settings->save();

        // Add the gateway record
        $gateway = new \Gateway();
        $gateway->slug = 'authorizenet';
        $gateway->name = 'Authorize.net';
        $gateway->addon_id = $addon_id;
        $gateway->is_merchant = '1';
        $gateway->process_cc = '1';
        $gateway->store_cc = '1';
        $gateway->process_ach = '1';
        $gateway->store_ach = '1';
        $gateway->is_active = '1';
        $gateway->sort = '1';
        $gateway->save();
    }

    public function down($addon_id)
    {
        // Remove all settings
        \Setting::where('addon_id', '=', $addon_id)->delete();

        // Remove all settings groups
        \SettingCategory::where('addon_id', '=', $addon_id)->delete();

        // Load the gateway as we'll need the ID
        $gateway = \Gateway::where('addon_id', '=', $addon_id)->first();

        if (isset($gateway->id) && $gateway->id > 0) {
            $gateway_id = $gateway->id;

            // Delete the gateway currency linkage
            \GatewayCurrency::where('gateway_id', '=', $gateway_id)->delete();

            // Remove credit cards handled by this addon
            \ClientCc::where('gateway_id', '=', $gateway_id)->delete();

            // Remove credit cards handled by this addon
            \ClientAch::where('gateway_id', '=', $gateway_id)->delete();

            // Delete the gateway
            $gateway->delete();
        }
    }
}
