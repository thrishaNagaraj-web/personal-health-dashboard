<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

require_once 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 2rem 0;">
    <h2 style="margin-bottom: 1rem;">Calorie Deficit Food Guide</h2>
    <p style="margin-bottom: 2rem; color: var(--text-muted);">
        A collection of high-volume, low-calorie foods to help you stay full while maintaining a calorie deficit.
    </p>

    <div class="card" style="padding: 0;">
        <div class="table-container" style="border-radius: 16px; border: none;">
            <table style="margin: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Calories (per 100g)</th>
                        <th>Protein (per 100g)</th>
                        <th>Why it's great</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Spinach</strong></td>
                        <td>Vegetable</td>
                        <td>23 kcal</td>
                        <td>2.9 g</td>
                        <td>Extremely high volume, packed with vitamins.</td>
                    </tr>
                    <tr>
                        <td><strong>Chicken Breast</strong></td>
                        <td>Protein</td>
                        <td>165 kcal</td>
                        <td>31 g</td>
                        <td>Leanest meat, keeps you full for hours.</td>
                    </tr>
                    <tr>
                        <td><strong>Strawberries</strong></td>
                        <td>Fruit</td>
                        <td>32 kcal</td>
                        <td>0.7 g</td>
                        <td>Sweet craving fix with very few calories.</td>
                    </tr>
                    <tr>
                        <td><strong>Egg Whites</strong></td>
                        <td>Protein</td>
                        <td>52 kcal</td>
                        <td>11 g</td>
                        <td>Pure protein, very low in calories compared to whole eggs.</td>
                    </tr>
                    <tr>
                        <td><strong>Zucchini</strong></td>
                        <td>Vegetable</td>
                        <td>17 kcal</td>
                        <td>1.2 g</td>
                        <td>Great pasta substitute (zoodles); mainly water.</td>
                    </tr>
                    <tr>
                        <td><strong>Greek Yogurt (0% Fat)</strong></td>
                        <td>Dairy / Protein</td>
                        <td>59 kcal</td>
                        <td>10 g</td>
                        <td>Creamy texture, high protein, very versatile.</td>
                    </tr>
                    <tr>
                        <td><strong>Broccoli</strong></td>
                        <td>Vegetable</td>
                        <td>34 kcal</td>
                        <td>2.8 g</td>
                        <td>High fiber keeps digestive tract healthy and full.</td>
                    </tr>
                    <tr>
                        <td><strong>Watermelon</strong></td>
                        <td>Fruit</td>
                        <td>30 kcal</td>
                        <td>0.6 g</td>
                        <td>Over 90% water, allowing for massive portion sizes.</td>
                    </tr>
                    <tr>
                        <td><strong>Cottage Cheese (Low Fat)</strong></td>
                        <td>Dairy / Protein</td>
                        <td>72 kcal</td>
                        <td>11 g</td>
                        <td>Slow-digesting casein protein, ideal for nighttime.</td>
                    </tr>
                    <tr>
                        <td><strong>Cauliflower</strong></td>
                        <td>Vegetable</td>
                        <td>25 kcal</td>
                        <td>1.9 g</td>
                        <td>Extremely versatile (rice/mash substitute).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
