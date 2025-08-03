<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AS_Autoloader {

    /**
     * راه‌اندازی PSR-4 like اتولودر
     *
     * @param string $base_dir مسیر پایه‌ی دایرکتوری includes/
     * @param string $prefix   پیش‌وند کلاس‌ها (AS_)
     */
    public static function run( $base_dir, $prefix ) {
        spl_autoload_register( function( $class ) use ( $base_dir, $prefix ) {
            // اگر کلاس با پیش‌وند ما شروع نشده، نادیده می‌گیریم
            if ( strpos( $class, $prefix ) !== 0 ) {
                return;
            }

            // حذف پیش‌وند و تبدیل نام کلاس به مسیر فایل
            $relative_class = substr( $class, strlen( $prefix ) );
            $relative_path  = str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';
            $file           = trailingslashit( $base_dir ) . $relative_path;

            if ( file_exists( $file ) ) {
                include_once $file;
            }
        } );
    }
}

