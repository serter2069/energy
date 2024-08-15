<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'check_auth.php';
require_once 'db_connection.php';

require_login();

$user_id = $_SESSION['user_id'];

$start_time = microtime(true);

date_default_timezone_set('Europe/Moscow');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$today = date('Y-m-d');

// Функции для работы с файловым кэшем
function getCache($key, $ttl = 3600) {
    $file = sys_get_temp_dir() . '/energy_diary_cache_' . md5($key);
    if (file_exists($file) && (filemtime($file) + $ttl) > time()) {
        return unserialize(file_get_contents($file));
    }
    return false;
}

function setCache($key, $value, $ttl = 3600) {
    $file = sys_get_temp_dir() . '/energy_diary_cache_' . md5($key);
    file_put_contents($file, serialize($value));
}

// Используем кэширование для общего количества отчетов
$cache_key = "total_reports_user_{$user_id}";
$total_reports = getCache($cache_key);
if ($total_reports === false) {
    $count_sql = "SELECT COUNT(*) as total FROM energy_reports WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $total_reports = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    setCache($cache_key, $total_reports, 3600); // Кэшируем на 1 час
}
$total_pages = ceil($total_reports / $limit);

// Проверка наличия отчета за сегодня
$check_today_sql = "SELECT id FROM energy_reports WHERE user_id = ? AND report_date = ? LIMIT 1";
$check_today_stmt = $conn->prepare($check_today_sql);
$check_today_stmt->bind_param("is", $user_id, $today);
$check_today_stmt->execute();
$today_report = $check_today_stmt->get_result()->fetch_assoc();
$check_today_stmt->close();

// Загрузка отчетов
$load_reports_start = microtime(true);
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
$stmt->close();
$load_reports_time = microtime(true) - $load_reports_start;

$conn->close();

$total_php_time = microtime(true) - $start_time;

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

function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y');
}

function escapeHtml($unsafe) {
    return htmlspecialchars($unsafe, ENT_QUOTES, 'UTF-8');
}

// Если это AJAX-запрос, возвращаем только HTML для отчетов
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    ob_start();
    foreach ($reports as $report):
        $avgEnergy = getAverageEnergy($report);
        $color = getColorForEnergy($avgEnergy);
        ?>
        <div class="col-md-6 mb-4">
            <div class="card report-card" onclick="window.location='daily_report.php?id=<?php echo $report['id']; ?>'">
                <div class="card-body">
                    <h5 class="card-title"><?php echo formatDate($report['report_date']); ?></h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="average-energy" style="width: 60px; height: 60px; border-radius: 50%; background-color: <?php echo $color; ?>; display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; font-size: 18px;">
                            <?php echo $avgEnergy; ?>
                        </div>
                        <div class="ms-3">Average Energy</div>
                    </div>
                    <?php
                    $energyTypes = [
                        'physical' => 'Physical',
                        'mental' => 'Mental',
                        'emotional' => 'Emotional',
                        'why_not' => 'Why Not'
                    ];
                    foreach ($energyTypes as $key => $label):
                        $energy = $report["{$key}_energy"];
                        $comment = $report["{$key}_comment"];
                        $barColor = getColorForEnergy($energy);
                    ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span><?php echo $label; ?></span>
                                <span><?php echo $energy; ?>%</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar energy-bar" 
                                     role="progressbar" 
                                     style="width: <?php echo $energy; ?>%; background-color: <?php echo $barColor; ?>;"
                                     aria-valuenow="<?php echo $energy; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"
                                     <?php if ($comment): ?>
                                     data-bs-toggle="tooltip" 
                                     data-bs-placement="top" 
                                     title="<?php echo escapeHtml($comment); ?>"
                                     <?php endif; ?>>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php
    endforeach;
    echo ob_get_clean();
    exit;
}

// Если это не AJAX-запрос, отображаем всю страницу
include 'header.php';
?>

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

    <div id="preloader" class="text-center my-4">
        <div class="spinner-border text-primary" role="status">

        </div>
    </div>

    <div id="reports-container" class="row" style="display: none;">
        <!-- Reports will be loaded here -->
    </div>

    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center" id="pagination">
            <!-- Pagination will be generated here -->
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
<script>
    let currentPage = 1;
    const totalPages = <?php echo $total_pages; ?>;

    console.log('Performance Data:');
    console.log('Load Reports Time:', <?php echo json_encode($load_reports_time); ?>, 'seconds');
    console.log('Total PHP Execution Time:', <?php echo json_encode($total_php_time); ?>, 'seconds');

    window.addEventListener('load', function() {
        var pageLoadTime = (performance.now()) / 1000;
        console.log('Total Page Load Time:', pageLoadTime, 'seconds');
    });

    function loadReports(page) {
        $('#preloader').show();
        $('#reports-container').hide();
        $.ajax({
            url: 'dashboard.php',
            method: 'GET',
            data: { page: page },
            success: function(response) {
                $('#reports-container').html(response).show();
                $('#preloader').hide();
                currentPage = page;
                if (totalPages > 1) {
                    updatePagination();
                }
                initTooltips();
            }
        });
    }

    function updatePagination() {
        let paginationHtml = '';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadReports(${i}); return false;">${i}</a>
                               </li>`;
        }
        $('#pagination').html(paginationHtml);
    }

    function initTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    $(document).ready(function() {
        loadReports(1);
    });
</script>

<?php include 'footer.php'; ?>