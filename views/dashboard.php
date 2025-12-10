<?php

class Dashboard
{
    public static function render()
    {
        if (!is_user_logged_in()) {
            return '<p class="font-bold">Você precisa estar logado para acessar esta página.</p>';
        }

        $assistant = new Chatbot();
        $orgRepo = new OrganizationRepository();

        $user_id = get_current_user_id();
        $currentUser = wp_get_current_user();
        $organization_id = 0;
        $resource_user_id = 0;
        $assistantId = 0;
        $instance = null;

        if (!empty($user_id)) {
            // Usa o repositório para buscar a organização à qual o usuário está vinculado.
            // Espera-se que \$orgData inclua 'id' e 'owner_user_id'
            $orgData = $orgRepo->findByUserId($user_id);
            $organization_id = $orgData ? (int) $orgData->id : 0;

            // Define o ID principal para busca: Owner_ID da Organização ou ID do Usuário Simples
            $resource_user_id = $organization_id > 0 && isset($orgData->owner_user_id)
                ? (int) $orgData->owner_user_id
                : $user_id;
        }

        if (!empty($resource_user_id)) {
            $assistantId = $assistant->getChatbotIdByUser($resource_user_id);
            $instance = WhatsappInstance::findByUserId($resource_user_id);
        }

        if(!get_active_subscription_product_id($resource_user_id)){
            return '<p class="font-bold text-center">Recurso bloqueado. Para desbloquear:<br><a href="' . get_home_url() . '/loja" class="underline text-lime-400 hover:text-lime-300">Obtenha um plano agora</a>.</p>';
        }
        
        $benefits = get_user_subscription_benefits($resource_user_id);

        $is_Chat = $benefits['dashboard_completo'];

        if(!$is_Chat){
            return '<p class="font-bold text-center">Recurso bloqueado. Para desbloquear:<br><a href="' . get_home_url() . '/loja" class="underline text-lime-400 hover:text-lime-300">Faça upgrade agora</a>.</p>';
        }

        /**
         * Métricas de chats
         */
        $allChats = MessageRepository::findAllChats(
            $assistantId,
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null
        );

        $allWebChats = MessageRepository::findAllWebChats(
            $assistantId,
            // date('Y-m-d', strtotime('-30 days')),
        );

        $allWhatsappChats = MessageRepository::findAllWhatsappChats(
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null,
            // date('Y-m-d', strtotime('-30 days'))
        );

        /**
         * Métricas de mensagens
         */
        $allMessages = MessageRepository::findAllMessages(
            $assistantId,
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null
        );

        $allWebMessages = MessageRepository::findAllWebMessages(
            $assistantId,
            // date('Y-m-d', strtotime('-30 days')),
        );

        $allWhatsappMessages = MessageRepository::findAllWhatsappMessages(
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null,
            // date('Y-m-d', strtotime('-30 days'))
        );

        /**
         * Métricas de usuários
         */

        $allWhatsappUsers = MessageRepository::findAllWhatsappUsers(
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null,
        );

        /**
         * Métricas de Reuniões
         */

        $allMeetings = Meet::all(
            $assistantId,
        );

        $messagesTotal = count($allMessages);
        $webPercent = $messagesTotal > 0 ? round(count($allWebMessages) / $messagesTotal * 100, 2) : 0;
        $whatsPercent = $messagesTotal > 0 ? round(count($allWhatsappMessages) / $messagesTotal * 100, 2) : 0;

        $chatsTotal = count($allChats);
        $averageMessagesPerChat = $chatsTotal > 0 ? round($messagesTotal / $chatsTotal, 2) : 0;

        // Humans Sessions
        $allHumansSessions = (new HumanSessionRepository())->findAll(
            $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null
        );

        ob_start();
?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const getChartOptions = () => {
                    return {
                        series: [<?= $webPercent ?>, <?= $whatsPercent ?>],
                        colors: ["#c1ff72", "#3B1798", ],
                        chart: {
                            height: 420,
                            width: "100%",
                            type: "pie",
                        },
                        stroke: {
                            colors: ["white"],
                            lineCap: "",
                        },
                        plotOptions: {
                            pie: {
                                labels: {
                                    show: true,
                                },
                                size: "100%",
                                dataLabels: {
                                    offset: -25
                                }
                            },
                        },
                        labels: ["Web", "Whatsapp"],
                        dataLabels: {
                            enabled: true,
                            style: {
                                fontFamily: "Inter, sans-serif",
                            },
                        },
                        legend: {
                            position: "bottom",
                            fontFamily: "Inter, sans-serif",
                        },
                        yaxis: {
                            labels: {
                                formatter: function(value) {
                                    return value + "%"
                                },
                            },
                        },
                        xaxis: {
                            labels: {
                                formatter: function(value) {
                                    return value + "%"
                                },
                            },
                            axisTicks: {
                                show: false,
                            },
                            axisBorder: {
                                show: false,
                            },
                        },
                    }
                }

                if (document.getElementById("pie-chart") && typeof ApexCharts !== 'undefined') {
                    const chart = new ApexCharts(document.getElementById("pie-chart"), getChartOptions());
                    chart.render();
                }
            });

            document.addEventListener("DOMContentLoaded", () => {
                const assistantId = document.getElementById("dashboard-container").dataset.assistantid;
                const instanceName = document.getElementById("dashboard-container").dataset.instancename;

                const chartEl = document.getElementById("column-chart");
                let myChart;

                const skeleton = `
                    <div role="status" class="w-full h-96 p-4 border border-gray-300 rounded-sm shadow-sm animate-pulse md:p-6">
                        <div class="h-2.5 bg-gray-300 rounded-full w-32 mb-2.5"></div>
                        <div class="w-48 h-2 mb-10 bg-gray-300 rounded-full"></div>
                        <div class="flex items-baseline mt-4">
                            <div class="w-full bg-gray-300 rounded-t-lg h-72"></div>
                            <div class="w-full h-56 ms-6 bg-gray-300 rounded-t-lg"></div>
                            <div class="w-full bg-gray-300 rounded-t-lg h-72 ms-6"></div>
                            <div class="w-full h-64 ms-6 bg-gray-300 rounded-t-lg"></div>
                            <div class="w-full bg-gray-300 rounded-t-lg h-80 ms-6"></div>
                            <div class="w-full bg-gray-300 rounded-t-lg h-72 ms-6"></div>
                            <div class="w-full bg-gray-300 rounded-t-lg h-80 ms-6"></div>
                        </div>
                        <span class="sr-only">Loading...</span>
                    </div>
                `;

                const dropdownButton = document.querySelector('#dropdownDefaultButton');
                const dropdownItems = document.querySelectorAll('#lastDaysdropdown a');


                function renderChart(chartData) {
                    chartEl.innerHTML = "";

                    const options = {
                        colors: ['#3B1798', '#c1ff72', ],
                        series: chartData.series,
                        chart: {
                            type: "bar",
                            height: "320px",
                            fontFamily: "Inter, sans-serif",
                            toolbar: {
                                show: false
                            },
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: "70%",
                                borderRadiusApplication: "end",
                                borderRadius: 8,
                            },
                        },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            style: {
                                fontFamily: "Inter, sans-serif"
                            },
                        },
                        states: {
                            hover: {
                                filter: {
                                    type: "darken",
                                    value: 1
                                },
                            },
                        },
                        stroke: {
                            show: true,
                            width: 0,
                            colors: ["transparent"],
                        },
                        grid: {
                            show: false,
                            strokeDashArray: 4,
                            padding: {
                                left: 2,
                                right: 2,
                                top: -14
                            },
                        },
                        dataLabels: {
                            enabled: false
                        },
                        legend: {
                            show: false
                        },
                        xaxis: {
                            categories: chartData.categories, // Use as categorias dinâmicas aqui
                            floating: false,
                            labels: {
                                show: true,
                                style: {
                                    fontFamily: "Inter, sans-serif",
                                    cssClass: 'text-xs font-normal fill-gray-500'
                                }
                            },
                            axisBorder: {
                                show: false
                            },
                            axisTicks: {
                                show: false
                            },
                        },
                        yaxis: {
                            show: false
                        },
                        fill: {
                            opacity: 1,
                        },
                    };

                    if (document.getElementById("column-chart") && typeof ApexCharts !== 'undefined') {
                        const chart = new ApexCharts(document.getElementById("column-chart"), options);
                        chart.render();
                        return chart; // Retorna a instância do gráfico
                    }
                }

                async function fetchChartData(period, assistantId, instanceName) {
                    chartEl.innerHTML = skeleton;
                    dropdownButton.innerHTML = `Últimos ${period === '7days' ? '7 dias' : period === '30days' ? '30 dias' : '12 meses'} <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" /></svg>`;

                    const body = new URLSearchParams({
                        action: 'weekly_messages',
                        period: period,
                        assistant_id: assistantId,
                        instance_name: instanceName || ''
                    });

                    const response = await fetch(`/wp-admin/admin-ajax.php`, {
                        method: 'POST',
                        body: body
                    });

                    return await response.json();
                }

                fetchChartData('7days', assistantId, instanceName).then(data => {
                    myChart = renderChart(data);
                });

                dropdownButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    const dropdown = document.getElementById('lastDaysdropdown');
                    dropdown.classList.toggle('hidden');
                });

                dropdownItems.forEach(item => {
                    item.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const period = e.target.dataset.period;
                        const assistantId = document.getElementById("dashboard-container").dataset.assistantid;
                        const instanceName = document.getElementById("dashboard-container").dataset.instancename;
                        const data = await fetchChartData(period, assistantId, instanceName);
                        myChart.updateSeries(data.series);
                        myChart.updateOptions({
                            xaxis: {
                                categories: data.categories
                            }
                        });
                    });
                });
            });
            
            document.addEventListener("DOMContentLoaded", function() {
                const getChartOptions = () => {
                    return {
                        // series: [35.1, 64.9],
                        series: [<?= count($allWhatsappChats) ?>, <?= count($allHumansSessions) ?>],
                        colors: ["#c1ff72", "#3B1798", ],
                        chart: {
                            height: 320,
                            width: "100%",
                            type: "donut",
                        },
                        stroke: {
                            colors: ["transparent"],
                            lineCap: "",
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            fontFamily: "Inter, sans-serif",
                                            offsetY: 20,
                                        },
                                        total: {
                                            showAlways: true,
                                            show: true,
                                            label: "Intervenções humanas",
                                            fontFamily: "Inter, sans-serif",
                                            // formatter: function(w) {
                                            //     const sum = w.globals.seriesTotals.reduce((a, b) => {
                                            //         return a + b
                                            //     }, 0)
                                            //     return '$' + sum + 'k'
                                            // },
                                            formatter: function() {
                                                return <?= count($allHumansSessions) ?>
                                            },

                                        },
                                        value: {
                                            show: true,
                                            fontFamily: "Inter, sans-serif",
                                            offsetY: -20,
                                            formatter: function(value) {
                                                return value + "k"
                                            },
                                        },
                                    },
                                    size: "80%",
                                },
                            },
                        },
                        grid: {
                            padding: {
                                top: -2,
                            },
                        },
                        labels: ["Chats", "Intervenções"],
                        dataLabels: {
                            enabled: false,
                        },
                        legend: {
                            position: "bottom",
                            fontFamily: "Inter, sans-serif",
                        },
                        yaxis: {
                            labels: {
                                formatter: function(value) {
                                    return value
                                },
                            },
                        },
                        xaxis: {
                            labels: {
                                formatter: function(value) {
                                    return value
                                },
                            },
                            axisTicks: {
                                show: false,
                            },
                            axisBorder: {
                                show: false,
                            },
                        },
                    }
                }

                if (document.getElementById("donut-chart") && typeof ApexCharts !== 'undefined') {
                    const chart = new ApexCharts(document.getElementById("donut-chart"), getChartOptions());
                    chart.render();
                }
            });
        </script>

        <div id="dashboard-container" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16" data-assistantId="<?= $assistantId ?>" data-instanceName="<?= $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null ?>">
            <div class="block max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-100">
                <h1 class="mb-2 text-xl font-bold tracking-tight text-gray-900">Olá <?php echo esc_html(wp_get_current_user()->display_name); ?></h1>
                <p class="font-normal text-gray-700">Parabéns! Até aqui, você alcançou esses resultados!</p>
                <p class="text-xs font-light text-gray-700">Acompanhe sempre as suas métricas.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
                <span class="text-sm text-gray-500">Total de Interações</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm"><?= count($allMessages); ?></h4>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
                <span class="text-sm text-gray-500">Usuários Atendidos</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm"><?= count($allWebChats) + count($allWhatsappUsers); ?></h4>
            </div>


            <div class="col-span-2 w-full bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="pb-4 mb-4 border-b border-gray-200">
                    <div class="!flex !items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 !flex !items-center !justify-center me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                </svg>
                            </div>
                            <div>
                                <h5 class="leading-none text-2xl font-bold text-gray-900 pb-1 !mb-0">Mensagens</h5>
                                <p class="!mb-0 text-sm font-normal text-gray-500">Mensagens por período</p>
                            </div>
                        </div>
                        <div class="relative">
                            <span
                                id="dropdownDefaultButton"
                                data-dropdown-toggle="lastDaysdropdown"
                                data-dropdown-placement="bottom"
                                class="text-sm font-medium text-gray-500 hover:text-gray-900 text-center inline-flex !items-center cursor-pointer"
                                type="button">
                                Últimos 7 dias
                                <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </span>
                            <div id="lastDaysdropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 absolute top-full transition-all duration-200">
                                <ul class="py-2 text-sm text-gray-700 !list-none" aria-labelledby="dropdownDefaultButton">
                                    <li>
                                        <a data-period="7days" href="#" class="block px-4 py-2 hover:bg-gray-100">Últimos 7 dias</a>
                                    </li>
                                    <li>
                                        <a data-period="30days" href="#" class="block px-4 py-2 hover:bg-gray-100">Últimos 30 dias</a>
                                    </li>
                                    <li>
                                        <a data-period="12months" href="#" class="block px-4 py-2 hover:bg-gray-100">Último ano</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="column-chart" data-assistantId="<?= $assistantId ?>" class="min-h-96"></div>
            </div>

            <div class="w-full bg-white rounded-lg shadow-sm p-4 md:p-6">

                <div class="">
                    <div class="flex-col items-center">
                        <div class="flex items-center mb-1">
                            <h5 class="text-xl font-bold leading-none text-gray-90 me-1">Canais</h5>
                        </div>
                        <div>
                            <div class="grid grid-cols-2 gap-3 mb-2">
                                <dl class="bg-blue-50 rounded-lg !flex !flex-col !items-center justify-center h-[78px]">
                                    <dt class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 text-sm font-medium !flex !items-center justify-center mb-1"><?= count($allWebMessages); ?></dt>
                                    <dd class="text-blue-600 text-sm font-medium">Web</dd>
                                </dl>
                                <dl class="bg-teal-50 rounded-lg !flex flex-col !items-center justify-center h-[78px]">
                                    <dt class="w-8 h-8 rounded-full bg-teal-100 text-teal-600 text-sm font-medium !flex !items-center justify-center mb-1"><?= count($allWhatsappMessages); ?></dt>
                                    <dd class="text-teal-600 text-sm font-medium">Whatsapp</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="py-6" id="pie-chart"></div>

            </div>


            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
                <span class="text-sm text-gray-500">Média de mensagens por Conversa</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm"><?= $averageMessagesPerChat; ?></h4>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
                <span class="text-sm text-gray-500">Total de Conversas</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm"><?= $chatsTotal; ?></h4>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
                <span class="text-sm text-gray-500">Reuniões Agendadas</span>
                <h4 class="mt-2 font-bold text-gray-800 text-title-sm"><?= count($allMeetings); ?></h4>
            </div>

            <?php if($instance) :?>
            <div class="w-full bg-white border border-default rounded-base shadow-xs p-4 md:p-6">

                <div class="flex justify-between mb-3">
                    <div class="flex flex-col justify-center items-center">
                        <h5 class="text-xl font-semibold text-heading me-1 !mb-1">Média de Resoluções</h5>
                        <p class="text-sm text-heading me-1">Intervenções humanas necessárias em Chats</p>
                    </div>
                </div>

                <!-- Donut Chart -->
                <div class="py-6" id="donut-chart"></div>
            </div>
            <?php endif; ?>


        </div>

<?php
        return ob_get_clean();
    }
}

add_shortcode('dashboard', [Dashboard::class, 'render']);
