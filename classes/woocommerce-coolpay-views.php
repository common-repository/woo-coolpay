<?php

/**
 * Class WC_CoolPay_Views
 */
class WC_CoolPay_Views
{
    /**
     * Fetches and shows a view
     *
     * @param string $path
     * @param array $args
     */
    public static function get_view( $path, $args = array())
    {
        if (is_array($args) && ! empty($args)) {
            extract($args);
        }

        $file = WCCP_PATH . 'views/' . trim($path);

        if (file_exists($file)) {
            include $file;
        }
    }
}