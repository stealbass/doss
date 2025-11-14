@php
    use App\Models\Utility;
    $settings = \Modules\LandingPage\Entities\LandingPageSetting::settings();

    $logo = Utility::get_file('uploads/landing_page_image');
    $sup_logo = Utility::get_file('uploads/logo');
    $meta_image = Utility::get_file('uploads/meta/');

    $adminSettings = Utility::settings();
    $metatitle = isset($adminSettings['meta_title']) ? $adminSettings['meta_title'] : '';
    $metsdesc = isset($adminSettings['meta_desc']) ? $adminSettings['meta_desc'] : '';
    $meta_logo = isset($adminSettings['meta_image']) ? $adminSettings['meta_image'] : '';

    $color = !empty($adminSettings['color']) ? $adminSettings['color'] : 'theme-1';
    if (isset($adminSettings['color_flag']) && $adminSettings['color_flag'] == 'true') {
        $themeColor = 'custom-color';
    } else {
        $themeColor = $color;
    }

    \App::setLocale(isset($adminSettings['default_language']) ? $adminSettings['default_language'] : 'en');
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $adminSettings['SITE_RTL'] == 'on' ? 'rtl' : '' }}">

<head>
    <title>
        {{ Utility::getValByName('title_text') ? Utility::getValByName('title_text') : config('app.name', 'AdvocateGo-SaaS') }}
    </title>
    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />

    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metsdesc }}">
    <meta name="base-url" content="{{ URL::to('/') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:title" content="{{ $metatitle }}">
    <meta property="og:description" content="{{ $metsdesc }}">
    <meta property="og:image" content="{{ $meta_image . $meta_logo }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ env('APP_URL') }}">
    <meta property="twitter:title" content="{{ $metatitle }}">
    <meta property="twitter:description" content="{{ $metsdesc }}">
    <meta property="twitter:image" content="{{ $meta_image . $meta_logo }}">

   <!-- Favicon icon -->
   <link rel="icon"
   href="{{ $sup_logo . '/' . (isset($adminSettings['company_favicon']) && !empty($adminSettings['company_favicon']) ? $adminSettings['company_favicon'] : 'favicon.png') . '?' . time() }}"
   type="image/x-icon" />
    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('public/LandingPage_assets/fonts/tabler-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/LandingPage_assets/fonts/feather.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/LandingPage_assets/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/LandingPage_assets/fonts/material.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/notifier.css') }}">

    <!-- vendor css -->
    @if ($adminSettings['SITE_RTL'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif

    @if ($adminSettings['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('public/LandingPage_assets/css/style.css') }}"
            id="main-style-link">
    @endif

    <link rel="stylesheet" href=" {{ asset('public/LandingPage_assets/css/customizer.css') }}" />
    <link rel="stylesheet" href=" {{ asset('public/LandingPage_assets/css/landing-page.css') }}" />
    <link rel="stylesheet" href=" {{ asset('public/LandingPage_assets/css/custom.css') }}" />

    <style>
        :root {
            --color-customColor: <?=$color ?>;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/custom-color.css') }}">

</head>


@if ($adminSettings['cust_darklayout'] == 'on')

    <body class="{{ $themeColor }} landing-dark">
    @else

        <body class="{{ $themeColor }}">
@endif
<!-- [ Header ] start -->
<header class="main-header">
    @if ($settings['topbar_status'] == 'on')
        <div class="announcement bg-dark text-center p-2">
            <p class="mb-0">{!! $settings['topbar_notification_msg'] !!}</p>
        </div>
    @endif
    @if ($settings['menubar_status'] == 'on')
        <div class="container">
            <nav class="navbar navbar-expand-md  default top-nav-collapse">
                <div class="header-left">
                    <a class="navbar-brand bg-transparent" href="#">
                        {{-- @if (Storage::exists('/uploads/landing_page_image/' . $settings['site_logo'])) --}}
                            <img src="{{ $logo . '/' . $settings['site_logo'] }}" alt="logo">
                        {{-- @endif --}}
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ url('/#home') }}">{{ $settings['home_title'] }}</a>
                        </li>
                        @if ($settings['feature_status'] == 'on')
                            <li class="nav-item">
                                <a class="nav-link"
                                    href="{{ url('/#features') }}">{{ $settings['feature_title'] }}</a>
                            </li>
                        @endif
                        @if ($settings['plan_status'] == 'on')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/#plan') }}">{{ $settings['plan_title'] }}</a>
                            </li>
                        @endif
                        @if ($settings['faq_status'] == 'on')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/#faq') }}">{{ $settings['faq_title'] }}</a>
                            </li>
                        @endif
                        @if (is_array(json_decode($settings['menubar_page'])) || is_object(json_decode($settings['menubar_page'])))
                            @foreach (json_decode($settings['menubar_page']) as $key => $value)
                                @if ($value->header == 'on' && $value->template_name == 'page_content')
                                    <li class="nav-item">
                                        <a class="nav-link"
                                            href="{{ route('custom.page', $value->page_slug) }}">{{ $value->menubar_page_name }}</a>
                                    </li>
                                @elseif($value->header == 'on')
                                    <li class="nav-item">
                                        <a class="nav-link"
                                            href="{{ $value->page_url }}">{{ $value->menubar_page_name }}</a>
                                    </li>
                                @endif
                            @endforeach
                        @endif
                    </ul>
                    <button class="navbar-toggler bg-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                <div class="ms-auto d-flex justify-content-end gap-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-dark rounded"><span
                            class="hide-mob me-2">{{ __('Login') }}</span> <i data-feather="log-in"></i></a>
                    <a href="{{ route('register') }}" class="btn btn-outline-dark rounded"><span
                            class="hide-mob me-2">{{ __('Register') }}</span> <i data-feather="user-check"></i></a>
                    <button class="navbar-toggler " type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </nav>
        </div>
    @endif
</header>
<!-- [ Header ] End -->
<!-- [ common banner ] start -->
<section class="common-banner bg-primary">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4">
                <div class="title">
                    <h1 class="text-white">{!! $page['menubar_page_name'] !!}</h1>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- [ common banner ] end -->
<!-- [ Static content ] start -->
<section class="static-content section-gap">
    <div class="container">
        <div class="mb-5">
            {!! $page['menubar_page_contant'] !!}
        </div>
        <div class="container">
            <div class="bg-primary p-4 rounded">
            <div class="row g-0 gy-2 mt-4 align-items-center">
                <div class="col-xxl-12">
                    <div class="row gy-3 row-cols-9">
                        @foreach (explode(',', $settings['home_logo']) as $k => $home_logo)
                            <div class="col-auto">
                                {{-- @if (Storage::exists('/uploads/landing_page_image/' . $home_logo)) --}}
                                <img src="{{ $logo . '/' . $home_logo }}" alt="" class="landing_logo"
                                    style="width: 130px;">
                                {{-- @endif --}}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>
<!-- [ Static content ] end -->
<!-- [ Footer ] start -->
<footer class="site-footer bg-gray-100">
    <div class="container">
        <div class="footer-row">
            <div class="ftr-col cmp-detail">
                <div class="footer-logo mb-3">
                    <a href="#">
                        {{-- @if (Storage::exists('/uploads/landing_page_image/' . $settings['site_logo'])) --}}
                            <img src="{{ $logo . '/' . $settings['site_logo'] }}" alt="logo">
                        {{-- @endif --}}
                    </a>
                </div>
                <p>
                    {!! $settings['site_description'] !!}
                </p>
            </div>
            <div class="ftr-col">
                <ul class="list-unstyled">
                    @if (is_array(json_decode($settings['menubar_page'])) || is_object(json_decode($settings['menubar_page'])))
                        @foreach (json_decode($settings['menubar_page']) as $key => $value)
                            @if ($value->footer == 'on' && $value->header == 'off' && $value->template_name == 'page_content')
                                <li><a
                                        href="{{ route('custom.page', $value->page_slug) }}">{!! $value->menubar_page_name !!}</a>
                                </li>
                            @endif
                            @if ($value->footer == 'on' && $value->header == 'on' && $value->template_name == 'page_content')
                                <li><a
                                        href="{{ route('custom.page', $value->page_slug) }}">{!! $value->menubar_page_name !!}</a>
                                </li>
                            @endif
                            @if ($value->footer == 'on' && $value->header == 'on' && $value->template_name == 'page_url')
                                <li><a href="{{ $value->page_url }}">{!! $value->menubar_page_name !!}</a></li>
                            @endif
                            @if ($value->footer == 'on' && $value->header == 'off' && $value->template_name == 'page_url')
                                <li><a href="{{ $value->page_url }}">{!! $value->menubar_page_name !!}</a></li>
                            @endif
                        @endforeach
                    @endif
                </ul>
            </div>
            @if ($settings['joinus_status'] == 'on')
                <div class="ftr-col ftr-subscribe">
                    <h2>{!! $settings['joinus_heading'] !!}</h2>
                    <p>{!! $settings['joinus_description'] !!}</p>
                    <form method="post" action="{{ route('join_us_store') }}">
                        @csrf
                        <div class="input-wrapper border border-dark">
                            <input type="text" name="email" placeholder="Type your email address...">
                            <button type="submit" class="btn btn-dark rounded-pill">{{ __('Join Us') }}!</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <div class="border-top border-dark text-center p-2">
        <p class="mb-0"> &copy;
            {{ date('Y') }}
            {{ Utility::getValByName('footer_text') ? Utility::getValByName('footer_text') : config('app.name', 'ERPGo') }}
        </p>
    </div>
</footer>
<!-- [ Footer ] end -->
<!-- Required Js -->

<script src="{{ asset('public/LandingPage_assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('public/LandingPage_assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('public/LandingPage_assets/js/plugins/feather.min.js') }}"></script>

<script src="{{ asset('js/jquery.js') }}"></script>
<script src="{{ asset('assets/js/plugins/notifier.js') }}"></script>

<script>
    var site_url = $('meta[name="base-url"]').attr("content");

    function show_toastr(title, message, type) {
        var o, i;
        var icon = "";
        var cls = "";
        if (type == "success") {
            cls = "primary";
            notifier.show(
                "Success",
                message,
                "success",
                site_url + "/public/assets/images/notification/ok-48.png",
                4000
            );
        } else {
            cls = "danger";
            notifier.show(
                "Error",
                message,
                "danger",
                site_url +
                "/public/assets/images/notification/high_priority-48.png",
                4000
            );
        }
    }
</script>

@if ($message = Session::get('success'))
    <script>
        show_toastr('{{ __('Success') }}', '{!! $message !!}', 'success')
    </script>
@endif
@if ($message = Session::get('error'))
    <script>
        show_toastr('{{ __('Error') }}', '{!! $message !!}', 'error')
    </script>
@endif
<script>
    // Start [ Menu hide/show on scroll ]
    let ost = 0;
    document.addEventListener("scroll", function() {
        let cOst = document.documentElement.scrollTop;
        if (cOst == 0) {
            document.querySelector(".navbar").classList.add("top-nav-collapse");
        } else if (cOst > ost) {
            document.querySelector(".navbar").classList.add("top-nav-collapse");
            document.querySelector(".navbar").classList.remove("default");
        } else {
            document.querySelector(".navbar").classList.add("default");
            document
                .querySelector(".navbar")
                .classList.remove("top-nav-collapse");
        }
        ost = cOst;
    });
    // End [ Menu hide/show on scroll ]
    var scrollSpy = new bootstrap.ScrollSpy(document.body, {
        target: "#navbar-example",
    });
    feather.replace();
</script>
<script>
        // Calculateur de gains – 20% annuel récurrent
        const clientsInput = document.getElementById('clients');
        const planSelect = document.getElementById('plan');
        const firstYearEl = document.getElementById('first-year');
        const recurringEl = document.getElementById('recurring');
        const threeYearsEl = document.getElementById('three-years');

        function calculate() {
            const clients = parseInt(clientsInput.value) || 0;
            const plan = parseInt(planSelect.value);
            const commission = clients * plan * 0.20;
            const threeYears = commission * 3;

            firstYearEl.textContent = `${commission.toLocaleString()} FCFA`;
            recurringEl.textContent = `${commission.toLocaleString()} FCFA/an`;
            threeYearsEl.textContent = `${threeYears.toLocaleString()} FCFA`;
        }

        clientsInput.addEventListener('input', calculate);
        planSelect.addEventListener('change', calculate);
        calculate();

        // FAQ Toggle
        document.querySelectorAll('.faq-question').forEach(item => {
            item.addEventListener('click', () => {
                const answer = item.nextElementSibling;
                const icon = item.querySelector('i');
                answer.classList.toggle('open');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });
    </script>
</body>

</html>
