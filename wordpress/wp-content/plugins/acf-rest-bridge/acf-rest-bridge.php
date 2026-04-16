<?php
/**
 * Plugin Name: ACF REST Bridge
 * Description: Permite salvar campos ACF via WordPress REST API usando after_insert_post.
 * Version: 1.0
 * Author: Dashboard
 */

if (!defined('ABSPATH')) exit;

/**
 * Intercepta qualquer POST/PUT na REST API e salva os campos ACF
 * DEPOIS que o post é criado, usando o hook rest_after_insert_{post_type}
 */
add_action('rest_after_insert_usuarios', function (WP_Post $post, WP_REST_Request $request) {
    $acf_data = $request->get_param('acf');
    
    if (empty($acf_data) || !is_array($acf_data)) {
        return;
    }

    if (!function_exists('update_field')) {
        return;
    }

    // Mapeamento: nome amigável -> field_key do banco de dados
    $map = [
        'nome'               => 'field_69df7a7017d73',
        'email'              => 'field_69df7aac17d74',
        'senha'              => 'field_69df7abc17d75',
        'cpf'                => 'field_69df7ad117d76',
        'data_de_nascimento' => 'field_69df7ae117d77',
        'imagem'             => 'field_69df7af917d78',
    ];

    foreach ($acf_data as $key => $value) {
        $field_key = isset($map[$key]) ? $map[$key] : $key;
        update_field($field_key, $value, $post->ID);
    }
}, 10, 2);
