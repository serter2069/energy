<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

require_login();

$user_id = $_SESSION['user_id'];

// Установка часового пояса (замените на нужный вам часовой пояс)
date_default_timezone_set('Europe/Moscow');

// Обработка AJAX-запроса
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT id, report_date, 
                   physical_energy, mental_energy, emotional_energy, why_not_energy,
                   physical_comment, mental_comment, emotional_comment, why_not_comment
            FROM energy_reports 
            WHERE user_id = ? 
            ORDER BY report_date DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = $result->fetch_all(MYSQLI_ASSOC);
    
    // Логирование дат для отладки
    foreach ($reports as $report) {
        error_log("Report date from DB: " . $report['report_date']);
    }
    
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode($reports);
    exit;
}

// Обычная загрузка страницы
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Проверка отчета на сегодня
$today = date('Y-m-d');
$check_sql = "SELECT id FROM energy_reports WHERE user_id = ? AND report_date = ? LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $today);
$check_stmt->execute();
$today_report = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

// Подсчет общего количества отчетов
$count_sql = "SELECT COUNT(*) as total FROM energy_reports WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_reports = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_reports / $limit);
$count_stmt->close();

$conn->close();

function getAverageEnergy($report) {
    return round(($report['physical_energy'] + $report['mental_energy'] + 
                  $report['emotional_energy'] + $report['why_not_energy']) / 4);
}

function getColorForEnergy($energy) {
    if ($energy >= 90) return '#4caf50';
    if ($energy >= 70) return '#8bc34a';
    if ($energy >= 50) return '#ffeb3b';
    if ($energy >= 30) return '#ff9800';
    return '#f44336';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Reports Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .energy-bar {
            height: 20px;
            transition: all 0.3s ease;
        }
        .energy-bar:hover {
            opacity: 0.8;
            cursor: pointer;
        }
        .report-card {
            transition: all 0.3s ease;
        }
        .report-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .average-energy {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Energy Reports Dashboard</h1>
        
        <?php if ($today_report): ?>
            <a href="daily_report.php?id=<?php echo $today_report['id']; ?>" class="btn btn-primary mb-4">
                Edit Today's Report
            </a>
        <?php else: ?>
            <a href="daily_report.php" class="btn btn-success mb-4">
                New Report for Today
            </a>
        <?php endif; ?>

        <div id="reports-container" class="row">
            <!-- Reports will be loaded here via JavaScript -->
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function loadReports(page = 1) {
            fetch(`dashboard.php?ajax=1&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('reports-container');
                    container.innerHTML = '';
                    data.forEach(report => {
                        const reportHtml = createReportHtml(report);
                        container.innerHTML += reportHtml;
                    });
                    initTooltips();
                })
                .catch(error => console.error('Error:', error));
        }

        function createReportHtml(report) {
            const avgEnergy = getAverageEnergy(report);
            const color = getColorForEnergy(avgEnergy);
            let html = `
                <div class="col-md-6 mb-4">
                    <div class="card report-card" onclick="window.location='daily_report.php?id=${report.id}'">
                        <div class="card-body">
                            <h5 class="card-title">${formatDate(report.report_date)}</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="average-energy" style="background-color: ${color};">
                                    ${avgEnergy}
                                </div>
                                <div class="ms-3">Average Energy</div>
                            </div>`;
            
            const energyTypes = {
                'physical': 'Physical',
                'mental': 'Mental',
                'emotional': 'Emotional',
                'why_not': 'Why Not'
            };

            for (const [key, label] of Object.entries(energyTypes)) {
                const energy = report[`${key}_energy`];
                const comment = report[`${key}_comment`];
                const barColor = getColorForEnergy(energy);
                html += `
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>${label}</span>
                            <span>${energy}%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar energy-bar" 
                                 role="progressbar" 
                                 style="width: ${energy}%; background-color: ${barColor};"
                                 aria-valuenow="${energy}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"
                                 ${comment ? `data-bs-toggle="tooltip" data-bs-placement="top" title="${escapeHtml(comment)}"` : ''}>
                            </div>
                        </div>
                    </div>`;
            }

            html += `
                        </div>
                    </div>
                </div>`;
            return html;
        }

        function getAverageEnergy(report) {
            return Math.round((parseInt(report.physical_energy) + parseInt(report.mental_energy) + 
                               parseInt(report.emotional_energy) + parseInt(report.why_not_energy)) / 4);
        }

        function getColorForEnergy(energy) {
            if (energy >= 90) return '#4caf50';
            if (energy >= 70) return '#8bc34a';
            if (energy >= 50) return '#ffeb3b';
            if (energy >= 30) return '#ff9800';
            return '#f44336';
        }

        function formatDate(dateString) {
            console.log("Original date string:", dateString);
            // Создаем объект Date в UTC, чтобы избежать проблем с часовыми поясами
            const date = new Date(dateString + 'T00:00:00Z');
            console.log("Parsed date object:", date);
            const options = { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            console.log("Formatted date:", formattedDate);
            return formattedDate;
        }

        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function initTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Загрузка первой страницы отчетов
        loadReports();

        // Обработка пагинации
        document.querySelector('.pagination').addEventListener('click', function(e) {
            if (e.target.tagName === 'A') {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                loadReports(page);
            }
        });
    });
    </script>
</body>
</html>