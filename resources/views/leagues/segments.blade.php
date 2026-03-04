<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Segments - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .back-link {
            display: inline-block; color: white; text-decoration: none;
            padding: 10px 20px; background: rgba(255,255,255,0.2);
            border-radius: 5px; margin-bottom: 20px; transition: background 0.3s ease;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }
        .content-section {
            background: white; padding: 30px; border-radius: 12px;
            margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .section-title { font-size: 1.8em; color: var(--primary-color); margin-bottom: 5px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        .success-message {
            background: #28a745; color: white; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;
        }
        .error-message {
            background: #f8d7da; color: #721c24; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px;
        }
        .form-row {
            display: grid; grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px; align-items: end; margin-bottom: 20px;
        }
        .form-group { margin-bottom: 0; }
        label {
            display: block; font-weight: 600; color: #333;
            margin-bottom: 8px; font-size: 0.95em;
        }
        input[type="text"], input[type="number"] {
            width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 1em; font-family: inherit;
            transition: border-color 0.3s ease;
        }
        input:focus { outline: none; border-color: var(--primary-color); }
        .btn {
            padding: 12px 20px; border: none; border-radius: 8px;
            font-size: 1em; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-small { padding: 6px 12px; font-size: 0.85em; }
        .segment-card {
            background: var(--primary-light); border-radius: 8px; padding: 20px;
            margin-bottom: 15px; border-left: 4px solid var(--primary-color);
            display: flex; justify-content: space-between; align-items: center;
        }
        .segment-info { flex: 1; }
        .segment-name { font-size: 1.3em; font-weight: 600; color: #333; margin-bottom: 5px; }
        .segment-weeks { color: #666; font-size: 0.95em; }
        .segment-teams { color: #888; font-size: 0.85em; margin-top: 3px; }
        .segment-actions { display: flex; gap: 8px; align-items: center; }
        .edit-form {
            display: none; background: #f0f2ff; border-radius: 8px;
            padding: 20px; margin-bottom: 15px; border-left: 4px solid var(--primary-color);
        }
        .edit-form.active { display: block; }
        .edit-form-row {
            display: grid; grid-template-columns: 2fr 1fr 1fr;
            gap: 15px; margin-bottom: 15px;
        }
        .week-bar {
            background: #f0f0f0; border-radius: 8px; padding: 15px;
            margin-bottom: 25px; overflow-x: auto;
        }
        .week-bar-label { font-weight: 600; color: #333; margin-bottom: 10px; font-size: 0.9em; }
        .week-bar-track {
            display: flex; gap: 2px; min-width: fit-content;
        }
        .week-cell {
            width: 32px; height: 28px; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7em; font-weight: 600; color: white;
        }
        .week-cell.uncovered { background: #ddd; color: #999; }
        .week-cell.covered { background: var(--primary-color); }
        .empty-state {
            text-align: center; padding: 40px; color: #666;
            background: var(--primary-light); border-radius: 8px;
        }
        .help-text { font-size: 0.85em; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">← Back to League</a>

        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="error-message">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <div class="content-section">
            <h1 class="section-title">Manage Segments</h1>
            <p class="subtitle">{{ $league->name }} — {{ $league->season }}</p>

            @if($maxWeek > 0)
                <div class="week-bar">
                    <div class="week-bar-label">Week Coverage ({{ $maxWeek }} weeks scheduled)</div>
                    <div class="week-bar-track">
                        @php
                            $coveredWeeks = [];
                            $segmentColors = ['var(--primary-color)', '#28a745', '#fd7e14', '#dc3545', '#6f42c1', '#20c997'];
                            foreach ($league->segments as $si => $seg) {
                                for ($w = $seg->start_week; $w <= $seg->end_week; $w++) {
                                    $coveredWeeks[$w] = ['name' => $seg->name, 'color' => $segmentColors[$si % count($segmentColors)]];
                                }
                            }
                        @endphp
                        @for($w = 1; $w <= $maxWeek; $w++)
                            @if(isset($coveredWeeks[$w]))
                                <div class="week-cell covered" style="background: {{ $coveredWeeks[$w]['color'] }};" title="Week {{ $w }}: {{ $coveredWeeks[$w]['name'] }}">{{ $w }}</div>
                            @else
                                <div class="week-cell uncovered" title="Week {{ $w }}: No segment">{{ $w }}</div>
                            @endif
                        @endfor
                    </div>
                </div>
            @endif

            <!-- Create Segment Form -->
            <h2 style="font-size: 1.2em; color: var(--primary-color); margin-bottom: 15px;">Add Segment</h2>
            <form action="{{ route('admin.leagues.segments.store', $league->id) }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Segment Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., First Half">
                    </div>
                    <div class="form-group">
                        <label for="start_week">Start Week</label>
                        <input type="number" id="start_week" name="start_week" value="{{ old('start_week', 1) }}" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="end_week">End Week</label>
                        <input type="number" id="end_week" name="end_week" value="{{ old('end_week') }}" min="1" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Add Segment</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Existing Segments -->
        <div class="content-section">
            <h2 style="font-size: 1.3em; color: var(--primary-color); margin-bottom: 20px;">Segments ({{ $league->segments->count() }})</h2>

            @if($league->segments->isEmpty())
                <div class="empty-state">
                    <p style="font-size: 1.1em; margin-bottom: 10px;">No segments defined</p>
                    <p>Add segments above to split the league schedule into separate team groupings.</p>
                </div>
            @else
                @foreach($league->segments as $segment)
                    <div class="segment-card" id="segment-display-{{ $segment->id }}">
                        <div class="segment-info">
                            <div class="segment-name">{{ $segment->name }}</div>
                            <div class="segment-weeks">Weeks {{ $segment->start_week }} – {{ $segment->end_week }} ({{ $segment->weekCount() }} weeks)</div>
                            <div class="segment-teams">{{ $segment->teams->count() }} team(s)</div>
                        </div>
                        <div class="segment-actions">
                            <a href="{{ route('admin.leagues.teams.manage', $league->id) }}?segment={{ $segment->id }}" class="btn btn-primary btn-small">
                                Manage Teams
                            </a>
                            <button type="button" class="btn btn-secondary btn-small" onclick="showEditSegment({{ $segment->id }})">Edit</button>
                            <form action="{{ route('admin.segments.destroy', $segment->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete segment \'{{ $segment->name }}\'? This will also delete {{ $segment->teams->count() }} team(s) in this segment!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="edit-form" id="segment-edit-{{ $segment->id }}">
                        <form action="{{ route('admin.segments.update', $segment->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="edit-form-row">
                                <div class="form-group">
                                    <label>Segment Name</label>
                                    <input type="text" name="name" value="{{ $segment->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Start Week</label>
                                    <input type="number" name="start_week" value="{{ $segment->start_week }}" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label>End Week</label>
                                    <input type="number" name="end_week" value="{{ $segment->end_week }}" min="1" required>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-success btn-small">Save</button>
                                <button type="button" class="btn btn-secondary btn-small" onclick="hideEditSegment({{ $segment->id }})">Cancel</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        function showEditSegment(id) {
            document.getElementById('segment-display-' + id).style.display = 'none';
            document.getElementById('segment-edit-' + id).classList.add('active');
        }
        function hideEditSegment(id) {
            document.getElementById('segment-display-' + id).style.display = 'flex';
            document.getElementById('segment-edit-' + id).classList.remove('active');
        }
    </script>
</body>
</html>
