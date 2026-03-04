<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scorecard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-size: 1.2em;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        select, input[type="date"], input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .radio-option:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }
        .radio-option input[type="radio"] {
            width: auto;
            cursor: pointer;
        }
        .radio-option.selected {
            border-color: var(--primary-color);
            background: #e8f0fe;
        }
        .score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .score-input-group {
            text-align: center;
        }
        .score-input-group label {
            font-size: 0.85em;
            margin-bottom: 5px;
            color: #666;
        }
        .score-input-group input {
            text-align: center;
            font-weight: 600;
            font-size: 1.1em;
        }
        .score-input-group .par-display {
            font-size: 0.75em;
            color: #999;
            margin-top: 3px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
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
        #holeByHoleScores, #totalScoreOnly {
            display: none;
        }
        #nineHoleType {
            display: none;
        }
        .success-message {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .total-score-display {
            background: var(--primary-light);
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
        }
        .total-score-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
        }
        .total-score-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.players') }}" class="back-link">← Back to Players</a>

        <div class="form-container">
            <h1>📊 Enter Scorecard</h1>
            <p class="subtitle">Record a round of golf</p>

            @if(session('success'))
                <div class="success-message">
                    ✓ {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('admin.scorecard.store') }}" method="POST" id="scorecardForm">
                @csrf

                <!-- Round Information -->
                <div class="section">
                    <h2 class="section-title">Round Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Player <span style="color: #dc3545;">*</span></label>
                            <select name="player_id" id="player_id" required>
                                <option value="">Select a player...</option>
                                @foreach($players as $player)
                                    <option value="{{ $player->id }}" {{ request('player') == $player->id ? 'selected' : '' }}>{{ $player->first_name }} {{ $player->last_name }}</option>
                                @endforeach
                            </select>
                            @error('player_id')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Course <span style="color: #dc3545;">*</span></label>
                            <select name="golf_course_id" id="golf_course_id" required onchange="loadCourseInfo()">
                                <option value="">Select a course...</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" data-course="{{ json_encode($course->courseInfo) }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                            @error('golf_course_id')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Teebox <span style="color: #dc3545;">*</span></label>
                            <select name="teebox" id="teebox" required onchange="updatePars()">
                                <option value="">Select teebox...</option>
                            </select>
                            @error('teebox')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Date Played <span style="color: #dc3545;">*</span></label>
                            <input type="date" name="played_at" id="played_at" value="{{ date('Y-m-d') }}" required max="{{ date('Y-m-d') }}">
                            @error('played_at')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Entry Type -->
                <div class="section">
                    <h2 class="section-title">Entry Method</h2>

                    <div class="form-group">
                        <label>How would you like to enter the score?</label>
                        <div class="radio-group">
                            <label class="radio-option" onclick="selectEntryType('hole_by_hole')">
                                <input type="radio" name="entry_type" value="hole_by_hole" required>
                                Hole-by-Hole Scores
                            </label>
                            <label class="radio-option" onclick="selectEntryType('total_only')">
                                <input type="radio" name="entry_type" value="total_only" required>
                                Total Score Only
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Holes Played <span style="color: #dc3545;">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option" onclick="selectHolesPlayed(9)">
                                <input type="radio" name="holes_played" value="9" required>
                                9 Holes
                            </label>
                            <label class="radio-option" onclick="selectHolesPlayed(18)">
                                <input type="radio" name="holes_played" value="18" required>
                                18 Holes
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="nineHoleType">
                        <label>Which 9 Holes? <span style="color: #dc3545;">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option" onclick="selectNineType('front')">
                                <input type="radio" name="nine_type" value="front">
                                Front 9 (Holes 1-9)
                            </label>
                            <label class="radio-option" onclick="selectNineType('back')">
                                <input type="radio" name="nine_type" value="back">
                                Back 9 (Holes 10-18)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Hole-by-Hole Entry -->
                <div id="holeByHoleScores">
                    <div class="section">
                        <h2 class="section-title">Hole-by-Hole Scores</h2>
                        <div class="info-box">
                            💡 Enter the number of strokes for each hole. Par is shown below each input.
                        </div>
                        <div class="score-grid" id="scoreInputs">
                            <!-- Score inputs will be generated by JavaScript -->
                        </div>
                        <div class="total-score-display" style="margin-top: 20px;">
                            <div class="total-score-label">Current Total</div>
                            <div class="total-score-value" id="currentTotal">0</div>
                        </div>
                    </div>
                </div>

                <!-- Total Score Only -->
                <div id="totalScoreOnly">
                    <div class="section">
                        <h2 class="section-title">Total Score</h2>
                        <div class="info-box">
                            💡 Enter your total score for the round. Individual hole scores will be estimated.
                        </div>
                        <div class="form-group">
                            <label>Total Score <span style="color: #dc3545;">*</span></label>
                            <input type="number" name="total_score" id="total_score" min="18" max="200" placeholder="e.g., 85">
                            <div class="help-text">Enter your total strokes for the round</div>
                            @error('total_score')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Submit Scorecard</button>
                    <a href="{{ route('admin.players') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let courseData = {};
        let currentPars = {};

        function loadCourseInfo() {
            const courseSelect = document.getElementById('golf_course_id');
            const teeboxSelect = document.getElementById('teebox');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];

            if (!selectedOption.value) {
                teeboxSelect.innerHTML = '<option value="">Select teebox...</option>';
                return;
            }

            courseData = JSON.parse(selectedOption.dataset.course);

            // Get unique teeboxes
            const teeboxes = [...new Set(courseData.map(info => info.teebox))];

            // Populate teebox dropdown
            teeboxSelect.innerHTML = '<option value="">Select teebox...</option>';
            teeboxes.forEach(teebox => {
                const option = document.createElement('option');
                option.value = teebox;
                option.textContent = `${teebox} Tees`;
                teeboxSelect.appendChild(option);
            });
        }

        function updatePars() {
            const teeboxSelect = document.getElementById('teebox');
            const selectedTeebox = teeboxSelect.value;

            if (!selectedTeebox) return;

            // Get pars for this teebox
            currentPars = {};
            courseData.filter(info => info.teebox === selectedTeebox)
                .forEach(info => {
                    currentPars[info.hole_number] = info.par;
                });

            // Update score inputs if visible
            updateScoreInputs();
        }

        function selectEntryType(type) {
            document.querySelectorAll('input[name="entry_type"]').forEach(radio => {
                radio.parentElement.classList.remove('selected');
            });
            document.querySelector(`input[name="entry_type"][value="${type}"]`).parentElement.classList.add('selected');

            if (type === 'hole_by_hole') {
                document.getElementById('holeByHoleScores').style.display = 'block';
                document.getElementById('totalScoreOnly').style.display = 'none';
                document.getElementById('total_score').required = false;
            } else {
                document.getElementById('holeByHoleScores').style.display = 'none';
                document.getElementById('totalScoreOnly').style.display = 'block';
                document.getElementById('total_score').required = true;
            }

            updateScoreInputs();
        }

        function selectHolesPlayed(holes) {
            document.querySelectorAll('input[name="holes_played"]').forEach(radio => {
                radio.parentElement.classList.remove('selected');
            });
            document.querySelector(`input[name="holes_played"][value="${holes}"]`).parentElement.classList.add('selected');

            if (holes === 9) {
                document.getElementById('nineHoleType').style.display = 'block';
            } else {
                document.getElementById('nineHoleType').style.display = 'none';
                document.querySelectorAll('input[name="nine_type"]').forEach(r => r.required = false);
            }

            updateScoreInputs();
        }

        function selectNineType(type) {
            document.querySelectorAll('input[name="nine_type"]').forEach(radio => {
                radio.parentElement.classList.remove('selected');
            });
            document.querySelector(`input[name="nine_type"][value="${type}"]`).parentElement.classList.add('selected');

            updateScoreInputs();
        }

        function updateScoreInputs() {
            const holesPlayed = document.querySelector('input[name="holes_played"]:checked')?.value;
            const nineType = document.querySelector('input[name="nine_type"]:checked')?.value;
            const entryType = document.querySelector('input[name="entry_type"]:checked')?.value;

            if (entryType !== 'hole_by_hole' || !holesPlayed) return;

            const scoreInputsDiv = document.getElementById('scoreInputs');
            scoreInputsDiv.innerHTML = '';

            let startHole = 1;
            let endHole = parseInt(holesPlayed);

            if (holesPlayed === '9') {
                if (nineType === 'back') {
                    startHole = 10;
                    endHole = 18;
                } else {
                    startHole = 1;
                    endHole = 9;
                }
            }

            for (let i = startHole; i <= endHole; i++) {
                const div = document.createElement('div');
                div.className = 'score-input-group';
                const par = currentPars[i] || 4;
                div.innerHTML = `
                    <label>Hole ${i}</label>
                    <input type="number" name="scores[${i}]" min="1" max="15" placeholder="${par}" onchange="updateTotal()" oninput="updateTotal()">
                    <div class="par-display">Par ${par}</div>
                `;
                scoreInputsDiv.appendChild(div);
            }
        }

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('#scoreInputs input').forEach(input => {
                if (input.value) {
                    total += parseInt(input.value) || 0;
                }
            });
            document.getElementById('currentTotal').textContent = total || '0';
        }

        // Add click handlers for radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.radio-option').forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                    }
                });
            });
        });
    </script>
</body>
</html>
