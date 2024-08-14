<?php
session_start();
require_once 'check_auth.php';
require_once 'db_connection.php';

require_login();

$user_id = $_SESSION['user_id'];
$report_id = $_GET['id'] ?? 0;
$report_date = date('Y-m-d');

// Вызов скрипта create_missing_reports.php
$create_reports_url = "http://" . $_SERVER['HTTP_HOST'] . "/create_missing_reports.php?user_id=" . $user_id;
file_get_contents($create_reports_url);

// Получение настроек Pro Mode пользователя
$pro_settings_sql = "SELECT pro_mode, pro_focus_of_day, pro_thought_of_day, pro_what_im_afraid_of, 
                            pro_what_to_take_from_surroundings, pro_world_picture, pro_happiness
                     FROM users WHERE id = ?";
$pro_settings_stmt = $conn->prepare($pro_settings_sql);
$pro_settings_stmt->bind_param("i", $user_id);
$pro_settings_stmt->execute();
$pro_settings_result = $pro_settings_stmt->get_result();
$pro_settings = $pro_settings_result->fetch_assoc();
$pro_settings_stmt->close();

if ($report_id) {
    $sql = "SELECT * FROM energy_reports WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if (!$report) {
        header("Location: dashboard.php");
        exit();
    }
    $report_date = $report['report_date'];
} else {
    $sql = "SELECT * FROM energy_reports WHERE user_id = ? AND report_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $report_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if (!$report) {
        header("Location: dashboard.php");
        exit();
    }
    $report_id = $report['id'];
}

$conn->close();

// Определение описаний для типов энергии и полей Pro Mode
$descriptions = [
    'physical' => 'Your physical energy relates to your body\'s vitality and stamina.',
    'mental' => 'Mental energy is about your cognitive abilities and focus.',
    'emotional' => 'Emotional energy reflects your mood and feelings.',
    'why_not' => 'Why Not energy represents your motivation and willingness to act.',
    'focus_of_day' => 'What is your main focus or goal for today?',
    'thought_of_day' => 'What significant thought or idea do you want to remember today?',
    'what_im_afraid_of' => 'What fears or concerns are on your mind today?',
    'what_to_take_from_surroundings' => 'What can you learn or gain from your environment today?',
    'world_picture' => 'What can you do to expand your worldview today?',
    'happiness' => 'What can you do to bring joy into your life today?'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Energy Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
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
            top: 20px;
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
            resize: vertical;
            height: 60px;
            margin-top: 10px;
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
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <a href="dashboard.php" class="back-button top-back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <header>
            <h1>Daily Energy Report - <?php echo date('F j, Y', strtotime($report_date)); ?></h1>
        </header>
        
        <div class="save-status" id="save-status">
            <i class="fas fa-save"></i> <span id="save-status-text">All changes saved</span>
        </div>
        
        <form id="energy-form">
            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_focus_of_day']): ?>
            <div class="pro-field">
                <label for="focus_of_day">
                    Focus of the Day
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['focus_of_day']; ?></span>
                    </span>
                </label>
                <textarea id="focus_of_day" name="focus_of_day"><?php echo htmlspecialchars($report['focus_of_day'] ?? ''); ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_thought_of_day']): ?>
            <div class="pro-field">
                <label for="thought_of_day">
                    Thought of the Day
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['thought_of_day']; ?></span>
                    </span>
                </label>
                <textarea id="thought_of_day" name="thought_of_day"><?php echo htmlspecialchars($report['thought_of_day'] ?? ''); ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_what_im_afraid_of']): ?>
            <div class="pro-field">
                <label for="what_im_afraid_of">
                    What I'm Afraid Of
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['what_im_afraid_of']; ?></span>
                    </span>
                </label>
                <textarea id="what_im_afraid_of" name="what_im_afraid_of"><?php echo htmlspecialchars($report['what_im_afraid_of'] ?? ''); ?></textarea>
            </div>
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
                        <span class="tooltip">
                            <i class="fas fa-info-circle info-icon"></i>
                            <span class="tooltiptext"><?php echo $descriptions[$type]; ?></span>
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
                        <textarea id="<?php echo $type; ?>_comment" name="<?php echo $type; ?>_comment" placeholder="Add a comment..."><?php echo htmlspecialchars($comment); ?></textarea>
                    </div>
                </div>
                <input type="hidden" name="<?php echo $type; ?>_energy" id="<?php echo $type; ?>_energy" value="<?php echo $value; ?>">
            <?php endforeach; ?>

            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_what_to_take_from_surroundings']): ?>
            <div class="pro-field">
                <label for="what_to_take_from_surroundings">
                    What to Take from Surroundings
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['what_to_take_from_surroundings']; ?></span>
                    </span>
                </label>
                <textarea id="what_to_take_from_surroundings" name="what_to_take_from_surroundings"><?php echo htmlspecialchars($report['what_to_take_from_surroundings'] ?? ''); ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_world_picture']): ?>
            <div class="pro-field">
                <label for="world_picture">
                    World Picture
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['world_picture']; ?></span>
                    </span>
                </label>
                <textarea id="world_picture" name="world_picture"><?php echo htmlspecialchars($report['world_picture'] ?? ''); ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($pro_settings['pro_mode'] && $pro_settings['pro_happiness']): ?>
            <div class="pro-field">
                <label for="happiness">
                    Happiness
                    <span class="tooltip">
                        <i class="fas fa-info-circle info-icon"></i>
                        <span class="tooltiptext"><?php echo $descriptions['happiness']; ?></span>
                    </span>
                </label>
                <textarea id="happiness" name="happiness"><?php echo htmlspecialchars($report['happiness'] ?? ''); ?></textarea>
            </div>
            <?php endif; ?>

            <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
            <input type="hidden" name="report_date" value="<?php echo $report['report_date']; ?>">
        </form>

        <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            let saveTimer;
            const saveStatus = $('#save-status');
            const saveStatusText = $('#save-status-text');

            function showSaveStatus(message, isError = false) {
                saveStatusText.text(message);
                saveStatus.removeClass('error').toggleClass('visible', true);
                if (isError) {
                    saveStatus.addClass('error');
                }
                setTimeout(() => {
                    saveStatus.removeClass('visible');
                }, 2000);
            }

            function saveReport() {
                const formData = $('#energy-form').serialize();
                $.ajax({
                    url: 'save_report.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showSaveStatus('All changes saved');
                        } else {
                            showSaveStatus('Error: ' + response.message, true);
                        }
                    },
                    error: function(xhr, status, error) {
                        showSaveStatus('Error saving changes: ' + error, true);
                    }
                });
            }

            function updateBattery(type, value) {
                const battery = $(`#${type}_battery`);
                const level = battery.find('.energy-level');
                level.css('clip-path', `inset(0 ${100 - value}% 0 0)`);
                $(`#${type}_energy`).val(value);
            }

            $('.energy-division').on('click', function() {
                const value = $(this).data('value');
                const battery = $(this).closest('.energy-battery');
                const type = battery.attr('id').replace('_battery', '');
                updateBattery(type, value);
                saveReport();
            });

            $('textarea').on('change keyup', function() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveReport, 1000);
            });

            // Initialize batteries
            <?php foreach ($energyTypes as $type => $label): ?>
            updateBattery('<?php echo $type; ?>', <?php echo $report["{$type}_energy"] ?? 100; ?>);
            <?php endforeach; ?>

            // Tooltip functionality for mobile devices
            $('.info-icon').on('click touchstart', function(e) {
                e.preventDefault();
                const tooltip = $(this).siblings('.tooltiptext');
                $('.tooltiptext').not(tooltip).css('visibility', 'hidden');
                tooltip.css('visibility', tooltip.css('visibility') === 'visible' ? 'hidden' : 'visible');
            });

            // Hide tooltips when clicking outside
            $(document).on('click touchstart', function(e) {
                if (!$(e.target).closest('.tooltip').length) {
                    $('.tooltiptext').css('visibility', 'hidden');
                }
            });
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
                        