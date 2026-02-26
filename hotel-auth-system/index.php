<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Luxe Hotel | Experience Luxury & Comfort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Animate On Scroll Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#D4AF37',
                        cream: '#FDFBF7',
                        maroon: '#6A1E2D',
                        teal: '#0D9488',
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                        playfair: ['Playfair Display', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        .glass-nav {
            background: rgba(248, 245, 240, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(201, 161, 74, 0.2);
        }

        .hero-gradient {
            background: linear-gradient(135deg, rgba(106, 30, 45, 0.4) 0%, rgba(201, 161, 74, 0.4) 100%);
        }

        .room-card:hover .room-overlay {
            opacity: 1;
        }

        .service-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #C9A14A;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Modal transition */
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
    </style>
</head>
<body class="font-outfit bg-cream text-gray-800 overflow-x-hidden">

    <!-- STICKY NAVBAR -->
    <nav class="fixed top-0 w-full z-50 glass-nav transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="#" class="flex items-center gap-2">
                <span class="text-2xl font-playfair font-bold text-maroon tracking-tighter">Grand<span class="text-gold">Luxe</span></span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#home" class="nav-link text-sm font-semibold uppercase tracking-wider text-maroon">Home</a>
                <a href="#about" class="nav-link text-sm font-semibold uppercase tracking-wider text-maroon">About Us</a>
                <a href="#rooms" class="nav-link text-sm font-semibold uppercase tracking-wider text-maroon">Rooms</a>
                
                <a href="#services" class="nav-link text-sm font-semibold uppercase tracking-wider text-maroon">Services</a>

                <a href="#contact" class="nav-link text-sm font-semibold uppercase tracking-wider text-maroon">Contact</a>
            </div>

            <!-- Auth Buttons -->
            <div class="hidden md:flex items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="customer-dashboard.php" class="bg-maroon text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-opacity-90 transition-all shadow-lg flex items-center gap-2">
                        <i class="fas fa-user-circle"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-maroon font-bold text-sm hover:text-gold transition-colors">Login</a>
                    <a href="register.html" class="bg-gold text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-maroon transition-all shadow-lg shadow-gold/20">Register</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Toggle -->
            <button class="md:hidden text-maroon hover:text-gold transition-colors outline-none focus:outline-none" id="mobileMenuBtn">
                <i class="fas fa-bars text-2xl" id="menuIcon"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div class="md:hidden hidden bg-white border-t border-cream" id="mobileMenu">
            <div class="flex flex-col p-6 gap-4">
                <a href="#home" class="text-maroon font-bold uppercase tracking-widest text-sm">Home</a>
                <a href="#about" class="text-maroon font-bold uppercase tracking-widest text-sm">About Us</a>
                <a href="#rooms" class="text-maroon font-bold uppercase tracking-widest text-sm">Rooms</a>
                <a href="#services" class="text-maroon font-bold uppercase tracking-widest text-sm">Services</a>
                <a href="#contact" class="text-maroon font-bold uppercase tracking-widest text-sm">Contact</a>
                <hr class="border-cream">
                <div class="flex flex-col gap-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="customer-dashboard.php" class="bg-maroon text-white text-center py-3 rounded-xl font-bold">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="text-maroon text-center py-3 border border-maroon rounded-xl font-bold">Login</a>
                        <a href="register.html" class="bg-gold text-white text-center py-3 rounded-xl font-bold">Register Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="home" class="relative h-screen flex items-center justify-center overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&q=80&w=1920" 
                 alt="Luxury Hotel Lobby" class="w-full h-full object-cover">
            <!-- Removed gradient overlay to show clear photo -->
        </div>

        <!-- Content -->
        <div class="container mx-auto px-6 relative z-10 text-center md:text-left">
            <div class="max-w-3xl">
                <span class="inline-block bg-white/10 backdrop-blur-md text-white px-5 py-2 rounded-full text-xs font-bold uppercase tracking-[5px] mb-6" data-aos="fade-down">
                    Welcome to Elegance
                </span>
                <h1 class="text-4xl sm:text-5xl md:text-8xl font-playfair font-bold text-white mb-6 leading-tight drop-shadow-[0_5px_15px_rgba(0,0,0,0.5)]" data-aos="fade-right" data-aos-delay="200">
                    Experience <span class="text-gold italic">Luxury</span> & Comfort
                </h1>
                <p class="text-lg md:text-xl text-white mb-10 max-w-xl font-medium leading-relaxed drop-shadow-md" data-aos="fade-right" data-aos-delay="400">
                    Discover a sanctuary of sophistication where every detail is curated for your ultimate relaxation and refined taste.
                </p>
                <div class="flex flex-col md:flex-row gap-6" data-aos="fade-up" data-aos-delay="600">
                    <a href="#rooms" class="bg-gold text-white px-10 py-5 rounded-full font-bold text-sm uppercase tracking-widest hover:bg-white hover:text-gold transition-all duration-300 shadow-2xl">
                        Explore Rooms
                    </a>
                    <a href="<?php echo isset($_SESSION['user_id']) ? '#rooms' : 'register.html'; ?>" class="bg-transparent border-2 border-white/50 text-white px-10 py-5 rounded-full font-bold text-sm uppercase tracking-widest hover:bg-white/10 transition-all duration-300 backdrop-blur-sm">
                        Book Now
                    </a>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
            <a href="#about" class="text-white opacity-50"><i class="fas fa-chevron-down text-2xl"></i></a>
        </div>
    </section>

    <!-- ABOUT SECTION (MOVED TO SECOND POSITION) -->
    <section id="about" class="py-24 bg-cream relative overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <!-- Image Side -->
                <div class="lg:w-1/2 relative" data-aos="fade-right">
                    <div class="relative z-10 rounded-3xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&q=80&w=1000" 
                             alt="Luxury Experience" class="w-full h-[600px] object-cover">
                    </div>
                    <!-- Accent Box -->
                    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-maroon rounded-3xl z-0 hidden lg:block"></div>
                    <div class="absolute -top-10 -left-10 w-64 h-64 border-8 border-gold/20 rounded-3xl z-0 hidden lg:block"></div>
                </div>

                <!-- Text Side -->
                <div class="lg:w-1/2" data-aos="fade-left">
                    <span class="text-maroon font-bold uppercase tracking-[4px] text-xs">Our Heritage</span>
                    <h2 class="text-4xl md:text-6xl font-playfair font-bold text-maroon mt-4 mb-8">Legacy of <br><span class="text-gold italic">Pure Excellence</span></h2>
                    
                    <p class="text-gray-600 leading-relaxed mb-8 font-light text-lg">
                        Founded on the principles of timeless elegance and unparalleled hospitality, Grand Luxe Hotel has been the premier destination for discerning travelers for decades. Our mission is to create moments of profound beauty and lasting memories.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div data-aos="zoom-in" data-aos-delay="200">
                            <h4 class="text-gold font-bold uppercase tracking-widest text-sm mb-4">Our Mission</h4>
                            <p class="text-gray-500 text-sm leading-relaxed">To redefine the art of luxury living through innovation, personalized service, and architectural brilliance.</p>
                        </div>
                        <div data-aos="zoom-in" data-aos-delay="400">
                            <h4 class="text-gold font-bold uppercase tracking-widest text-sm mb-4">Why Choose Us</h4>
                            <p class="text-gray-500 text-sm leading-relaxed">Curated experiences, prime locations, and a commitment to perfection in every single detail of your stay.</p>
                        </div>
                    </div>

                    <button id="ourStoryBtn" class="mt-12 bg-maroon text-white px-12 py-5 rounded-full font-bold text-sm uppercase tracking-widest hover:bg-gold transition-colors shadow-xl">
                        Our Story
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ROOMS SECTION -->
    <section id="rooms" class="py-24 bg-white relative overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-gold font-bold uppercase tracking-[4px] text-xs">Our Sanctuaries</span>
                <h2 class="text-4xl md:text-5xl font-playfair font-bold text-maroon mt-4">Luxurious Accommodations</h2>
                <div class="w-24 h-1 bg-gold mx-auto mt-6 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php
                $rooms = [
                    ['id' => 'ac', 'name' => 'Premium AC Suite', 'price' => '4500', 'img' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', 'Luxury View', 'AC']],
                    ['id' => 'non-ac', 'name' => 'Classic Non-AC', 'price' => '2500', 'img' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', 'Private Balcony', 'Desk']],
                    ['id' => 'single', 'name' => 'Serene Single', 'price' => '1800', 'img' => 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', 'Compact Luxury', 'Modern Bath']],
                    ['id' => 'double', 'name' => 'Deluxe Double', 'price' => '3200', 'img' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', 'Spacious AC', 'Coffee Maker']],
                    ['id' => 'family', 'name' => 'Grand Family', 'price' => '6500', 'img' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', '2 King Beds', 'Kitchenette']],
                    ['id' => 'penthouse', 'name' => 'Royal Penthouse', 'price' => '9500', 'img' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&q=80&w=800', 'features' => ['WiFi', 'TV', 'Chef Service', 'Private Pool', 'Panoramic View']]
                ];

                foreach($rooms as $index => $room):
                    $isLoggedIn = isset($_SESSION['user_id']);
                    $bookingLink = $isLoggedIn ? "booking.php?room=" . $room['id'] : "register.html";
                ?>
                <div class="group bg-cream rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 border border-gray-100" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 200; ?>">
                    <!-- Image -->
                    <div class="relative h-72 overflow-hidden">
                        <img src="<?php echo $room['img']; ?>" alt="<?php echo $room['name']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-4 py-2 rounded-2xl font-bold text-maroon">
                            ₹<?php echo $room['price']; ?><span class="text-[10px] text-gray-500 font-normal"> / Night</span>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-8">
                        <h3 class="text-2xl font-playfair font-bold text-maroon mb-4"><?php echo $room['name']; ?></h3>
                        <ul class="space-y-3 mb-8">
                            <?php foreach($room['features'] as $f): ?>
                                <li class="text-sm text-gray-600 flex items-center gap-3">
                                    <i class="fas fa-check text-teal text-xs"></i> <?php echo $f; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo $bookingLink; ?>" 
                           class="block w-full text-center py-4 rounded-2xl border-2 border-gold text-gold font-bold uppercase tracking-widest hover:bg-gold hover:text-white transition-all duration-300">
                           Book Now
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SERVICES SECTION -->
    <section id="services" class="py-24 bg-cream overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-teal font-bold uppercase tracking-[4px] text-xs">Exquisite Services</span>
                <h2 class="text-4xl md:text-5xl font-playfair font-bold text-maroon mt-4">A World of Indulgence</h2>
                <div class="w-24 h-1 bg-teal mx-auto mt-6 rounded-full mb-8"></div>
                <p class="text-gray-500 font-light max-w-2xl mx-auto text-lg leading-relaxed">
                    We redefine hospitality through impeccable service and attention to every moment of your stay.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Restaurant -->
                <div class="service-card bg-gold p-10 rounded-3xl relative overflow-hidden group text-white" data-aos="fade-right">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-8">
                        <i class="fas fa-utensils text-3xl text-cream"></i>
                    </div>
                    <h3 class="text-2xl font-playfair font-bold mb-4">Gourmet Dining</h3>
                    <p class="text-cream/90 leading-relaxed mb-6 font-light">
                        Experience a culinary journey crafted by world-class chefs at our signature restaurant.
                    </p>
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=400" 
                         alt="Restaurant" class="w-full h-48 object-cover rounded-2xl opacity-50 group-hover:opacity-100 transition-opacity">
                </div>

                <!-- Cleaning -->
                <div class="service-card bg-maroon p-10 rounded-3xl relative overflow-hidden group text-white" data-aos="fade-up" data-aos-delay="200">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8">
                        <i class="fas fa-broom text-3xl text-cream"></i>
                    </div>
                    <h3 class="text-2xl font-playfair font-bold mb-4">Pristine Care</h3>
                    <p class="text-cream/80 leading-relaxed mb-6 font-light">
                        Our housekeeping ensures a spotless and serene environment for your absolute peace of mind.
                    </p>
                    <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=400" 
                         alt="Cleaning" class="w-full h-48 object-cover rounded-2xl opacity-50 group-hover:opacity-100 transition-opacity">
                </div>

                <!-- Infinity Pool -->
                <div class="service-card bg-teal p-10 rounded-3xl relative overflow-hidden group text-white" data-aos="fade-left" data-aos-delay="400">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8">
                        <i class="fas fa-swimming-pool text-3xl text-cream"></i>
                    </div>
                    <h3 class="text-2xl font-playfair font-bold mb-4">Infinity Pool</h3>
                    <p class="text-cream/80 leading-relaxed mb-6 font-light">
                        Dive into pure bliss at our rooftop infinity pool with panoramic views of the city horizon.
                    </p>
                    <img src="https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?auto=format&fit=crop&q=80&w=400" 
                         alt="Pool" class="w-full h-48 object-cover rounded-2xl opacity-50 group-hover:opacity-100 transition-opacity">
                </div>
            </div>
        </div>
    </section>


    <!-- TESTIMONIALS SECTION -->
    <section class="py-24 bg-white overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-maroon font-bold uppercase tracking-[4px] text-xs">Guest Stories</span>
                <h2 class="text-4xl md:text-5xl font-playfair font-bold text-gold mt-4">Voices of Grand Luxe</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-cream/50 p-10 rounded-[40px] shadow-sm hover:shadow-xl transition-all border border-cream group" data-aos="zoom-in">
                    <div class="flex items-center gap-4 mb-8">
                        <img src="https://i.pravatar.cc/150?u=12" alt="Guest" class="w-16 h-16 rounded-full border-4 border-white shadow-lg group-hover:scale-110 transition-transform">
                        <div>
                            <h4 class="font-bold text-maroon">Elena Rodriguez</h4>
                            <div class="flex text-gold text-xs mt-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic font-light leading-relaxed">
                        "The architectural brilliance and the warmth of the staff made our anniversary truly unforgettable. From the rooftop infinity pool to the personalized chef service, every moment felt like a dream."
                    </p>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-teal/5 p-10 rounded-[40px] shadow-sm hover:shadow-xl transition-all border border-teal/10 group md:mt-12" data-aos="zoom-in" data-aos-delay="200">
                    <div class="flex items-center gap-4 mb-8">
                        <img src="https://i.pravatar.cc/150?u=34" alt="Guest" class="w-16 h-16 rounded-full border-4 border-white shadow-lg group-hover:scale-110 transition-transform">
                        <div>
                            <h4 class="font-bold text-maroon">James Harrison</h4>
                            <div class="flex text-gold text-xs mt-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic font-light leading-relaxed">
                        "A fantastic stay with breathtaking city views. The room was pristine and the breakfast was world-class. Only reason for 4 stars is that I wish I could have stayed longer! Definitely coming back."
                    </p>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-maroon/5 p-10 rounded-[40px] shadow-sm hover:shadow-xl transition-all border border-maroon/10 group md:mt-6" data-aos="zoom-in" data-aos-delay="400">
                    <div class="flex items-center gap-4 mb-8">
                        <img src="https://i.pravatar.cc/150?u=56" alt="Guest" class="w-16 h-16 rounded-full border-4 border-white shadow-lg group-hover:scale-110 transition-transform">
                        <div>
                            <h4 class="font-bold text-maroon">Sophia Chen</h4>
                            <div class="flex text-gold text-xs mt-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic font-light leading-relaxed">
                        "The ultimate definition of royalty. I have stayed in many luxury hotels globally, but Grand Luxe's attention to detail and heritage charm is in a league of its own. Simply unmatched."
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section id="contact" class="py-24 bg-white overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="bg-cream rounded-[50px] overflow-hidden shadow-2xl border border-cream flex flex-col lg:flex-row" data-aos="fade-up">
                <!-- Info Section -->
                <div class="lg:w-2/5 p-12 lg:p-20 bg-maroon text-white">
                    <h2 class="text-4xl font-playfair font-bold mb-10">Get in Touch</h2>
                    <ul class="space-y-10">
                        <li class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center shrink-0">
                                <i class="fas fa-map-marker-alt text-xl text-gold"></i>
                            </div>
                            <div>
                                <h4 class="text-gold uppercase tracking-widest text-xs font-bold mb-1">Our Address</h4>
                                <p class="text-white/80 font-light text-sm md:text-base">Grand Luxe Palace, Marine Drive, Nariman Point, Mumbai, Maharashtra 400021, India</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center shrink-0">
                                <i class="fas fa-phone-alt text-xl text-gold"></i>
                            </div>
                            <div>
                                <h4 class="text-gold uppercase tracking-widest text-xs font-bold mb-1">Phone Number</h4>
                                <p class="text-white/80 font-light">+91 63541 10488</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center shrink-0">
                                <i class="fas fa-envelope text-xl text-gold"></i>
                            </div>
                            <div>
                                <h4 class="text-gold uppercase tracking-widest text-xs font-bold mb-1">Email Inquiry</h4>
                                <p class="text-white/80 font-light">grandluxe.luxury@gmail.com</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Map Section -->
                <div class="lg:w-3/5 relative min-h-[450px]">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3774.225134706362!2d72.8206197!3d18.9219840!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7d1e704a2fad5%3A0xc023a1050e50eb97!2sMarine%20Drive%2C%20Mumbai!5e0!3m2!1sen!2sin!4v1740477215383!5m2!1sen!2sin" 
                        class="absolute inset-0 w-full h-full grayscale hover:grayscale-0 transition-all duration-700 border-none"
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-gray-900 pt-24 pb-12 text-white">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                <!-- Brand -->
                <div class="col-span-1">
                    <a href="#" class="text-3xl font-playfair font-bold text-white mb-8 block tracking-tighter">Grand<span class="text-gold">Luxe</span></a>
                    <p class="text-gray-400 font-light leading-relaxed mb-8">
                        Elevating the art of hospitality through dedication, luxury, and the pursuit of absolute perfection since 1995.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full border border-gray-700 flex items-center justify-center hover:bg-gold hover:border-gold transition-all"><i class="fab fa-facebook-f text-xs"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full border border-gray-700 flex items-center justify-center hover:bg-gold hover:border-gold transition-all"><i class="fab fa-instagram text-xs"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full border border-gray-700 flex items-center justify-center hover:bg-gold hover:border-gold transition-all"><i class="fab fa-twitter text-xs"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full border border-gray-700 flex items-center justify-center hover:bg-gold hover:border-gold transition-all"><i class="fab fa-linkedin-in text-xs"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-gold font-bold uppercase tracking-widest text-xs mb-8">Quick Links</h4>
                    <ul class="space-y-4 text-gray-400 font-light">
                        <li><a href="#home" class="hover:text-gold transition-colors">Home Experience</a></li>
                        <li><a href="#about" class="hover:text-gold transition-colors">The Grand Story</a></li>
                        <li><a href="#rooms" class="hover:text-gold transition-colors">Our Sanctuaries</a></li>
                        <li><a href="login.php" class="hover:text-gold transition-colors">Member Portal</a></li>
                        <li><a href="privacy-policy.html" target="_blank" class="hover:text-gold transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-gold font-bold uppercase tracking-widest text-xs mb-8">Our Services</h4>
                    <ul class="space-y-4 text-gray-400 font-light">
                        <li><a href="#services" class="hover:text-gold transition-colors">Gourmet Dining</a></li>
                        <li><a href="#services" class="hover:text-gold transition-colors">Room Cleaning</a></li>
                        <li><a href="#services" class="hover:text-gold transition-colors">Infinity Pool</a></li>
                        <li><a href="#" class="hover:text-gold transition-colors">Concierge Desk</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h4 class="text-gold font-bold uppercase tracking-widest text-xs mb-8">Newsletter</h4>
                    <p class="text-gray-400 font-light text-sm mb-6">Join our privilege club for exclusive offers and hotel updates.</p>
                    <div class="relative" id="newsletterContainer">
                        <input type="email" id="newsletterEmail" placeholder="Your Luxury Email" class="w-full bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm focus:ring-1 focus:ring-gold outline-none">
                        <button id="subscribeBtn" class="absolute right-2 top-1/2 -translate-y-1/2 bg-gold p-2 rounded-xl hover:bg-white transition-colors">
                            <i class="fas fa-paper-plane text-gray-900"></i>
                        </button>
                    </div>
                    <p id="newsletterSuccess" class="text-teal text-xs mt-3 hidden font-medium">✨ Welcome to the Privilege Club!</p>
                </div>
            </div>

            <hr class="border-gray-800 mb-10">

            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-gray-500 text-[10px] md:text-xs text-center font-light">
                    &copy; <?php echo date('Y'); ?> Grand Luxe Hotel. All Rights Reserved. Crafted for Absolute Luxury.
                </p>
                <div class="flex flex-wrap justify-center gap-4 md:gap-8 text-gray-600 font-light text-[9px] md:text-[10px] uppercase tracking-widest">
                    <a href="privacy-policy.html" target="_blank" class="hover:text-gold transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-gold transition-colors">Site Map</a>
                    <a href="#" class="hover:text-gold transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- STORY MODAL -->
    <div id="storyModal" class="modal fixed inset-0 z-[100] flex items-center justify-center p-6 hidden">
        <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" id="modalOverlay"></div>
        <div class="relative bg-white max-w-2xl w-full rounded-[40px] overflow-hidden shadow-2xl animate-fade-in-up">
            <button id="closeModal" class="absolute top-6 right-6 text-maroon hover:text-gold transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="h-64 overflow-hidden">
                <img src="https://images.unsplash.com/photo-1560200353-ce0a76b1d438?auto=format&fit=crop&q=80" alt="History" class="w-full h-full object-cover">
            </div>
            <div class="p-10">
                <span class="text-gold font-bold uppercase tracking-widest text-xs">Our Journey Since 1995</span>
                <h3 class="text-3xl font-playfair font-bold text-maroon mt-4 mb-6">A Legacy of Excellence</h3>
                <p class="text-gray-600 leading-relaxed font-light">
                    Grand Luxe Hotel began as a single vision: to create a destination where "luxury" wasn't just a word, but a felt experience. For over three decades, we have hosted royalty, innovators, and families alike, refining our art with every guest. Our story is written in the smiles of our visitors and the timeless elegance of our suites.
                </p>
                <button id="modalCloseBtn" class="mt-8 border-2 border-maroon text-maroon px-8 py-3 rounded-full font-bold text-sm uppercase tracking-widest hover:bg-maroon hover:text-white transition-all">
                    Close Story
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            offset: 100,
            once: true,
            easing: 'ease-out-quad'
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                const isHidden = mobileMenu.classList.contains('hidden');
                if (menuIcon) {
                    menuIcon.className = isHidden ? 'fas fa-bars text-2xl' : 'fas fa-times text-2xl';
                }
            });

            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    if (menuIcon) menuIcon.className = 'fas fa-bars text-2xl';
                });
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (nav) {
                if (window.scrollY > 50) {
                    nav.classList.add('py-2', 'shadow-2xl');
                    nav.classList.remove('py-4');
                } else {
                    nav.classList.add('py-4');
                    nav.classList.remove('py-2', 'shadow-2xl');
                }
            }
        });

        // Modal Logic
        const storyBtn = document.getElementById('ourStoryBtn');
        const storyModal = document.getElementById('storyModal');
        const closeModal = document.getElementById('closeModal');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const modalOverlay = document.getElementById('modalOverlay');

        function toggleModal() {
            if (storyModal) {
                storyModal.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            }
        }

        if (storyBtn) storyBtn.addEventListener('click', toggleModal);
        if (closeModal) closeModal.addEventListener('click', toggleModal);
        if (modalCloseBtn) modalCloseBtn.addEventListener('click', toggleModal);
        if (modalOverlay) modalOverlay.addEventListener('click', toggleModal);

        // Newsletter Subscription
        const subscribeBtn = document.getElementById('subscribeBtn');
        const newsletterEmail = document.getElementById('newsletterEmail');
        const newsletterSuccess = document.getElementById('newsletterSuccess');

        if (subscribeBtn) {
            subscribeBtn.addEventListener('click', () => {
                const email = newsletterEmail.value.trim();
                if (email && email.includes('@')) {
                    // Success state
                    subscribeBtn.innerHTML = '<i class="fas fa-check text-gray-900"></i>';
                    subscribeBtn.classList.add('bg-teal');
                    subscribeBtn.classList.remove('bg-gold');
                    newsletterSuccess.classList.remove('hidden');
                    
                    // Reset after 5 seconds
                    setTimeout(() => {
                        subscribeBtn.innerHTML = '<i class="fas fa-paper-plane text-gray-900"></i>';
                        subscribeBtn.classList.remove('bg-teal');
                        subscribeBtn.classList.add('bg-gold');
                        newsletterSuccess.classList.add('hidden');
                        newsletterEmail.value = '';
                    }, 5000);
                } else {
                    // Error shake effect
                    newsletterEmail.classList.add('ring-2', 'ring-red-500');
                    setTimeout(() => newsletterEmail.classList.remove('ring-2', 'ring-red-500'), 1000);
                }
            });
        }
    </script>
</body>
</html>
