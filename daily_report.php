<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

require_login();

$start_time = microtime(true);

$user_id = $_SESSION['user_id'];
$report_id = $_GET['id'] ?? 0;
$report_date = date('Y-m-d');

// Получение настроек Pro Mode и данных отчета одним запросом
$sql = "SELECT u.pro_mode, u.pro_focus_of_day, u.pro_thought_of_day, u.pro_what_im_afraid_of, 
               u.pro_what_to_take_from_surroundings, u.pro_world_picture, u.pro_happiness,
               r.*
        FROM users u
        LEFT JOIN energy_reports r ON u.id = r.user_id AND " . ($report_id ? "r.id = ?" : "r.report_date = ?") . "
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
if ($report_id) {
    $stmt->bind_param("ii", $report_id, $user_id);
} else {
    $stmt->bind_param("si", $report_date, $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: dashboard.php");
    exit();
}

$pro_settings = array_slice($data, 0, 7);
$report = array_slice($data, 7);

$conn->close();

// Определение описаний для типов энергии и полей Pro Mode
$descriptions = [
    'physical' => 'How physically good and energized do you feel?',
    'mental' => 'Ready for mental work? Able to focus your attention today?',
    'emotional' => 'Is there someone or something that annoys, irritates, or distracts your attention?',
    'why_not' => 'Ready to do something extra? Based on the motivation \'why not do it?\'',
    'focus_of_day' => 'What is your main focus or goal for today?',
    'thought_of_day' => 'What idea or thought seems interesting to you today?',
    'what_im_afraid_of' => 'What might slow you down today?',
    'what_to_take_from_surroundings' => 'What can you gain today from your environment or surroundings? Of course, without toxic or selfish behavior.',
    'world_picture' => 'What are you ready to learn or discover new to expand your worldview?',
    'happiness' => 'What can you do today for your own pleasure and joy?'
];

$placeholders = [
    'focus_of_day' => 'Enter your primary objective for today...',
    'thought_of_day' => 'Share an intriguing thought or idea for today...',
    'what_im_afraid_of' => 'Note potential obstacles or concerns...',
    'what_to_take_from_surroundings' => 'Identify positive influences from your environment...',
    'world_picture' => 'Note opportunities to broaden your perspective...',
    'happiness' => 'List activities that bring you joy today...'
];

$page_title = "Daily Energy Report - " . date('F j, Y', strtotime($report['report_date']));
include 'header.php';

$load_time = microtime(true) - $start_time;
?>

<div class="container">
    <a href="dashboard.php" class="back-button top-back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <header>
        <h1>Daily Energy Report - <?php echo date('F j, Y', strtotime($report['report_date'])); ?></h1>
    </header>
    
    <div class="save-status" id="save-status">
        <i class="fas fa-save"></i> <span id="save-status-text">All changes saved</span>
    </div>
    
    <form id="energy-form">
        <?php if ($pro_settings['pro_mode']): ?>
            <?php foreach (['focus_of_day', 'thought_of_day', 'what_im_afraid_of'] as $field): ?>
                <?php if ($pro_settings["pro_$field"]): ?>
                <div class="pro-field">
                    <label for="<?php echo $field; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $field)); ?>
                        <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($descriptions[$field]); ?>">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </label>
                    <textarea id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="auto-expand" placeholder="<?php echo htmlspecialchars($placeholders[$field]); ?>"><?php echo htmlspecialchars($report[$field] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php
        $energyTypes = [
            'physical' => 'Physical Energy',
            'mental' => 'Mental Energy',
            'emotional' => 'Emotional Energy',
            'why_not' => 'Why Not Energy'
        ];
        
        foreach ($energyTypes as $type => $label):
            $value = isset($report["{$type}_energy"]) ? intval($report["{$type}_energy"]) : 100;
            $comment = $report["{$type}_comment"] ?? '';
        ?>
            <div class="energy-row">
                <div class="energy-type">
                    <?php echo $label; ?>
                    <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($descriptions[$type]); ?>">
                        <i class="fas fa-question-circle"></i>
                    </span>
                </div>
                <div class="energy-input">
                    <div class="energy-battery" id="<?php echo $type; ?>_battery">
                        <div class="energy-level"></div>
                        <div class="energy-divisions">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="energy-division" data-value="<?php echo $i * 20; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="energy-comment">
                    <textarea id="<?php echo $type; ?>_comment" name="<?php echo $type; ?>_comment" class="auto-expand" placeholder="Add a comment about your <?php echo strtolower($label); ?>..."><?php echo htmlspecialchars($comment); ?></textarea>
                </div>
            </div>
            <input type="hidden" name="<?php echo $type; ?>_energy" id="<?php echo $type; ?>_energy" value="<?php echo $value; ?>">
        <?php endforeach; ?>

        <?php if ($pro_settings['pro_mode']): ?>
            <?php foreach (['what_to_take_from_surroundings', 'world_picture', 'happiness'] as $field): ?>
                <?php if ($pro_settings["pro_$field"]): ?>
                <div class="pro-field">
                    <label for="<?php echo $field; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $field)); ?>
                        <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo htmlspecialchars($descriptions[$field]); ?>">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </label>
                    <textarea id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="auto-expand" placeholder="<?php echo htmlspecialchars($placeholders[$field]); ?>"><?php echo htmlspecialchars($report[$field] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
        <input type="hidden" name="report_date" value="<?php echo $report['report_date']; ?>">
    </form>

    <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>
console.log('Page load time: <?php echo $load_time; ?> seconds');

document.addEventListener('DOMContentLoaded', function() {
    console.time('DOMContentLoaded');
    
    let saveTimer;
    const saveStatus = document.getElementById('save-status');
    const saveStatusText = document.getElementById('save-status-text');

    function showSaveStatus(message, isError = false) {
        console.log('Save status:', message, isError ? 'Error' : 'Success');
        saveStatusText.textContent = message;
        saveStatus.classList.toggle('visible', true);
        saveStatus.classList.toggle('error', isError);
        setTimeout(() => {
            saveStatus.classList.remove('visible');
        }, 2000);
    }

    function saveReport() {
        console.time('saveReport');
        const formData = new FormData(document.getElementById('energy-form'));
        fetch('save_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSaveStatus('All changes saved');
            } else {
                showSaveStatus('Error: ' + data.message, true);
            }
            console.timeEnd('saveReport');
        })
        .catch(error => {
            showSaveStatus('Error saving changes: ' + error, true);
            console.error('Save error:', error);
            console.timeEnd('saveReport');
        });
    }

    function updateBattery(type, value) {
        const battery = document.getElementById(`${type}_battery`);
        const level = battery.querySelector('.energy-level');
        level.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
        document.getElementById(`${type}_energy`).value = value;
    }

    document.querySelectorAll('.energy-division').forEach(div => {
        div.addEventListener('click', function() {
            const value = this.dataset.value;
            const battery = this.closest('.energy-battery');
            const type = battery.id.replace('_battery', '');
            updateBattery(type, value);
            saveReport();
        });
    });

    function autoExpand(field) {
        field.style.height = 'inherit';
        const computed = window.getComputedStyle(field);
        const height = parseInt(computed.getPropertyValue('border-top-width'), 10)
                     + parseInt(computed.getPropertyValue('padding-top'), 10)
                     + field.scrollHeight
                     + parseInt(computed.getPropertyValue('padding-bottom'), 10)
                     + parseInt(computed.getPropertyValue('border-bottom-width'), 10);

        field.style.height = height + 'px';
    }

    document.querySelectorAll('textarea.auto-expand').forEach(textarea => {
        autoExpand(textarea);
        textarea.addEventListener('input', () => {
            autoExpand(textarea);
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveReport, 1000);
        });
    });

    // Initialize batteries
    <?php foreach ($energyTypes as $type => $label): ?>
    updateBattery('<?php echo $type; ?>', <?php echo $report["{$type}_energy"] ?? 100; ?>);
    <?php endforeach; ?>

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Handle tooltip behavior on mobile
    if ('ontouchstart' in document.documentElement) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                var tooltip = bootstrap.Tooltip.getInstance(this);
                if (tooltip._isShown()) {
                    tooltip.hide();
                } else {
                    tooltip.show();
                }
            });
        });
    }

    console.timeEnd('DOMContentLoaded');
});
</script>

<style>
    .container {
        max-width: 800px;
        margin: 20px auto;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 20px;
    }
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    h1 {
        font-size: 24px;
        font-weight: 600;
        margin: 0;
    }
    .save-status {
        font-size: 14px;
        padding: 5px 10px;
        border-radius: 4px;
        transition: opacity 0.3s ease;
        opacity: 0;
        position: fixed;
        top: 80px;
        right: 20px;
        background-color: #d4edda;
        color: #155724;
    }
    .save-status.visible {
        opacity: 1;
    }
    .save-status.error {
        background-color: #f8d7da;
        color: #721c24;
    }
    .energy-row, .pro-field {
        background-color: #f7f6f3;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .energy-type {
        font-weight: 500;
        margin-bottom: 10px;
    }
    .energy-input {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .energy-battery {
        width: 100%;
        height: 40px;
        background-color: #e0e0e0;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
    }
    .energy-level {
        height: 100%;
        width: 100%;
        background: linear-gradient(to right, #ff4b4b 0%, #ffeb3b 60%, #4caf50 100%);
        transition: transform 0.3s ease;
    }
    .energy-divisions {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
    }
    .energy-division {
        flex: 1;
        border-right: 2px solid rgba(255, 255, 255, 0.5);
    }
    .energy-division:last-child {
        border-right: none;
    }
    .energy-comment textarea, .pro-field textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        font-size: 14px;
        resize: none;
        overflow: hidden;
        min-height: 60px;
    }
    .back-button {
        display: inline-block;
        margin-top: 20px;
        padding: 8px 16px;
        background-color: #f7f6f3;
        color: #37352f;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }
    .back-button:hover {
        background-color: #e0e0e0;
        text-decoration: none;
        color: #37352f;
    }
    .top-back-button {
        margin-bottom: 20px;
    }
    .info-icon {
        cursor: pointer;
        margin-left: 5px;
        color: #6c757d;
    }
    .tooltip {
        font-size: 14px;
    }
    .tooltip-inner {
        max-width: 300px;
        text-align: left;
    }
    label {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }
        h1 {
            font-size: 20px;
        }
        .energy-battery {
            height: 30px;
        }
        .tooltip-inner {
            max-width: 200px;
        }
    }
</style>

<?php include 'footer.php'; ?>