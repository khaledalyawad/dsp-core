<?php
/**
 * fabric.database.config.php
 *
 * This file is part of the DreamFactory Document Service Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * The database configuration file for shared DSPs
 */
global $_dbName;
static $_privatePath;

const AUTH_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api/instance/credentials';

require_once dirname( __DIR__ ) . '/web/protected/components/HttpMethod.php';
require_once dirname( __DIR__ ) . '/web/protected/components/Curl.php';

$_host = isset( $_SERVER, $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : gethostname();

if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) )
{
	throw new \CHttpException( 401, 'You are not authorized to access this system (' . $_host . ').' );
}

if ( !empty( $_privatePath ) )
{
	//	Non-existent DSP, redirect
	if ( false === ( $_config = @require( $_privatePath . '/database.config.php' ) ) )
	{
		header( 'Location: http://cumulus.cloud.dreamfactory.com/future-dreamer.php' );
		die();
	}

	return $_config;
}

$_parts = explode( '.', $_host );
$_dbName = $_dspName = $_parts[0];

if ( empty( $_config ) || !is_array( $_config ) )
{
	//	Otherwise, get the credentials from the auth server...
	$_response = \Curl::get( AUTH_ENDPOINT . '/' . $_dspName . '/database' );

	if ( !$_response || !is_object( $_response ) || false == $_response->success )
	{
		throw new RuntimeException( 'Cannot connect to authentication service:' . print_r( $_response, true ) );
	}

	$_dbCredentialsCache = $_response->details;

	$_date = date( 'c' );
	$_dbUser = $_dbCredentialsCache->db_user;
	$_dbPassword = $_dbCredentialsCache->db_password;
	$_storageKey = $_dbCredentialsCache->storage_key;
	$_privatePath = '/data/storage/' . $_storageKey . '/.private';

	$_config = array(
		'connectionString' => 'mysql:host=localhost;port=3306;dbname=' . $_dbName,
		'username'         => $_dbUser,
		'password'         => $_dbPassword,
		'emulatePrepare'   => true,
		'charset'          => 'utf8',
	);

//	Also create a config file for next time...
	file_put_contents(
		$_privatePath . '/database.config.php',
		<<<PHP
	<?php
/** generated by DSP @ {$_date} */
return array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname={$_dbName}',
	'username'         => '{$_dbUser}',
	'password'         => '{$_dbPassword}',
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);
PHP
	);
}

//	Return the configuration
return $_config;