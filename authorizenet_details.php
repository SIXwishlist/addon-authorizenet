<?php
namespace Addon\Authorizenet;

class AuthorizenetDetails extends \App\Libraries\AddonDetails
{
    /**
     * addon details
     */
    protected static $details = array(
        'name' => 'Authorize.net',
        'description' => 'Gateway Module for Authorize.net. <a href="http://reseller.authorize.net/application/?id=Turn24Limited" target="_blank">Apply for an Authorize.net account</a>.',
        'author' => array(
            'name' => 'WHSuite Dev Team',
            'email' => 'info@whsuite.com'
        ),
        'website' => 'http://www.whsuite.com',
        'version' => '1.1.0',
        'license' => 'http://whsuite.com/license/ The WHSuite License Agreement',
        'type' => 'gateway',
        'gateway_type' => 'merchant'
    );
}
