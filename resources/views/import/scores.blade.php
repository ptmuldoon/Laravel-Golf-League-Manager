<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Player Scores</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
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
        .card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .format-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .format-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .format-tab:hover {
            color: var(--secondary-color);
        }
        .format-tab.active {
            color: var(--secondary-color);
            border-bottom-color: var(--secondary-color);
        }
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .info-box.hidden {
            display: none;
        }
        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        .csv-format-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9em;
        }
        .csv-format-table th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }
        .csv-format-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .csv-format-table tr:last-child td {
            border-bottom: none;
        }
        .csv-format-table .required {
            color: #dc3545;
            font-weight: bold;
        }
        .csv-format-table .optional {
            color: #28a745;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed var(--secondary-color);
            border-radius: 8px;
            background: var(--primary-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        input[type="file"]:hover {
            border-color: var(--primary-color);
            background: #f0f2ff;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
            background: #28a745;
            color: white;
        }
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .error {
            background: #fee;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #721c24;
        }
        .error-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .error-group {
            margin-bottom: 15px;
        }
        .error-group-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .error-list {
            list-style: none;
            padding-left: 15px;
        }
        .error-list li {
            padding: 3px 0;
        }
        .error-list li:before {
            content: "• ";
            color: #dc3545;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.players') }}" class="back-link">← Back to Players</a>

        <div class="card">
            <h1>📥 Import Player Scores</h1>
            <p class="subtitle">Upload a CSV file to import player scorecard data</p>

            @if($errors->has('csv_file'))
                <div class="error">
                    <div class="error-title">Upload Error</div>
                    {{ $errors->first('csv_file') }}
                </div>
            @endif

            @if(session('importErrors'))
                <div class="error">
                    <div class="error-title">Import Errors Found</div>
                    @foreach(session('importErrors') as $rowKey => $rowErrors)
                        <div class="error-group">
                            <div class="error-group-title">{{ $rowKey }}</div>
                            <ul class="error-list">
                                @foreach($rowErrors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="format-tabs">
                <button class="format-tab active" onclick="showFormat('hole_by_hole')">Hole-by-Hole Format</button>
                <button class="format-tab" onclick="showFormat('total_only')">Total Score Only</button>
            </div>

            <div class="info-box" id="hole-by-hole-format">
                <h3>📋 Hole-by-Hole CSV Format</h3>
                <p>One row per round with individual scores for each hole (1-18). Leave holes blank if not played.</p>

                <table class="csv-format-table">
                    <thead>
                        <tr>
                            <th>Column Name</th>
                            <th>Required</th>
                            <th>Description</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>first_name</code></td>
                            <td class="required">Yes</td>
                            <td>Player first name</td>
                            <td>John</td>
                        </tr>
                        <tr>
                            <td><code>last_name</code></td>
                            <td class="required">Yes</td>
                            <td>Player last name</td>
                            <td>Doe</td>
                        </tr>
                        <tr>
                            <td><code>email</code></td>
                            <td class="required">Yes</td>
                            <td>Player email (unique identifier)</td>
                            <td>john@example.com</td>
                        </tr>
                        <tr>
                            <td><code>phone_number</code></td>
                            <td class="optional">No</td>
                            <td>Player phone number</td>
                            <td>555-1234</td>
                        </tr>
                        <tr>
                            <td><code>course_name</code></td>
                            <td class="required">Yes</td>
                            <td>Name of course (must exist)</td>
                            <td>Pebble Beach</td>
                        </tr>
                        <tr>
                            <td><code>teebox</code></td>
                            <td class="required">Yes</td>
                            <td>Teebox played (must exist for course)</td>
                            <td>Blue</td>
                        </tr>
                        <tr>
                            <td><code>played_at</code></td>
                            <td class="required">Yes</td>
                            <td>Date played (YYYY-MM-DD)</td>
                            <td>2026-02-10</td>
                        </tr>
                        <tr>
                            <td><code>holes_played</code></td>
                            <td class="required">Yes</td>
                            <td>Number of holes (9 or 18)</td>
                            <td>18</td>
                        </tr>
                        <tr>
                            <td><code>nine_type</code></td>
                            <td class="required">If 9 holes</td>
                            <td>"front" or "back"</td>
                            <td>front</td>
                        </tr>
                        <tr>
                            <td><code>hole_1</code> through <code>hole_18</code></td>
                            <td class="optional">No</td>
                            <td>Strokes for each hole (1-15)</td>
                            <td>4</td>
                        </tr>
                    </tbody>
                </table>

                <p style="margin-top: 15px;"><strong>Note:</strong> For 9-hole rounds, only fill in holes 1-9 (front) or 10-18 (back), and set <code>nine_type</code> accordingly.</p>

                <a href="/samples/sample_scores_hole_by_hole.csv" download style="display: inline-block; margin-top: 15px; padding: 8px 16px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: 0.9em;">Download Sample CSV</a>
            </div>

            <div class="info-box hidden" id="total-only-format">
                <h3>📋 Total Score Only CSV Format</h3>
                <p>One row per round with just the total score. Scores will be distributed across holes based on par.</p>

                <table class="csv-format-table">
                    <thead>
                        <tr>
                            <th>Column Name</th>
                            <th>Required</th>
                            <th>Description</th>
                            <th>Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>first_name</code></td>
                            <td class="required">Yes</td>
                            <td>Player first name</td>
                            <td>John</td>
                        </tr>
                        <tr>
                            <td><code>last_name</code></td>
                            <td class="required">Yes</td>
                            <td>Player last name</td>
                            <td>Doe</td>
                        </tr>
                        <tr>
                            <td><code>email</code></td>
                            <td class="required">Yes</td>
                            <td>Player email (unique identifier)</td>
                            <td>john@example.com</td>
                        </tr>
                        <tr>
                            <td><code>phone_number</code></td>
                            <td class="optional">No</td>
                            <td>Player phone number</td>
                            <td>555-1234</td>
                        </tr>
                        <tr>
                            <td><code>course_name</code></td>
                            <td class="required">Yes</td>
                            <td>Name of course (must exist)</td>
                            <td>Pebble Beach</td>
                        </tr>
                        <tr>
                            <td><code>teebox</code></td>
                            <td class="required">Yes</td>
                            <td>Teebox played (must exist for course)</td>
                            <td>Blue</td>
                        </tr>
                        <tr>
                            <td><code>played_at</code></td>
                            <td class="required">Yes</td>
                            <td>Date played (YYYY-MM-DD)</td>
                            <td>2026-02-10</td>
                        </tr>
                        <tr>
                            <td><code>holes_played</code></td>
                            <td class="required">Yes</td>
                            <td>Number of holes (9 or 18)</td>
                            <td>18</td>
                        </tr>
                        <tr>
                            <td><code>nine_type</code></td>
                            <td class="required">If 9 holes</td>
                            <td>"front" or "back"</td>
                            <td>front</td>
                        </tr>
                        <tr>
                            <td><code>total_score</code></td>
                            <td class="required">Yes</td>
                            <td>Total strokes (18-200)</td>
                            <td>85</td>
                        </tr>
                    </tbody>
                </table>

                <p style="margin-top: 15px;"><strong>Note:</strong> The system will distribute your total score across holes based on course par, similar to manual "Total Only" entry.</p>

                <a href="/samples/sample_scores_total_only.csv" download style="display: inline-block; margin-top: 15px; padding: 8px 16px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; font-weight: 600; font-size: 0.9em;">Download Sample CSV</a>
            </div>

            <form action="{{ route('admin.import.scores.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label>Select CSV File <span style="color: #dc3545;">*</span></label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">📤 Import Scores</button>
                    <a href="{{ route('admin.players') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFormat(format) {
            // Update tabs
            document.querySelectorAll('.format-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Show/hide format info boxes
            if (format === 'hole_by_hole') {
                document.getElementById('hole-by-hole-format').classList.remove('hidden');
                document.getElementById('total-only-format').classList.add('hidden');
            } else {
                document.getElementById('hole-by-hole-format').classList.add('hidden');
                document.getElementById('total-only-format').classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
