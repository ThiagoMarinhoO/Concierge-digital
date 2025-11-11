<?php
function charlie_register_custom_roles() {

    $organizer_caps = [
        'read'                  => true,
        'edit_charlie_settings' => true, 
        'manage_organization'   => true, // Permissão principal para gestão de membros/assistentes
        'create_assistants'     => true,
        'edit_assistants'       => true,
        'delete_assistants'     => true,
        // Capacidades para gerenciar usuários (membros da sua org)
        'list_users_in_org'     => true, 
        'add_users_to_org'      => true,
        'remove_users_from_org' => true,
    ];
    
    add_role( 'charlie_organizer', 'Organizador', $organizer_caps );


    $operator_caps = [
        'read'                  => true,
        'use_assistants'        => true, // Capacidade de usar o chatbot/assistente
    ];

    add_role( 'charlie_operator', 'Operador', $operator_caps );
    
    // --- 3. Papel: Administrador Global (Opcional) ---
    // Se você quer que o Admin do WP tenha todas as permissões Charlie
    $admin_role = get_role('administrator');
    if ($admin_role) {
        foreach ($organizer_caps as $cap => $grant) {
            if ($grant) {
                $admin_role->add_cap($cap);
            }
        }
    }
}
add_action('init', 'charlie_register_custom_roles');
