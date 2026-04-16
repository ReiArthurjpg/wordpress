<?php

add_action('rest_api_init', function () {
    register_rest_field('usuarios', 'acf', [
        'get_callback' => function ($post) {
            if (function_exists('get_fields')) {
                return get_fields($post['id']);
            }
            return null;
        },
        'update_callback' => function ($value, $post, $field_name) {
            if (!function_exists('update_field') || !is_array($value)) {
                return false;
            }
            
            // Mapeamento seguro: Nomes do Front-End -> Field Keys originais do Banco
            $map = [
                'nome'               => 'field_69df7a7017d73',
                'email'              => 'field_69df7aac17d74',
                'senha'              => 'field_69df7abc17d75',
                'cpf'                => 'field_69df7ad117d76',
                'data_de_nascimento' => 'field_69df7ae117d77',
                'imagem'             => 'field_69df7af917d78',
            ];

            foreach ($value as $k => $v) {
                // Usa a chave oficial se estiver mapeada para garantir o vínculo no primeiro POST
                $field_key = isset($map[$k]) ? $map[$k] : $k;
                update_field($field_key, $v, $post->ID);
            }
            
            return true;
        },
        'schema' => null,
    ]);
});