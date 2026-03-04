<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golf Leagues</title>
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
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 3em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .nav-links {
            text-align: center;
            margin-bottom: 20px;
        }
        .nav-links a {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            font-weight: 600;
            margin: 0 5px;
            transition: background 0.3s ease;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        .leagues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .league-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .league-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .league-name {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .league-season {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        .league-info {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 10px;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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
        .create-button {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .create-button:hover {
            background: #218838;
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
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="{{ route('admin.courses.index') }}">⛳ Golf Courses</a>
            <a href="{{ route('players.index') }}">👥 Players</a>
            <a href="{{ route('admin.scorecard.create') }}">📋 Enter Scorecard</a>
        </div>

        <div class="header-section">
            <h1 style="margin-bottom: 0;">🏆 Golf Leagues</h1>
            <a href="{{ route('admin.leagues.create') }}" class="create-button">
                + Create New League
            </a>
        </div>

        @if(session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">
                ✕ {{ session('error') }}
            </div>
        @endif

        @if($leagues->isEmpty())
            <div style="background: white; padding: 40px; border-radius: 12px; text-align: center;">
                <p style="font-size: 1.2em; color: #666; margin-bottom: 20px;">No leagues found. Create your first league to get started!</p>
                <a href="{{ route('admin.leagues.create') }}" class="create-button">
                    + Create New League
                </a>
            </div>
        @else
            <div class="leagues-grid">
                @foreach($leagues as $league)
                    <div class="league-card">
                        <div class="league-name">{{ $league->name }}</div>
                        <div class="league-season">{{ $league->season }}</div>
                        <div class="league-info">
                            📅 {{ $league->start_date->format('M d, Y') }} - {{ $league->end_date->format('M d, Y') }}<br>
                            ⛳ {{ $league->golfCourse->name ?? 'No course set' }}<br>
                            👥 {{ $league->teams->count() }} teams
                        </div>
                        <span class="status-badge {{ $league->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $league->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <a href="{{ route('admin.leagues.show', $league->id) }}" class="view-details">
                            View League Details →
                        </a>
                        <div class="card-actions">
                            <a href="{{ route('admin.leagues.edit', $league->id) }}" class="btn-small btn-edit-small">
                                ✏️ Edit
                            </a>
                            @if($league->is_active && in_array($league->id, $leaguesWithScores))
                                <span class="btn-small" style="background: #ccc; color: #666; cursor: not-allowed;" title="Cannot delete — active league with scores recorded">
                                    🗑️ Delete
                                </span>
                            @elseif(in_array($league->id, $leaguesWithScores))
                                <span class="btn-small" style="background: #ccc; color: #666; cursor: not-allowed;" title="Cannot delete — scores have been recorded">
                                    🗑️ Delete
                                </span>
                            @else
                                <form action="{{ route('admin.leagues.destroy', $league->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete {{ $league->name }}? This will delete all teams and matches!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-small btn-delete-small">
                                        🗑️ Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
