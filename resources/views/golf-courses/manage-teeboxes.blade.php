<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teeboxes - {{ $course->name }}</title>
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
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .teebox-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        .teebox-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .teebox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .teebox-name {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .teebox-actions {
            display: flex;
            gap: 10px;
        }
        .teebox-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background: var(--primary-light);
            padding: 15px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.3em;
            font-weight: 600;
            color: var(--primary-color);
        }
        .par-display {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
            gap: 8px;
            margin-top: 15px;
        }
        .par-hole {
            text-align: center;
            padding: 8px;
            background: #e8f0fe;
            border-radius: 5px;
        }
        .par-hole .hole-num {
            font-size: 0.8em;
            color: #666;
        }
        .par-hole .par-val {
            font-size: 1.1em;
            font-weight: bold;
            color: var(--primary-color);
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
            overflow-y: auto;
            padding: 20px;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 1.5em;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #2a5298;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .par-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
            gap: 10px;
        }
        .par-input-group {
            text-align: center;
        }
        .par-input-group label {
            font-size: 0.85em;
            margin-bottom: 5px;
        }
        .par-input-group input {
            text-align: center;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        .error {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
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
        <a href="{{ route('admin.courses.show', $course->id) }}" class="back-link">← Back to Course</a>

        <div class="header">
            <h1>⛳ Manage Teeboxes</h1>
            <p class="subtitle">{{ $course->name }}</p>

            <button class="btn btn-success" onclick="showAddTeeboxModal()">+ Add New Teebox</button>
        </div>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="error-message">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <div class="teebox-grid">
            @foreach($teeboxes as $teeboxName => $holes)
                @php
                    $firstHole = $holes->first();
                    $holesCount = $holes->count();
                @endphp
                <div class="teebox-card">
                    <div class="teebox-header">
                        <div class="teebox-name">{{ $teeboxName }} Tees</div>
                        <div class="teebox-actions">
                            <button class="btn btn-primary" onclick="showEditModal('{{ $teeboxName }}')">✏️ Edit</button>
                            <button class="btn btn-danger" onclick="confirmDelete('{{ $teeboxName }}')">🗑️ Delete</button>
                        </div>
                    </div>

                    <div class="teebox-info-grid">
                        <div class="info-box">
                            <div class="info-label">Holes</div>
                            <div class="info-value">{{ $holesCount }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Course Rating</div>
                            <div class="info-value">{{ $firstHole->rating }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Slope Rating</div>
                            <div class="info-value">{{ $firstHole->slope }}</div>
                        </div>
                        @if($firstHole->rating_9_front)
                        <div class="info-box">
                            <div class="info-label">9-Hole Front Rating</div>
                            <div class="info-value">{{ $firstHole->rating_9_front }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">9-Hole Back Rating</div>
                            <div class="info-value">{{ $firstHole->rating_9_back }}</div>
                        </div>
                        @endif
                    </div>

                    <div>
                        <strong>Par / Yardage / Handicap by Hole:</strong>
                        <div class="par-display">
                            @foreach($holes as $hole)
                                <div class="par-hole">
                                    <div class="hole-num">{{ $hole->hole_number }}</div>
                                    <div class="par-val">{{ $hole->par }}</div>
                                    <div style="font-size: 0.8em; color: #666;">{{ $hole->yardage ?? '-' }} yd</div>
                                    <div style="font-size: 0.75em; color: var(--secondary-color); font-weight: 600;">Hdcp {{ $hole->handicap ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @php
                            $totalYardage = $holes->sum('yardage');
                        @endphp
                        @if($totalYardage > 0)
                            <div style="margin-top: 8px; font-size: 0.9em; color: #666;">
                                Total: <strong>{{ number_format($totalYardage) }}</strong> yards
                            </div>
                        @endif
                    </div>

                    <!-- Hidden form for deletion -->
                    <form id="delete-form-{{ $teeboxName }}" action="{{ route('admin.courses.teeboxes.delete', [$course->id, $teeboxName]) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>

                    <!-- Hidden data for editing -->
                    <div id="teebox-data-{{ $teeboxName }}" style="display: none;">
                        {{ json_encode([
                            'name' => $teeboxName,
                            'rating' => $firstHole->rating,
                            'slope' => $firstHole->slope,
                            'rating_9_front' => $firstHole->rating_9_front,
                            'rating_9_back' => $firstHole->rating_9_back,
                            'slope_9_front' => $firstHole->slope_9_front,
                            'slope_9_back' => $firstHole->slope_9_back,
                            'pars' => $holes->pluck('par')->toArray(),
                            'yardages' => $holes->pluck('yardage')->toArray(),
                            'handicaps' => $holes->pluck('handicap')->toArray(),
                            'holes_count' => $holesCount
                        ]) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Add Teebox Modal -->
    <div id="addTeeboxModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Add New Teebox</h2>
            <form action="{{ route('admin.courses.teeboxes.add', $course->id) }}" method="POST" id="addTeeboxForm">
                @csrf
                <div class="form-group">
                    <label>Teebox Name <span style="color: #dc3545;">*</span></label>
                    <input type="text" name="teebox_name" required placeholder="e.g., Gold, Silver, Bronze">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Course Rating <span style="color: #dc3545;">*</span></label>
                        <input type="number" name="rating" step="0.1" min="50" max="85" value="72.0" required>
                        <div class="help-text">Expected score for scratch golfer</div>
                    </div>
                    <div class="form-group">
                        <label>Slope Rating <span style="color: #dc3545;">*</span></label>
                        <input type="number" name="slope" step="0.1" min="55" max="155" value="113.0" required>
                        <div class="help-text">Course difficulty (113 is average)</div>
                    </div>
                </div>

                @if($teeboxes->first()->first()->rating_9_front)
                <div class="form-row">
                    <div class="form-group">
                        <label>Front 9 Rating</label>
                        <input type="number" name="rating_9_front" step="0.1" min="20" max="45" value="36.0">
                    </div>
                    <div class="form-group">
                        <label>Back 9 Rating</label>
                        <input type="number" name="rating_9_back" step="0.1" min="20" max="45" value="36.0">
                    </div>
                    <div class="form-group">
                        <label>Front 9 Slope</label>
                        <input type="number" name="slope_9_front" step="0.1" min="55" max="155" value="112.0">
                    </div>
                    <div class="form-group">
                        <label>Back 9 Slope</label>
                        <input type="number" name="slope_9_back" step="0.1" min="55" max="155" value="114.0">
                    </div>
                </div>
                @endif

                <div class="form-group">
                    <label>Par, Yardage & Handicap for Each Hole <span style="color: #dc3545;">*</span></label>
                    <div class="par-inputs" id="addParInputs">
                        @php
                            $holesCount = $teeboxes->first()->count();
                        @endphp
                        @for($i = 1; $i <= $holesCount; $i++)
                            <div class="par-input-group">
                                <label>{{ $i }}</label>
                                <input type="number" name="pars[{{ $i - 1 }}]" min="3" max="6" value="4" required placeholder="Par">
                                <input type="number" name="yardages[{{ $i - 1 }}]" min="50" max="700" placeholder="Yds" style="margin-top: 4px;">
                                <input type="number" name="handicaps[{{ $i - 1 }}]" min="1" max="18" placeholder="Hdcp" style="margin-top: 4px;">
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-success">Add Teebox</button>
                    <button type="button" class="btn btn-secondary" onclick="hideAddTeeboxModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Teebox Modal -->
    <div id="editTeeboxModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header" id="editModalTitle">Edit Teebox</h2>
            <form method="POST" id="editTeeboxForm">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label>Course Rating <span style="color: #dc3545;">*</span></label>
                        <input type="number" name="rating" id="edit_rating" step="0.1" min="50" max="85" required>
                    </div>
                    <div class="form-group">
                        <label>Slope Rating <span style="color: #dc3545;">*</span></label>
                        <input type="number" name="slope" id="edit_slope" step="0.1" min="55" max="155" required>
                    </div>
                </div>

                <div id="edit9HoleRatings" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Front 9 Rating</label>
                            <input type="number" name="rating_9_front" id="edit_rating_9_front" step="0.1" min="20" max="45">
                        </div>
                        <div class="form-group">
                            <label>Back 9 Rating</label>
                            <input type="number" name="rating_9_back" id="edit_rating_9_back" step="0.1" min="20" max="45">
                        </div>
                        <div class="form-group">
                            <label>Front 9 Slope</label>
                            <input type="number" name="slope_9_front" id="edit_slope_9_front" step="0.1" min="55" max="155">
                        </div>
                        <div class="form-group">
                            <label>Back 9 Slope</label>
                            <input type="number" name="slope_9_back" id="edit_slope_9_back" step="0.1" min="55" max="155">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Par, Yardage & Handicap for Each Hole <span style="color: #dc3545;">*</span></label>
                    <div class="par-inputs" id="editParInputs">
                        <!-- Par, yardage and handicap inputs will be generated by JavaScript -->
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Update Teebox</button>
                    <button type="button" class="btn btn-secondary" onclick="hideEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddTeeboxModal() {
            document.getElementById('addTeeboxModal').classList.add('show');
        }

        function hideAddTeeboxModal() {
            document.getElementById('addTeeboxModal').classList.remove('show');
        }

        function showEditModal(teeboxName) {
            const dataEl = document.getElementById(`teebox-data-${teeboxName}`);
            const data = JSON.parse(dataEl.textContent);

            document.getElementById('editModalTitle').textContent = `Edit ${data.name} Tees`;
            document.getElementById('edit_rating').value = data.rating;
            document.getElementById('edit_slope').value = data.slope;

            // Set 9-hole ratings if they exist
            if (data.rating_9_front) {
                document.getElementById('edit9HoleRatings').style.display = 'block';
                document.getElementById('edit_rating_9_front').value = data.rating_9_front || '';
                document.getElementById('edit_rating_9_back').value = data.rating_9_back || '';
                document.getElementById('edit_slope_9_front').value = data.slope_9_front || '';
                document.getElementById('edit_slope_9_back').value = data.slope_9_back || '';
            }

            // Generate par, yardage and handicap inputs
            const parInputsDiv = document.getElementById('editParInputs');
            parInputsDiv.innerHTML = '';
            data.pars.forEach((par, index) => {
                const yardage = data.yardages && data.yardages[index] ? data.yardages[index] : '';
                const handicap = data.handicaps && data.handicaps[index] ? data.handicaps[index] : '';
                const div = document.createElement('div');
                div.className = 'par-input-group';
                div.innerHTML = `
                    <label>${index + 1}</label>
                    <input type="number" name="pars[${index}]" min="3" max="6" value="${par}" required placeholder="Par">
                    <input type="number" name="yardages[${index}]" min="50" max="700" value="${yardage}" placeholder="Yds" style="margin-top: 4px;">
                    <input type="number" name="handicaps[${index}]" min="1" max="18" value="${handicap}" placeholder="Hdcp" style="margin-top: 4px;">
                `;
                parInputsDiv.appendChild(div);
            });

            // Set form action
            const form = document.getElementById('editTeeboxForm');
            form.action = `/courses/{{ $course->id }}/teeboxes/${encodeURIComponent(data.name)}`;

            document.getElementById('editTeeboxModal').classList.add('show');
        }

        function hideEditModal() {
            document.getElementById('editTeeboxModal').classList.remove('show');
        }

        function confirmDelete(teeboxName) {
            if (confirm(`Are you sure you want to delete the ${teeboxName} teebox? This will remove all hole data and any rounds played from this teebox.`)) {
                document.getElementById(`delete-form-${teeboxName}`).submit();
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const addModal = document.getElementById('addTeeboxModal');
            const editModal = document.getElementById('editTeeboxModal');

            if (event.target === addModal) {
                hideAddTeeboxModal();
            }
            if (event.target === editModal) {
                hideEditModal();
            }
        });
    </script>
</body>
</html>
