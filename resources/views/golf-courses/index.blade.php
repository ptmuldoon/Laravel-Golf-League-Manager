<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golf Courses</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5em;
            font-weight: bold;
        }
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .navbar-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 3em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .course-name {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .course-address {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .course-link {
            display: inline-block;
            color: #2a5298;
            text-decoration: none;
            font-size: 0.9em;
            padding: 8px 15px;
            background: #e8f0fe;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .course-link:hover {
            background: #d2e3fc;
        }
        .view-details {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .view-details:hover {
            background: var(--secondary-color);
        }
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-small {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-edit-small {
            background: #28a745;
            color: white;
        }
        .btn-edit-small:hover {
            background: #218838;
        }
        .btn-delete-small {
            background: #dc3545;
            color: white;
        }
        .btn-delete-small:hover {
            background: #c82333;
        }
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .navbar-hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 4px 8px;
            line-height: 1;
        }
        @media (max-width: 768px) {
            .navbar { padding: 12px 16px; flex-wrap: wrap; }
            .navbar-brand { flex: 1; }
            .navbar-hamburger { display: block; }
            .navbar-links {
                display: none; width: 100%; flex-direction: column;
                gap: 0; padding-top: 8px;
                border-top: 1px solid rgba(255,255,255,0.2); margin-top: 8px;
            }
            .navbar-links.open { display: flex; }
            .navbar-links a { padding: 10px 12px; border-radius: 4px; }
            .navbar-links form { width: 100%; display: block !important; }
            .navbar-links form button { width: 100%; text-align: left; padding: 10px 12px; border-radius: 4px; }
            .container { padding: 16px; }
            .container > div:first-child { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🏌️ Golf Admin</div>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">☰</button>
        <div class="navbar-links">
            <a href="{{ route('home') }}">🏠 Public Site</a>
            <a href="{{ route('admin.leagues') }}">🏆 Leagues</a>
            <a href="{{ route('admin.players') }}">👥 Players</a>
            <a href="{{ route('admin.users') }}">🔑 Users</a>
            <a href="{{ route('admin.courses.index') }}">⛳ Courses</a>
            <a href="{{ route('admin.scorecard.create') }}">📋 Enter Scorecard</a>
            <a href="{{ route('profile.show') }}">👤 Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 8px 16px; border-radius: 5px; transition: background 0.3s ease;">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container" style="padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin-bottom: 0; color: #333;">⛳ Golf Courses</h1>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('import.courses.form') }}" style="display: inline-block; padding: 10px 20px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;">
                    📥 Import CSV
                </a>
                <a href="{{ route('admin.courses.create') }}" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;">
                    + Create New Course
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="courses-grid">
            @foreach($courses as $course)
                <div class="course-card">
                    <div class="course-name">{{ $course->name }}</div>
                    <div class="course-address">{{ $course->address }}</div>

                    @if($course->address_link)
                        <a href="{{ $course->address_link }}" target="_blank" class="course-link" onclick="event.stopPropagation();">
                            📍 View on Map
                        </a>
                    @endif

                    <a href="{{ route('admin.courses.show', $course->id) }}" class="view-details">
                        View Course Details →
                    </a>

                    <div class="card-actions">
                        <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn-small btn-edit-small" onclick="event.stopPropagation();">
                            ✏️ Edit
                        </a>
                        <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" style="display: inline;" onclick="event.stopPropagation();" onsubmit="return confirm('Are you sure you want to delete {{ $course->name }}? This will delete all course data and rounds!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-small btn-delete-small">
                                🗑️ Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
