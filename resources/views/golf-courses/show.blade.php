<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $course->name }} - Course Details</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            padding: 10px 20px;
            background: white;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-link:hover {
            background: #f0f0f0;
        }
        .course-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2.5em;
        }
        .course-address {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        .map-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            padding: 10px 20px;
            background: #e8f0fe;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .map-link:hover {
            background: #d2e3fc;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
        }
        .btn-edit {
            background: #28a745;
            color: white;
        }
        .btn-edit:hover {
            background: #218838;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }
        .modal-header {
            color: #dc3545;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .modal-body {
            margin-bottom: 20px;
            color: #666;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background: #5a6268;
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
        .teebox-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .teebox-tab {
            padding: 12px 25px;
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        .teebox-tab:hover {
            background: var(--primary-light);
        }
        .teebox-tab.active {
            background: var(--primary-color);
            color: white;
        }
        .teebox-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .teebox-content.active {
            display: block;
        }
        .teebox-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background: var(--primary-color);
            color: white;
        }
        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            font-weight: 600;
        }
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        tbody tr:hover {
            background: #e8f0fe;
        }
        .hole-number {
            font-weight: bold;
            color: var(--primary-color);
        }
        .par-3 {
            background: #d4edda !important;
        }
        .par-4 {
            background: #fff3cd !important;
        }
        .par-5 {
            background: #f8d7da !important;
        }
        .totals-row {
            background: var(--primary-color) !important;
            color: white !important;
            font-weight: bold;
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
        <a href="{{ route('admin.courses.index') }}" class="back-link">← Back to All Courses</a>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="course-header">
            <h1>⛳ {{ $course->name }}</h1>
            <div class="course-address">📍 {{ $course->address }}</div>
            @if($course->address_link)
                <a href="{{ $course->address_link }}" target="_blank" class="map-link">
                    View on Map →
                </a>
            @endif

            <div class="action-buttons">
                <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-edit">
                    ✏️ Edit Course
                </a>
                <a href="{{ route('admin.courses.teeboxes.manage', $course->id) }}" class="btn btn-edit" style="background: #17a2b8;">
                    🎯 Manage Teeboxes
                </a>
                <button onclick="showDeleteModal()" class="btn btn-delete">
                    🗑️ Delete Course
                </button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2 class="modal-header">⚠️ Confirm Deletion</h2>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong>{{ $course->name }}</strong>?</p>
                    <p style="margin-top: 10px;">This will also delete:</p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li>All teebox configurations</li>
                        <li>All hole information</li>
                        <li>All rounds played at this course</li>
                    </ul>
                    <p style="margin-top: 10px; color: #dc3545; font-weight: 600;">This action cannot be undone!</p>
                </div>
                <div class="modal-buttons">
                    <button onclick="hideDeleteModal()" class="btn btn-cancel">Cancel</button>
                    <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-delete">Delete Course</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="teebox-tabs">
            @foreach($teeboxes as $teeboxName => $holes)
                <div class="teebox-tab" onclick="showTeebox('{{ $teeboxName }}')">
                    {{ $teeboxName }} Tees
                </div>
            @endforeach
        </div>

        @foreach($teeboxes as $teeboxName => $holes)
            <div class="teebox-content" id="teebox-{{ $teeboxName }}">
                <h2>{{ $teeboxName }} Tees</h2>

                <div class="teebox-info">
                    <div class="info-item">
                        <div class="info-label">Course Rating</div>
                        <div class="info-value">{{ $holes->first()->rating }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Slope Rating</div>
                        <div class="info-value">{{ $holes->first()->slope }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Par</div>
                        <div class="info-value">{{ $holes->sum('par') }}</div>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Hole</th>
                            <th>Par</th>
                            <th>Hdcp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $frontNine = $holes->slice(0, 9);
                            $backNine = $holes->slice(9, 9);
                        @endphp

                        @foreach($frontNine as $hole)
                            <tr class="par-{{ $hole->par }}">
                                <td class="hole-number">{{ $hole->hole_number }}</td>
                                <td>{{ $hole->par }}</td>
                                <td>{{ $hole->handicap ?? '-' }}</td>
                            </tr>
                        @endforeach

                        <tr class="totals-row">
                            <td>OUT</td>
                            <td>{{ $frontNine->sum('par') }}</td>
                            <td>-</td>
                        </tr>

                        @foreach($backNine as $hole)
                            <tr class="par-{{ $hole->par }}">
                                <td class="hole-number">{{ $hole->hole_number }}</td>
                                <td>{{ $hole->par }}</td>
                                <td>{{ $hole->handicap ?? '-' }}</td>
                            </tr>
                        @endforeach

                        <tr class="totals-row">
                            <td>IN</td>
                            <td>{{ $backNine->sum('par') }}</td>
                            <td>-</td>
                        </tr>

                        <tr class="totals-row">
                            <td>TOTAL</td>
                            <td>{{ $holes->sum('par') }}</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function showTeebox(teeboxName) {
            // Hide all teebox contents
            document.querySelectorAll('.teebox-content').forEach(el => {
                el.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.teebox-tab').forEach(el => {
                el.classList.remove('active');
            });

            // Show selected teebox content
            document.getElementById('teebox-' + teeboxName).classList.add('active');

            // Add active class to selected tab
            event.target.classList.add('active');
        }

        // Show first teebox by default
        document.addEventListener('DOMContentLoaded', function() {
            const firstTab = document.querySelector('.teebox-tab');
            if (firstTab) {
                firstTab.click();
            }
        });

        // Modal functions
        function showDeleteModal() {
            document.getElementById('deleteModal').classList.add('show');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>
