<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leagues</title>
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
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            color: #333;
            font-size: 2em;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--primary-light);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: var(--primary-light);
        }
        .league-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .league-link:hover {
            text-decoration: underline;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-secondary {
            background: #e0e0e0;
            color: #666;
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary-color);
        }
        .pagination .active span {
            background: var(--primary-color);
            color: white;
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
            .content-section { padding: 16px; }
            .header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🏌️ Golf Admin</div>
        <button class="navbar-hamburger" onclick="var nl=this.closest('.navbar').querySelector('.navbar-links');nl.classList.toggle('open');" aria-label="Menu">☰</button>
        <div class="navbar-links">
            <a href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
            <a href="{{ route('home') }}">🏠 Public Site</a>
            <a href="{{ route('admin.players') }}">👥 Players</a>
            <a href="{{ route('admin.users') }}">🔑 Users</a>
            <a href="{{ route('admin.courses.index') }}">⛳ Courses</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.super.index') }}">🛡️ Super</a>
            @endif
            <a href="{{ route('profile.show') }}">👤 Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: white; cursor: pointer; padding: 8px 16px; border-radius: 5px; transition: background 0.3s ease;">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>🏆 Manage Leagues</h1>
            <a href="{{ route('admin.leagues.create') }}" class="btn btn-primary">+ Create New League</a>
        </div>

        @if(session('success'))
            <div style="background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
                {{ session('error') }}
            </div>
        @endif

        <div class="content-section">
            <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>League</th>
                        <th>Season</th>
                        <th>Course</th>
                        <th>Teams</th>
                        <th>Matches</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leagues as $league)
                        <tr>
                            <td>
                                <a href="{{ route('admin.leagues.show', $league->id) }}" class="league-link">
                                    {{ $league->name }}
                                </a>
                            </td>
                            <td>{{ $league->season }}</td>
                            <td>{{ $league->golfCourse->name ?? 'N/A' }}</td>
                            <td>{{ $league->teams_count }}</td>
                            <td>{{ $league->matches_count }}</td>
                            <td>
                                <span class="badge badge-{{ $league->is_active ? 'success' : 'secondary' }}">
                                    {{ $league->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="{{ route('admin.leagues.show', $league->id) }}" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-right: 10px;">
                                    Manage
                                </a>
                                <a href="{{ route('admin.leagues.edit', $league->id) }}" style="color: #28a745; text-decoration: none; font-weight: 600; margin-right: 10px;">
                                    Edit
                                </a>
                                @if($league->is_active && in_array($league->id, $leaguesWithScores))
                                    <span style="color: #ccc; font-weight: 600; cursor: not-allowed;" title="Cannot delete — active league with scores recorded">Delete</span>
                                @elseif(in_array($league->id, $leaguesWithScores))
                                    <span style="color: #ccc; font-weight: 600; cursor: not-allowed;" title="Cannot delete — scores have been recorded">Delete</span>
                                @else
                                    <form action="{{ route('admin.leagues.destroy', $league->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete {{ $league->name }}? This will delete all teams and matches!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: #dc3545; font-weight: 600; cursor: pointer; padding: 0; font-size: inherit;">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="pagination">
                {{ $leagues->links() }}
            </div>
        </div>
    </div>
</body>
</html>
