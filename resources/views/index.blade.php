<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Beranda</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite('resources/css/app.css')

</head>
<body>

    {{-- Banner --}}
    <section class="banner">
        <img src="{{ asset('images/banner.webp') }}" alt="Banner">
        <div class="banner-text">
            <div class="nav-menu">
                <a href="{{ url('/') }}">HOME</a>
                <a href="{{ url('/about') }}">ABOUT</a>
                <a href="{{ url('/products') }}">PRODUCTS</a>
                <a href="{{ url('/contact') }}">CONTACT US</a>
                <a href="{{ url('/career') }}">CAREER</a>
            </div>

            <h1>
                <span style="display: block;">Available To Fulfill</span>
                <span style="display: block;">Your Request</span>
            </h1>

            <a href="{{ url('/contact') }}" class="customer-service-link">
                <span>Customer Service</span>
                <span class="arrow-circle">&#8594;</span>
            </a>

        </div>
    </section>


    {{-- Kategori Produk --}}
    <div class="section">
        <h2>Category Produk Kami</h2>
        
        <div class="scrolling-cards">
            
            {{-- Adhesive --}}
            <a href="#" class="card">
                <div class="card-image-container">
                    <img src="{{ asset('images/adhesive.png') }}" alt="Adhesive">
                    <div class="label-overlay">Adhesive</div>
                </div>
            </a>

            {{-- Home Appliance--}}
            <a class="card coming-soon">
                <div class="card-image-container">
                    <img src="{{ asset('images/comingsoon/home-appliance.png') }}" alt="Home Appliance">
                    <div class="coming-soon-overlay">
                        <span class="coming-soon-box">COMING SOON</span>
                    </div>
                    <div class="label-overlay-black">Home Appliance</div>
                </div>
            </a>

            {{-- Rooftop --}}
            <a class="card coming-soon">
                <div class="card-image-container">
                    <img src="{{ asset('images/comingsoon/rooftop.png') }}" alt="Rooftop">
                    <div class="coming-soon-overlay">
                        <span class="coming-soon-box">COMING SOON</span>
                    </div>
                    <div class="label-overlay-black">Rooftop</div>
                </div>
            </a>

            {{-- Plumbing --}}
            <a class="card coming-soon">
                <div class="card-image-container">
                    <img src="{{ asset('images/comingsoon/plumbing.png') }}" alt="Plumbing">
                    <div class="coming-soon-overlay">
                        <span class="coming-soon-box">COMING SOON</span>
                    </div>
                    <div class="label-overlay-black">Plumbing</div>
                </div>
            </a> 
            
        </div>
    </div>

    {{-- Mitra Resmi --}}
    <div class="section">
        <h2>Daftar Mitra Resmi</h2>
        <a href="#">
        <div class="card-map">
            <div class="card-image-container-map">
                {{-- <p>Lihat Berbagai Toko Yang Menjual Produk Asli</p> --}}
                <img src="{{ asset('images/map-indonesia.png') }}" alt="Peta Toko">
                <div class="label-overlay-map-black custom-line-height">Lihat Berbagai Toko Yang<br> Menjual Produk Asli</div>
                
                <div class="hover-overlay">
                    <div class="hover-button">Lihat</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    
    <footer class="custom-footer">
        <hr class="footer-line">
        <div class="footer-wms-container">
            <a href="{{ url('/master') }}" class="footer-wms">MASTER</a>
            <a href="{{ url('/oms') }}" class="footer-wms">OMS</a>
            <a href="{{ url('/wms/login') }}" class="footer-wms">WMS</a>
        </div>
        <div class="footer-copy-container">
            <span class="footer-copy">Hak Cipta © 2025 PT Quark Neural Partikel</span>
        </div>
    </footer>


</body>
</html>
