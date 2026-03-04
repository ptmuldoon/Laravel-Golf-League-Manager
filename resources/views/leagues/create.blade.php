<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New League</title>
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
            max-width: 900px;
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
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
        .btn {
            padding: 12px 24px;
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
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
            font-size: 0.9em;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" class="back-link">← Back to Dashboard</a>

        <div class="form-container">
            <h1>🏆 Create New League</h1>
            <p class="subtitle">Set up a new golf league for team competition</p>

            <form action="{{ route('admin.leagues.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="name">League Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Spring 2026 League">
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="season">Season <span class="required">*</span></label>
                    <input type="text" id="season" name="season" value="{{ old('season') }}" required placeholder="e.g., Spring 2026">
                    @error('season')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date <span class="required">*</span></label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="golf_course_id">Default Golf Course <span class="required">*</span></label>
                        <select id="golf_course_id" name="golf_course_id" required onchange="updateTeeboxes()">
                            <option value="">Select a course...</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('golf_course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('golf_course_id')
                            <div class="error">{{ $message }}</div>
                        @enderror
                        <div class="help-text">This will be the default course for league matches</div>
                    </div>
                    <div class="form-group">
                        <label for="default_teebox">Default Teebox <span class="required">*</span></label>
                        <select id="default_teebox" name="default_teebox" required>
                            <option value="">Select course first...</option>
                        </select>
                        @error('default_teebox')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h2 style="color: var(--primary-color); font-size: 1.3em; margin: 30px 0 15px; padding-top: 20px; border-top: 2px solid #f0f0f0;">Fees & Payouts</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fee_per_player">Fee Per Player ($)</label>
                        <input type="number" id="fee_per_player" name="fee_per_player" value="{{ old('fee_per_player') }}" step="0.01" min="0" placeholder="e.g., 150.00">
                        @error('fee_per_player')
                            <div class="error">{{ $message }}</div>
                        @enderror
                        <div class="help-text">Total cost per player for the league season</div>
                    </div>
                    <div class="form-group">
                        <label for="par3_payout">Par 3 Winner Payout ($)</label>
                        <input type="number" id="par3_payout" name="par3_payout" value="{{ old('par3_payout') }}" step="0.01" min="0" placeholder="e.g., 25.00">
                        @error('par3_payout')
                            <div class="error">{{ $message }}</div>
                        @enderror
                        <div class="help-text">Amount paid out for par 3 contest winners</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Place Payout Percentages</label>
                    <div class="help-text" style="margin-bottom: 10px;">Percentage of remaining funds (after par 3 payouts) distributed to top finishers</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="payout_1st_pct" style="font-size: 0.85em;">1st Place %</label>
                            <input type="number" id="payout_1st_pct" name="payout_1st_pct" value="{{ old('payout_1st_pct', 50) }}" step="1" min="0" max="100">
                            @error('payout_1st_pct')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="payout_2nd_pct" style="font-size: 0.85em;">2nd Place %</label>
                            <input type="number" id="payout_2nd_pct" name="payout_2nd_pct" value="{{ old('payout_2nd_pct', 30) }}" step="1" min="0" max="100">
                            @error('payout_2nd_pct')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="payout_3rd_pct" style="font-size: 0.85em;">3rd Place %</label>
                            <input type="number" id="payout_3rd_pct" name="payout_3rd_pct" value="{{ old('payout_3rd_pct', 20) }}" step="1" min="0" max="100">
                            @error('payout_3rd_pct')
                                <div class="error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    💡 After creating the league, you'll be able to add teams and assign players to each team.
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Create League</button>
                    <a href="{{ route('admin.leagues.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const courseTeeboxes = @json($courses->mapWithKeys(function($course) {
            return [$course->id => $course->courseInfo->pluck('teebox')->unique()->values()];
        }));

        function updateTeeboxes() {
            const courseId = document.getElementById('golf_course_id').value;
            const teeboxSelect = document.getElementById('default_teebox');

            teeboxSelect.innerHTML = '<option value="">Select a teebox...</option>';

            if (courseId && courseTeeboxes[courseId]) {
                courseTeeboxes[courseId].forEach(teebox => {
                    const option = document.createElement('option');
                    option.value = teebox;
                    option.textContent = teebox;
                    teeboxSelect.appendChild(option);
                });
            }
        }

        // Initialize teeboxes if course is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            const courseId = document.getElementById('golf_course_id').value;
            if (courseId) {
                updateTeeboxes();
                const oldTeebox = "{{ old('default_teebox') }}";
                if (oldTeebox) {
                    document.getElementById('default_teebox').value = oldTeebox;
                }
            }
        });
    </script>
</body>
</html>
