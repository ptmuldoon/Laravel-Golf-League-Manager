<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Golf Course</title>
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
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 35px;
            padding-bottom: 35px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-size: 1.3em;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        .required {
            color: #dc3545;
        }
        input[type="text"],
        input[type="url"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #2a5298;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .radio-option input[type="radio"] {
            width: auto;
            cursor: pointer;
        }
        .teebox-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .teebox-item {
            background: var(--primary-light);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        .teebox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .teebox-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1em;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            font-size: 0.9em;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 0.9em;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        .par-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 15px;
        }
        .par-input-group {
            text-align: center;
        }
        .par-input-group label {
            font-size: 0.85em;
            margin-bottom: 5px;
            color: #666;
        }
        .par-input-group input {
            text-align: center;
            font-weight: 600;
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
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .info-box {
            background: #e8f0fe;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 0.9em;
        }
        .auto-calc-hint {
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.85em;
            color: #155724;
        }
        .search-box {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .search-box input {
            flex: 1;
            min-width: 250px;
        }
        .search-status {
            margin-top: 12px;
            font-size: 0.9em;
        }
        .search-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px 15px;
            border-radius: 6px;
        }
        .search-error {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 6px;
        }
        .search-success {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 10px 15px;
            border-radius: 6px;
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
        <a href="{{ route('admin.courses.index') }}" class="back-link">← Back to Courses</a>

        <div class="form-container">
            <h1>⛳ Create New Golf Course</h1>
            <p class="subtitle">Add a new golf course with complete configuration</p>

            <!-- Course Search (outside the form so it doesn't submit) -->
            <div class="section" style="background: #f0f4ff; border: 2px solid #c7d4f7; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                <h2 class="section-title">🔍 Auto-Fill from Internet Search</h2>
                <p style="color: #555; margin-bottom: 15px; font-size: 0.95em;">
                    Search the internet to automatically fill in course details — tee boxes, slope, rating, address, and par values.
                    You can still edit everything after it's filled in.
                </p>
                <div class="search-box">
                    <input type="text" id="courseSearchInput"
                           placeholder="e.g. Pebble Beach Golf Links, Monterey CA"
                           onkeydown="if(event.key==='Enter'){event.preventDefault();searchCourse();}">
                    <button type="button" class="btn btn-primary" id="searchBtn" onclick="searchCourse()">
                        🔍 Search
                    </button>
                </div>
                <div id="searchStatus" class="search-status" style="display:none;"></div>
            </div>

            <form action="{{ route('admin.courses.store') }}" method="POST" id="courseForm">
                @csrf

                <!-- Basic Information -->
                <div class="section">
                    <h2 class="section-title">📋 Basic Information</h2>

                    <div class="form-group">
                        <label for="name">Course Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <textarea id="address" name="address" required>{{ old('address') }}</textarea>
                        @error('address')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address_link">Google Maps Link (optional)</label>
                        <input type="url" id="address_link" name="address_link" value="{{ old('address_link') }}" placeholder="https://maps.google.com/?q=...">
                        @error('address_link')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Course Configuration -->
                <div class="section">
                    <h2 class="section-title">⛳ Course Configuration</h2>

                    <div class="form-group">
                        <label>Number of Holes <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="holes" value="9" {{ old('holes') == '9' ? 'checked' : '' }} onchange="updateHoleCount()">
                                9 Holes
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="holes" value="18" {{ old('holes', '18') == '18' ? 'checked' : '' }} onchange="updateHoleCount()">
                                18 Holes
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Par for Each Hole <span class="required">*</span></label>
                        <div class="info-box">
                            💡 Typical par values: Par 3 (short), Par 4 (medium), Par 5 (long)
                        </div>
                        <div id="parInputs" class="par-grid">
                            <!-- Par inputs will be generated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Teeboxes -->
                <div class="section">
                    <h2 class="section-title">🎯 Teeboxes & Ratings</h2>

                    <div class="info-box">
                        💡 <strong>Slope:</strong> 113 is average (range: 55-155). <strong>Rating:</strong> Expected score for a scratch golfer (range: 67-77 for most courses).
                    </div>

                    <div id="teeboxList" class="teebox-list">
                        <!-- Teeboxes will be generated by JavaScript -->
                    </div>

                    <button type="button" class="btn btn-success" onclick="addTeebox()">+ Add Teebox</button>
                </div>

                <!-- Submit Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Create Golf Course</button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let teeboxCount = 0;
        const teeboxColors = [
            { name: 'Black', bg: '#333' },
            { name: 'Blue', bg: '#4169E1' },
            { name: 'White', bg: '#f0f0f0' },
            { name: 'Red', bg: '#DC143C' },
            { name: 'Gold', bg: '#FFD700' },
            { name: 'Green', bg: '#228B22' }
        ];

        function updateHoleCount() {
            const holes = document.querySelector('input[name="holes"]:checked').value;
            const parInputsDiv = document.getElementById('parInputs');
            parInputsDiv.innerHTML = '';

            for (let i = 1; i <= parseInt(holes); i++) {
                const div = document.createElement('div');
                div.className = 'par-input-group';
                div.innerHTML = `
                    <label>Hole ${i}</label>
                    <input type="number" name="pars[${i - 1}]" min="3" max="6" value="4" required>
                `;
                parInputsDiv.appendChild(div);
            }

            // Update all teeboxes to show/hide 9-hole ratings
            updateAllTeeboxRatings();
        }

        function addTeebox() {
            const teeboxList = document.getElementById('teeboxList');
            const color = teeboxColors[teeboxCount % teeboxColors.length];
            const holes = document.querySelector('input[name="holes"]:checked').value;
            const show9HoleRatings = holes === '18';

            const teeboxDiv = document.createElement('div');
            teeboxDiv.className = 'teebox-item';
            teeboxDiv.id = `teebox-${teeboxCount}`;
            teeboxDiv.innerHTML = `
                <div class="teebox-header">
                    <div class="teebox-title">Teebox ${teeboxCount + 1}</div>
                    <button type="button" class="btn btn-danger btn-small" onclick="removeTeebox(${teeboxCount})">Remove</button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Teebox Name <span class="required">*</span></label>
                        <input type="text" name="teeboxes[${teeboxCount}][name]" value="${color.name}" required>
                    </div>
                    <div class="form-group">
                        <label>Course Rating <span class="required">*</span></label>
                        <input type="number" name="teeboxes[${teeboxCount}][rating]" step="0.1" min="50" max="85" value="72.0" required onchange="autoCalculate9HoleRatings(${teeboxCount})">
                        <div class="help-text">Expected score for scratch golfer</div>
                    </div>
                    <div class="form-group">
                        <label>Slope Rating <span class="required">*</span></label>
                        <input type="number" name="teeboxes[${teeboxCount}][slope]" step="0.1" min="55" max="155" value="113.0" required onchange="autoCalculate9HoleSlopes(${teeboxCount})">
                        <div class="help-text">Course difficulty (113 is average)</div>
                    </div>
                </div>
                <div class="nine-hole-ratings" id="nineHoleRatings-${teeboxCount}" style="display: ${show9HoleRatings ? 'block' : 'none'};">
                    <div class="auto-calc-hint">
                        ✨ 9-hole ratings are auto-calculated based on 18-hole values. You can override them if needed.
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Front 9 Rating</label>
                            <input type="number" name="teeboxes[${teeboxCount}][rating_9_front]" step="0.1" min="20" max="45" value="36.0">
                        </div>
                        <div class="form-group">
                            <label>Back 9 Rating</label>
                            <input type="number" name="teeboxes[${teeboxCount}][rating_9_back]" step="0.1" min="20" max="45" value="36.0">
                        </div>
                        <div class="form-group">
                            <label>Front 9 Slope</label>
                            <input type="number" name="teeboxes[${teeboxCount}][slope_9_front]" step="0.1" min="55" max="155" value="112.0">
                        </div>
                        <div class="form-group">
                            <label>Back 9 Slope</label>
                            <input type="number" name="teeboxes[${teeboxCount}][slope_9_back]" step="0.1" min="55" max="155" value="114.0">
                        </div>
                    </div>
                </div>
            `;

            teeboxList.appendChild(teeboxDiv);
            teeboxCount++;
        }

        function removeTeebox(index) {
            const teebox = document.getElementById(`teebox-${index}`);
            if (teebox) {
                teebox.remove();
            }
        }

        function autoCalculate9HoleRatings(teeboxIndex) {
            const rating18 = parseFloat(document.querySelector(`input[name="teeboxes[${teeboxIndex}][rating]"]`).value) || 72;
            const rating9 = (rating18 / 2).toFixed(1);

            const frontInput = document.querySelector(`input[name="teeboxes[${teeboxIndex}][rating_9_front]"]`);
            const backInput = document.querySelector(`input[name="teeboxes[${teeboxIndex}][rating_9_back]"]`);

            if (frontInput) frontInput.value = rating9;
            if (backInput) backInput.value = rating9;
        }

        function autoCalculate9HoleSlopes(teeboxIndex) {
            const slope18 = parseFloat(document.querySelector(`input[name="teeboxes[${teeboxIndex}][slope]"]`).value) || 113;
            const slopeFront = (slope18 - 1).toFixed(1);
            const slopeBack = (slope18 + 1).toFixed(1);

            const frontInput = document.querySelector(`input[name="teeboxes[${teeboxIndex}][slope_9_front]"]`);
            const backInput = document.querySelector(`input[name="teeboxes[${teeboxIndex}][slope_9_back]"]`);

            if (frontInput) frontInput.value = slopeFront;
            if (backInput) backInput.value = slopeBack;
        }

        function updateAllTeeboxRatings() {
            const holes = document.querySelector('input[name="holes"]:checked').value;
            const show9HoleRatings = holes === '18';

            for (let i = 0; i < teeboxCount; i++) {
                const nineHoleDiv = document.getElementById(`nineHoleRatings-${i}`);
                if (nineHoleDiv) {
                    nineHoleDiv.style.display = show9HoleRatings ? 'block' : 'none';
                }
            }
        }

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            updateHoleCount();
            // Add default teebox
            addTeebox();
        });

        // --- Course Internet Search ---
        async function searchCourse() {
            const query = document.getElementById('courseSearchInput').value.trim();
            if (!query) {
                showSearchStatus('warning', 'Please enter a course name to search.');
                return;
            }

            const btn = document.getElementById('searchBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Searching...';
            showSearchStatus('info', '🔍 Searching the internet for course information — this may take up to 30 seconds...');

            try {
                const response = await fetch('{{ route("admin.courses.search") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({ query }),
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    showSearchStatus('error', '❌ ' + (data.error || 'Search failed. Please enter details manually.'));
                    return;
                }

                if (!data.found) {
                    showSearchStatus('warning', '⚠️ Course not found: ' + (data.notes || 'Try adding a city or state to your search.'));
                    return;
                }

                fillCourseData(data);

                let msg = '✅ Course found! Form has been auto-filled — please review and verify all values before saving.';
                if (data.notes) msg += '<br><small style="opacity:0.8;">' + data.notes + '</small>';
                showSearchStatus('success', msg);

                // Scroll down to the form fields
                document.getElementById('name').scrollIntoView({ behavior: 'smooth', block: 'center' });

            } catch (err) {
                showSearchStatus('error', '❌ Search request failed. Check your connection and try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = '🔍 Search';
            }
        }

        function showSearchStatus(type, message) {
            const el = document.getElementById('searchStatus');
            const classMap = { info: 'info-box', warning: 'search-warning', error: 'search-error', success: 'search-success' };
            el.className = 'search-status ' + (classMap[type] || 'info-box');
            el.innerHTML = message;
            el.style.display = 'block';
        }

        function fillCourseData(data) {
            // Basic info
            if (data.name)         document.getElementById('name').value = data.name;
            if (data.address)      document.getElementById('address').value = data.address;
            if (data.address_link) document.getElementById('address_link').value = data.address_link;

            // Number of holes
            if (data.holes) {
                const radio = document.querySelector(`input[name="holes"][value="${data.holes}"]`);
                if (radio) { radio.checked = true; updateHoleCount(); }
            }

            // Par values — fill after updateHoleCount() rebuilds the grid
            if (Array.isArray(data.pars)) {
                data.pars.forEach((par, i) => {
                    const input = document.querySelector(`input[name="pars[${i}]"]`);
                    if (input) input.value = par;
                });
            }

            // Teeboxes — clear existing and rebuild
            document.getElementById('teeboxList').innerHTML = '';
            teeboxCount = 0;

            if (Array.isArray(data.teeboxes) && data.teeboxes.length > 0) {
                data.teeboxes.forEach(tb => {
                    addTeebox();
                    const idx = teeboxCount - 1;

                    const set = (field, val) => {
                        const el = document.querySelector(`input[name="teeboxes[${idx}][${field}]"]`);
                        if (el && val !== undefined && val !== null) el.value = val;
                    };

                    set('name',           tb.name);
                    set('rating',         tb.rating);
                    set('slope',          tb.slope);
                    set('rating_9_front', tb.rating_9_front);
                    set('rating_9_back',  tb.rating_9_back);
                    set('slope_9_front',  tb.slope_9_front);
                    set('slope_9_back',   tb.slope_9_back);
                });
            }
        }
    </script>
</body>
</html>
