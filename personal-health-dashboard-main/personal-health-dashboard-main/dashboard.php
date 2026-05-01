<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
// User Details (Height for BMI)
$stmt = $pdo->prepare("SELECT height FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$height = $stmt->fetchColumn();

// Latest Weight
$stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_weight = $stmt->fetchColumn() ?: '--';

$bmi = null;
if ($height && $height > 0 && $latest_weight !== '--') {
    $height_m = $height / 100;
    $bmi = round($latest_weight / ($height_m * $height_m), 1);
}
// Today's Water
$stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_water = $stmt->fetchColumn() ?: 0;

// Today's Calories
$stmt = $pdo->prepare("SELECT SUM(calories) FROM calories_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$today_calories = $stmt->fetchColumn() ?: 0;

// Latest Exercise
$stmt = $pdo->prepare("SELECT activity, duration_mins FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_exercise = $stmt->fetch();

require_once 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 2rem 0;">
    <h2 style="margin-bottom: 2rem;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        
        <div class="card" style="text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="color: #8b5cf6;">⚖️ Latest Weight</h3>
                <p style="font-size: 2.5rem; margin: 1rem 0; color: #8b5cf6;">
                    <?= htmlspecialchars($latest_weight) ?> <?= $latest_weight !== '--' ? '<span style="font-size: 1rem;">kg</span>' : '' ?>
                </p>
                <?php if ($bmi): ?>
                    <div style="background-color: var(--bg-color); padding: 0.5rem; border-radius: 8px; margin-bottom: 1rem;">
                        <span style="font-weight: 600;">BMI: <?= htmlspecialchars($bmi) ?></span>
                    </div>
                <?php else: ?>
                    <div style="background-color: var(--bg-color); padding: 0.5rem; border-radius: 8px; margin-bottom: 1rem;">
                        <a href="profile.php" style="color: var(--text-color); font-size: 0.9rem; text-decoration: underline;">Add height in Profile for BMI</a>
                    </div>
                <?php endif; ?>
            </div>
            <a href="weight-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem; border-color: #8b5cf6; color: #8b5cf6;">View History</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Water Today</h3>
            <p style="font-size: 2.5rem; margin: 1rem 0; color: #3498db;">
                <?= htmlspecialchars($today_water) ?> <span style="font-size: 1rem;">ml</span>
            </p>
            <a href="water-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Drink More</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Calories Today</h3>
            <p style="font-size: 2.5rem; margin: 1rem 0; color: #e67e22;">
                <?= htmlspecialchars($today_calories) ?> <span style="font-size: 1rem;">kcal</span>
            </p>
            <a href="calories-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Log Meal</a>
        </div>

        <div class="card" style="text-align: center;">
            <h3>Latest Exercise</h3>
            <p style="font-size: 1.5rem; margin: 1.5rem 0; color: #2ecc71;">
                <?php if ($latest_exercise): ?>
                    <?= htmlspecialchars($latest_exercise['activity']) ?><br>
                    <span style="font-size: 1rem; color: #666;"><?= htmlspecialchars($latest_exercise['duration_mins']) ?> mins</span>
                <?php else: ?>
                    <span style="font-size: 1.5rem; color: #aaa;">--</span>
                <?php endif; ?>
            </p>
            <a href="exercise-history.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Add Activity</a>
        </div>

    </div>
</div>

<!-- Chatbot Widget -->
<div class="chatbot-widget">
    <div class="chatbot-toggle" id="chatbotToggle">💬</div>
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chat-header">
            Health Assistant 
            <button class="chat-close" id="chatbotClose">&times;</button>
        </div>
        <div class="chat-messages" id="chatbotMessages">
            <div class="msg-bubble msg-bot">
                Hello <?= htmlspecialchars($_SESSION['username']) ?>! 👋 I'm your Health Assistant. You can text me about calories, water, weight, or exercise.
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatbotInput" placeholder="Type a message..." autocomplete="off" />
            <button class="chat-send" id="chatbotSend">Send</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById('chatbotToggle');
    const closeBtn = document.getElementById('chatbotClose');
    const chatWindow = document.getElementById('chatbotWindow');
    const inputField = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const messagesContainer = document.getElementById('chatbotMessages');

    function toggleChat() {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            inputField.focus();
        }
    }

    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', () => chatWindow.classList.remove('active'));

    function appendMessage(text, isUser) {
        const div = document.createElement('div');
        div.className = 'msg-bubble ' + (isUser ? 'msg-user' : 'msg-bot');
        div.textContent = text;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    const foodDatabase = {
        'chicken': { cal: 165, protein: 31 },
        'rice': { cal: 130, protein: 2.7 },
        'egg': { cal: 143, protein: 12.6 },
        'apple': { cal: 52, protein: 0.3 },
        'banana': { cal: 89, protein: 1.1 },
        'beef': { cal: 250, protein: 26 },
        'salmon': { cal: 208, protein: 20 },
        'oats': { cal: 389, protein: 16.9 },
        'broccoli': { cal: 34, protein: 2.8 },
        'potato': { cal: 86, protein: 1.7 }
    };

    function getBotResponse(input) {
        const lowerInput = input.toLowerCase();
        
        // Check for food and grams (e.g. "150g chicken" or "chicken 150 grams")
        const gramMatch = lowerInput.match(/(\d+)\s*(g|grams)/);
        if (gramMatch) {
            const grams = parseInt(gramMatch[1]);
            for (let food in foodDatabase) {
                if (lowerInput.includes(food)) {
                    const nutrition = foodDatabase[food];
                    const cals = Math.round((nutrition.cal / 100) * grams);
                    const protein = ((nutrition.protein / 100) * grams).toFixed(1);
                    return `For ${grams}g of ${food}, you're looking at about ${cals} kcal and ${protein}g of protein!`;
                }
            }
            return `I see you're asking about ${grams}g of food, but I don't have that specific item in my database yet! Try common foods like chicken, rice, beef, oats, or salmon.`;
        }

        if (lowerInput.includes('water')) {
            return "Aim for at least 2000ml to 3000ml of water daily based on activity level. Dehydration masks itself as hunger sometimes! Check the Water section to log today's intake.";
        } else if (lowerInput.includes('calorie') || lowerInput.includes('food')) {
            return "To lose weight, you need a calorie deficit. To gain muscle, a calorie surplus. Check out the new 'Food Guide' in the menu for low-calorie filling foods!";
        } else if (lowerInput.includes('weight')) {
            return "Weight isn't everything! Track your average weekly weight instead of day-to-day to see the real trend.";
        } else if (lowerInput.includes('exercise') || lowerInput.includes('workout')) {
            return "Get at least 150 minutes of moderate aerobic activity or 75 minutes of vigorous activity a week, plus strength training twice a week!";
        } else if (lowerInput.includes('hello') || lowerInput.includes('hi')) {
            return "Hi there! Feel free to ask me anything about your tracking metrics, or ask me about the calories in a specific food (e.g., '150g chicken').";
        } else {
            return "I'm a simple assistant. Try asking me about 'water', 'calories', 'weight', or 'exercise'! You can also ask me things like 'how much protein in 200g chicken'.";
        }
    }

    function handleSend() {
        const text = inputField.value.trim();
        if (text === '') return;
        
        appendMessage(text, true);
        inputField.value = '';

        // Simulate typing delay
        setTimeout(() => {
            const response = getBotResponse(text);
            appendMessage(response, false);
        }, 500);
    }

    sendBtn.addEventListener('click', handleSend);
    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') handleSend();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
