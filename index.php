<?php
// ===== PLIK: index.php =====
// Monitor statusów zamówień BaseLinker z backendem PHP

// Obsługa zapytań AJAX do API BaseLinkera
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    $token = $_POST['token'] ?? '';
    $method = $_POST['method'] ?? '';
    $parameters = $_POST['parameters'] ?? '{}';
    
    if (empty($token) || empty($method)) {
        echo json_encode(['status' => 'ERROR', 'error_message' => 'Brak wymaganych parametrów']);
        exit;
    }
    
    // Przygotuj zapytanie do API BaseLinkera
    $postFields = http_build_query([
        'method' => $method,
        'parameters' => $parameters
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.baselinker.com/connector.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-BLToken: ' . $token,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo json_encode(['status' => 'ERROR', 'error_message' => 'Błąd połączenia: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }
    
    curl_close($ch);
    
    echo $response;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Statusów Zamówień BaseLinker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            /* margin-bottom: 30px; */
        }

        .header h1 {
            font-size: 1.5em;
            /* margin-bottom: 10px; */
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .config-panel {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6674ff;
        }

        .status-selector {
            margin-top: 20px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .status-checkbox {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .status-checkbox:hover {
            background: #e3e7ff;
            transform: translateY(-2px);
        }

        .status-checkbox input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-checkbox label {
            cursor: pointer;
            margin: 0;
            font-weight: 400;
            flex-grow: 1;
        }

        .btn {
            background: #1a237e;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(26,35,126,0.15);
        }

        .btn:hover {
            background: #3949ab;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26,35,126,0.25);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: var(--status-color, #667eea);
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .status-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .order-count {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
            line-height: 1;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .last-update {
            text-align: center;
            color: white;
            font-size: 14px;
            opacity: 0.8;
        }

        .refresh-info {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .updating {
            animation: pulse 1.5s infinite;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .save-token-checkbox {
            margin-top: 10px;
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }

        .save-token-checkbox input {
            margin-right: 8px;
        }

        /* Dodaję styl modala */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.4);
        }
        .modal-content {
            background: #fff;
            border-radius: 15px;
            padding: 30px 30px 10px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1001;
            min-width: 350px;
            max-width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 2em;
            color: #888;
            cursor: pointer;
        }
        .close:hover {
            color: #333;
        }

        .footer {
            margin-top: 40px;
            text-align: left;
            color: #fff;
            /* background: #1a237e; */
            padding: 18px 0 12px 0;
            font-size: 16px;
            border-radius: 0 0 15px 15px;
            letter-spacing: 0.5px;
        }

        .footer a {
            color: #fff;
            text-decoration: underline;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 id="lastUpdate" class="last-update"></h1>
            <!-- <h1>Monitor Statusów Zamówień BaseLinker</h1> -->
            <!-- <p>Śledź liczbę zamówień w wybranych statusach w czasie rzeczywistym</p> -->
        </div>

        <!-- Modal z konfiguracją -->
        <div id="settingsModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" id="closeModalBtn">&times;</span>
                <div class="config-panel">
                <div id="messageContainer"></div>
                    <h2>Konfiguracja</h2>
                    <div class="form-group">
                        <label for="apiToken">Token API BaseLinker:</label>
                        <input type="password" id="apiToken" placeholder="Wprowadź token API">
                        <div class="save-token-checkbox">
                            <input type="checkbox" id="saveToken">
                            <label for="saveToken">Zapamiętaj token (tylko w tej przeglądarce)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="refreshInterval">Interwał odświeżania (sekundy):</label>
                        <input type="number" id="refreshInterval" value="60" min="10" max="3600">
                    </div>
                    <div class="status-selector">
                        <h3>Wybierz statusy do monitorowania i kolor:</h3>
                        <div id="statusList" class="status-grid">
                            <div class="loading">Wprowadź token API aby załadować statusy...</div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button class="btn" id="saveSettingsBtn" type="button">Zapisz</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="statusCards" class="status-cards"></div>
        

        <div style="display: flex; gap: 10px; margin: 20px 0 0 0;">
            <button class="btn" id="settingsBtn">Ustawienia</button>
            <button class="btn" id="startBtn">Rozpocznij monitorowanie</button>
            <button class="btn" id="stopBtn" style="display: none;">Zatrzymaj monitorowanie</button>
        </div>

        <div class="footer">
        😎 Łukasz Szynczewski 👉 <a href="https://linktr.ee/szynczewski" target="_blank" rel="noopener">Kontakt</a>
        </div>

    </div>

    <script>
        let monitoringInterval = null;
        let countdownInterval = null;
        let timeToNextRefresh = 0;
        let selectedStatuses = new Set();
        let statusData = []; // Zmienione na tablicę

        // Pobierz kolory statusów z localStorage
        function getStatusColors() {
            try {
                return JSON.parse(localStorage.getItem('baselinker_status_colors')) || {};
            } catch (e) {
                return {};
            }
        }
        function saveStatusColors(colors) {
            localStorage.setItem('baselinker_status_colors', JSON.stringify(colors));
        }
        let statusColors = getStatusColors();

        // Sprawdź zapisany token
        window.addEventListener('load', () => {
            const savedToken = localStorage.getItem('baselinker_token');
            if (savedToken) {
                document.getElementById('apiToken').value = savedToken;
                document.getElementById('saveToken').checked = true;
            }
            // Przywróć interwał odświeżania
            const savedInterval = localStorage.getItem('baselinker_refresh_interval');
            if (savedInterval) {
                document.getElementById('refreshInterval').value = savedInterval;
            }
            // Przywróć wybrane statusy
            const savedStatuses = localStorage.getItem('baselinker_selected_statuses');
            if (savedStatuses) {
                try {
                    selectedStatuses = new Set(JSON.parse(savedStatuses));
                } catch (e) {
                    selectedStatuses = new Set();
                }
            }
            loadStatusList();

            // Automatyczne uruchomienie monitoringu jeśli jest token i wybrany status
            setTimeout(() => {
                const token = document.getElementById('apiToken').value;
                if (token && selectedStatuses.size > 0) {
                    startMonitoring();
                }
            }, 500); // opóźnienie, by statusy się załadowały
        });

        // Funkcja do wywołania API przez PHP proxy
        async function callBaseLinkerAPI(method, parameters = {}) {
            const token = document.getElementById('apiToken').value;
            
            if (!token) {
                throw new Error('Brak tokenu API');
            }

            const formData = new FormData();
            formData.append('ajax_action', 'true');
            formData.append('token', token);
            formData.append('method', method);
            formData.append('parameters', JSON.stringify(parameters));

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.status === 'ERROR') {
                    throw new Error(data.error_message || 'Błąd API');
                }

                return data;
            } catch (error) {
                console.error('Błąd połączenia:', error);
                throw error;
            }
        }

        // Pobierz listę statusów
        async function loadStatusList() {
            try {
                showMessage('Ładowanie statusów...', 'info');
                const response = await callBaseLinkerAPI('getOrderStatusList');
                
                // Zapisz jako tablicę, bo tak zwraca API
                statusData = response.statuses || [];
                displayStatusList();
                showMessage('Statusy załadowane pomyślnie', 'success');
                
                // Zapisz token jeśli zaznaczono
                if (document.getElementById('saveToken').checked) {
                    localStorage.setItem('baselinker_token', document.getElementById('apiToken').value);
                }
            } catch (error) {
                showMessage(`Błąd podczas pobierania statusów: ${error.message}`, 'error');
            }
        }

        // Wyświetl listę statusów do wyboru
        function displayStatusList() {
            const statusList = document.getElementById('statusList');
            statusList.innerHTML = '';

            if (!statusData || statusData.length === 0) {
                statusList.innerHTML = '<div class="loading">Brak dostępnych statusów</div>';
                return;
            }

            statusData.forEach((statusInfo) => {
                const statusId = statusInfo.id.toString();
                const checkbox = document.createElement('div');
                checkbox.className = 'status-checkbox';
                // Kolor z localStorage lub domyślny
                const color = statusColors[statusId] || '#667eea';
                checkbox.innerHTML = `
                    <input type="checkbox" id="status_${statusId}" value="${statusId}">
                    <label for="status_${statusId}">${statusInfo.name}</label>
                    <input type="color" id="color_${statusId}" value="${color}" title="Wybierz kolor" style="margin-left:10px; width:28px; height:28px; border:none; background:none; cursor:pointer;">
                `;
                const input = checkbox.querySelector('input[type="checkbox"]');
                const colorInput = checkbox.querySelector('input[type="color"]');
                if (selectedStatuses.has(statusId)) {
                    input.checked = true;
                }
                input.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        selectedStatuses.add(statusId);
                    } else {
                        selectedStatuses.delete(statusId);
                    }
                    saveSelectedStatuses();
                });
                colorInput.addEventListener('input', (e) => {
                    statusColors[statusId] = e.target.value;
                    saveStatusColors(statusColors);
                });
                statusList.appendChild(checkbox);
            });
        }

        // Pobierz liczbę zamówień dla wybranych statusów
        async function fetchOrderCounts() {
            const statusCards = document.getElementById('statusCards');
            statusCards.classList.add('updating');

            try {
                showMessage('Pobieranie zamówień...', 'info');
                const counts = {};

                // Dla każdego wybranego statusu pobierz zamówienia tylko o tym statusie
                const promises = Array.from(selectedStatuses).map(async statusId => {
                    const response = await callBaseLinkerAPI('getOrders', {
                        status_id: statusId,
                        get_unconfirmed_orders: false
                        // date_from: Math.floor(Date.now() / 1000) - (30 * 24 * 60 * 60) // ostatnie 30 dni
                    });
                    counts[statusId] = response.orders ? response.orders.length : 0;
                });

                await Promise.all(promises);

                displayOrderCounts(counts);
                updateLastRefreshTime();
                showMessage('', '');
            } catch (error) {
                console.error('Błąd fetchOrderCounts:', error);
                showMessage(`Błąd podczas pobierania zamówień: ${error.message}`, 'error');
            } finally {
                statusCards.classList.remove('updating');
            }
        }

        // Wyświetl liczby zamówień
        function displayOrderCounts(counts) {
            const statusCards = document.getElementById('statusCards');
            statusCards.innerHTML = '';

            selectedStatuses.forEach(statusId => {
                const card = document.createElement('div');
                card.className = 'status-card';
                // Kolor z localStorage lub domyślny
                const color = statusColors[statusId] || '#667eea';
                card.style.setProperty('--status-color', color);
                const statusInfo = statusData.find(s => s.id.toString() === statusId);
                const statusName = statusInfo ? statusInfo.name : `Status ${statusId}`;
                const orderCount = counts[statusId] || 0;
                card.innerHTML = `
                    <div class="status-name">${statusName}</div>
                    <div class="order-count">${orderCount}</div>
                    <div style="margin-top: 10px; color: #666;">zamówień</div>
                `;
                statusCards.appendChild(card);
            });
        }

        // Aktualizuj czas ostatniego odświeżenia i licznik do kolejnego
        // function updateLastRefreshTime() {
        //     const lastUpdate = document.getElementById('lastUpdate');
        //     const now = new Date();
        //     let refreshInterval = parseInt(document.getElementById('refreshInterval').value);
        //     if (isNaN(refreshInterval) || refreshInterval < 1) refreshInterval = 60;
        //     timeToNextRefresh = refreshInterval;
        //     lastUpdate.innerHTML = `Ostatnia aktualizacja: ${now.toLocaleTimeString('pl-PL')}<br>Do kolejnego odświeżenia: <span id='countdown'>${timeToNextRefresh}</span> s`;
        // }

        function updateLastRefreshTime() {
            const lastUpdate = document.getElementById('lastUpdate');
            const now = new Date();
            let refreshInterval = parseInt(document.getElementById('refreshInterval').value);
            if (isNaN(refreshInterval) || refreshInterval < 1) refreshInterval = 60;
            timeToNextRefresh = refreshInterval;
            lastUpdate.innerHTML = `Do kolejnego odświeżenia: <span id='countdown'>${timeToNextRefresh}</span> s`;
        }

        // Odliczanie do kolejnego odświeżenia
        function startCountdown() {
            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                const countdown = document.getElementById('countdown');
                if (countdown) {
                    timeToNextRefresh--;
                    if (timeToNextRefresh < 0) timeToNextRefresh = 0;
                    countdown.textContent = timeToNextRefresh;
                }
            }, 1000);
        }
        function stopCountdown() {
            if (countdownInterval) clearInterval(countdownInterval);
        }

        // Rozpocznij monitorowanie
        function startMonitoring() {
            if (selectedStatuses.size === 0) {
                showMessage('Wybierz przynajmniej jeden status do monitorowania', 'error');
                return;
            }

            const refreshInterval = parseInt(document.getElementById('refreshInterval').value) * 1000;

            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'inline-block';

            // Pierwsze pobranie
            fetchOrderCounts();
            updateLastRefreshTime();
            startCountdown();

            // Ustaw interwał
            monitoringInterval = setInterval(() => {
                fetchOrderCounts();
                updateLastRefreshTime();
            }, refreshInterval);
        }

        // Zatrzymaj monitorowanie
        function stopMonitoring() {
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
                monitoringInterval = null;
            }
            stopCountdown();
            document.getElementById('startBtn').style.display = 'inline-block';
            document.getElementById('stopBtn').style.display = 'none';
            const refreshInfo = document.querySelector('.refresh-info');
            if (refreshInfo) {
                refreshInfo.remove();
            }
        }

        // Wyświetl komunikat
        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            if (message) {
                const className = type === 'error' ? 'error' : type === 'success' ? 'success' : 'loading';
                messageContainer.innerHTML = `<div class="${className}">${message}</div>`;
            } else {
                messageContainer.innerHTML = '';
            }
        }

        // Nasłuchuj zmian tokenu API
        document.getElementById('apiToken').addEventListener('input', (e) => {
            if (document.getElementById('saveToken').checked) {
                localStorage.setItem('baselinker_token', e.target.value);
            }
            if (e.target.value.length > 10) {
                loadStatusList();
            }
        });

        // Obsługa zapisywania tokenu
        document.getElementById('saveToken').addEventListener('change', (e) => {
            const token = document.getElementById('apiToken').value;
            if (e.target.checked) {
                localStorage.setItem('baselinker_token', token);
            } else {
                localStorage.removeItem('baselinker_token');
            }
        });

        // Zapisuj interwał odświeżania przy zmianie
        document.getElementById('refreshInterval').addEventListener('change', (e) => {
            localStorage.setItem('baselinker_refresh_interval', e.target.value);
        });

        // Zapisuj wybrane statusy przy zmianie
        function saveSelectedStatuses() {
            localStorage.setItem('baselinker_selected_statuses', JSON.stringify(Array.from(selectedStatuses)));
        }

        // Modal obsługa
        window.addEventListener('DOMContentLoaded', function() {
            const settingsBtn = document.getElementById('settingsBtn');
            const settingsModal = document.getElementById('settingsModal');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const saveSettingsBtn = document.getElementById('saveSettingsBtn');
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');

            function openSettingsModal() {
                settingsModal.style.display = 'flex';
            }
            function closeSettingsModal() {
                settingsModal.style.display = 'none';
            }
            settingsBtn.addEventListener('click', openSettingsModal);
            closeModalBtn.addEventListener('click', closeSettingsModal);
            settingsModal.addEventListener('click', function(e) {
                if (e.target === settingsModal) closeSettingsModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeSettingsModal();
            });
            saveSettingsBtn.addEventListener('click', function() {
                closeSettingsModal();
                // Restart monitorowania jeśli aktywne
                if (monitoringInterval) {
                    stopMonitoring();
                    startMonitoring();
                }
            });
            startBtn.addEventListener('click', function() {
                startMonitoring();
            });
            stopBtn.addEventListener('click', function() {
                stopMonitoring();
            });
        });
    </script>
</body>
</html>