<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorecard Generator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 720px; margin: 0 auto; }
        .back-link {
            display: inline-block; color: white; text-decoration: none;
            padding: 10px 20px; background: rgba(255,255,255,0.2);
            border-radius: 5px; margin-bottom: 20px;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); font-size: 1.8em; margin-bottom: 6px; }
        .subtitle { color: #666; margin-bottom: 24px; }
        label { display: block; font-weight: 600; color: #333; margin-bottom: 6px; font-size: 0.9em; }
        select, input[type="text"], input[type="number"] {
            width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 1em;
        }
        select:focus, input:focus { outline: none; border-color: var(--primary-color); }
        .field { margin-bottom: 18px; }
        .row { display: flex; gap: 12px; }
        .row > * { flex: 1; }
        .players-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .players-table th { text-align: left; font-size: 0.8em; color: #888; padding: 4px 6px; }
        .players-table td { padding: 4px 6px; }
        .players-table td.hcp { width: 120px; }
        .btn {
            display: inline-block; padding: 12px 28px; border: none; border-radius: 8px;
            font-size: 1em; font-weight: 600; cursor: pointer;
            background: var(--primary-color); color: white; margin-top: 10px;
        }
        .btn:hover { background: var(--secondary-color); }
        .errors { background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 8px; margin-bottom: 18px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('home') }}" class="back-link">&larr; Home</a>
        <div class="card">
            <h1>🖨️ Scorecard Generator</h1>
            <div class="subtitle">Pick a course, choose 9 or 18 holes, type in players and their playing handicaps, then print a blank scorecard.</div>

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('scorecardGenerator.print') }}" method="GET" target="_blank">
                <div class="field">
                    <label for="golf_course_id">Golf Course</label>
                    <select name="golf_course_id" id="golf_course_id" required onchange="populateTeeboxes()">
                        <option value="">— Select a course —</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('golf_course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="teebox">Tee Box</label>
                        <select name="teebox" id="teebox" required>
                            <option value="">— Select course first —</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="holes">Holes</label>
                        <select name="holes" id="holes" required>
                            <option value="full_18" {{ old('holes') === 'full_18' ? 'selected' : '' }}>18 Holes</option>
                            <option value="front_9" {{ old('holes') === 'front_9' ? 'selected' : '' }}>Front 9</option>
                            <option value="back_9" {{ old('holes') === 'back_9' ? 'selected' : '' }}>Back 9</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>Players (up to 4)</label>
                    <table class="players-table">
                        <thead>
                            <tr><th>Name</th><th>Handicap (strokes)</th></tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 4; $i++)
                                <tr>
                                    <td><input type="text" name="players[{{ $i }}][name]" maxlength="60" placeholder="Player {{ $i + 1 }}" value="{{ old("players.$i.name") }}"></td>
                                    <td class="hcp"><input type="number" name="players[{{ $i }}][handicap]" min="0" max="54" placeholder="e.g. 12" value="{{ old("players.$i.handicap") }}"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn">Generate &amp; Print →</button>
            </form>
        </div>
    </div>

    <script>
        var courseTeeboxes = @json($courseTeeboxes);
        var oldTeebox = @json(old('teebox'));

        function populateTeeboxes() {
            var courseId = document.getElementById('golf_course_id').value;
            var teeboxSelect = document.getElementById('teebox');
            teeboxSelect.innerHTML = '';
            var boxes = courseTeeboxes[courseId] || [];
            if (!boxes.length) {
                teeboxSelect.innerHTML = '<option value="">— No tee boxes —</option>';
                return;
            }
            boxes.forEach(function (name) {
                var opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (name === oldTeebox) opt.selected = true;
                teeboxSelect.appendChild(opt);
            });
        }

        // Restore on load if a course was preselected (validation bounce-back).
        if (document.getElementById('golf_course_id').value) {
            populateTeeboxes();
        }
    </script>
</body>
</html>
