<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('partials.theme-vars')
    <link rel="icon" type="image/svg+xml" href="/images/logo3.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances - {{ $league->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
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
        .section-title {
            font-size: 1.8em; color: var(--primary-color); margin-bottom: 20px;
        }
        .success-message {
            background: #28a745; color: white; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;
        }
        .error-message {
            background: #dc3545; color: white; padding: 15px 20px;
            border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;
        }
        .summary-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .summary-item {
            padding: 15px; background: var(--primary-light); border-radius: 8px; text-align: center;
        }
        .summary-label { font-size: 0.85em; color: #888; margin-bottom: 5px; }
        .summary-value { font-size: 1.3em; font-weight: 700; color: #333; }
        .summary-value.positive { color: #28a745; }
        .summary-value.negative { color: #dc3545; }
        .form-row {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label {
            font-weight: 600; margin-bottom: 4px; color: #333; font-size: 0.85em;
        }
        .form-group select,
        .form-group input {
            padding: 8px 12px; font-size: 0.95em; border: 2px solid #e0e0e0;
            border-radius: 8px; background: white; font-family: inherit;
        }
        .form-group select:focus,
        .form-group input:focus { outline: none; border-color: var(--primary-color); }
        .form-group.grow { flex: 1; min-width: 120px; }
        .btn {
            padding: 8px 18px; border-radius: 8px; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer;
            display: inline-block; font-size: 0.95em;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; padding: 4px 10px; font-size: 0.8em; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: var(--primary-light); padding: 12px; text-align: left;
            font-weight: 600; color: var(--primary-color); border-bottom: 2px solid #e0e0e0;
        }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background: var(--primary-light); }
        .player-row { cursor: pointer; }
        .player-row td:first-child { font-weight: 600; color: #333; }
        .transaction-rows { display: none; }
        .transaction-rows.open { display: table-row-group; }
        .transaction-row td {
            padding: 8px 12px 8px 30px; font-size: 0.9em; color: #666;
            background: #fafbff; border-bottom: 1px solid #f0f0f0;
        }
        .type-badge {
            display: inline-block; padding: 2px 8px; border-radius: 4px;
            font-size: 0.8em; font-weight: 600;
        }
        .type-fee { background: #cce5ff; color: #004085; }
        .type-winnings { background: #d4edda; color: #155724; }
        .type-payout { background: #fff3cd; color: #856404; }
        .expand-icon {
            display: inline-block; width: 16px; margin-right: 6px;
            transition: transform 0.2s; font-size: 0.8em; color: var(--primary-color);
        }
        .expand-icon.open { transform: rotate(90deg); }
        .empty-state {
            text-align: center; padding: 30px; color: #888; font-size: 1em;
        }
        .totals-row td {
            font-weight: 700; border-top: 2px solid var(--primary-color);
            background: var(--primary-light); color: #333;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-section { padding: 16px; }
            .section-title { font-size: 1.3em; }
            .form-row { flex-direction: column; }
            .form-group.grow { min-width: 100%; }
            table { font-size: 0.85em; }
            th, td { padding: 8px 6px; }
            .transaction-row td { padding-left: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ route('admin.leagues.show', $league->id) }}" class="back-link">&larr; Back to League</a>

        @if(session('success'))
            <div class="success-message">&check; {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <div class="content-section">
            <h2 class="section-title">{{ $league->name }} &mdash; Finances</h2>

            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Fees Owed</div>
                    <div class="summary-value">${{ number_format($totals['fees_owed'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Fees Collected</div>
                    <div class="summary-value">${{ number_format($totals['fees_paid'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Fees Outstanding</div>
                    <div class="summary-value {{ $totals['fees_outstanding'] > 0 ? 'negative' : 'positive' }}">
                        ${{ number_format($totals['fees_outstanding'], 2) }}
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Winnings Awarded</div>
                    <div class="summary-value">${{ number_format($totals['winnings'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Paid Out</div>
                    <div class="summary-value">${{ number_format($totals['payouts'], 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Balance</div>
                    <div class="summary-value {{ $totals['balance'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format(abs($totals['balance']), 2) }}{{ $totals['balance'] < 0 ? ' deficit' : '' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2 class="section-title" style="font-size: 1.3em;">Add Transaction</h2>
            <form method="POST" action="{{ route('admin.leagues.finances.store', $league->id) }}">
                @csrf
                <div class="form-row">
                    <div class="form-group grow">
                        <label for="player_id">Player</label>
                        <select name="player_id" id="player_id" required>
                            <option value="">Select player...</option>
                            @foreach($league->players->sortBy('first_name') as $player)
                                <option value="{{ $player->id }}" {{ old('player_id') == $player->id ? 'selected' : '' }}>
                                    {{ $player->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" required>
                            <option value="fee_paid" {{ old('type') == 'fee_paid' ? 'selected' : '' }}>Fee Paid</option>
                            <option value="winnings" {{ old('type') == 'winnings' ? 'selected' : '' }}>Winnings</option>
                            <option value="payout" {{ old('type') == 'payout' ? 'selected' : '' }}>Payout</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 120px;">
                        <label for="amount">Amount ($)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                            value="{{ old('amount', $league->fee_per_player ?? '') }}" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" required
                            value="{{ old('date', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group grow">
                        <label for="notes">Notes <span style="font-weight: normal; color: #aaa;">(optional)</span></label>
                        <input type="text" name="notes" id="notes" maxlength="255"
                            value="{{ old('notes') }}" placeholder="e.g., Cash, Venmo, Check #123">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Add</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-section">
            <h2 class="section-title" style="font-size: 1.3em;">Player Summary</h2>

            @if(empty($playerSummaries))
                <div class="empty-state">No players in this league yet.</div>
            @else
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th style="text-align: right;">Fees Owed</th>
                                <th style="text-align: right;">Fees Paid</th>
                                <th style="text-align: right;">Winnings</th>
                                <th style="text-align: right;">Paid Out</th>
                                <th style="text-align: right;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($playerSummaries as $index => $summary)
                                <tr class="player-row" onclick="toggleTransactions({{ $index }})">
                                    <td>
                                        <span class="expand-icon" id="icon-{{ $index }}">&#9654;</span>
                                        {{ $summary['player']->name }}
                                        @if($summary['transactions']->count() > 0)
                                            <span style="color: #aaa; font-size: 0.8em; font-weight: normal;">({{ $summary['transactions']->count() }})</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; color: {{ $summary['fees_owed'] > 0 ? '#dc3545' : '#28a745' }};">
                                        ${{ number_format($summary['fees_owed'], 2) }}
                                    </td>
                                    <td style="text-align: right;">${{ number_format($summary['fees_paid'], 2) }}</td>
                                    <td style="text-align: right;">${{ number_format($summary['winnings'], 2) }}</td>
                                    <td style="text-align: right;">${{ number_format($summary['payouts'], 2) }}</td>
                                    <td style="text-align: right; font-weight: 600; color: {{ $summary['balance'] >= 0 ? '#28a745' : '#dc3545' }};">
                                        ${{ number_format(abs($summary['balance']), 2) }}{{ $summary['balance'] < 0 ? ' owed' : '' }}
                                    </td>
                                </tr>
                                <tbody class="transaction-rows" id="transactions-{{ $index }}">
                                    @forelse($summary['transactions'] as $txn)
                                        <tr class="transaction-row">
                                            <td>
                                                {{ $txn->date->format('M d, Y') }}
                                                @if($txn->notes)
                                                    &mdash; <span style="color: #888;">{{ $txn->notes }}</span>
                                                @endif
                                            </td>
                                            <td></td>
                                            <td style="text-align: right;">
                                                @if($txn->type === 'fee_paid') ${{ number_format($txn->amount, 2) }} @endif
                                            </td>
                                            <td style="text-align: right;">
                                                @if($txn->type === 'winnings') ${{ number_format($txn->amount, 2) }} @endif
                                            </td>
                                            <td style="text-align: right;">
                                                @if($txn->type === 'payout') ${{ number_format($txn->amount, 2) }} @endif
                                            </td>
                                            <td style="text-align: right;">
                                                <form method="POST" action="{{ route('admin.leagues.finances.delete', [$league->id, $txn->id]) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this transaction?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="transaction-row">
                                            <td colspan="6" style="text-align: center; color: #aaa;">No transactions recorded</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="totals-row">
                                <td>Totals</td>
                                <td style="text-align: right; color: {{ $totals['fees_outstanding'] > 0 ? '#dc3545' : '#28a745' }};">
                                    ${{ number_format($totals['fees_outstanding'], 2) }}
                                </td>
                                <td style="text-align: right;">${{ number_format($totals['fees_paid'], 2) }}</td>
                                <td style="text-align: right;">${{ number_format($totals['winnings'], 2) }}</td>
                                <td style="text-align: right;">${{ number_format($totals['payouts'], 2) }}</td>
                                <td style="text-align: right; color: {{ $totals['balance'] >= 0 ? '#28a745' : '#dc3545' }};">
                                    ${{ number_format(abs($totals['balance']), 2) }}{{ $totals['balance'] < 0 ? ' deficit' : '' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleTransactions(index) {
            var rows = document.getElementById('transactions-' + index);
            var icon = document.getElementById('icon-' + index);
            if (rows.classList.contains('open')) {
                rows.classList.remove('open');
                icon.classList.remove('open');
            } else {
                rows.classList.add('open');
                icon.classList.add('open');
            }
        }
    </script>
</body>
</html>
