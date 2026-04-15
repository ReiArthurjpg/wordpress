<?php

add_action('rest_api_init', function () {
    register_rest_field('usuarios', 'acf', [
        'get_callback' => function ($post) {
            if (function_exists('get_fields')) {
                return get_fields($post['id']);
            }
            return null;
        },
        'schema' => null,
    ]);
});