<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$veg_foods = [
    ['name' => 'Spinach', 'calories' => '23 kcal/100g', 'benefit' => 'Iron, Vitamin K', 'emoji' => '🥦'],
    ['name' => 'Broccoli', 'calories' => '55 kcal/100g', 'benefit' => 'Fiber, Vitamin C', 'emoji' => '🥦'],
    ['name' => 'Lentils (Dal)', 'calories' => '116 kcal/100g', 'benefit' => 'Protein, Iron', 'emoji' => '🥣'],
    ['name' => 'Chickpeas', 'calories' => '164 kcal/100g', 'benefit' => 'Protein, Fiber', 'emoji' => '🥣'],
    ['name' => 'Brown Rice', 'calories' => '216 kcal/cup', 'benefit' => 'Complex Carbs', 'emoji' => '🍚'],
    ['name' => 'Quinoa', 'calories' => '222 kcal/cup', 'benefit' => 'Complete Protein', 'emoji' => '🥗'],
    ['name' => 'Sweet Potato', 'calories' => '86 kcal/100g', 'benefit' => 'Vitamin A, Fiber', 'emoji' => '🍠'],
    ['name' => 'Greek Yogurt', 'calories' => '59 kcal/100g', 'benefit' => 'Probiotics, Calcium', 'emoji' => '🥛'],
    ['name' => 'Almonds', 'calories' => '579 kcal/100g', 'benefit' => 'Healthy Fats, Vitamin E', 'emoji' => '🥜'],
    ['name' => 'Banana', 'calories' => '89 kcal/100g', 'benefit' => 'Potassium, Energy', 'emoji' => '🍌'],
    ['name' => 'Oats', 'calories' => '389 kcal/100g', 'benefit' => 'Fiber, Beta-Glucan', 'emoji' => '🥣'],
    ['name' => 'Paneer', 'calories' => '265 kcal/100g', 'benefit' => 'Calcium, Protein', 'emoji' => '🧀'],
    ['name' => 'Avocado', 'calories' => '160 kcal/100g', 'benefit' => 'Healthy Fats, Folate', 'emoji' => '🥑'],
    ['name' => 'Tofu', 'calories' => '76 kcal/100g', 'benefit' => 'Plant Protein, Calcium', 'emoji' => '🧊'],
    ['name' => 'Blueberries', 'calories' => '57 kcal/100g', 'benefit' => 'Antioxidants', 'emoji' => '🫐'],
];

$non_veg_foods = [
    ['name' => 'Chicken Breast', 'calories' => '165 kcal/100g', 'benefit' => 'Lean Protein', 'emoji' => '🍗'],
    ['name' => 'Salmon', 'calories' => '208 kcal/100g', 'benefit' => 'Omega-3, Protein', 'emoji' => '🐟'],
    ['name' => 'Eggs', 'calories' => '155 kcal/100g', 'benefit' => 'Complete Protein, Choline', 'emoji' => '🥚'],
    ['name' => 'Tuna', 'calories' => '132 kcal/100g', 'benefit' => 'Protein, Selenium', 'emoji' => '🐟'],
    ['name' => 'Turkey Breast', 'calories' => '135 kcal/100g', 'benefit' => 'Lean Protein, B12', 'emoji' => '🦃'],
    ['name' => 'Sardines', 'calories' => '208 kcal/100g', 'benefit' => 'Omega-3, Calcium', 'emoji' => '🐟'],
    ['name' => 'Shrimp', 'calories' => '99 kcal/100g', 'benefit' => 'Low Fat, Protein', 'emoji' => '🍤'],
    ['name' => 'Cod Fish', 'calories' => '82 kcal/100g', 'benefit' => 'Low Cal, Protein', 'emoji' => '🐟'],
    ['name' => 'Lamb', 'calories' => '294 kcal/100g', 'benefit' => 'Iron, Zinc', 'emoji' => '🥩'],
    ['name' => 'Duck Breast', 'calories' => '201 kcal/100g', 'benefit' => 'Iron, Protein', 'emoji' => '🦆'],
    ['name' => 'Mackerel', 'calories' => '205 kcal/100g', 'benefit' => 'Omega-3, Vitamin D', 'emoji' => '🐟'],
    ['name' => 'Crab', 'calories' => '97 kcal/100g', 'benefit' => 'Lean Protein, Zinc', 'emoji' => '🦀'],
    ['name' => 'Lean Beef', 'calories' => '250 kcal/100g', 'benefit' => 'Iron, Creatine', 'emoji' => '🥩'],
    ['name' => 'Chicken Liver', 'calories' => '172 kcal/100g', 'benefit' => 'Iron, Vitamin A', 'emoji' => '🍗'],
    ['name' => 'Oysters', 'calories' => '68 kcal/100g', 'benefit' => 'Zinc, Vitamin B12', 'emoji' => '🦪'],
];

require_once 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 2rem 0;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="margin-bottom: 1rem;">🥗 Food Guide</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Discover the best diet-friendly foods to reach your goals.</p>
    </div>

    <!-- Search & Filter Area -->
    <div style="background: var(--surface); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2rem; display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="position: relative;">
            <input type="text" id="foodSearch" class="form-control" placeholder="Search foods (e.g. Protein, Chicken, Spinach)..." style="padding-left: 2.5rem;">
            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 1.2rem;">🔍</span>
        </div>
        
        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
            <button class="btn btn-primary filter-btn active" data-filter="all">All Foods</button>
            <button class="btn btn-outline filter-btn" data-filter="veg">🥦 Vegetarian</button>
            <button class="btn btn-outline filter-btn" data-filter="nonveg">🍗 Non-Vegetarian</button>
        </div>
    </div>

    <div id="foodGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: start;">
        
        <!-- Veg Column -->
        <div class="food-column" id="vegColumn">
            <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🥦</span>
                <h2 style="margin: 0; color: white; font-size: 1.4rem;">Vegetarian Best Diet Foods</h2>
            </div>
            
            <div class="column-content" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($veg_foods as $food): ?>
                <div class="food-card" data-type="veg" data-name="<?= strtolower($food['name']) ?>" data-benefit="<?= strtolower($food['benefit']) ?>">
                    <div class="food-card-header">
                        <span class="food-emoji"><?= $food['emoji'] ?></span>
                        <span class="food-name"><?= $food['name'] ?></span>
                        <span class="cal-pill"><?= $food['calories'] ?></span>
                    </div>
                    <div class="food-benefit"><?= $food['benefit'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Non-Veg Column -->
        <div class="food-column" id="nonVegColumn">
            <div style="background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🍗</span>
                <h2 style="margin: 0; color: white; font-size: 1.4rem;">Non-Vegetarian Best Diet Foods</h2>
            </div>

            <div class="column-content" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($non_veg_foods as $food): ?>
                <div class="food-card" data-type="nonveg" data-name="<?= strtolower($food['name']) ?>" data-benefit="<?= strtolower($food['benefit']) ?>">
                    <div class="food-card-header">
                        <span class="food-emoji"><?= $food['emoji'] ?></span>
                        <span class="food-name"><?= $food['name'] ?></span>
                        <span class="cal-pill"><?= $food['calories'] ?></span>
                    </div>
                    <div class="food-benefit"><?= $food['benefit'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<style>
.food-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.food-card[data-type="veg"] { border-left: 4px solid #10b981; }
.food-card[data-type="nonveg"] { border-left: 4px solid #f97316; }

.food-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-color: var(--primary);
}

.food-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.food-emoji { font-size: 1.2rem; }
.food-name { font-weight: 700; font-size: 1.1rem; color: var(--text-main); }

.cal-pill {
    background: var(--background);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 999px;
    border: 1px solid var(--border);
    margin-left: auto;
}

.food-benefit {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.4;
}

@media (max-width: 768px) {
    #foodGrid { grid-template-columns: 1fr; }
    .food-column { width: 100%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('foodSearch');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const foodCards = document.querySelectorAll('.food-card');
    const vegColumn = document.getElementById('vegColumn');
    const nonVegColumn = document.getElementById('nonVegColumn');

    function filterFoods() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;

        let hasVeg = false;
        let hasNonVeg = false;

        foodCards.forEach(card => {
            const name = card.dataset.name;
            const benefit = card.dataset.benefit;
            const type = card.dataset.type;

            const matchesSearch = name.includes(searchTerm) || benefit.includes(searchTerm);
            const matchesFilter = activeFilter === 'all' || activeFilter === type;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'block';
                if (type === 'veg') hasVeg = true;
                if (type === 'nonveg') hasNonVeg = true;
            } else {
                card.style.display = 'none';
            }
        });

        // Hide entire columns if no matches found in them
        vegColumn.style.display = hasVeg ? 'block' : 'none';
        nonVegColumn.style.display = hasNonVeg ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterFoods);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline');
            filterFoods();
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
