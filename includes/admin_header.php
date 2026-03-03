<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> | Grand Luxe</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Heroicons / FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#a855f7',
                            DEFAULT: '#8b5cf6',
                            dark: '#6d28d9',
                        },
                        secondary: {
                            light: '#fb7185',
                            DEFAULT: '#f43f5e',
                            dark: '#e11d48',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'bounce-subtle': 'bounceSubtle 2s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .gradient-mesh {
            background-color: #f3f4f6;
            background-image: 
                radial-gradient(at 0% 0%, rgba(168, 85, 247, 0.15) 0, transparent 50%), 
                radial-gradient(at 50% 0%, rgba(251, 113, 133, 0.15) 0, transparent 50%),
                radial-gradient(at 100% 0%, rgba(45, 212, 191, 0.15) 0, transparent 50%);
        }
        .sidebar-link.active {
            background: linear-gradient(to right, #8b5cf6, #f43f5e);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
        }
        .card-soft {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .card-soft:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="gradient-mesh min-h-screen flex">
