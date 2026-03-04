<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Golf Courses</title>
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
        .card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
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
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            background: var(--primary-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        input[type="file"]:hover {
            border-color: var(--secondary-color);
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
        <a href="{{ route('admin.courses.index') }}" class="back-link">← Back to Courses</a>

        <div class="card">
            <h1>📥 Import Golf Courses</h1>
            <p class="subtitle">Upload a CSV file to import golf course data</p>

            @if($errors->has('csv_file'))
                <div class="error">
                    <div class="error-title">Upload Error</div>
                    {{ $errors->first('csv_file') }}
                </div>
            @endif

            @if(session('importErrors'))
                <div class="error">
                    <div class="error-title">Import Errors Found</div>
                    @foreach(session('importErrors') as $courseName => $courseErrors)
                        <div class="error-group">
                            <div class="error-group-title">Course: {{ $courseName }}</div>
                            <ul class="error-list">
                                @foreach($courseErrors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="info-box">
                <h3>📋 CSV Format Requirements</h3>
                <p>Your CSV file should have one row per hole per teebox. Each row represents a single hole for a specific teebox on a course.</p>

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
                            <td><code>course_name</code></td>
                            <td class="required">Yes</td>
                            <td>Name of the golf course</td>
                            <td>Pebble Beach</td>
                        </tr>
                        <tr>
                            <td><code>address</code></td>
                            <td class="required">Yes</td>
                            <td>Full address of the course</td>
                            <td>1700 17 Mile Dr, CA</td>
                        </tr>
                        <tr>
                            <td><code>address_link</code></td>
                            <td class="optional">No</td>
                            <td>Google Maps URL (optional)</td>
                            <td>https://maps.google.com/?q=...</td>
                        </tr>
                        <tr>
                            <td><code>teebox</code></td>
                            <td class="required">Yes</td>
                            <td>Teebox name (Blue, White, etc.)</td>
                            <td>Blue</td>
                        </tr>
                        <tr>
                            <td><code>hole_number</code></td>
                            <td class="required">Yes</td>
                            <td>Hole number (1-18)</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td><code>par</code></td>
                            <td class="required">Yes</td>
                            <td>Par for this hole (3-6)</td>
                            <td>4</td>
                        </tr>
                        <tr>
                            <td><code>handicap</code></td>
                            <td class="optional">No</td>
                            <td>Hole handicap / difficulty (1-18)</td>
                            <td>7</td>
                        </tr>
                        <tr>
                            <td><code>yardage</code></td>
                            <td class="optional">No</td>
                            <td>Hole yardage (50-700)</td>
                            <td>385</td>
                        </tr>
                        <tr>
                            <td><code>rating</code></td>
                            <td class="required">Yes</td>
                            <td>18-hole course rating (50-85)</td>
                            <td>74.5</td>
                        </tr>
                        <tr>
                            <td><code>slope</code></td>
                            <td class="required">Yes</td>
                            <td>18-hole slope rating (55-155)</td>
                            <td>142</td>
                        </tr>
                        <tr>
                            <td><code>rating_9_front</code></td>
                            <td class="optional">No</td>
                            <td>Front 9 rating (20-45)</td>
                            <td>37.2</td>
                        </tr>
                        <tr>
                            <td><code>rating_9_back</code></td>
                            <td class="optional">No</td>
                            <td>Back 9 rating (20-45)</td>
                            <td>37.3</td>
                        </tr>
                        <tr>
                            <td><code>slope_9_front</code></td>
                            <td class="optional">No</td>
                            <td>Front 9 slope (55-155)</td>
                            <td>140</td>
                        </tr>
                        <tr>
                            <td><code>slope_9_back</code></td>
                            <td class="optional">No</td>
                            <td>Back 9 slope (55-155)</td>
                            <td>144</td>
                        </tr>
                    </tbody>
                </table>

                <p style="margin-top: 15px;"><strong>Note:</strong> Duplicate course names will update the existing course data. Rating and slope values must be the same for all holes of the same teebox.</p>
                <p style="margin-top: 10px;">
                    <a href="/samples/sample_courses.csv" download class="btn btn-secondary" style="font-size: 0.85em; padding: 8px 16px;">📄 Download Sample CSV</a>
                </p>
            </div>

            <form action="{{ route('import.courses.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label>Select CSV File <span style="color: #dc3545;">*</span></label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">📤 Import Courses</button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
