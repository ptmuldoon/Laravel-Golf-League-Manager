<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players</title>
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            color: white;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .nav-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .player-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .player-name {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .player-info {
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .player-stats {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .stat {
            display: inline-block;
            padding: 5px 12px;
            background: #f0f0f0;
            border-radius: 15px;
            font-size: 0.85em;
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏌️ Players</h1>
        </div>

        <div class="players-grid">
            @foreach($players as $player)
                <a href="{{ route('players.show', $player->id) }}" class="player-card">
                    <div class="player-name">{{ $player->first_name }} {{ $player->last_name }}</div>
                    <div class="player-info">📧 {{ $player->email }}</div>
                    @if($player->phone_number)
                        <div class="player-info">📱 {{ $player->phone_number }}</div>
                    @endif
                    <div class="player-stats">
                        <span class="stat">{{ $player->rounds_count }} {{ Str::plural('Round', $player->rounds_count) }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</body>
</html>
