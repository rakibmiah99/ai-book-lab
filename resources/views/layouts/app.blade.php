<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AI Book Lab')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="data:,">

    <style>
        :root {
            --reader-bg: #f7f5f1;
            --reader-surface: #ffffff;
            --reader-accent: #6d4aff;
            --reader-accent-dark: #5636d6;
            --reader-text: #262429;
            --reader-muted: #756f7d;
        }

        body {
            font-family: 'Inter', 'Hind Siliguri', sans-serif;
            background: var(--reader-bg);
            color: var(--reader-text);
        }

        .reading-text {
            font-family: 'Hind Siliguri', 'Inter', sans-serif;
            font-size: 1.08rem;
            line-height: 1.9;
            white-space: pre-line;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: .01em;
        }

        .navbar-brand span {
            color: var(--reader-accent);
        }

        .btn-accent {
            background-color: var(--reader-accent);
            border-color: var(--reader-accent);
            color: #fff;
        }

        .btn-accent:hover {
            background-color: var(--reader-accent-dark);
            border-color: var(--reader-accent-dark);
            color: #fff;
        }

        .card {
            border: 1px solid rgba(0, 0, 0, .06);
            box-shadow: 0 1px 3px rgba(20, 10, 40, .04);
        }

        .topic-sidebar {
            position: sticky;
            top: 1rem;
        }

        .topic-sidebar .list-group-item.active {
            background-color: var(--reader-accent);
            border-color: var(--reader-accent);
        }

        .zoomable-image {
            cursor: zoom-in;
            transition: transform .25s ease;
            transform-origin: center center;
        }

        .zoomable-image.zoomed {
            cursor: zoom-out;
            transform: scale(1.9);
        }

        .zoom-frame {
            overflow: hidden;
            border-radius: .5rem;
            background: #111;
        }

        .favorite-btn.is-favorited {
            background-color: #ffb100;
            border-color: #ffb100;
            color: #262429;
        }

        .punch-line {
            font-style: italic;
            border-left: 3px solid var(--reader-accent);
            padding-left: .75rem;
        }

        .feedback-resolved {
            border-left: 3px solid #2fb380;
        }

        .feedback-pending {
            border-left: 3px solid #d99a2b;
        }
    </style>

    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand" href="{{ route('books.index') }}">AI Book<span>Lab</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('books.index') }}">Books</a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">My Favourites</a>
                    </li>
                    @if (auth()->user()->is_admin)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.feedbacks.index') }}">Feedback Inbox</a>
                        </li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav">
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-accent btn-sm ms-2" href="{{ route('register') }}">Register</a>
                    </li>
                @else
                    <li class="nav-item d-flex align-items-center">
                        <span class="text-muted small me-3">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button>
                        </form>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="text-center text-muted small py-4">
    &copy; {{ date('Y') }} AI Book Lab
</footer>

{{-- Shared modal used by any page that renders a `.js-zoom-trigger` link, to view a page image zoomed in. --}}
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageZoomModalLabel">Page image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="zoom-frame">
                    <img id="imageZoomModalImg" src="" alt="Page image" class="img-fluid zoomable-image">
                </div>
                <p class="text-muted small mt-2 mb-0">Click the image to zoom in/out.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $(function () {
        var zoomModalEl = document.getElementById('imageZoomModal');
        var zoomModal = new bootstrap.Modal(zoomModalEl);

        $(document).on('click', '.js-zoom-trigger', function (e) {
            e.preventDefault();
            $('#imageZoomModalImg').attr('src', $(this).data('image-url')).removeClass('zoomed');
            $('#imageZoomModalLabel').text($(this).data('image-label') || 'Page image');
            zoomModal.show();
        });

        $(document).on('click', '.zoomable-image', function () {
            $(this).toggleClass('zoomed');
        });

        zoomModalEl.addEventListener('hidden.bs.modal', function () {
            $('#imageZoomModalImg').removeClass('zoomed').attr('src', '');
        });

        $(document).on('click', '.js-favorite-toggle', function (e) {
            e.preventDefault();
            var $btn = $(this);

            $.post($btn.data('url')).done(function (response) {
                $btn.toggleClass('is-favorited', response.favorited);
                $btn.find('.js-favorite-label').text(response.favorited ? 'Favourited' : 'Favourite this topic');
            });
        });

        $(document).on('submit', '#feedbackForm', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $status = $('#feedbackFormStatus');

            $.post($form.attr('action'), $form.serialize())
                .done(function (response) {
                    $status.removeClass('text-danger').addClass('text-success')
                        .text(response.message).show();
                    $form.trigger('reset');

                    if (response.html) {
                        $('#feedbackList').prepend(response.html);
                        $('#feedbackEmpty').remove();
                    }
                })
                .fail(function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Could not submit your note. Please try again.';
                    $status.removeClass('text-success').addClass('text-danger').text(message).show();
                });
        });
    });
</script>
@stack('scripts')
</body>
</html>
