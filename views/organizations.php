<?php

class OrganizationsPage
{
    public function __construct()
    {
        add_shortcode('organizations_component', [OrganizationsPage::class, 'render']);
    }

    public function get() {}

    public static function render()
    {
        if (!is_user_logged_in()) {
            return '<p class="font-bold">Você precisa estar logado para acessar esta página.</p>';
        }

        $user = wp_get_current_user();
        $organization = null;
        $members = null;

        $organization_id = 0;

        if ($user) {
            $orgRepo = new OrganizationRepository();

            $organization = $orgRepo->findByUserId($user->ID);

            $organization_id = $organization ? $organization->id : 0;
        }

        if ($organization_id > 0) {
            $userRepo = new UserRepository();
            $members = $userRepo->findAllByOrganizationId($organization_id);
        }

        $form_action_url = esc_url(admin_url('admin-post.php'));

        ob_start();
?>

        <!-- <script>
            window.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('organization_form');
                if (form) {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();

                        const orgNameInput = document.getElementById('organization_name');
                        const orgName = orgNameInput.value;

                        const data = new FormData();
                        data.append('action', 'create_organization');
                        data.append('name', orgName);

                        swal.fire({
                            title: 'Criando organização',
                            text: 'Aguarde enquanto criamos sua organização.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                method: 'POST',
                                body: data
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status == 200) {
                                    // alert('Organização criada com sucesso!');
                                    swal.fire({
                                        icon: 'success',
                                        title: 'Organização criada com sucesso',
                                        text: 'Aguarde enquanto salvamos suas configurações.',
                                    });
                                    location.reload();
                                } else if (result.status == 409) {
                                    // alert('Erro: ' + result.data.message);
                                    swal.fire({
                                        icon: 'error',
                                        title: 'Erro !',
                                        text: 'Houve um conflito na criação da organização.',
                                    });
                                } else {
                                    // alert('Erro: ' + result.data.message);
                                    swal.fire({
                                        icon: 'error',
                                        title: 'Erro !',
                                        text: 'Erro ao criar organização',
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Erro:', error);
                                // alert('Ocorreu um erro ao criar a organização.');
                                swal.fire({
                                        icon: 'error',
                                        title: 'Erro !',
                                        text: 'Erro ao criar organização',
                                });
                            });
                    });
                }

                const addMembersButton = document.getElementById('add_members_button');
                const addMembersForm = document.getElementById('add_members_form');
                const removeMemberForms = document.querySelectorAll('form.remove_member_form');

                if (addMembersButton) {

                    addMembersButton.addEventListener('click', function() {
                        addMembersForm.classList.toggle('hidden');
                    });

                    if (addMembersForm) {
                        addMembersForm.addEventListener('submit', function(event) {
                            event.preventDefault();

                            const form = addMembersForm;
                            const data = new FormData(addMembersForm);
                            data.append('action', 'add_member_to_organization');

                            const res = fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                    method: 'POST',
                                    body: data
                                }).then(response => response.json())
                                .then(result => {
                                    if (result.status == 200 || result.status == 201) {
                                        alert('Membro adicionado com sucesso!');
                                        location.reload();
                                    } else {
                                        alert('Erro: ' + result.data);
                                    }
                                })
                        });
                    }

                    if (removeMemberForms.length > 0) {
                        removeMemberForms.forEach(function(form) {
                            form.addEventListener('submit', function(event) {
                                event.preventDefault();

                                const data = new FormData(form);
                                data.append('action', 'remove_member_from_organization');

                                const res = fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                        method: 'POST',
                                        body: data
                                    }).then(response => response.json())
                                    .then(result => {
                                        if (result.status == 200 || result.status == 201) {
                                            alert('Membro removido com sucesso!');
                                            location.reload();
                                        } else {
                                            alert('Erro: ' + result.data);
                                        }
                                    })
                            });
                        });

                    }
                }
            });
        </script> -->
        <script>
            // Certifique-se de que a biblioteca SweetAlert2 (com alias Swal ou swal)
            // esteja carregada antes de executar este script.

            window.addEventListener('DOMContentLoaded', function() {
                // --- 1. Lógica de Criação da Organização ---
                const form = document.getElementById('organization_form');
                if (form) {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();

                        const orgNameInput = document.getElementById('organization_name');
                        const orgName = orgNameInput.value;

                        const data = new FormData();
                        data.append('action', 'create_organization');
                        data.append('name', orgName);

                        // 🚀 Alerta de Carregamento (Criação)
                        Swal.fire({
                            title: 'Criando organização...',
                            text: `Por favor, aguarde enquanto criamos a organização`,
                            icon: 'info', // 'info' é mais adequado para carregamento do que o padrão
                            allowOutsideClick: false,
                            showConfirmButton: false, // Não mostrar botão
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                method: 'POST',
                                body: data
                            })
                            .then(response => {
                                // O SweetAlert de carregamento precisa ser fechado antes de mostrar o resultado
                                Swal.close();
                                return response.json();
                            })
                            .then(result => {
                                if (result.success) {
                                    // 🎉 Sucesso
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Organização criada com sucesso!',
                                        text: `A organização foi criada. Redirecionando...`,
                                        timer: 2000,
                                        showConfirmButton: false,
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erro ao Criar Organização',
                                        text: result.data.message || 'Ocorreu um erro inesperado. Por favor, tente novamente.',
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Erro de Fetch:', error);
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro de Conexão',
                                    text: 'Não foi possível se comunicar com o servidor. Verifique sua conexão.',
                                });
                            });
                    });
                }

                // --- 2. Lógica de Adicionar Membros ---
                const addMembersButton = document.getElementById('add_members_button');
                const addMembersForm = document.getElementById('add_members_form');

                if (addMembersButton) {
                    addMembersButton.addEventListener('click', function() {

                        const userIsOperator = <?php echo $user->roles && in_array('charlie_operator', $user->roles) ? 'true' : 'false' ?>;

                        if (userIsOperator) {
                            swal.fire({
                                icon: 'warning',
                                title: 'Não autorizado',
                                text: 'Somente organizadores podem adicionar membros',
                                timer: 1500,
                                showConfirmButton: false,
                            });

                            return
                        }

                        addMembersForm.classList.toggle('hidden');
                    });

                    if (addMembersForm) {
                        addMembersForm.addEventListener('submit', function(event) {
                            event.preventDefault();

                            const form = addMembersForm;
                            const data = new FormData(addMembersForm);
                            data.append('action', 'add_member_to_organization');
                            const memberEmail = data.get('email'); // Captura o email para a mensagem

                            // 🚀 Alerta de Carregamento (Adicionar Membro)
                            Swal.fire({
                                title: 'Adicionando Membro...',
                                text: `Aguarde enquanto adicionamos ${memberEmail}.`,
                                icon: 'info',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                },
                            });

                            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                    method: 'POST',
                                    body: data
                                })
                                .then(response => {
                                    Swal.close();
                                    return response.json();
                                })
                                .then(result => {
                                    if (result.success) {
                                        // 🎉 Sucesso
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Membro Adicionado!',
                                            text: `Membro adicionado à organização. Redirecionando...`,
                                            timer: 1500,
                                            showConfirmButton: false,
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        // ❌ Erro
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Erro ao Adicionar Membro',
                                            text: result.data.message || 'Não foi possível adicionar o membro. Tente novamente.',
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Erro de Fetch:', error);
                                    Swal.close();
                                    // 💥 Erro de Rede/Conexão
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erro de Conexão',
                                        text: 'Não foi possível se comunicar com o servidor.',
                                    });
                                });
                        });
                    }

                    // --- 3. Lógica de Remover Membros ---
                    const removeMemberForms = document.querySelectorAll('form.remove_member_form');

                    if (removeMemberForms.length > 0) {
                        removeMemberForms.forEach(function(form) {
                            form.addEventListener('submit', function(event) {
                                event.preventDefault();

                                const data = new FormData(form);
                                data.append('action', 'remove_member_from_organization');
                                const userIdToRemove = data.get('user_id'); // Captura o ID para o diálogo

                                // ⚠️ Alerta de Confirmação antes da Ação
                                Swal.fire({
                                    title: 'Tem certeza?',
                                    text: "Você irá remover este membro da organização. Esta ação é reversível, mas pode exigir que o membro seja adicionado novamente.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    customClass: {
                                        confirmButton: 'swal-confirm-blue',
                                        cancelButton: 'swal-cancel-red'
                                    },
                                    confirmButtonText: 'Sim, remover!',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // 🚀 Alerta de Carregamento (Remover Membro)
                                        Swal.fire({
                                            title: 'Removendo Membro...',
                                            text: `Aguarde enquanto removemos o membro.`,
                                            icon: 'info',
                                            allowOutsideClick: false,
                                            showConfirmButton: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            },
                                        });

                                        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                                method: 'POST',
                                                body: data
                                            })
                                            .then(response => {
                                                Swal.close();
                                                return response.json();
                                            })
                                            .then(result => {
                                                if (result.success) {
                                                    // 🎉 Sucesso
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Membro Removido!',
                                                        text: 'O membro foi removido com sucesso. Redirecionando...',
                                                        timer: 1500,
                                                        showConfirmButton: false,
                                                    }).then(() => {
                                                        location.reload();
                                                    });
                                                } else {
                                                    // ❌ Erro
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Erro ao Remover Membro',
                                                        text: result.data.message || 'Não foi possível remover o membro. Tente novamente.',
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Erro de Fetch:', error);
                                                Swal.close();
                                                // 💥 Erro de Rede/Conexão
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Erro de Conexão',
                                                    text: 'Não foi possível se comunicar com o servidor.',
                                                });
                                            });
                                    }
                                }); // Fim do .then((result) =>
                            });
                        });
                    }
                }
            });
        </script>

        <?php if ($organization_id == 0) : ?>

            <div class="text-center border-4 border-dashed border-indigo-200 rounded-lg p-12 mt-10 bg-indigo-50">
                <svg class="mx-auto h-12 w-12 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>

                <h3 class="mt-4 text-xl font-semibold text-gray-900">
                    Você ainda não tem uma Organização
                </h3>

                <p class="mt-2 text-sm text-gray-600">
                    Crie sua organização agora para começar a configurar seus assistentes e adicionar membros da equipe.
                </p>

                <div class="mt-6 max-w-sm mx-auto">
                    <form method="POST" id="organization_form">
                        <input
                            type="text"
                            name="organization_name"
                            id="organization_name"
                            placeholder="Nome da sua Organização (Ex: Alpha Solutions)"
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

                        <button
                            type="submit"
                            class="mt-4 w-full flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Criar Organização e Começar
                        </button>
                    </form>
                </div>
            </div>

        <?php else : ?>
            <h2 class="text-2xl font-bold mb-10">Painel da Organização: <?php echo esc_html($organization->name); ?></h2>
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <div class="flex justify-between items-center p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Membros</h3>
                    <button id="add_members_button" class="text-sm font-medium text-gray-900 p-2.5 hover:bg-gray-900 group hover:text-gray-100 items-center">
                        Adicionar Membro
                        <svg class="group-hover:text-gray-100 inline-block ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <form class="container mx-auto hidden px-2.5 mb-10" id="add_members_form">
                    <div class="mb-5">
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                        <input type="email" id="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="name@flowbite.com" required />
                    </div>
                    <div class="mb-5">
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Senha</label>
                        <input type="password" id="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required />
                    </div>
                    <div class="mb-5 hidden">
                        <label for="organizationId" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ID da Organização</label>
                        <input type="hidden" id="organizationId" name="organization_id" value="<?php echo $organization_id; ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required />
                    </div>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Criar operador</button>
                </form>

                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Cargo
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <?php if ($members) : ?>
                        <?php foreach ($members as $member_db_row) :
                            $full_member = get_user_by('ID', $member_db_row->ID);

                            $display_name = $full_member ? $full_member->display_name : esc_html($member_db_row->user_login);
                            $roles = $full_member ? $full_member->roles : [];

                            $role_names = array_map(function ($role) {
                                if ($role === 'charlie_organizer') return 'Organizador';
                                if ($role === 'charlie_operator') return 'Operador';
                                if ($role === 'administrator') return 'Administrador (Global)';
                                return ucfirst(str_replace('_', ' ', $role));
                            }, $roles);

                            if (!$full_member) continue;
                        ?>
                            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    <?php echo esc_html($display_name); ?><br>
                                    <span class="text-gray-400 text-xs"><?php echo esc_html($full_member->user_email); ?></span>
                                </th>
                                <td class="px-6 py-4">
                                    <?php echo esc_html(implode(', ', $role_names)); ?>
                                </td>
                                <td>
                                    <form class="inline remove_member_form">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($full_member->ID); ?>">
                                        <button type="submit" class="font-medium text-red-600 p-2.5 dark:text-red-500 hover:underline">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tbody>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    Nenhum dado disponível para esta organização.
                                </td>
                            </tr>
                        </tbody>
                    <?php endif; ?>
                </table>
            </div>


        <?php endif; ?>

<?php
        return ob_get_clean();
    }
}

add_shortcode('organizations_component', [OrganizationsPage::class, 'render']);
?>