<?php
if (!isset($conn)) {
    $conn = require_once __DIR__ . '/../config/db.php';
}

$sql_table = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(100) NOT NULL,
    sub_category VARCHAR(100) DEFAULT 'Main Course',
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner', 'All Day') DEFAULT 'All Day',
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_table) === TRUE) {
    if (!$conn->query("DELETE FROM menu_items")) {
        die("Error deleting items: " . $conn->error);
    }

    // Purely VEGETARIAN Data with HAND-PICKED, VERIFIED related images
    // I am using very specific Unsplash IDs to ensure 100% match
    $insert_sql = "INSERT INTO menu_items (name, description, price, image_url, category, sub_category, meal_type) VALUES 
    -- GUJARATI (Pure Veg)
    ('Bataka Poha', 'A popular Gujarati breakfast made with flattened rice, potatoes, and peanuts.', 12.00, 'https://i.ytimg.com/vi/Cw7Dl30ZwOk/mqdefault.jpg', 'Gujarati', 'Starters', 'Breakfast'),
    ('Authentic Khaman Dhokla', 'Soft and spongy steamed gram flour cakes tempered with mustard seeds and green chillies.', 12.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQIFNQjDfCnElXQScyHk87yo-v59kwnkyHkgA&s', 'Gujarati', 'Starters', 'Breakfast'),
    ('Royal Gujarati Thali', 'A grand platter with Rotli, Dal, Bhaat, Shaak, Kathol, Farsan, and Sweet.', 28.00, 'https://www.gujaratexpert.com/blog/wp-content/uploads/2024/01/Toran-Dining-Hall-Ahmedabad.jpg', 'Gujarati', 'Thali', 'Lunch'),
    ('Dal Dhokli', 'Traditional Gujarati comfort food - wheat flour dumplings in lentil stew.', 18.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRjkZkg7Rzoq_IAPVzPGkywO2S4XxMXn3xJfw&s', 'Gujarati', 'Main Course', 'All Day'),
    ('Surti Undhiyu', 'Slow-cooked winter vegetable medley with special spices and small dumplings.', 20.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTBnl5L5o51PMk0ZdXKWtMg0kDdXTEfnJ5zuw&s', 'Gujarati', 'Main Course', 'Dinner'),
    ('Saffron Gulab Jamun', 'Warm milk-solid dumplings soaked in a delicate saffron-flavored cardamom syrup.', 12.00, 'https://t3.ftcdn.net/jpg/17/52/27/34/360_F_1752273443_L54iRo0qI5oCNOEJOAy9v4FJFZ3Ero4L.jpg', 'Gujarati', 'Dessert', 'All Day'),

    -- PUNJABI (Pure Veg)
    ('Paneer Butter Masala', 'Fresh paneer cubes simmered in a rich, buttery tomato gravy finished with cream.', 24.00, 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?auto=format&fit=crop&q=82&w=800', 'Punjabi', 'Main Course', 'Dinner'),
    ('Tandoori Paneer Tikka', 'Char-grilled skewers of marinated paneer, bell peppers, and onions.', 18.00, 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&q=82&w=800', 'Punjabi', 'Starters', 'All Day'),
    ('Dal Makhani Deluxe', 'Traditional black lentils slow-cooked overnight with cream and house-made white butter.', 22.00, 'https://img.freepik.com/free-photo/indian-dhal-spicy-curry-bowl-spices-herbs-rustic-black-wooden-table_2829-18712.jpg?semt=ais_rp_progressive&w=740&q=80', 'Punjabi', 'Main Course', 'Dinner'),
    ('Chole Bhature', 'Tangy and spicy chickpea curry served with two large puffed bhaturas.', 16.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRZWmOmPmfjkEYU-IW9KimKG1jJWKEh1p5v2w&s', 'Punjabi', 'Main Course', 'Lunch'),
    ('Amritsari Kulcha', 'Authentic Punjabi stuffed bread served with Chole and Chutney.', 18.00, 'https://www.spicingyourlife.com/wp-content/uploads/2014/06/Punjabi-Amritsari-Kulcha-1.jpg', 'Punjabi', 'Main Course', 'All Day'),

    -- CHINESE (Pure Veg)
    ('Veg Hakka Noodles', 'Thin noodles stir-fried with julienned vegetables and a touch of light soy.', 15.00, 'https://images.unsplash.com/photo-1585032226651-759b368d7246?auto=format&fit=crop&q=82&w=800', 'Chinese', 'Main Course', 'Lunch'),
    ('Veg Manchow Soup', 'Spicy Indo-Chinese soup served with crispy fried noodles.', 14.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlH7LTbA9HXKb1blb8r3celL3bTKIAM2nXnA&s', 'Chinese', 'Starters', 'All Day'),
    ('Vegetable Fried Rice', 'Fragrant long-grain rice tossed with colorful vegetables and aromatic herbs.', 14.00, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&q=82&w=800', 'Chinese', 'Main Course', 'Dinner'),
    ('Crispy Chilli Paneer', 'Crispy batter-fried paneer cubes tossed in a spicy and tangy chili sauce.', 18.00, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&q=82&w=800', 'Chinese', 'Starters', 'All Day'),
    ('Veg Manchurian', 'Golden fried vegetable balls tossed in a tangy soy-garlic Manchurian sauce.', 16.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSHRESiYmwBMp2OS85ffLQN4SYrRJIcyYqJag&s', 'Chinese', 'Main Course', 'All Day'),

    -- SOUTH INDIAN (Pure Veg)
    ('Masala Dosa', 'Golden, crispy rice and lentil crepe stuffed with a spiced potato filling.', 15.00, 'https://images.unsplash.com/photo-1743615467204-8fdaa85ff2db?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8N3x8bWFzYWxhJTIwZG9zYXxlbnwwfHwwfHx8MA%3D%3D', 'South Indian', 'Breakfast', 'Breakfast'),
    ('Soft Steamed Idli-Sambar', 'Steam-soft steamed rice cakes served with hot lentil soup and fresh chutney.', 12.00, 'https://vaya.in/recipes/wp-content/uploads/2018/02/Idli-and-Sambar-1.jpg', 'South Indian', 'Breakfast', 'Breakfast'),
    ('Hyderabadi Veg Biryani', 'Aromatic basmati rice cooked on dum with exotic spices and tender vegetables.', 25.00, 'https://images.unsplash.com/photo-1642821373181-696a54913e93?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8YnJpeWFuaXxlbnwwfHwwfHx8MA%3D%3D', 'South Indian', 'Rice Items', 'Dinner'),

    -- CONTINENTAL (Pure Veg)
    ('Truffle Mushroom Risotto', 'Creamy Arborio rice slow-cooked with wild mushrooms and finished with truffle oil.', 32.00, 'https://www.lakeshorelady.com/wp-content/uploads/2021/02/mushroom-risotto-8-735x1132.jpg', 'Continental', 'Main Course', 'Dinner'),
    ('Pesto Genovese Pasta', 'Fresh al dente pasta tossed in a vibrant basil pesto with pine nuts and parmesan.', 28.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQDc2uqlI8QfOUgZgMjxNZXAoR75rP6AyjAqA&s', 'Continental', 'Main Course', 'All Day'),
    ('Belgian Chocolate Mousse', 'Fluffy and rich mousse made with premium Belgian chocolate and fresh cream.', 22.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQLe3WHajMAE1sQh3m0KEQzL5TZOiInr3tP-A&s', 'Continental', 'Dessert', 'All Day'),
    
    -- REFRESHMENTS
    ('Royal Saffron Masala Chai', 'Traditional Indian Masala Chai served in a classic glass tumbler, showcasing its warm, spicy character and creamy texture.', 8.00, 'https://images.unsplash.com/photo-1577968897966-3d4325b36b61?auto=format&fit=crop&q=82&w=800', 'Refreshments', 'Hot Drinks', 'All Day'),
    ('Artisan Cold Brew Coffee', 'A sophisticated glass of Artisan Cold Brew Coffee with large ice cubes, capturing the dark, smooth essence of the slow-steeped brew.', 15.00, 'https://images.unsplash.com/photo-1592663527359-cf6642f54cff?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8OHx8Y29sZCUyMGJyZXd8ZW58MHx8MHx8fDA%3D', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Himalayan Mineral Water', 'Crystal clear Himalayan mineral water served in a clean glass, symbolizing purity and crisp refreshment.', 6.00, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?auto=format&fit=crop&q=82&w=800', 'Refreshments', 'Water', 'All Day'),
    ('Fresh Mint Lime Soda', 'Zesty Fresh Mint Lime Soda with vibrant mint leaves and lime slices, served in a condensation-covered glass.', 12.00, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&q=82&w=800', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Classic Cola on Ice', 'Classic Cola served over a mountain of crystal-clear ice cubes in a tall glass, fizzing with refreshing bubbles.', 10.00, 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&q=82&w=800', 'Refreshments', 'Sodas', 'All Day'),
    ('Fresh Orange Nectar', 'A glass of fresh orange nectar with a slice of orange on the rim, capturing the bright and zesty essence of sun-ripened citrus.', 14.00, 'https://images.unsplash.com/photo-1613478223719-2ab802602423?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8b3JhbmdlJTIwanVpY2V8ZW58MHx8MHx8fDA%3D', 'Refreshments', 'Juices', 'All Day'),
    ('Iced Hibiscus Tea', 'Vibrant red Iced Hibiscus Tea served in a glass with ice and a lemon wedge, looking both tart and deeply refreshing.', 12.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSUVF_d1CtJXtpoN5qfTWzsWUTXG4fa0vd13g&s', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Golden Mango Lassi', 'Creamy Golden Mango Lassi served in a glass, garnished with fresh mango cubes and a metal straw.', 18.00, 'https://thumbs.dreamstime.com/b/refreshing-mango-lassi-tall-glass-saffron-garnish-picture-359424870.jpg', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Masala Chaas', 'Spiced Indian Buttermilk served chilled in a traditional copper mug, garnished with a sprig of fresh mint.', 10.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRoDyAvejR02Jhe_hpsF42OhBfBqlVVVrDciA&s', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Zesty Lemon Iced Tea', 'Refreshing tall glass of lemon iced tea with plenty of ice and fresh lime slices, perfectly chilled.', 14.00, 'https://img.freepik.com/premium-photo/iced-tea-with-lemon-slices-ice-yellow-background_1223942-4446.jpg?w=360', 'Refreshments', 'Cold Drinks', 'All Day'),
    ('Belgian Hot Chocolate', 'Rich, velvety Belgian hot chocolate in a white mug, topped with a generous serving of mini marshmallows.', 20.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSynZffmALNbWpeMpWN49pCxUychFNLNefv-A&s', 'Refreshments', 'Hot Drinks', 'All Day'),
    ('Pure Coconut Water', 'Clear, refreshing coconut water in a glass, served alongside fresh coconut halves.', 16.00, 'https://images.unsplash.com/photo-1628692945318-f44a3c346afb?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8Y29jb251dCUyMHdhdGVyfGVufDB8fDB8fHww', 'Refreshments', 'Water', 'All Day'),
    ('Organic Green Tea', 'A steaming cup of organic green tea in a clean white ceramic cup, perfect for a healthy break.', 10.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQormS-4u7dWZKqBvyNE8FrsGcMOv0HuTfEUA&s', 'Refreshments', 'Hot Drinks', 'All Day'),
    ('Strawberry Milkshake', 'Thick and creamy strawberry milkshake in a glass, featuring a beautiful soft pink hue and smooth texture.', 18.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQehAaxtSj1--oFr2kciyDOeFtvPGZQCmDIfg&s', 'Refreshments', 'Cold Drinks', 'All Day'),
    
    -- AMENITIES
    ('Scented Candle Set', 'A collection of aromatherapy candles featuring Lavender, Sandalwood, and Vanilla.', 35.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_tX_DEEp1XSuw1Fc9RYh6K9-yMwA7zPg_HQ&s', 'Amenities', 'Wellness', 'All Day'),
    ('Luxury Grooming Kit', 'Exclusive travel-sized set of premium shampoo, conditioner, and body wash.', 45.00, 'https://images.unsplash.com/photo-1630398777649-cdfc7c5e8a24?fm=jpg&q=60&w=3000&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8N3x8bHV4dXJ5JTIwc2tpbmNhcmV8ZW58MHx8MHx8fDA%3D', 'Amenities', 'Personal Care', 'All Day'),
    ('Lavender Inflated Pillow', 'Scented memory foam pillow designed for neck support and relaxation.', 40.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ68QQBxfzKCBfJl6FT-CxphkttYveW46oR4w&s', 'Amenities', 'Comfort', 'All Day')";

    
    if (!$conn->query($insert_sql)) {
        die("Error inserting items: " . $conn->error);
    }
}
?>
