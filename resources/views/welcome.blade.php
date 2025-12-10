<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery</title>
    @vite('resources/css/app.css')
</head>

<body class="font-serif bg-cover bg-center" style="background-image: url('{{ asset('storage/images/Donut.jpg') }}')">

    <!-- Overlay -->
    <div class="bg-black/40 w-full h-screen">

        <!-- Navbar -->
        <nav class="flex items-center justify-between px-10 py-6 text-white">
            <div class="flex items-center gap-3">
                <h1 class="text-4xl font-bold">DavidBakery</h1>
            </div>

            <ul class="flex items-center gap-10 text-lg">
                <li class="flex items-center gap-2">
                    <a  href="{{ route('login') }}""
                       class="border border-white px-5 py-2 rounded-full hover:bg-white hover:text-black transition">
                        Login
                    </a>
                    <a  href="{{ route('register') }}""
                       class="border border-white px-5 py-2 rounded-full hover:bg-white hover:text-black transition">
                        Register
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Hero Content -->
        <div class="px-16 mt-24 text-white max-w-3xl">
            <h1 class="text-6xl font-extrabold">DavidBakery</h1>

            <p class="mt-6 text-xl leading-relaxed">
                A modern bakery with a touch of classic flavor.  
                We craft fresh bread and pastries daily, made from the finest ingredients.  
                Simple, warm, and baked with love in every batch.
            </p>

            <a href="#"
               class="inline-flex items-center gap-3 mt-8 text-2xl font-semibold hover:text-yellow-300">
                Take a Look →
            </a>
        </div>

        <!-- Social Icons -->
        <div class="absolute bottom-10 left-10 flex gap-6 text-white text-3xl">
            <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
            <a href="#"><i class="fa-brands fa-tiktok"></i></a>
        </div>

    </div>

</body>
</html>
