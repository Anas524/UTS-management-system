@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@push('before-scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@endpush

@section('title', 'Universal Trade Services')

@section('content')
<div id="splashScreen">
    <img id="UTSLogo" src="{{ asset('images/UTS-Logo.png') }}" alt="">
</div>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="{{ asset('images/UTS-Logo.png') }}" alt="UTS Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link text" href="#" data-en="Home" data-id="Beranda">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text" href="#aboutUs" data-en="About UTS" data-id="Tentang kami">Tentang kami</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text" href="#contact" data-en="Contact" data-id="Kontak">Kontak</a>
                </li>

                {{-- Auth links --}}
                @auth
                <li class="nav-item">
                    <a href="{{ auth()->user()->is_admin ? route('admin.index') : route('dashboard') }}" class="nav-link fw-semibold">
                        <span class="text"
                            data-en="{{ auth()->user()->is_admin ? 'Admin' : 'Dashboard' }}"
                            data-id="{{ auth()->user()->is_admin ? 'Admin' : 'Dasbor' }}">
                            {{ auth()->user()->is_admin ? 'Admin' : 'Dasbor' }}
                        </span>
                    </a>
                </li>
                @endauth

                @guest
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="nav-link fw-semibold">
                        <span class="text" data-en="Login" data-id="Masuk">Masuk</span>
                    </a>
                </li>
                @endguest

                {{-- Language switcher --}}
                <li class="nav-item ms-lg-2">
                    <button id="languageSwitcher" class="btn btn-link d-flex align-items-center gap-2 p-0">
                        <img id="languageIcon" src="{{ asset('images/indonesia-flag copy.png') }}" alt="ID Flag" style="width:30px;height:30px;border-radius:50px;">
                        <span id="languageLabel" style="font-size:14px;font-weight:600;">ID</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section id="heroCarousel" class="carousel slide carousel-fade hero-section" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active" data-bs-interval="5000" aria-labelledby="carouselCaption1" aria-describedby="carouselDesc1">
            <img src="{{ asset('images/welcome screen image.png') }}" class="d-block w-100 hero-img zoom-effect" alt="Slide 1">
            <div class="carousel-caption d-flex flex-column justify-content-center align-items-center">
                <h1 class="hero-title text" data-en="Welcome to Universal Trade Services" data-id="Selamat datang di Universal Trade Services">Selamat datang di Universal Trade Services</h1>
                <p class="hero-subtitle text" data-en="Your trusted partner in fine perfumery, cosmetics & wellness" data-id="Mitra terpercaya Anda dalam parfum, kosmetik, & kebugaran">Mitra terpercaya Anda dalam parfum, kosmetik, & kebugaran</p>
            </div>
        </div>

        <div class="carousel-item" data-bs-interval="5000" aria-labelledby="carouselCaption2" aria-describedby="carouselDesc2">
            <img src="{{ asset('images/slider 2 image.png') }}" class="d-block w-100 hero-img zoom-effect" alt="Slide 2">
            <div class="carousel-caption d-flex flex-column justify-content-center align-items-center">
                <h1 class="hero-title text" data-en="Premium Perfumery and Cosmetics" data-id="Parfum dan Kosmetik Premium">Parfum dan Kosmetik Premium</h1>
                <p class="hero-subtitle text" data-en="Experience the elegance of botanical luxury" data-id="Rasakan keanggunan dari kemewahan botani">Rasakan keanggunan dari kemewahan botani</p>
            </div>
        </div>

        <div class="carousel-item" data-bs-interval="5000" aria-labelledby="carouselCaption3" aria-describedby="carouselDesc3">
            <img src="{{ asset('images/slider 3 image.png') }}" class="d-block w-100 hero-img zoom-effect" alt="Slide 3">
            <div class="carousel-caption d-flex flex-column justify-content-center align-items-center">
                <h1 class="hero-title text" data-en="Elegance in Every Drop" data-id="Keanggunan di Setiap Tetes">Keanggunan di Setiap Tetes</h1>
                <p class="hero-subtitle text" data-en="Discover our signature incense, skincare, and beauty blends" data-id="Temukan dupa, produk perawatan kulit, dan campuran kecantikan unggulan kami">Temukan dupa, produk perawatan kulit, dan campuran kecantikan unggulan kami</p>
            </div>
        </div>
    </div>

</section>

<section id="aboutUs" class="about-us-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <!-- Image on the left (for desktop, on smaller screens it will go below the text) -->
            <div class="col-md-6 mb-4 mb-md-0">
                <img src="{{ asset('images/About us image.png') }}" alt="About Us" class="img-fluid rounded shadow">
            </div>
            <!-- Text content on the right -->
            <div class="col-md-6">
                <h2 class="text-center text-md-left text" data-en="About Universal Trade Services" data-id="Tentang Layanan Perdagangan Universal">Tentang Layanan Perdagangan Universal</h2>
                <p class="text-muted text"
                    data-en="At <strong>Universal Trade Services</strong>, we specialize in offering high-quality <strong>perfumery</strong> and <strong>cosmetics</strong>. Our products are carefully selected to provide luxurious experiences and results. Based in <strong>Indonesia</strong>, we pride ourselves on delivering premium products that enhance beauty and well-being."
                    data-id="Di <strong>Universal Trade Services</strong>, kami mengkhususkan diri dalam menawarkan <strong>parfum</strong> dan <strong>kosmetik</strong> berkualitas tinggi. Produk kami dipilih dengan cermat untuk memberikan pengalaman dan hasil mewah. Berdasarkan di <strong>Indonesia</strong>, kami bangga dapat menghadirkan produk premium yang meningkatkan kecantikan dan kesejahteraan.">
                    At <strong>Universal Trade Services</strong>, we specialize in offering high-quality <strong>perfumery</strong> and <strong>cosmetics</strong>. Our products are carefully selected to provide luxurious experiences and results. Based in <strong>Indonesia</strong>, we pride ourselves on delivering premium products that enhance beauty and well-being.
                </p>
                <p class="text" data-en="We are dedicated to offering traditional and natural solutions, making sure our clients always feel confident in the products they choose. Whether you're looking for exquisite perfumes or skincare solutions, our mission is to ensure you always look and feel your best." data-id="Kami berdedikasi untuk menawarkan solusi tradisional dan alami, memastikan klien kami selalu merasa percaya diri dengan produk yang mereka pilih. Apakah Anda sedang mencari parfum yang indah atau solusi perawatan kulit, misi kami adalah memastikan Anda selalu terlihat dan merasa yang terbaik.">
                    Kami berdedikasi untuk menawarkan solusi tradisional dan alami, memastikan klien kami selalu merasa percaya diri dengan produk yang mereka pilih. Apakah Anda sedang mencari parfum yang indah atau solusi perawatan kulit, misi kami adalah memastikan Anda selalu terlihat dan merasa yang terbaik.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2 class="text" data-en="Categories" data-id="Kategori">Kategori</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card category-card border-0">
                    <img src="{{ asset('images/vecteezy_ai-generated-gift-of-beauty-cosmetic-product-in-a-gift-like_38106375.jpg') }}"
                        class="card-img-top category-img" alt="Perfumes">
                    <div class="card-body">
                        <h5 class="card-title text" data-en="Perfumes" data-id="Parfum">Perfumes</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card category-card border-0">
                    <img src="{{ asset('images/vecteezy_top-view-of-cosmetics-set-for-makeup-on-a-black-background_10720559.jpg') }}"
                        class="card-img-top category-img" alt="Skincare">
                    <div class="card-body">
                        <h5 class="card-title text" data-en="Cosmetics" data-id="Kosmetik">Kosmetik</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card category-card border-0">
                    <img src="{{ asset('images/Weihrauchmischung-Basilika.png') }}" class="card-img-top category-img"
                        alt="Incense">
                    <div class="card-body">
                        <h5 class="card-title text" data-en="Incense" data-id="Pewangi">Incense</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="slider" style="--width: 100px; --height: 100px; --quantity: 8; direction: ltr;">
    <div class="sliderList">
        <div class="sliderItem" style="--position: 1"><img
                src="{{ asset('images/Starwest resized.jpg') }}" alt="Brand 1"></div>
        <div class="sliderItem" style="--position: 2"><img
                src="{{ asset('images/Atralia.jpg') }}" alt="Brand 2"></div>
        <div class="sliderItem" style="--position: 3"><img
                src="{{ asset('images/Dumont Paris resized.jpg') }}" alt="Brand 3"></div>
        <div class="sliderItem" style="--position: 4"><img
                src="{{ asset('images/Tropic Isle rewrited.jpg') }}" alt="Brand 4"></div>
        <div class="sliderItem" style="--position: 5"><img
                src="{{ asset('images/Ebin New York.jpg') }}" alt="Brand 5"></div>
        <div class="sliderItem" style="--position: 6"><img
                src="{{ asset('images/Starwest resized.jpg') }}" alt="Brand 1"></div>
        <div class="sliderItem" style="--position: 7"><img
                src="{{ asset('images/Atralia.jpg') }}" alt="Brand 2"></div>
        <div class="sliderItem" style="--position: 8"><img
                src="{{ asset('images/Dumont Paris resized.jpg') }}" alt="Brand 3"></div>
        <div class="sliderItem" style="--position: 9"><img
                src="{{ asset('images/Tropic Isle rewrited.jpg') }}" alt="Brand 4"></div>
        <div class="sliderItem" style="--position: 10"><img
                src="{{ asset('images/Ebin New York.jpg') }}" alt="Brand 5"></div>
    </div>
</div>
<div class="slider-description py-4 text-center">
    <p class="text" data-en="We are proud to collaborate with industry leaders who share our vision for quality and innovation. Our trusted partners help us bring the best products and services to our customers, ensuring we deliver excellence in every experience. Together, we strive to make a positive impact in our industry and beyond." data-id="Kami bangga bermitra dengan para pemimpin industri yang sejalan dengan visi kami dalam hal kualitas dan inovasi. Mitra terpercaya kami membantu menghadirkan produk dan layanan terbaik bagi pelanggan, memastikan pengalaman terbaik di setiap kesempatan. Bersama, kami berkomitmen memberikan dampak positif bagi industri dan masyarakat luas.">Kami bangga bermitra dengan para pemimpin industri yang sejalan dengan visi kami dalam hal kualitas dan inovasi. Mitra terpercaya kami membantu menghadirkan produk dan layanan terbaik bagi pelanggan, memastikan pengalaman terbaik di setiap kesempatan. Bersama, kami berkomitmen memberikan dampak positif bagi industri dan masyarakat luas.</p>
</div>

<section id="contact" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4 text" data-en="Contact Us" data-id="Hubungi Kami">Hubungi Kami</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5>Universal Trade Services</h5>
                <p><strong class="text" data-en="Address:" data-id="Alamat:">Alamat:</strong> Cikini Building, Jl. Cikini Raya No. 9 RT. 016 RW. 001, Cikini, Menteng, Kota Adm. Jakarta Pusat, DKI Jakarta</p>
                <p><strong>Email:</strong> <a class="links" href="info@ptuts.id">info@ptuts.id</a></p>
                <p><strong class="text" data-en="Phone:" data-id="Telepon:">Telepon:</strong> <a class="links" href="tel:+6282156644661">+62 822-6165-5100</a></p>
            </div>
            <div class="col-md-6">
                <form id="contact-form">
                    <div class="mb-3">
                        <input type="text" class="form-control" data-en="Your Name" data-id="Nama Anda" placeholder="Nama Anda" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" data-en="Your Email" data-id="Email Anda" placeholder="Email Anda" required>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" rows="4" data-en="Your Message" data-id="Pesan Anda" placeholder="Pesan Anda" required></textarea>
                    </div>
                    <button type="submit" class="btn contact-form-btn w-100 text" data-en="Send Message" data-id="Kirim Pesan">Kirim Pesan</button>
                </form>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        &copy; 2025 Universal Trade Services. All rights reserved.
    </div>
</footer>

<div class="modal fade" id="thankYouModal" tabindex="-1" aria-labelledby="thankYouModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text" id="thankYouModalLabel" data-en="Message Sent" data-id="Pesan Terkirim">Pesan Terkirim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text" data-en="Thank you! Your message has been sent." data-id="Terima kasih! Pesan Anda telah dikirim.">
                Terima kasih! Pesan Anda telah dikirim.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary text" data-bs-dismiss="modal" data-en="Close" data-id="Tutup">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/script.js') }}"></script>
@endpush

@endsection