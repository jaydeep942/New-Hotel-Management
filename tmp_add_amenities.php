<?php
$conn = require_once __DIR__ . '/config/db.php';

$amenities = [
    ['Extra Plush Feather Pillow', 'High-loft white goose down pillow for the ultimate neck support and softness.', 0.00, 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Bedding', 'All Day'],
    ['Premium Silk Bedding', 'Hypoallergenic 25-momme mulberry silk sheets for a temperature-regulated sleep.', 45.00, 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Bedding', 'All Day'],
    ['Luxury Grooming Kit', 'Organic charcoal soaps, bamboo toothbrush, and premium sandalwood shaving cream.', 15.00, 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Toiletries', 'All Day'],
    ['Spa Bathrobe & Slippers', 'Waffle-weave 100% Turkish cotton robe and matching cushioned slippers.', 35.00, 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Comfort', 'All Day'],
    ['Scented Candle Set', 'A trio of hand-poured soy candles in Lavender, Sandalwood, and Sea Salt scents.', 25.00, 'https://images.unsplash.com/photo-1603006905393-d2d46e06da8d?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Wellness', 'All Day'],
    ['Artisan Bath Bomb Trio', 'Handcrafted bath bombs made with essential oils, epsom salts, and dried flower petals.', 18.00, 'https://images.unsplash.com/photo-1600857062241-98e5dba7f214?auto=format&fit=crop&q=82&w=800', 'Amenities', 'Wellness', 'All Day']
];

$stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image_url, category, sub_category, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($amenities as $a) {
    $stmt->bind_param("ssdssss", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]);
    $stmt->execute();
}

echo "Amenities added successfully!";
?>
